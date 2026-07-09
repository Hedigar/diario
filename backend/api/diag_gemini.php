<?php
require_once 'config.php';

$apiKey = GEMINI_API_KEY;
$endpoints = [
    "v1beta" => "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey,
    "v1" => "https://generativelanguage.googleapis.com/v1/models?key=" . $apiKey
];

header('Content-Type: application/json');

$results = [];

foreach ($endpoints as $version => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    $modelNames = [];
    if (isset($decoded['models'])) {
        foreach ($decoded['models'] as $m) {
            $modelNames[] = $m['name'];
        }
    }
    
    $results[$version] = [
        "http_code" => $httpCode,
        "models" => $modelNames,
        "full_response" => $decoded
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
