<?php
// public/debug-mail.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Config;
use App\Services\MailService;

echo "<h1>Debug Mail</h1>";

// 1. Load Env
Config::loadEnv();
$apiKey = getenv('RESEND_API_KEY');
echo "<strong>API Key loaded:</strong> " . ($apiKey ? (substr($apiKey, 0, 5) . '...') : 'NULL') . "<br>";
echo "<strong>From Address:</strong> " . getenv('MAIL_FROM_ADDRESS') . "<br>";

// 2. Test Send
$mailService = new MailService();
$to = 'contacto@nestorovallos.com'; // Change this if needed, using user's known email
// Or better, let's use a query param or default to a safe one
if (isset($_GET['to'])) {
    $to = $_GET['to'];
}

echo "<strong>Attempting to send to:</strong> $to<br>";

// Manually calling the underlying send to capture output
$url = 'https://api.resend.com/emails';
$fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'onboarding@resend.dev';
$fromName = getenv('MAIL_FROM_NAME') ?: 'Debug';
$subject = "Debug Test " . date('H:i:s');
$html = "Test email from debug script.";

$data = [
    'from' => "$fromName <$fromEmail>",
    'to' => [$to],
    'subject' => $subject,
    'html' => $html
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true); // Verbose!

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h3>Result</h3>";
echo "<strong>HTTP Code:</strong> $httpCode<br>";
echo "<strong>Curl Error:</strong> $curlError<br>";
echo "<strong>Response API:</strong> <pre>" . htmlspecialchars($response) . "</pre>";
