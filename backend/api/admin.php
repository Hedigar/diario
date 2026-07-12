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
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: #111827; height: 100vh; overflow: hidden; }
        .navbar { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 1rem 0; z-index: 1000; }
        .navbar-brand { font-weight: 700; color: var(--primary-color) !important; display: flex; align-items: center; gap: 10px; }
        .nav-link { border: none !important; color: #6b7280; font-weight: 600; padding: 10px 0; position: relative; cursor: pointer; }
        .nav-link.active { color: var(--primary-color) !important; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--primary-color); border: none; font-weight: 600; }
        .sidebar { background: white; border-right: 1px solid #e5e7eb; height: calc(100vh - 72px); overflow-y: auto; }
        .sidebar-item { padding: 12px 16px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s; }
        .sidebar-item:hover { background: #f9fafb; }
        .sidebar-item.active { background: #eff6ff; border-left-color: var(--primary-color); color: var(--primary-color); font-weight: 600; }
        .main-content { height: calc(100vh - 72px); overflow-y: auto; }
        .lesson-card { border-left: 4px solid #e5e7eb; transition: all 0.2s; margin-bottom: 0.5rem; }
        .lesson-card.used { border-left-color: #10b981; background-color: #f0fdf4; }
        .lesson-card.current { border-left-color: var(--primary-color); background-color: #eef2ff; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2); }
        .lesson-card.pending { border-left-color: #f59e0b; }
        .accordion-button:not(.collapsed) { background-color: #f9fafb; box-shadow: none; }
        .accordion-button:focus { box-shadow: none; }
        .token-box { background: #f3f4f6; padding: 10px; border-radius: 8px; font-family: monospace; font-size: 0.9em; word-break: break-all; }
        .loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; z-index: 100; }
        .spinner-border-sm { width: 1.5rem; height: 1.5rem; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container-fluid px-4">
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

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 sidebar">
            <div class="p-3">
                <ul class="nav nav-pills flex-column mb-3" id="adminTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#aulas">📚 Aulas</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#importacao">📥 Importar</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#config">⚙️ Config</a></li>
                    <li class="nav-item"><a class="nav-link" href="wizard.php">🪄 Wizard</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#token">🔑 Token</a></li>
                    <?php if ($is_admin): ?>
                    <li class="nav-item"><a class="nav-link text-primary" data-bs-toggle="tab" href="#usuarios">👥 Professores</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-12 col-md-9 col-lg-10 main-content">
            <div class="tab-content p-4">
                <!-- ABA DE AULAS -->
                <div class="tab-pane fade show active" id="aulas">
                    <div class="row g-4">
                        <div class="col-12 col-lg-3">
                            <div class="card sticky-top" style="top: 1rem;">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Turmas</h5>
                                    <div id="sidebarTurmas"></div>
                                    <hr class="my-4">
                                    <h5 class="card-title mb-4">Nova Aula</h5>
                                    <form id="formAula">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Disciplina</label>
                                            <select id="selectDisciplina" class="form-select" required></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Conteúdo do Registro</label>
                                            <textarea id="conteudo" class="form-control" rows="4" required placeholder="Digite aqui as atividades..."></textarea>
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
                        <div class="col-12 col-lg-9">
                            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
                                <h4 class="mb-0" id="turmaTitle">Selecione uma turma</h4>
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="form-check align-self-center" id="selectAllContainer" style="display: none;">
                                        <input class="form-check-input" type="checkbox" id="selectAllAulas">
                                        <label class="form-check-label small" for="selectAllAulas">Selecionar Todas</label>
                                    </div>
                                    <button class="btn btn-danger btn-sm" id="deleteSelectedBtn" style="display: none;">🗑️ Excluir Selecionadas</button>
                                    <input type="text" id="searchLessons" class="form-control form-control-sm" style="width: 250px;" placeholder="🔍 Buscar aula...">
                                    <button class="btn btn-outline-primary btn-sm" id="expandAllBtn">Expandir Todas</button>
                                    <button class="btn btn-outline-secondary btn-sm" id="collapseAllBtn">Recolher Todas</button>
                                </div>
                            </div>
                            <div id="lessonsArea" style="position: relative;">
                                <div class="card p-5 text-center text-muted">
                                    <h5>👋 Selecione uma turma na barra lateral</h5>
                                    <p class="mb-0">As aulas da turma selecionada aparecerão aqui</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA DE IMPORTAÇÃO EM MASSA -->
                <div class="tab-pane fade" id="importacao">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Importar Aulas em Massa</h5>
                                    <form id="formImportacao">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Disciplina</label>
                                            <select id="importDisciplina" class="form-select" required></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Turmas (selecione múltiplas)</label>
                                            <select id="importTurmas" class="form-select" multiple required style="min-height: 150px;"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Cole a lista de aulas (texto livre)</label>
                                            <textarea id="importTexto" class="form-control" rows="10" required placeholder="Exemplo:
1. Introdução à Matemática - Habilidade: BM1.1
2. Números Inteiros - Habilidade: BM1.2
3. Operações Básicas - Habilidade: BM1.3"></textarea>
                                        </div>
                                        <button type="submit" id="btnProcessar" class="btn btn-primary w-100">Processar e Extrair Aulas</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Resumo da Importação</h5>
                                    <div id="importResumo"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA DE CONFIGURAÇÃO -->
                <div class="tab-pane fade" id="config">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="card">
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
                        <div class="col-12 col-md-6">
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
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
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
                        <div class="col-12 col-md-8">
                            <div id="listProfs"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const API = 'admin_api.php';
    let state = { aulas: [], turmas: [], disciplinas: [] };
    let selectedTurma = null;
    let expandedAll = false;
    let selectedAulaIds = new Set();

    async function loadData() {
        try {
            const res = await fetch(`${API}?action=list`);
            const data = await res.json();
            if (data.error) {
                alert('Erro ao carregar dados: ' + data.error);
                renderAll();
                return;
            }
            state = data;
            renderAll();
            if (<?php echo $is_admin ? 'true' : 'false'; ?>) loadProfs();
        } catch (e) {
            alert('Erro ao carregar dados: ' + e.message);
            renderAll();
        }
    }

    function renderAll() {
        renderSidebarTurmas();
        renderFormTurmas();
        document.getElementById('selectDisciplina').innerHTML = state.disciplinas.map(d => `<option value="${d}">${d}</option>`).join('');
        renderConfig();
        if (selectedTurma) {
            renderLessonsForTurma(selectedTurma);
        }
    }

    function renderSidebarTurmas() {
        const container = document.getElementById('sidebarTurmas');
        if (state.turmas.length === 0) {
            container.innerHTML = '<div class="text-muted small">Nenhuma turma cadastrada</div>';
            return;
        }
        container.innerHTML = state.turmas.map(t => `
            <div class="sidebar-item ${selectedTurma === t ? 'active' : ''}" onclick="selectTurma('${t}')">
                📚 Turma ${t}
            </div>
        `).join('');
    }

    function selectTurma(turma) {
        selectedTurma = turma;
        renderSidebarTurmas();
        renderLessonsForTurma(turma, true);
    }

    async function renderLessonsForTurma(turma, showLoading = false) {
        const container = document.getElementById('lessonsArea');
        const title = document.getElementById('turmaTitle');
        title.textContent = `📚 Turma ${turma}`;
        
        if (showLoading) {
            container.innerHTML = `
                <div class="loading-overlay">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;
            await new Promise(r => setTimeout(r, 300));
        }

        const searchTerm = document.getElementById('searchLessons').value.toLowerCase();
        const turmaAulas = state.aulas.filter(a => a.turma === turma);
        
        if (turmaAulas.length === 0) {
            container.innerHTML = '<div class="card p-5 text-center text-muted">Nenhuma aula cadastrada para esta turma.</div>';
            document.getElementById('selectAllContainer').style.display = 'none';
            document.getElementById('deleteSelectedBtn').style.display = 'none';
            return;
        }

        // Group by disciplina
        const grouped = {};
        turmaAulas.forEach(a => {
            if (!grouped[a.disciplina]) grouped[a.disciplina] = [];
            grouped[a.disciplina].push(a);
        });

        // For each disciplina, find current lesson (first without data_uso)
        let html = '';
        Object.entries(grouped).forEach(([disciplina, aulas]) => {
            // Find current lesson index
            let currentIndex = aulas.findIndex(a => !a.data_uso);
            if (currentIndex === -1) currentIndex = aulas.length;

            html += `
                <div class="mb-4">
                    <h5 class="mb-3">
                        <span class="badge text-bg-primary">${disciplina}</span>
                    </h5>
                    <div class="accordion" id="accordion-${disciplina.replace(/\s/g, '-')}">
            `;

            aulas.forEach((a, idx) => {
                const isUsed = !!a.data_uso;
                const isCurrent = idx === currentIndex;
                const matchesSearch = !searchTerm || 
                    a.conteudo.toLowerCase().includes(searchTerm) || 
                    String(a.ordem).includes(searchTerm);
                
                if (!matchesSearch) return;

                const collapseId = `collapse-${a.id}`;
                const headerId = `heading-${a.id}`;
                const isChecked = selectedAulaIds.has(a.id);
                
                html += `
                    <div class="accordion-item border-0 lesson-card ${isUsed ? 'used' : (isCurrent ? 'current' : 'pending')}">
                        <h2 class="accordion-header" id="${headerId}">
                            <button class="accordion-button ${expandedAll ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${expandedAll}" aria-controls="${collapseId}">
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input aula-checkbox" type="checkbox" data-id="${a.id}" ${isChecked ? 'checked' : ''} style="margin-right: 8px;">
                                    <span class="badge ${isCurrent ? 'text-bg-primary' : (isUsed ? 'text-bg-success' : 'text-bg-warning')}">#${a.ordem}</span>
                                    <strong>${isCurrent ? '🎯 Próxima Aula' : (isUsed ? '✅ Aula Usada' : '📖 Aula Pendente')}</strong>
                                    ${isUsed ? `<span class="text-muted small">(${a.data_uso})</span>` : ''}
                                </div>
                            </button>
                        </h2>
                        <div id="${collapseId}" class="accordion-collapse collapse ${expandedAll ? 'show' : ''}" data-bs-parent="#accordion-${disciplina.replace(/\s/g, '-')}">
                            <div class="accordion-body">
                                <p class="mb-3">${a.conteudo}</p>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="setNext(${a.id})" title="Definir como Próxima">🎯 Definir como Próxima</button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAula(${a.id})">🗑️ Excluir</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Show controls
        document.getElementById('selectAllContainer').style.display = 'block';
        document.getElementById('deleteSelectedBtn').style.display = 'block';

        // Attach event listeners to checkboxes
        attachCheckboxListeners();
        updateSelectAllCheckbox();
        updateDeleteButton();
    }

    function attachCheckboxListeners() {
        document.querySelectorAll('.aula-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const id = parseInt(e.target.dataset.id);
                if (e.target.checked) {
                    selectedAulaIds.add(id);
                } else {
                    selectedAulaIds.delete(id);
                }
                updateSelectAllCheckbox();
                updateDeleteButton();
            });
        });
    }

    function updateSelectAllCheckbox() {
        const selectAll = document.getElementById('selectAllAulas');
        if (!selectAll) return;

        const visibleCheckboxes = document.querySelectorAll('.aula-checkbox');
        const allChecked = visibleCheckboxes.length > 0 && Array.from(visibleCheckboxes).every(cb => selectedAulaIds.has(parseInt(cb.dataset.id)));
        selectAll.checked = allChecked;
    }

    function updateDeleteButton() {
        const btn = document.getElementById('deleteSelectedBtn');
        if (!btn) return;
        btn.textContent = `🗑️ Excluir Selecionadas (${selectedAulaIds.size})`;
        btn.disabled = selectedAulaIds.size === 0;
    }

    function renderFormTurmas() {
        const container = document.getElementById('checkTurmas');
        container.innerHTML = state.turmas.map(t => `
            <div class="form-check">
                <input class="form-check-input turma-check" type="checkbox" value="${t}" id="chk-${t}">
                <label class="form-check-label small" for="chk-${t}">${t}</label>
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
        document.getElementById('conteudo').value = ''; 
        await loadData();
    };

    if (<?php echo $is_admin ? 'true' : 'false'; ?>) {
        document.getElementById('formProf').onsubmit = async (e) => {
            e.preventDefault();
            await fetch(`${API}?action=save_prof`, { method: 'POST', body: JSON.stringify({ nome: document.getElementById('profNome').value, email: document.getElementById('profEmail').value, senha: document.getElementById('profSenha').value, role: document.getElementById('profRole').value }), headers: { 'Content-Type': 'application/json' } });
            e.target.reset(); 
            loadProfs();
        };
    }

    async function deleteAula(id) { 
        if (confirm('Excluir aula?')) { 
            await fetch(`${API}?action=delete_aula&id=${id}`); 
            await loadData(); 
        } 
    }
    async function setNext(id) { 
        if (confirm('Definir como próxima aula?')) { 
            await fetch(`${API}?action=set_next&id=${id}`); 
            await loadData(); 
        } 
    }
    async function addTurma() { 
        const nome = document.getElementById('newTurma').value; 
        if (nome) { 
            await fetch(`${API}?action=add_turma`, { method: 'POST', body: JSON.stringify({nome}) }); 
            document.getElementById('newTurma').value = ''; 
            await loadData(); 
        } 
    }
    async function delTurma(nome) { 
        if (confirm('Excluir turma?')) { 
            await fetch(`${API}?action=del_turma&nome=${nome}`); 
            if (selectedTurma === nome) selectedTurma = null;
            await loadData(); 
        } 
    }
    async function addDisciplina() { 
        const nome = document.getElementById('newDisciplina').value; 
        if (nome) { 
            await fetch(`${API}?action=add_disciplina`, { method: 'POST', body: JSON.stringify({nome}) }); 
            document.getElementById('newDisciplina').value = ''; 
            await loadData(); 
        } 
    }
    async function delDisciplina(nome) { 
        if (confirm('Excluir disciplina?')) { 
            await fetch(`${API}?action=del_disciplina&nome=${nome}`); 
            await loadData(); 
        } 
    }
    async function delProf(id) { 
        if (confirm('Excluir professor?')) { 
            await fetch(`${API}?action=del_prof&id=${id}`); 
            loadProfs(); 
        } 
    }
    async function logout() { 
        await fetch('auth.php?action=logout'); 
        window.location.href = 'login.php'; 
    }

    // Search functionality
    document.getElementById('searchLessons').addEventListener('input', () => {
        if (selectedTurma) {
            renderLessonsForTurma(selectedTurma);
        }
    });

    // Expand/collapse all
    document.getElementById('expandAllBtn').addEventListener('click', () => {
        expandedAll = true;
        if (selectedTurma) renderLessonsForTurma(selectedTurma);
    });
    document.getElementById('collapseAllBtn').addEventListener('click', () => {
        expandedAll = false;
        if (selectedTurma) renderLessonsForTurma(selectedTurma);
    });

    // Select All
    document.getElementById('selectAllAulas').addEventListener('change', (e) => {
        const isChecked = e.target.checked;
        document.querySelectorAll('.aula-checkbox').forEach(checkbox => {
            const id = parseInt(checkbox.dataset.id);
            if (isChecked) {
                selectedAulaIds.add(id);
            } else {
                selectedAulaIds.delete(id);
            }
            checkbox.checked = isChecked;
        });
        updateDeleteButton();
    });

    // Delete Selected
    document.getElementById('deleteSelectedBtn').addEventListener('click', async () => {
        if (selectedAulaIds.size === 0) return;
        if (!confirm(`Deseja realmente excluir ${selectedAulaIds.size} aula(s)?`)) return;

        try {
            const res = await fetch(`${API}?action=delete_aulas`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: Array.from(selectedAulaIds) })
            });
            const data = await res.json();
            if (data.success) {
                selectedAulaIds.clear();
                await loadData();
            } else {
                alert(data.error || 'Erro ao excluir aulas');
            }
        } catch (err) {
            alert('Erro ao excluir aulas: ' + err.message);
        }
    });

    // Importação em massa
    let extractedLessons = null;

    function renderImportForm() {
        document.getElementById('importDisciplina').innerHTML = state.disciplinas.map(d => `<option value="${d}">${d}</option>`).join('');
        document.getElementById('importTurmas').innerHTML = state.turmas.map(t => `<option value="${t}">Turma ${t}</option>`).join('');
    }

    document.getElementById('formImportacao').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnProcessar');
        const resumoDiv = document.getElementById('importResumo');
        
        btn.disabled = true;
        btn.innerHTML = '⌛ Processando...';
        resumoDiv.innerHTML = '<div class="text-center text-muted">Extraindo aulas...</div>';

        try {
            const turmas = Array.from(document.getElementById('importTurmas').selectedOptions).map(o => o.value);
            const disciplina = document.getElementById('importDisciplina').value;
            const texto = document.getElementById('importTexto').value;

            const res = await fetch(`${API}?action=extract_lessons`, {
                method: 'POST',
                body: JSON.stringify({ turmas, disciplina, texto }),
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await res.json();
            
            if (data.error) {
                resumoDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            extractedLessons = data;
            renderResumoImportacao(data);
        } catch (err) {
            resumoDiv.innerHTML = `<div class="alert alert-danger">Erro: ${err.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Processar e Extrair Aulas';
        }
    };

    function renderResumoImportacao(data) {
        const resumoDiv = document.getElementById('importResumo');
        let html = `
            <div class="mb-3">
                <span class="badge bg-primary">${data.aulas.length} aulas extraídas</span>
                <span class="badge bg-secondary ms-2">${data.turmas.length} turmas selecionadas</span>
            </div>
            <table class="table table-sm small mb-3">
                <thead><tr><th>#</th><th>Título</th><th>Habilidades</th></tr></thead>
                <tbody>
                    ${data.aulas.map(a => `
                        <tr>
                            <td>${a.numero}</td>
                            <td>${a.titulo}</td>
                            <td class="text-muted">${a.habilidades || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div class="alert alert-info small mb-3">
                <strong>Serão inseridas:</strong> ${data.aulas.length} aulas × ${data.turmas.length} turmas = ${data.aulas.length * data.turmas.length} registros no total
            </div>
            <button id="btnConfirmar" class="btn btn-success w-100" onclick="confirmarImportacao()">✅ Confirmar e Salvar</button>
        `;
        resumoDiv.innerHTML = html;
    }

    async function confirmarImportacao() {
        if (!extractedLessons) return;
        
        const btn = document.getElementById('btnConfirmar');
        btn.disabled = true;
        btn.innerHTML = '⌛ Salvando...';

        try {
            const res = await fetch(`${API}?action=confirm_import`, {
                method: 'POST',
                body: JSON.stringify(extractedLessons),
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await res.json();
            
            if (data.success) {
                document.getElementById('importResumo').innerHTML = '<div class="alert alert-success">✅ Importação concluída com sucesso!</div>';
                document.getElementById('formImportacao').reset();
                extractedLessons = null;
                await loadData();
            } else {
                document.getElementById('importResumo').innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            }
        } catch (err) {
            document.getElementById('importResumo').innerHTML = `<div class="alert alert-danger">Erro: ${err.message}</div>`;
        } finally {
            btn.disabled = false;
        }
    }

    // Chamar renderImportForm quando os dados são carregados
    const originalRenderAll = renderAll;
    renderAll = function() {
        originalRenderAll();
        renderImportForm();
    };

    // Auto-select first turma if available
    function autoSelectFirstTurma() {
        if (state.turmas.length > 0 && !selectedTurma) {
            selectTurma(state.turmas[0]);
        }
    }

    // Override loadData to auto-select first turma
    const originalLoadData = loadData;
    loadData = async function() {
        await originalLoadData();
        autoSelectFirstTurma();
    };

    loadData();
</script>

</body>
</html>
