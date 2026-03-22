<?php
session_start();
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] === 'admin');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare("SELECT * FROM aulas_planejadas WHERE usuario_id = ? ORDER BY turma, ordem");
        $stmt->execute([$user_id]);
        $aulas = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT nome FROM turmas WHERE usuario_id = ? ORDER BY nome");
        $stmt->execute([$user_id]);
        $turmas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT nome FROM disciplinas WHERE usuario_id = ? ORDER BY nome");
        $stmt->execute([$user_id]);
        $disciplinas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['aulas' => $aulas, 'turmas' => $turmas, 'disciplinas' => $disciplinas]);
        break;

    case 'save_aula':
        $data = json_decode(file_get_contents('php://input'), true);
        $turmas = is_array($data['turmas']) ? $data['turmas'] : [$data['turma']];
        foreach ($turmas as $t) {
            // CORREÇÃO: Pega a última ordem FILTRANDO por TURMA e DISCIPLINA
            $stmt = $pdo->prepare("SELECT MAX(ordem) as max_ordem FROM aulas_planejadas WHERE usuario_id = ? AND turma = ? AND disciplina = ?");
            $stmt->execute([$user_id, $t, $data['disciplina']]);
            $res = $stmt->fetch();
            $nova_ordem = ($res['max_ordem'] ?? 0) + 1;

            $stmt = $pdo->prepare("INSERT INTO aulas_planejadas (usuario_id, turma, disciplina, ordem, conteudo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $t, $data['disciplina'], $nova_ordem, $data['conteudo']]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'set_next':
        $id = $_GET['id'] ?? '';
        $stmt = $pdo->prepare("SELECT turma, disciplina, ordem FROM aulas_planejadas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $user_id]);
        $aula = $stmt->fetch();
        if ($aula) {
            // CORREÇÃO: Reseta data_uso apenas da mesma TURMA e DISCIPLINA
            $stmt = $pdo->prepare("UPDATE aulas_planejadas SET data_uso = NULL WHERE usuario_id = ? AND turma = ? AND disciplina = ? AND ordem >= ?");
            $stmt->execute([$user_id, $aula['turma'], $aula['disciplina'], $aula['ordem']]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'delete_aula':
        $pdo->prepare("DELETE FROM aulas_planejadas WHERE id = ? AND usuario_id = ?")->execute([$_GET['id'], $user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'add_turma':
        $data = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("INSERT IGNORE INTO turmas (usuario_id, nome) VALUES (?, ?)")->execute([$user_id, $data['nome']]);
        echo json_encode(['success' => true]);
        break;

    case 'del_turma':
        $pdo->prepare("DELETE FROM turmas WHERE nome = ? AND usuario_id = ?")->execute([$_GET['nome'], $user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'add_disciplina':
        $data = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("INSERT IGNORE INTO disciplinas (usuario_id, nome) VALUES (?, ?)")->execute([$user_id, $data['nome']]);
        echo json_encode(['success' => true]);
        break;

    case 'del_disciplina':
        $pdo->prepare("DELETE FROM disciplinas WHERE nome = ? AND usuario_id = ?")->execute([$_GET['nome'], $user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'list_profs':
        if (!$is_admin) exit;
        $stmt = $pdo->query("SELECT id, nome, email, role, api_token, criado_em FROM usuarios ORDER BY nome");
        echo json_encode($stmt->fetchAll());
        break;

    case 'save_prof':
        if (!$is_admin) exit;
        $data = json_decode(file_get_contents('php://input'), true);
        $senha = password_hash($data['senha'], PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, role, api_token) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['email'], $senha, $data['role'], $token]);
        echo json_encode(['success' => true]);
        break;

    case 'del_prof':
        if (!$is_admin) exit;
        $id = $_GET['id'] ?? '';
        if ($id != $user_id) {
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'reset_all':
        $pdo->prepare("UPDATE aulas_planejadas SET data_uso = NULL WHERE usuario_id = ?")->execute([$user_id]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
