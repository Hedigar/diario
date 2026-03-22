<?php
session_start();
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['api_token'] = $user['api_token'];
        echo json_encode(['success' => true, 'user' => [
            'nome' => $user['nome'],
            'role' => $user['role'],
            'api_token' => $user['api_token']
        ]]);
    } else {
        echo json_encode(['error' => 'E-mail ou senha incorretos']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['logged' => true, 'user' => [
            'nome' => $_SESSION['user_nome'],
            'role' => $_SESSION['user_role'],
            'api_token' => $_SESSION['api_token']
        ]]);
    } else {
        echo json_encode(['logged' => false]);
    }
    exit;
}
?>
