<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: admin.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SEDUC Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Inter', sans-serif; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); background: white; }
        .btn-primary { background-color: #4f46e5; border: none; padding: 12px; font-weight: 600; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold" style="color: #4f46e5;">SEDUC Smart</h2>
        <p class="text-muted">Acesse sua conta de professor</p>
    </div>
    
    <div id="loginError" class="alert alert-danger d-none"></div>

    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label small fw-bold">E-mail</label>
            <input type="email" id="email" class="form-control" placeholder="seu@email.com" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Senha</label>
            <input type="password" id="senha" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar no Painel</button>
    </form>
</div>

<script>
    document.getElementById('loginForm').onsubmit = async (e) => {
        e.preventDefault();
        const res = await fetch('auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({
                email: document.getElementById('email').value,
                senha: document.getElementById('senha').value
            }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = 'admin.php';
        } else {
            const err = document.getElementById('loginError');
            err.innerText = data.error;
            err.classList.remove('d-none');
        }
    };
</script>

</body>
</html>
