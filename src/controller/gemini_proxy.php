<?php
/**
 * Relays the literature matrix's requests to the Gemini API, so the API key stays on the
 * server instead of in the page's JavaScript. Only signed-in users can use it.
 */
session_start();
header('Content-Type: application/json');

function gemini_proxy_fail(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit();
}

if (empty($_SESSION['logged_in'])) {
    gemini_proxy_fail(401, 'Sign in to use the literature matrix.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gemini_proxy_fail(405, 'Use POST.');
}

$body = file_get_contents('php://input');
if ($body === false || $body === '' || strlen($body) > 2000000) {
    gemini_proxy_fail(400, 'The request is empty or too large.');
}
if (json_decode($body) === null) {
    gemini_proxy_fail(400, 'The request is not valid JSON.');
}

require_once __DIR__ . '/../config/secrets.php';
$apiKey = uiurp_secret('GEMINI_API_KEY', 'gemini_api_key', false);
if ($apiKey === '') {
    gemini_proxy_fail(503, 'The Gemini API key is not set on this server.');
}
$model = uiurp_secret('GEMINI_MODEL', 'gemini_model', false) ?: 'gemini-2.5-flash-lite';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nx-goog-api-key: {$apiKey}\r\n",
        'content' => $body,
        'ignore_errors' => true,
        'timeout' => 60,
    ],
]);
$response = @file_get_contents($url, false, $context);
if ($response === false) {
    gemini_proxy_fail(502, 'Could not reach the Gemini API.');
}

// Pass Gemini's status code through, so the page can still spot rate limits (429).
$headers = function_exists('http_get_last_response_headers')
    ? (http_get_last_response_headers() ?? [])
    : ($http_response_header ?? []);
$status = 502;
foreach ($headers as $line) {
    if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $match)) {
        $status = (int) $match[1];
    }
}
http_response_code($status);
echo $response;
