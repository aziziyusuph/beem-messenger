<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";

$user_id = (int) $_SESSION['user_id'];

// Initialize all variables before they are used in the HTML form.
$success = "";
$error = "";
$phone_number = "";
$template_name = "";
$template_language = "en";
$template_variables = "";

/*
|--------------------------------------------------------------------------
| Beem WhatsApp API Credentials
|--------------------------------------------------------------------------
| Replace the placeholder values below with your real Beem credentials.
| For better security, move them to a separate configuration file later.
*/
$beem_api_key = "PASTE_YOUR_REAL_BEEM_API_KEY_HERE";
$beem_secret_key = "PASTE_YOUR_REAL_BEEM_SECRET_KEY_HERE";
$beem_whatsapp_url = "https://apichatcore.beem.africa/v1/chatapi";
$beem_from_addr = "255674664154";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Use ?? so PHP does not produce undefined-index warnings.
    $phone_number = trim($_POST['phone_number'] ?? '');
    $template_name = trim($_POST['template_name'] ?? '');
    $template_language = trim($_POST['template_language'] ?? 'en');
    $template_variables = trim($_POST['template_variables'] ?? '');

    // Remove spaces and an optional leading + sign.
    $phone_number = preg_replace('/\s+/', '', $phone_number);
    $phone_number = ltrim($phone_number, '+');

    if ($phone_number === '' || $template_name === '') {
        $error = "Phone number and template ID are required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone_number)) {
        $error = "Please enter a valid phone number in international format, for example 2557XXXXXXXX.";
    } elseif (!ctype_digit($template_name)) {
        $error = "Template ID must contain numbers only.";
    } elseif (
        $beem_api_key === "PASTE_YOUR_REAL_BEEM_API_KEY_HERE" ||
        $beem_secret_key === "PASTE_YOUR_REAL_BEEM_SECRET_KEY_HERE"
    ) {
        $error = "Please add your real Beem API key and secret key in send_whatsapp.php.";
    } else {
        $status = "pending";

        $stmt = $conn->prepare("
            INSERT INTO whatsapp_messages
            (user_id, phone_number, message_type, template_name, message_content, status)
            VALUES (?, ?, 'template', ?, ?, ?)
        ");

        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param(
                "issss",
                $user_id,
                $phone_number,
                $template_name,
                $template_variables,
                $status
            );

            if ($stmt->execute()) {
                $whatsapp_message_id = $stmt->insert_id;

                /*
                |------------------------------------------------------------------
                | Prepare Beem WhatsApp Template Payload
                |------------------------------------------------------------------
                */
                $template_params = [];

                if ($template_variables !== '') {
                    $template_params = array_values(
                        array_filter(
                            array_map('trim', explode(',', $template_variables)),
                            static fn($value) => $value !== ''
                        )
                    );
                }

                $postData = [
                    "from_addr" => $beem_from_addr,
                    "destination_addr" => [
                        [
                            "phoneNumber" => $phone_number,
                            "params" => $template_params
                        ]
                    ],
                    "template_id" => (int) $template_name
                ];

                $json_payload = json_encode($postData);

                if ($json_payload === false) {
                    $error = "Failed to create the WhatsApp API request.";
                } elseif (!function_exists('curl_init')) {
                    $error = "PHP cURL is not enabled in XAMPP.";
                } else {
                    /*
                    |--------------------------------------------------------------
                    | Send request to Beem API
                    |--------------------------------------------------------------
                    */
                    $ch = curl_init($beem_whatsapp_url);

                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => [
                            "Authorization: Basic " . base64_encode($beem_api_key . ":" . $beem_secret_key),
                            "Content-Type: application/json"
                        ],
                        CURLOPT_POSTFIELDS => $json_payload,
                        CURLOPT_CONNECTTIMEOUT => 15,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0
                    ]);

                    $response = curl_exec($ch);
                    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curl_error = curl_error($ch);

                    curl_close($ch);

                    $decoded_response = is_string($response)
                        ? json_decode($response, true)
                        : null;

                    if ($response !== false && $http_code >= 200 && $http_code < 300) {
                        $final_status = "sent";
                        $error_message = null;
                        $beem_response_id = null;

                        if (is_array($decoded_response)) {
                            if (isset($decoded_response['message_id'])) {
                                $beem_response_id = (string) $decoded_response['message_id'];
                            } elseif (isset($decoded_response['request_id'])) {
                                $beem_response_id = (string) $decoded_response['request_id'];
                            } elseif (isset($decoded_response['id'])) {
                                $beem_response_id = (string) $decoded_response['id'];
                            }
                        }

                        $update = $conn->prepare("
                            UPDATE whatsapp_messages
                            SET status = ?, beem_message_id = ?, beem_response = ?,
                                error_message = ?, sent_at = NOW()
                            WHERE id = ? AND user_id = ?
                        ");

                        if ($update) {
                            $update->bind_param(
                                "ssssii",
                                $final_status,
                                $beem_response_id,
                                $response,
                                $error_message,
                                $whatsapp_message_id,
                                $user_id
                            );
                            $update->execute();
                            $update->close();
                        }

                        $success = "WhatsApp message sent successfully.";

                        // Clear the form only after a successful request.
                        $phone_number = "";
                        $template_name = "";
                        $template_language = "en";
                        $template_variables = "";
                    } else {
                        $final_status = "failed";

                        if ($curl_error !== '') {
                            $error_message = $curl_error;
                        } elseif (is_array($decoded_response) && isset($decoded_response['message'])) {
                            $error_message = (string) $decoded_response['message'];
                        } else {
                            $error_message = "API request failed with HTTP code: " . $http_code;
                        }

                        $stored_response = is_string($response) ? $response : '';

                        $update = $conn->prepare("
                            UPDATE whatsapp_messages
                            SET status = ?, beem_response = ?, error_message = ?
                            WHERE id = ? AND user_id = ?
                        ");

                        if ($update) {
                            $update->bind_param(
                                "sssii",
                                $final_status,
                                $stored_response,
                                $error_message,
                                $whatsapp_message_id,
                                $user_id
                            );
                            $update->execute();
                            $update->close();
                        }

                        $error = "Failed to send WhatsApp message. " . $error_message;
                    }
                }
            } else {
                $error = "Failed to save WhatsApp message: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send WhatsApp Message - Beem Messenger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 500px;
            max-width: calc(100% - 40px);
            margin: 50px auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #cccccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        textarea {
            height: 90px;
            resize: vertical;
        }

        button {
            width: 100%;
            background: #25D366;
            color: white;
            border: none;
            padding: 12px;
            margin-top: 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #1ebe5d;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            text-decoration: none;
            color: #007bff;
        }

        small {
            color: #666666;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Send WhatsApp Message</h2>

    <?php if ($success !== ''): ?>
        <div class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="phone_number">Recipient WhatsApp Number</label>
        <input
            type="text"
            id="phone_number"
            name="phone_number"
            value="<?php echo htmlspecialchars($phone_number, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Example: 2557XXXXXXXX"
            required
        >
        <small>Use international format without + or spaces. Example: 2557XXXXXXXX</small>

        <label for="template_name">Template ID</label>
        <input
            type="number"
            id="template_name"
            name="template_name"
            value="<?php echo htmlspecialchars($template_name, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Example: 1024"
            min="1"
            required
        >
        <small>Use the approved Beem WhatsApp template ID from your Beem/Moja dashboard.</small>

        <label for="template_language">Template Language</label>
        <select id="template_language" name="template_language">
            <option value="en" <?php echo $template_language === 'en' ? 'selected' : ''; ?>>English</option>
            <option value="sw" <?php echo $template_language === 'sw' ? 'selected' : ''; ?>>Swahili</option>
        </select>

        <label for="template_variables">Template Variables</label>
        <textarea
            id="template_variables"
            name="template_variables"
            placeholder="Example: Azizi, Your test message, 2026"
        ><?php echo htmlspecialchars($template_variables, ENT_QUOTES, 'UTF-8'); ?></textarea>
        <small>Separate variables with commas. Example: John, Order #123, Delivered</small>

        <button type="submit">Send WhatsApp</button>
    </form>

    <div class="links">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
