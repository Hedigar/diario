<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lógica para salvar os passos via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $step = $data['step'] ?? 0;

    try {
        if ($step == 1) {
            // Passo 1: Criar Plano Mestre
            $stmt = $pdo->prepare("INSERT INTO planos_mestres (usuario_id, nome_plano, disciplina, carga_horaria_total) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $data['nome_plano'], $data['disciplina'], $data['carga_horaria_total']]);
            $plano_id = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'plano_id' => $plano_id]);
            exit;
        }

        if ($step == 2) {
            // Passo 2: Adicionar Habilidades (processamento em massa)
            $plano_id = $data['plano_id'];
            $habilidades_raw = $data['habilidades']; // Texto do textarea
            $linhas = explode("\n", $habilidades_raw);
            
            $stmt = $pdo->prepare("INSERT INTO habilidades_plano (plano_id, codigo_habilidade, descricao) VALUES (?, ?, ?)");
            
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if (empty($linha)) continue;
                
                // Tenta separar por ":" ou "-" (priorizando ":" se ambos existirem)
                $codigo = $linha;
                $descricao = $linha;
                
                if (strpos($linha, ':') !== false) {
                    $partes = explode(":", $linha, 2);
                    $codigo = trim($partes[0]);
                    $descricao = trim($partes[1]);
                } elseif (strpos($linha, '-') !== false) {
                    $partes = explode("-", $linha, 2);
                    $codigo = trim($partes[0]);
                    $descricao = trim($partes[1]);
                }
                
                $stmt->execute([$plano_id, $codigo, $descricao]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        }

        if ($step == 3) {
            // Passo 3: Sequenciador de Aulas (Simplificado, pois a IA vai detalhar)
            // Apenas registramos que o passo 3 foi concluído
            echo json_encode(['success' => true]);
            exit;
        }

        if ($step === 'ai') {
            // Passo AI: Salvar Plano Revisado
            $plano_id = $data['plano_id'];
            $aulas = $data['aulas'] ?? [];
            
            // Limpa detalhes anteriores se houver (caso esteja voltando e salvando de novo)
            $pdo->prepare("DELETE FROM detalhes_plano WHERE plano_id = ?")->execute([$plano_id]);
            
            $stmt = $pdo->prepare("INSERT INTO detalhes_plano (plano_id, sequencia, topico, descricao, atividades) VALUES (?, ?, ?, ?, ?)");
            foreach ($aulas as $aula) {
                $stmt->execute([
                    $plano_id, 
                    $aula['sequencia'], 
                    $aula['topico'], 
                    $aula['descricao'], 
                    $aula['atividades']
                ]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        }

        if ($step == 4) {
            // Passo 4: Atribuição de Turmas
            $plano_id = $data['plano_id'];
            $turmas_selecionadas = $data['turmas'] ?? [];
            
            $stmt = $pdo->prepare("INSERT INTO atribuicoes (plano_id, turma_id) VALUES (?, ?)");
            foreach ($turmas_selecionadas as $turma_id) {
                $stmt->execute([$plano_id, $turma_id]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Outros passos serão implementados conforme a necessidade do frontend
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Busca planos existentes para clonagem
$stmt_planos = $pdo->prepare("SELECT id, nome_plano FROM planos_mestres WHERE usuario_id = ?");
$stmt_planos->execute([$user_id]);
$planos_existentes = $stmt_planos->fetchAll();

// Lógica para carregar dados de um plano (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'get_plano') {
    header('Content-Type: application/json');
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM planos_mestres WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $user_id]);
    $plano = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM habilidades_plano WHERE plano_id = ?");
    $stmt->execute([$id]);
    $habilidades = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM detalhes_plano WHERE plano_id = ? ORDER BY sequencia");
    $stmt->execute([$id]);
    $detalhes = $stmt->fetchAll();
    
    echo json_encode([
        'plano' => $plano,
        'habilidades' => $habilidades,
        'detalhes' => $detalhes
    ]);
    exit;
}

// Busca disciplinas existentes
$stmt_disc = $pdo->prepare("SELECT nome FROM disciplinas WHERE usuario_id = ?");
$stmt_disc->execute([$user_id]);
$disciplinas_existentes = $stmt_disc->fetchAll(PDO::FETCH_COLUMN);

// Busca turmas para o Passo 4
$stmt_turmas = $pdo->prepare("SELECT id, nome FROM turmas WHERE usuario_id = ?");
$stmt_turmas->execute([$user_id]);
$turmas = $stmt_turmas->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wizard de Planejamento Mestre - SEDUC Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #4f46e5; --bg-color: #f9fafb; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); }
        .wizard-card { max-width: 800px; margin: 50px auto; background: white; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 30px; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { flex: 1; text-align: center; padding: 10px; border-bottom: 3px solid #e5e7eb; color: #9ca3af; font-weight: 600; }
        .step.active { border-color: var(--primary-color); color: var(--primary-color); }
        .step.completed { border-color: #10b981; color: #10b981; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; }
        .btn-primary { background-color: var(--primary-color); border: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="wizard-card">
        <h2 class="text-center mb-4">Novo Planejamento Mestre</h2>
        
        <div class="step-indicator">
            <div class="step active" id="ind-1">1. Definição</div>
            <div class="step" id="ind-2">2. Habilidades</div>
            <div class="step" id="ind-3">3. Sequência</div>
            <div class="step" id="ind-ai">IA: Revisão</div>
            <div class="step" id="ind-4">4. Atribuição</div>
        </div>

        <form id="wizardForm">
            <input type="hidden" id="plano_id" name="plano_id">

            <!-- PASSO 1: DEFINIÇÃO -->
            <div class="wizard-step active" id="step-1">
                <h4>Passo 1: Criar Plano Mestre</h4>
                
                <?php if (!empty($planos_existentes)): ?>
                <div class="alert alert-info d-flex align-items-center justify-content-between">
                    <span>Deseja clonar um plano existente?</span>
                    <select class="form-select form-select-sm w-50" onchange="loadExistingPlano(this.value)">
                        <option value="">-- Selecione um plano --</option>
                        <?php foreach ($planos_existentes as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nome_plano'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nome do Plano</label>
                    <input type="text" class="form-control" name="nome_plano" id="nome_plano" placeholder="Ex: Matemática 1º Trimestre" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Disciplina</label>
                    <select class="form-select" name="disciplina" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($disciplinas_existentes as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Carga Horária Total (Aulas)</label>
                    <input type="number" class="form-control" name="carga_horaria_total" id="carga_horaria_total" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Instrumentos Avaliativos Planejados</label>
                    <textarea class="form-control" name="instrumentos_avaliacao" id="instrumentos_avaliacao" rows="2" placeholder="Ex: Prova objetiva, Trabalho em grupo, Seminário..."></textarea>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-primary" onclick="nextStep(1)">Próximo</button>
                </div>
            </div>

            <!-- PASSO 2: HABILIDADES -->
            <div class="wizard-step" id="step-2">
                <h4>Passo 2: Adicionar Habilidades (BNCC)</h4>
                <p class="text-muted">Cole as habilidades abaixo (uma por linha). Ex: EF15LP01 - Descrição</p>
                <div class="mb-3">
                    <textarea class="form-control" name="habilidades" rows="10" placeholder="Código - Descrição..."></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">Anterior</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(2)">Próximo</button>
                </div>
            </div>

            <!-- PASSO 3: SEQUENCIADOR -->
            <div class="wizard-step" id="step-3">
                <h4>Passo 3: Sequenciador de Aulas</h4>
                
                <div class="progress mb-3 d-none" id="wizard-progress-container" style="height: 25px;">
                    <div id="wizard-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <div id="wizard-status-msg" class="small text-muted mb-3"></div>

                <div id="sequencer-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Tópico</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="sequencer-body">
                            <!-- Linhas dinâmicas aqui -->
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addSequencerRow()">+ Adicionar Aula</button>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(3)">Anterior</button>
                    <button type="button" class="btn btn-info text-white" onclick="generateWithAI()">✨ Gerar Plano com IA</button>
                </div>
            </div>

            <!-- PASSO IA: REVISÃO -->
            <div class="wizard-step" id="step-ai">
                <h4>IA: Revisão do Planejamento</h4>
                <p class="text-muted">A IA dividiu seus tópicos nas aulas solicitadas. Você pode revisar e editar cada aula antes de finalizar.</p>
                <div id="ai-review-container" class="mb-4">
                    <!-- Aulas geradas aparecerão aqui -->
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="prevStep('ai')">Anterior</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep('ai')">Tudo OK, ir para Atribuição</button>
                </div>
            </div>

            <!-- PASSO 4: ATRIBUIÇÃO -->
            <div class="wizard-step" id="step-4">
                <h4>Passo 4: Atribuição de Turmas</h4>
                <p>Selecione as turmas que utilizarão este planejamento:</p>
                <div class="list-group mb-4">
                    <?php foreach ($turmas as $t): ?>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="checkbox" name="turmas[]" value="<?= $t['id'] ?>">
                        Turma <?= $t['nome'] ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(4)">Anterior</button>
                    <button type="button" class="btn btn-success" onclick="finishWizard()">Finalizar e Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let currentPlanoId = null;

async function loadExistingPlano(id) {
    if (!id) return;
    const res = await fetch(`wizard.php?action=get_plano&id=${id}`);
    const data = await res.json();
    
    // Preenche Passo 1
    document.getElementById('nome_plano').value = data.plano.nome_plano + ' (Cópia)';
    document.querySelector('[name="disciplina"]').value = data.plano.disciplina;
    document.querySelector('[name="carga_horaria_total"]').value = data.plano.carga_horaria_total;
    
    // Preenche Passo 2
    const habilidadesText = data.habilidades.map(h => `${h.codigo_habilidade} - ${h.descricao}`).join('\n');
    document.querySelector('[name="habilidades"]').value = habilidadesText;
    
    // Preenche Passo 3
    const tbody = document.getElementById('sequencer-body');
    tbody.innerHTML = '';
    data.detalhes.forEach(d => {
        addSequencerRow(d.topico);
    });
}

async function nextStep(step) {
    const form = document.getElementById('wizardForm');
    const formData = new FormData(form);
    const data = {};
    
    // Coleta dados específicos dependendo do passo
    if (step === 1) {
        data.nome_plano = formData.get('nome_plano');
        data.disciplina = formData.get('disciplina');
        data.carga_horaria_total = formData.get('carga_horaria_total');
    } else if (step === 2) {
        data.habilidades = formData.get('habilidades');
    } else if (step === 3) {
        data.topicos = formData.getAll('topicos[]');
    } else if (step === 'ai') {
        // Coleta as aulas editadas no container de revisão
        const aulas = [];
        document.querySelectorAll('.ai-aula-card').forEach(card => {
            aulas.push({
                sequencia: card.dataset.sequencia,
                topico: card.querySelector('.ai-topico').value,
                descricao: card.querySelector('.ai-descricao').value,
                atividades: card.querySelector('.ai-atividades').value
            });
        });
        data.aulas = aulas;
    }

    data.step = step;
    data.plano_id = currentPlanoId;

    const response = await fetch('wizard.php', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: { 'Content-Type': 'application/json' }
    });
    const result = await response.json();
    
    if (!result.success) {
        alert('Erro: ' + result.error);
        return;
    }
    
    if (step === 1) currentPlanoId = result.plano_id;

    // Muda visualmente o passo
    let next = step === 3 ? 'ai' : (step === 'ai' ? 4 : step + 1);
    
    document.getElementById(`step-${step}`).classList.remove('active');
    document.getElementById(`ind-${step}`).classList.add('completed');
    document.getElementById(`step-${next}`).classList.add('active');
    document.getElementById(`ind-${next}`).classList.add('active');
}

function prevStep(step) {
    let prev = step === 'ai' ? 3 : (step === 4 ? 'ai' : step - 1);
    document.getElementById(`step-${step}`).classList.remove('active');
    document.getElementById(`ind-${step}`).classList.remove('active');
    document.getElementById(`step-${prev}`).classList.add('active');
}

async function generateWithAI() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '✨ Gerando Diários...';
    btn.disabled = true;

    const form = document.getElementById('wizardForm');
    const formData = new FormData(form);
    const totalAulas = parseInt(formData.get('carga_horaria_total'));
    
    const progressContainer = document.getElementById('wizard-progress-container');
    const progressBar = document.getElementById('wizard-progress-bar');
    const statusMsg = document.getElementById('wizard-status-msg');
    
    progressContainer.classList.remove('d-none');
    
    let aulasFinais = [];
    const chunkSize = 5; // Fatias de 5 aulas completas (Tópico + Descrição)
    
    try {
        for (let i = 1; i <= totalAulas; i += chunkSize) {
            const fim = Math.min(i + chunkSize - 1, totalAulas);
            const qtdNoBloco = fim - i + 1;
            
            statusMsg.innerText = `Gerando diários: aulas ${i} até ${fim}...`;
            
            const response = await fetch('gemini_service.php', {
                method: 'POST',
                body: JSON.stringify({
                    nome_plano: formData.get('nome_plano'),
                    disciplina: formData.get('disciplina'),
                    total_aulas: qtdNoBloco,
                    topicos: formData.getAll('topicos[]'),
                    habilidades: formData.get('habilidades'),
                    instrumentos: formData.get('instrumentos_avaliacao')
                }),
                headers: { 'Content-Type': 'application/json' }
            });
            
            const result = await response.json();
            if (result.success) {
                const bloco = Array.isArray(result.plano_gerado) ? result.plano_gerado : [result.plano_gerado];
                // Ajusta a sequência se a IA se perder
                bloco.forEach((aula, idx) => {
                    aula.sequencia = i + idx;
                    aulasFinais.push(aula);
                });
                
                const percent = Math.round((aulasFinais.length / totalAulas) * 100);
                progressBar.style.width = `${percent}%`;
                progressBar.innerText = `${percent}%`;
                
                // Renderiza o que já temos para o professor ver o progresso
                renderAIPlan(aulasFinais);
            }
            
            // Pausa de 2 segundos para não travar a API
            if (fim < totalAulas) {
                await new Promise(r => setTimeout(r, 2000));
            }
        }

        nextStep(3); 
        
    } catch (e) {
        alert('Erro: ' + e.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        statusMsg.innerText = "Planejamento concluído!";
    }
}

async function expandLesson(index) {
    const card = document.querySelectorAll('.ai-aula-card')[index];
    if (!card) return;
    
    const btn = card.querySelector('.btn-expand');
    const originalText = btn.innerHTML;
    
    card.classList.add('border-primary');
    btn.innerHTML = '🪄 Gerando...';
    btn.disabled = true;

    const form = document.getElementById('wizardForm');
    const formData = new FormData(form);

    const prompt = `Gere uma descrição resumida (3-4 linhas) e 2 atividades curtas para a aula de ${formData.get('disciplina')} sobre o tópico: "${card.querySelector('.ai-topico').value}". 
    Habilidades: ${formData.get('habilidades')}.
    Instrumentos de avaliação citados: ${formData.get('instrumentos_avaliacao')}.
    Responda em JSON: {"descricao": "...", "atividades": "..."}`;

    try {
        const response = await fetch('gemini_service.php', {
            method: 'POST',
            body: JSON.stringify({
                nome_plano: "Detalhe",
                disciplina: formData.get('disciplina'),
                total_aulas: 1,
                topicos: [card.querySelector('.ai-topico').value],
                habilidades: formData.get('habilidades'),
                instrumentos: formData.get('instrumentos_avaliacao'),
                custom_prompt: prompt
            }),
            headers: { 'Content-Type': 'application/json' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const detalhe = Array.isArray(result.plano_gerado) ? result.plano_gerado[0] : result.plano_gerado;
            card.querySelector('.ai-descricao').value = detalhe.descricao || '';
            card.querySelector('.ai-atividades').value = detalhe.atividades || '';
            card.classList.remove('border-primary');
            card.classList.add('border-success');
        }
    } catch (e) {
        console.error(e);
    } finally {
        btn.innerHTML = '🪄 Refazer';
        btn.disabled = false;
    }
}

function renderAIPlan(plano) {
    const container = document.getElementById('ai-review-container');
    container.innerHTML = plano.map((aula, index) => `
        <div class="card mb-3 ai-aula-card border-success" data-sequencia="${aula.sequencia}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold">Aula ${aula.sequencia}</span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-expand" onclick="expandLesson(${index})">🪄 Refazer Aula</button>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="small fw-bold">Tópico</label>
                    <input type="text" class="form-control ai-topico" value="${aula.topico || ''}">
                </div>
                <div class="mb-0">
                    <label class="small fw-bold">Descrição (Diário de Aula)</label>
                    <textarea class="form-control ai-descricao" rows="3">${aula.descricao || ''}</textarea>
                </div>
                <input type="hidden" class="ai-atividades" value="">
            </div>
        </div>
    `).join('');
}

let rowCount = 0;
function addSequencerRow(topico = '') {
    rowCount++;
    const tbody = document.getElementById('sequencer-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${rowCount}</td>
        <td><input type="text" class="form-control" name="topicos[]" value="${topico}" placeholder="Título da aula"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); reorderRows();">Remover</button></td>
    `;
    tbody.appendChild(tr);
}

function reorderRows() {
    const rows = document.querySelectorAll('#sequencer-body tr');
    let count = 0;
    rows.forEach((row) => {
        count++;
        row.querySelector('td:first-child').innerText = count;
    });
    rowCount = count;
}

async function finishWizard() {
    const form = document.getElementById('wizardForm');
    const formData = new FormData(form);
    const data = {
        step: 4,
        plano_id: currentPlanoId,
        turmas: formData.getAll('turmas[]')
    };

    const response = await fetch('wizard.php', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: { 'Content-Type': 'application/json' }
    });
    const result = await response.json();
    
    if (result.success) {
        alert('Planejamento Mestre criado e atribuído com sucesso!');
        window.location.href = 'admin.php';
    } else {
        alert('Erro ao finalizar: ' + result.error);
    }
}

// Inicializa com uma linha no sequenciador
addSequencerRow();
</script>

</body>
</html>
