<?php

function sendPushNotification($serverKey, $deviceToken, $title, $message) {
    $data = [
        'to' => $deviceToken,
        'notification' => [
            'title' => $title,
            'body' => $message,
            'sound' => 'default'
        ]
    ];

    $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json'
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);

    if ($result === false) {
        $error = curl_error($ch);
        // Handle the error
    }

    curl_close($ch);

    return $result;
}

// Usage
$serverKey = 'AAAAO5N0a-4:APA91bGXqPYtfKSCbkCStjM92bMX070v_hLW2ScT72gL0jBjKG8HHOVhthxUGn-i3--KV_9xNiPOhG7olYZMcQftlJgQ0q2FXphzZ7T12PViPrzgP9OJnSi_Jkn0BajbVvhPKepL8UEp';
$deviceToken = 'exhokJMTSa6B-_hULJQM72:APA91bEAApw6Z2K5iMUj24zQPXsG36PTI5x7pSB6MS6tg0fuQ2Lztx50JZQo6h57eCwXeFKdYQ-3D2Ybx3Uz0yvBeKHO3BQfVmy35Fn6NTABY-W1zdy5OMgB3Wc9hMH4NQZ0HM1jWvkH';
$title = 'Test Notification';
$message = 'Hello, this is a test notification!';

$response = sendPushNotification($serverKey, $deviceToken, $title, $message);
echo $response;

?>
