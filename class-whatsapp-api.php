<?php
/**
 * Class WhatsAppApi
 * 
 * A utility class for interacting with the WhatsApp Cloud API. 
 * Provides methods to send template messages, text messages, 
 * and verification codes, and to manage phone verification.
 * 
 * @author Shaharyar Ansari
 * @link https://yourwebsite.com
 * @version 1.0.0
 * @since 1.0.0
 */

class WhatsAppApi {
    private $access_token;
    private $phone_number_id;
    private $version;

    public function __construct() {
        // Set your preconfigured access token and phone number ID here
        $this->access_token = 'ACCESS_TOKEN_FROM_META_APP';
        $this->phone_number_id = 'PHONE_NUMBER_ID_FROM_META_APP';
        $this->version = 'v21.0'; // CAN BE DIFFERENT LATER. AS OF NOW 22ND JAN 2025 IT WORKS
    }

    /**
     * Sends a template message via the WhatsApp Cloud API.
     * 
     * @param string $template_name WhatsApp template name. Defaults to 'hello_world'.
     * @param int|null $user_id WordPress user ID. Defaults to the current logged-in user.
     * @param string $language_code Language code for the template (e.g., "en_US"). Defaults to 'en_US'.
     * @return bool|string Response or false on failure.
     */
    public function send_template_message($template_name = 'hello_world', $user_id = null, $components = [],$language_code = 'en_US') {
        $user_id = $user_id ?? get_current_user_id();
        $phone_number = $this->get_user_phone_number($user_id);
        if (!$phone_number) {
            error_log("Phone Number Not Found for User $user_id");
            return false; // No phone number found.
        }

        if(!self::is_phone_verified($user_id)){
            error_log("Phone Number $phone_number Not Verified");
            return false;
        }
    
        
    
        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "template",
            "template" => [
                "name" => $template_name,
                "language" => ["code" => $language_code],
                "components" => $components
            ]
        ];

        error_log("Sending Whatsapp Message To: $phone_number, Template is: $template_name ");
    
        return $this->make_api_request($payload);
    }    

    /**
     * Sends a custom text message via the WhatsApp Cloud API.
     * 
     * @param string $message Custom text message.
     * @param int|null $user_id WordPress user ID. Defaults to the current logged-in user.
     * @return bool|string Response or false on failure.
     */
    public function send_text_message($message, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        $phone_number = $this->get_user_phone_number($user_id);

        if (!$phone_number) {
            return false; // No phone number found.
        }

        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "text",
            "text" => ["body" => $message]
        ];

        return $this->make_api_request($payload);
    }

    /**
     * Retrieves the phone number for a WordPress user.
     * 
     * @param int $user_id WordPress user ID.
     * @return string|null Phone number or null if not found.
     */
    
     private function get_user_phone_number($user_id) {
        // Retrieve the phone number from user meta
        $phone_number = get_user_meta($user_id, 'phone_number', true) ?? '+923042783912'; // Default for Admin
    
        // Normalize the phone number to the desired format
        return $this->normalize_phone_number($phone_number);
    }
    

    // You can Modify this function to Match with any country format For Now it supports Pakistani Format
    private function normalize_phone_number($phone_number) {
        // Remove spaces, dashes, and any non-numeric characters except "+"
        $phone_number = preg_replace('/[\s\-]/', '', $phone_number); // Remove spaces and dashes
        $phone_number = preg_replace('/[^\d+]/', '', $phone_number); // Remove any non-numeric characters except "+"
        
        // Check and normalize different formats
        if (preg_match('/^03[0-9]{9}$/', $phone_number)) {
            // Convert "03042783912" to "+923042783912"
            $phone_number = '+92' . substr($phone_number, 1);
        } elseif (preg_match('/^92[0-9]{10}$/', $phone_number)) {
            // Convert "923042783912" to "+923042783912"
            $phone_number = '+' . $phone_number;
        }
    
        // Return the normalized phone number
        return $phone_number;
    }

    /**
     * Sends a verification code to the user's phone number.
     * 
     * @param int|null $user_id WordPress user ID. Defaults to the current logged-in user.
     * @return bool|string Response or false on failure.
     */
    public function send_verification_code($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        $phone_number = $this->get_user_phone_number($user_id);

        if (!$phone_number) {
            return 'No phone number found for the user.';
        }

        $verification_code = $this->generate_verification_code();
        $this->store_verification_code($user_id, $verification_code);

        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "template",
            "template" => [
                "name" => "VERIFICATION_CODE_TEMPLATE_NAME_FROM_META_APP",
                "language" => ["code" => "en_US"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $verification_code]
                        ]
                    ],
                    [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => "0",
                        "parameters" => [
                            ["type" => "text", "text" => $verification_code]
                        ]
                    ]
                ]
            ]
        ];

        error_log("Sending Verification Code $verification_code to $phone_number");
        return $this->make_api_request($payload);
    }

    /**
     * Verifies a code provided by the user and marks the phone number as verified.
     * 
     * @param string $code The code provided by the user.
     * @param int|null $user_id WordPress user ID. Defaults to the current logged-in user.
     * @return bool True if the code is valid and verification successful, false otherwise.
     */
    public function verify_code($code, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        $stored_code = get_user_meta($user_id, 'verification_code', true);

        if ($stored_code && $stored_code === $code) {
            delete_user_meta($user_id, 'verification_code'); // Clear the code once verified
            $this->mark_phone_as_verified($user_id); // Mark the phone number as verified
            return true;
        }

        return false;
    }

    /**
     * Checks if the user's phone number is verified.
     * 
     * @param int|null $user_id WordPress user ID. Defaults to the current logged-in user.
     * @return bool True if the phone number is verified, false otherwise.
     */
    public static function is_phone_verified($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        return (bool) get_user_meta($user_id, 'whatsapp_phone_verified', true);
    }


    /**
     * Marks the user's phone number as verified.
     * 
     * @param int|null $user_id WordPress user ID. Defaults to the current logged-in user.
     * @return void
     */
    private function mark_phone_as_verified($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        update_user_meta($user_id, 'whatsapp_phone_verified', true);
    }

    /**
     * Generates a random verification code.
     * 
     * @return string A 5-digit verification code.
    */
    private function generate_verification_code() {
        return rand(10000, 99999); // Generate a 5-digit random number
    }

    /**
     * Stores the verification code temporarily in user meta.
     * 
     * @param int $user_id WordPress user ID.
     * @param string $code The verification code.
     * @return void
     */
    private function store_verification_code($user_id, $code) {
        update_user_meta($user_id, 'verification_code', $code);
    }

    /**
     * Makes an API request to the WhatsApp Cloud API.
     * 
     * @param array $payload Request payload.
     * @return bool|string Response or false on failure.
     */

    private function make_api_request($payload) {
        $url = "https://graph.facebook.com/{$this->version}/{$this->phone_number_id}/messages";
        $headers = [
            "Authorization: Bearer {$this->access_token}",
            "Content-Type: application/json"
        ];
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
        $response = curl_exec($ch);
        error_log("Reponse From Whatsapp API:". print_r($response,true));
    
        if (curl_errno($ch)) {
            error_log('WhatsApp API Error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }
    
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        if ($http_code !== 200) {
            error_log("WhatsApp API Error: HTTP {$http_code}, Response: {$response}");
            return false;
        }

    
        return $response;
    }
}
