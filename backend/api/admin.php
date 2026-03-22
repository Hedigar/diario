<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
$is_admin = ($_SESSION['user_role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SEDUC - Gestão de Aulas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #4f46e5; --primary-hover: #4338ca; --bg-color: #f9fafb; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: #111827; }
        .navbar { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 1rem 0; }
        .navbar-brand { font-weight: 700; color: var(--primary-color) !important; display: flex; align-items: center; gap: 10px; }
        .nav-link { border: none !important; color: #6b7280; font-weight: 600; padding: 10px 0; position: relative; cursor: pointer; }
        .nav-link.active { color: var(--primary-color) !important; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--primary-color); border: none; font-weight: 600; }
        .lesson-card { border-left: 4px solid #e5e7eb; transition: transform 0.2s; }
        .lesson-card.used { border-left-color: #10b981; background-color: #f0fdf4; }
        .lesson-card.pending { border-left-color: #f59e0b; }
        .sticky-form { position: sticky; top: 20px; }
        .token-box { background: #f3f4f6; padding: 10px; border-radius: 8px; font-family: monospace; font-size: 0.9em; word-break: break-all; }
    </style>
</head>
<body>

<nav class="navbar mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
            SEDUC Smart Admin
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="small text-muted">Olá, <strong><?php echo $_SESSION['user_nome']; ?></strong></span>
            <button class="btn btn-outline-danger btn-sm" onclick="logout()">Sair</button>
        </div>
    </div>
</nav>

<div class="container">
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#aulas">📚 Minhas Aulas</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#config">⚙️ Turmas e Disciplinas</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#token">🔑 Script Token</a></li>
        <?php if ($is_admin): ?>
        <li class="nav-item"><a class="nav-link text-primary" data-bs-toggle="tab" href="#usuarios">👥 Gerenciar Professores</a></li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">
        <!-- ABA DE AULAS -->
        <div class="tab-pane fade show active" id="aulas">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card sticky-form mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Nova Aula</h5>
                            <form id="formAula">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Disciplina</label>
                                    <select id="selectDisciplina" class="form-select" required></select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Conteúdo do Registro</label>
                                    <textarea id="conteudo" class="form-control" rows="6" required placeholder="Digite aqui as atividades..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Atribuir para Turmas</label>
                                    <div id="checkTurmas" class="d-flex flex-wrap gap-2 p-2 border rounded bg-light"></div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Adicionar à Sequência</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Minhas Sequências</h5>
                        <select id="filterTurma" class="form-select form-select-sm" style="width: 150px;" onchange="renderAulas()">
                            <option value="">Todas as Turmas</option>
                        </select>
                    </div>
                    <div id="listaAulas"></div>
                </div>
            </div>
        </div>

        <!-- ABA DE CONFIGURAÇÃO -->
        <div class="tab-pane fade" id="config">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Minhas Turmas</h5>
                            <div class="input-group mb-3">
                                <input type="text" id="newTurma" class="form-control" placeholder="Ex: 201">
                                <button class="btn btn-primary" onclick="addTurma()">Adicionar</button>
                            </div>
                            <div id="listTurmas" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Minhas Disciplinas</h5>
                            <div class="input-group mb-3">
                                <input type="text" id="newDisciplina" class="form-control" placeholder="Ex: Física">
                                <button class="btn btn-primary" onclick="addDisciplina()">Adicionar</button>
                            </div>
                            <div id="listDisciplinas" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA DE TOKEN -->
        <div class="tab-pane fade" id="token">
            <div class="card">
                <div class="card-body">
                    <h5>Seu Token de Acesso</h5>
                    <p class="text-muted small">Use este token nas configurações do seu script Tampermonkey para que ele saiba quais aulas puxar.</p>
                    <div class="token-box mb-3"><?php echo $_SESSION['api_token']; ?></div>
                    <div class="alert alert-warning small">⚠️ Não compartilhe este token com ninguém. Ele dá acesso às suas sequências de aula.</div>
                </div>
            </div>
        </div>

        <!-- ABA DE USUÁRIOS (ADMIN ONLY) -->
        <?php if ($is_admin): ?>
        <div class="tab-pane fade" id="usuarios">
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Novo Professor</h5>
                            <form id="formProf">
                                <div class="mb-2"><input type="text" id="profNome" class="form-control" placeholder="Nome Completo" required></div>
                                <div class="mb-2"><input type="email" id="profEmail" class="form-control" placeholder="E-mail" required></div>
                                <div class="mb-2"><input type="password" id="profSenha" class="form-control" placeholder="Senha Inicial" required></div>
                                <div class="mb-3">
                                    <select id="profRole" class="form-select">
                                        <option value="professor">Professor</option>
                                        <option value="admin">Administrador</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Criar Conta</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div id="listProfs"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const API = 'admin_api.php';
    let state = { aulas: [], turmas: [], disciplinas: [] };

    async function loadData() {
        const res = await fetch(`${API}?action=list`);
        state = await res.json();
        renderAll();
        if (<?php echo $is_admin ? 'true' : 'false'; ?>) loadProfs();
    }

    function renderAll() {
        const container = document.getElementById('checkTurmas');
        container.innerHTML = state.turmas.map(t => `
            <div class="form-check">
                <input class="form-check-input turma-check" type="checkbox" value="${t}" id="chk_${t}">
                <label class="form-check-label small" for="chk_${t}">${t}</label>
            </div>
        `).join('');

        document.getElementById('selectDisciplina').innerHTML = state.disciplinas.map(d => `<option value="${d}">${d}</option>`).join('');
        
        const filter = document.getElementById('filterTurma');
        const current = filter.value;
        filter.innerHTML = '<option value="">Todas as Turmas</option>' + state.turmas.map(t => `<option value="${t}" ${t === current ? 'selected' : ''}>Turma ${t}</option>`).join('');
        
        renderAulas();
        renderConfig();
    }

    function renderAulas() {
        const container = document.getElementById('listaAulas');
        const filter = document.getElementById('filterTurma').value;
        const filtered = filter ? state.aulas.filter(a => a.turma === filter) : state.aulas;

        if (filtered.length === 0) {
            container.innerHTML = '<div class="card p-5 text-center text-muted">Nenhuma aula cadastrada.</div>';
            return;
        }

        const grouped = {};
        filtered.forEach(a => { if (!grouped[a.turma]) grouped[a.turma] = []; grouped[a.turma].push(a); });

        container.innerHTML = Object.entries(grouped).map(([turma, aulas]) => `
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-indigo p-2" style="background:#4f46e5">Turma ${turma}</span>
                    <hr class="flex-grow-1 my-0">
                </div>
                ${aulas.map(a => `
                    <div class="card mb-2 lesson-card ${a.data_uso ? 'used' : 'pending'}">
                        <div class="card-body p-3 d-flex justify-content-between">
                            <div>
                                <div class="small fw-bold text-secondary mb-1">#${a.ordem} | ${a.disciplina} ${a.data_uso ? `<span class="badge bg-success ms-2">Usada em ${a.data_uso}</span>` : ''}</div>
                                <div class="small">${a.conteudo}</div>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <button class="btn btn-sm btn-link text-danger p-0" onclick="deleteAula(${a.id})">🗑️</button>
                                <button class="btn btn-sm btn-link text-primary p-0" onclick="setNext(${a.id})" title="Definir como Próxima">🎯</button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `).join('');
    }

    function renderConfig() {
        document.getElementById('listTurmas').innerHTML = state.turmas.map(t => `<div class="list-group-item d-flex justify-content-between align-items-center">Turma ${t} <button class="btn btn-sm text-danger" onclick="delTurma('${t}')">🗑️</button></div>`).join('');
        document.getElementById('listDisciplinas').innerHTML = state.disciplinas.map(d => `<div class="list-group-item d-flex justify-content-between align-items-center">${d} <button class="btn btn-sm text-danger" onclick="delDisciplina('${d}')">🗑️</button></div>`).join('');
    }

    async function loadProfs() {
        const res = await fetch(`${API}?action=list_profs`);
        const profs = await res.json();
        document.getElementById('listProfs').innerHTML = `
            <div class="card"><div class="card-body"><h5>Lista de Professores</h5>
            <table class="table table-sm small">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Role</th><th>Token</th><th>Ações</th></tr></thead>
                <tbody>${profs.map(p => `
                    <tr><td>${p.nome}</td><td>${p.email}</td><td>${p.role}</td><td class="text-muted">${p.api_token}</td>
                    <td><button class="btn btn-sm text-danger" onclick="delProf(${p.id})">Excluir</button></td></tr>
                `).join('')}</tbody>
            </table></div></div>`;
    }

    document.getElementById('formAula').onsubmit = async (e) => {
        e.preventDefault();
        const turmas = Array.from(document.querySelectorAll('.turma-check:checked')).map(c => c.value);
        if (turmas.length === 0) return alert('Selecione as turmas');
        await fetch(`${API}?action=save_aula`, { method: 'POST', body: JSON.stringify({ turmas, disciplina: document.getElementById('selectDisciplina').value, conteudo: document.getElementById('conteudo').value }), headers: { 'Content-Type': 'application/json' } });
        document.getElementById('conteudo').value = ''; loadData();
    };

    if (<?php echo $is_admin ? 'true' : 'false'; ?>) {
        document.getElementById('formProf').onsubmit = async (e) => {
            e.preventDefault();
            await fetch(`${API}?action=save_prof`, { method: 'POST', body: JSON.stringify({ nome: document.getElementById('profNome').value, email: document.getElementById('profEmail').value, senha: document.getElementById('profSenha').value, role: document.getElementById('profRole').value }), headers: { 'Content-Type': 'application/json' } });
            e.target.reset(); loadProfs();
        };
    }

    async function deleteAula(id) { if (confirm('Excluir aula?')) { await fetch(`${API}?action=delete_aula&id=${id}`); loadData(); } }
    async function setNext(id) { if (confirm('Definir como próxima aula?')) { await fetch(`${API}?action=set_next&id=${id}`); loadData(); } }
    async function addTurma() { const nome = document.getElementById('newTurma').value; if (nome) { await fetch(`${API}?action=add_turma`, { method: 'POST', body: JSON.stringify({nome}) }); document.getElementById('newTurma').value = ''; loadData(); } }
    async function delTurma(nome) { if (confirm('Excluir turma?')) { await fetch(`${API}?action=del_turma&nome=${nome}`); loadData(); } }
    async function addDisciplina() { const nome = document.getElementById('newDisciplina').value; if (nome) { await fetch(`${API}?action=add_disciplina`, { method: 'POST', body: JSON.stringify({nome}) }); document.getElementById('newDisciplina').value = ''; loadData(); } }
    async function delDisciplina(nome) { if (confirm('Excluir disciplina?')) { await fetch(`${API}?action=del_disciplina&nome=${nome}`); loadData(); } }
    async function delProf(id) { if (confirm('Excluir professor?')) { await fetch(`${API}?action=del_prof&id=${id}`); loadProfs(); } }
    async function logout() { await fetch('api/auth.php?action=logout'); window.location.href = 'login.php'; }
    async function resetAll() { if (confirm('Resetar todas as sequências?')) { await fetch(`${API}?action=reset_all`); loadData(); } }

    loadData();
</script>

</body>
</html>
