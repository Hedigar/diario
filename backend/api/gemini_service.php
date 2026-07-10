<?php
// Inclui db.php e helper apenas se não estiverem incluídos
if (!defined('DB_HOST')) {
    require_once 'db.php';
}
require_once 'gemini_helper.php';

// Verifica sessão apenas se este arquivo estiver sendo acessado diretamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Não autorizado']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
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
