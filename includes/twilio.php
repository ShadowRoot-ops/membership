<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure correct path

use Twilio\Rest\Client;
use Dotenv\Dotenv;

// Load environment variables safely
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->safeLoad(); // Prevent errors if .env is missing

// Twilio credentials from environment variables
$sid    = getenv('TWILIO_SID') ?: $_ENV['TWILIO_SID'];
$token  = getenv('TWILIO_AUTH_TOKEN') ?: $_ENV['TWILIO_AUTH_TOKEN'];
$from   = getenv('TWILIO_PHONE_NUMBER') ?: $_ENV['TWILIO_PHONE_NUMBER'];

// Function to send WhatsApp message
function sendWhatsAppMessage($to, $message) {
    global $sid, $token, $from;

    if (!$sid || !$token || !$from) {
        return "❌ Twilio credentials are missing.";
    }

    $client = new Client($sid, $token);

    try {
        $msg = $client->messages->create(
            "whatsapp:" . $to, 
            [
                "from" => $from,
                "body" => $message
            ]
        );
        return "✅ Message Sent! SID: " . $msg->sid;
    } catch (Exception $e) {
        return "⚠️ Twilio Error: " . $e->getMessage();
    }
}

// Example Usage:
echo sendWhatsAppMessage("+919813571154", "Hello! Your membership is expiring soon. Please renew it before the deadline.");
?>
