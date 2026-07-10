<?php
// Verifica se o config.php está incluído
if (!defined('GEMINI_API_KEY')) {
    throw new Exception('GEMINI_API_KEY não está definida (certifique-se que config.php está incluído)');
}

function callGemini($prompt, $temperature = 0.3) {
    $apiKey = GEMINI_API_KEY;
    if (empty($apiKey)) {
        throw new Exception('GEMINI_API_KEY está vazia');
    }
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => $temperature,
            "maxOutputTokens" => 4096
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception('Erro de conexão (cURL): ' . $error);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $msg = $errorData['error']['message'] ?? 'Erro desconhecido na API';
        throw new Exception('Erro ' . $httpCode . ': ' . $msg);
    }

    $resData = json_decode($response, true);
    if (!isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Resposta da IA em formato inesperado');
    }

    $aiText = $resData['candidates'][0]['content']['parts'][0]['text'];
    $aiText = preg_replace('/^```json\s*|```$/', '', trim($aiText));
    $jsonData = json_decode($aiText, true);

    if (!$jsonData) {
        throw new Exception('IA não gerou um JSON válido: ' . $aiText);
    }
    
    return $jsonData;
}
