# WhatsAppApi Class

The `WhatsAppApi` class is a utility for interacting with the WhatsApp Cloud API. It provides methods for sending template messages, custom text messages, verification codes, and managing phone number verification for WordPress users.

## Features

- Send WhatsApp template messages using the WhatsApp Cloud API.
- Send custom text messages.
- Generate and send phone number verification codes.
- Verify user phone numbers.
- Supports normalization of phone numbers (specific to Pakistani formats but customizable).

---

## Requirements

### WordPress User Meta Fields

For the `WhatsAppApi` class to work, the following meta fields must be set for each WordPress user:

| Meta Field Name         | Description                               | Example Value      |
|-------------------------|-------------------------------------------|--------------------|
| `phone_number`          | User's phone number in international format | `+923001234567`   |
| `verification_code`     | Temporary field to store the verification code | `12345`          |
| `whatsapp_phone_verified` | Indicates whether the user's phone number has been verified | `true` or `false` |

---

## Configuration

Before using the `WhatsAppApi` class, set up the following:

1. **WhatsApp Cloud API Access Token**: Replace `'ACCESS_TOKEN_FROM_META_APP'` in the class constructor with your access token from the Meta App.
2. **Phone Number ID**: Replace `'PHONE_NUMBER_ID_FROM_META_APP'` in the class constructor with your WhatsApp phone number ID.
3. **API Version**: Update the `$version` variable if the API version changes.

---

## Usage

### 1. Instantiate the Class

```php
$whatsapp = new WhatsAppApi();
```
### Sending a Template Message
```php
$template_name = 'order_confirmation'; // You will have to Create one in Meta Account APP
$user_id = 1; // WordPress User ID
$language_code = 'en_US';
$components = [
    [
        "type" => "body",
        "parameters" => [
            ["type" => "text", "text" => "John Doe" , param = "name"], // Name
            ["type" => "text", "text" => "Order #12345" , param = "order_details"], // Order Details
        ]
    ]
];

$response = $whatsapp->send_template_message($template_name, $user_id, $components, $language_code);

if ($response) {
    echo "Template message sent successfully!";
} else {
    echo "Failed to send template message.";
}
```
### Sending a Verification Code

```php
$user_id = 1; // WordPress User ID

$response = $whatsapp->send_verification_code($user_id);

if ($response) {
    echo "Verification code sent!";
} else {
    echo "Failed to send verification code.";
}
```
### Verifying a Code
```php 
$code = '12345'; // Code provided by the user
$user_id = 1; // WordPress User ID

$is_verified = $whatsapp->verify_code($code, $user_id);

if ($is_verified) {
    echo "Phone number verified successfully!";
} else {
    echo "Verification failed.";
}
```
### Checking Verification Status of a Phone Number
```php
$user_id = 1; // WordPress User ID

if (WhatsAppApi::is_phone_verified($user_id)) {
    echo "Phone number is verified.";
} else {
    echo "Phone number is not verified.";
}
```
Make sure to replace placeholders like `ACCESS_TOKEN_FROM_META_APP`, `PHONE_NUMBER_ID_FROM_META_APP`, and `VERIFICATION_CODE_TEMPLATE_NAME_FROM_META_APP` with your actual values from the WhatsApp Cloud API.
Customize the `normalize_phone_number` method as needed to support phone number formats outside Pakistan.


This `README.md` provides detailed instructions on how to configure and use the `WhatsAppApi` class, along with the required user meta fields and customization options.

