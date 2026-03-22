<?php
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$data = $_GET['data'] ?? '';
$turma = $_GET['turma'] ?? '';
$token = $_GET['token'] ?? '';
$disciplina = $_GET['disciplina'] ?? '';

if (empty($data) || empty($turma) || empty($token) || empty($disciplina)) {
    echo json_encode(['error' => 'Parâmetros incompletos (data, turma, disciplina ou token)']); exit;
}

// 1. Valida o Token e descobre quem é o professor
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE api_token = ?");
$stmt->execute([$token]);
$prof = $stmt->fetch();

if (!$prof) {
    echo json_encode(['error' => 'Token inválido']); exit;
}

$user_id = $prof['id'];

// 2. Já existe aula para este dia, professor e DISCIPLINA?
$stmt = $pdo->prepare("SELECT id, conteudo, ordem FROM aulas_planejadas WHERE usuario_id = ? AND data_uso = ? AND turma = ? AND disciplina = ?");
$stmt->execute([$user_id, $data, $turma, $disciplina]);
$row = $stmt->fetch();

if ($row) {
    echo json_encode(['texto' => $row['conteudo'], 'ordem' => $row['ordem'], 'status' => 'existente'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Busca a próxima aula disponível na sequência deste professor para esta TURMA e DISCIPLINA
$stmt = $pdo->prepare("SELECT id, conteudo, ordem FROM aulas_planejadas WHERE usuario_id = ? AND turma = ? AND disciplina = ? AND data_uso IS NULL ORDER BY ordem ASC LIMIT 1");
$stmt->execute([$user_id, $turma, $disciplina]);
$proxima = $stmt->fetch();

if ($proxima) {
    $update = $pdo->prepare("UPDATE aulas_planejadas SET data_uso = ? WHERE id = ?");
    $update->execute([$data, $proxima['id']]);
    echo json_encode(['texto' => $proxima['conteudo'], 'ordem' => $proxima['ordem'], 'status' => 'nova_atribuida'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Nenhuma aula disponível na sequência para este professor, turma e disciplina.'], JSON_UNESCAPED_UNICODE);
}
?>
