<?php
session_start();
require_once 'db.php';

function callGemini($prompt, $temperature = 0.3) {
    $apiKey = GEMINI_API_KEY;
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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verifica se é uma extração de aulas
    if (isset($data['type']) && $data['type'] === 'extract_lessons') {
        try {
            $prompt = "Extraia as aulas do texto abaixo.
Para cada aula, identifique:
- numero: número da aula (inteiro)
- titulo: título/assunto da aula
- habilidades: habilidades ou competências mencionadas (se houver)

Responda ESTRITAMENTE um array JSON no formato:
[
  {\"numero\": 1, \"titulo\": \"Título da Aula 1\", \"habilidades\": \"Habilidade 1\"},
  {\"numero\": 2, \"titulo\": \"Título da Aula 2\", \"habilidades\": \"Habilidade 2\"}
]

Não responda nada além do JSON.

Texto:
{$data['texto']}";
            
            $result = callGemini($prompt, 0.1);
            echo json_encode(['success' => true, 'aulas' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    // Lógica original para gerar plano de aulas
    $nome_plano = $data['nome_plano'];
    $disciplina = $data['disciplina'];
    $total_aulas = $data['total_aulas'];
    $topicos = implode(", ", $data['topicos']);
    $habilidades = $data['habilidades'];
    $instrumentos = $data['instrumentos'];
    $custom_prompt = $data['custom_prompt'] ?? null;

    if ($custom_prompt) {
        $prompt = $custom_prompt;
    } else {
        $prompt = "Você é um assistente pedagógico sênior.
Disciplina: $disciplina
Plano: $nome_plano
Habilidades (BNCC): $habilidades
Tópicos base: $topicos

Sua tarefa é gerar o DIÁRIO DE CLASSE para um bloco de aulas.
Para cada aula, forneça um resumo objetivo de 3 a 4 linhas sobre o que será trabalhado.

Responda ESTRITAMENTE em formato JSON, uma lista de objetos:
[
  {
    \"sequencia\": 1,
    \"topico\": \"Título curto da aula\",
    \"descricao\": \"Resumo de 3-4 linhas focado no conteúdo e prática pedagógica...\"
  },
  ...
]
Gere EXATAMENTE o número de aulas solicitado.";
    }

    try {
        $planoJson = callGemini($prompt, 0.7);
        echo json_encode(['success' => true, 'plano_gerado' => $planoJson]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
