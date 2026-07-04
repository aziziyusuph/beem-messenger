<?php

require_once __DIR__ . '/../config/beem.php';

function sendSMS($phone, $message)
{
    $url = "https://apisms.beem.africa/v1/send";

    $data = [
        "source_addr" => BEEM_SENDER_ID,
        "schedule_time" => "",
        "encoding" => 0,
        "message" => $message,
        "recipients" => [
            [
                "recipient_id" => 1,
                "dest_addr" => $phone
            ]
        ]
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Basic " . base64_encode(BEEM_API_KEY . ":" . BEEM_SECRET_KEY)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        return [
            "success" => false,
            "message" => "cURL Error: " . $error
        ];
    }

    return [
        "success" => $httpCode >= 200 && $httpCode < 300,
        "http_code" => $httpCode,
        "response" => $response
    ];
}