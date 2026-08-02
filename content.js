/**
 * Content Script - Versão Final (Baseada no Tampermonkey v3.7)
 */

console.log('🤖 [SEDUC] Extensão carregada com sucesso!');

/**
 * Funções de Extração Originais do Tampermonkey
 */

function extrairTurma() {
    const cab = document.querySelector('app-cabecalho-informacoes-turma');
    if (!cab) return null;
    let texto = cab.innerText;
    const matchEtapa = texto.match(/(Etapa\s+\d+|Ano\s+\d+)/i);
    if (matchEtapa) return matchEtapa[1].trim();
    const matchDigitos = texto.match(/\b\d{3}\b/);
    return matchDigitos ? matchDigitos[0] : null;
}

function extrairDisciplina() {
    const cab = document.querySelector('app-cabecalho-informacoes-turma');
    if (!cab) return null;

    const iconBook = cab.querySelector('ion-icon[name="book"]');
    if (iconBook) {
        let container = iconBook.closest('ion-col') || iconBook.parentElement;
        let texto = container.innerText.split('\n')[0].trim();
        const turmaCapturada = extrairTurma();
        if (turmaCapturada) {
            texto = texto.replace(turmaCapturada, '');
        }
        return texto.replace(/^[-\s\.]+|[-\s\.]+$|[:]/g, '').trim();
    }
    return null;
}

function extrairData() {
    const cal = document.querySelector('calendario-input');
    if (cal) {
        const m = cal.innerText.trim().match(/(\d{2})\/(\d{2})\/(\d{4})/);
        if (m) return `${m[3]}-${m[2]}-${m[1]}`;
    }
    return null;
}

/**
 * Lógica de Automação
 */

let ultimaChaveAula = null;
let ultimoSlotUsado = 0;

async function buscarEDirecionarAula() {
    const btn = document.getElementById('btn-automacao-fixo');
    const data = extrairData();
    const turma = extrairTurma();
    const disciplina = extrairDisciplina();

    console.log("%c--- DIAGNÓSTICO DE CAPTURA ---", "color: blue; font-size: 14px; font-weight: bold;");
    console.log("📅 Data:", data);
    console.log("👥 Turma:", turma);
    console.log("📚 Disciplina:", disciplina);

    if (!data || !turma || !disciplina) {
        alert(`Dados Faltantes:\n📅 Data: ${data || '?'}\n👥 Turma: ${turma || '?'}\n📚 Disciplina: ${disciplina || '?'}`);
        return;
    }

    const chave = `${data}|||${turma}|||${disciplina}`;
    if (ultimaChaveAula !== chave) {
        ultimaChaveAula = chave;
        ultimoSlotUsado = 0;
    }

    let slot = 1;
    let modoPreenchimento = 'replace';
    if (ultimoSlotUsado === 1) {
        const ok = confirm('Deseja adicionar uma segunda aula aqui?');
        if (!ok) return;
        slot = 2;
        modoPreenchimento = 'append';
    } else if (ultimoSlotUsado >= 2) {
        alert('Você já adicionou uma 2ª aula para este dia. Se quiser refazer, apague o texto e recarregue.');
        return;
    }

    if (btn) btn.innerHTML = '⌛ BUSCANDO...';

    try {
        const response = await chrome.runtime.sendMessage({
            action: 'getDataFromAPI',
            endpoint: '',
            params: {
                data: data,
                turma: turma,
                disciplina: disciplina,
                slot: slot
            }
        });

        if (response.success) {
            const json = response.data;
            if (json.texto) {
                const ok = preencherNoSistema(json.texto, modoPreenchimento);
                if (ok) {
                    ultimoSlotUsado = slot;
                }
                if (btn) {
                    btn.innerHTML = '✅ SUCESSO!';
                    setTimeout(() => { btn.innerHTML = '🚀 CARREGAR DADOS'; }, 2000);
                }
            } else {
                alert("Sua API avisou: " + (json.error || "Sem conteúdo para esta aula."));
                if (btn) btn.innerHTML = '🚀 CARREGAR DADOS';
            }
        } else {
            alert("Erro na comunicação com a extensão: " + response.error);
            if (btn) btn.innerHTML = '🚀 CARREGAR DADOS';
        }
    } catch (err) {
        console.error("Erro fatal:", err);
        if (btn) btn.innerHTML = '🚀 CARREGAR DADOS';
    }
}

function preencherNoSistema(texto, modo = 'replace') {
    const ionTextarea = document.querySelector('ion-textarea[placeholder="Descreva aqui as atividades do dia."]');
    if (ionTextarea) {
        // Lida com o Shadow DOM do Ionic
        const textareaReal = ionTextarea.shadowRoot ? ionTextarea.shadowRoot.querySelector('textarea') : ionTextarea.querySelector('textarea');
        if (textareaReal) {
            const textoNovo = (texto ?? '').toString();
            if (modo === 'append') {
                const atual = (textareaReal.value ?? '').toString();
                const atualTrim = atual.trim();
                const novoTrim = textoNovo.trim();
                if (novoTrim.length === 0) return true;
                if (atual.includes(novoTrim)) return true;
                textareaReal.value = atualTrim.length > 0 ? `${atualTrim}\n\n${novoTrim}` : novoTrim;
            } else {
                textareaReal.value = textoNovo;
            }
            // Dispara todos os eventos que o Ionic/Angular esperam
            textareaReal.dispatchEvent(new Event('input', { bubbles: true }));
            textareaReal.dispatchEvent(new Event('change', { bubbles: true }));
            ionTextarea.dispatchEvent(new Event('ionInput', { bubbles: true }));
            console.log("✅ Texto preenchido no ion-textarea.");
            return true;
        }
    }
    console.warn("❌ Campo ion-textarea não encontrado.");
    return false;
}

function criarBotao() {
    if (document.getElementById('btn-automacao-fixo')) return;
    const btn = document.createElement('button');
    btn.id = 'btn-automacao-fixo';
    btn.innerHTML = '🚀 CARREGAR DADOS';
    btn.style.cssText = `
        position: fixed !important; 
        top: 10px !important; 
        left: 50% !important; 
        transform: translateX(-50%) !important; 
        z-index: 9999999 !important; 
        background: #4f46e5 !important; 
        color: white !important; 
        border: 2px solid white !important; 
        padding: 12px 24px !important; 
        font-size: 16px !important; 
        font-weight: bold !important; 
        border-radius: 50px !important; 
        cursor: pointer !important; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;
    `;
    btn.onclick = buscarEDirecionarAula;
    if (document.body) document.body.appendChild(btn);
}

// Inicialização
setInterval(criarBotao, 1000);
