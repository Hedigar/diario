// ==UserScript==
// @name         Automacao SEDUC - MULTI USUARIO
// @version      3.3
// @description  Automação com suporte a múltiplos professores e disciplinas
// @author       Assistant & Hedigar
// @match        https://professor.escola.rs.gov.br/*
// @grant        GM_xmlhttpRequest
// @connect      api-seduc.myrandainformatica.com.br
// @run-at       document-start
// ==/UserScript==

(function() {
    'use strict';

    // ==========================================
    // CONFIGURAÇÃO DO PROFESSOR
    // Pegue seu token na aba "Script Token" do painel admin
    const PROFESSOR_TOKEN = 'token_mestre_123'; 
    // ==========================================

    const API_BASE_URL = `https://api-seduc.myrandainformatica.com.br/registro.php`;

    function criarBotao() {
        if (document.getElementById('btn-automacao-fixo')) return;

        const btn = document.createElement('button');
        btn.id = 'btn-automacao-fixo';
        btn.innerHTML = '🚀 CARREGAR DADOS';
        btn.style.cssText = `
            position: fixed !important; top: 10px !important; left: 50% !important;
            transform: translateX(-50%) !important; z-index: 9999999 !important;
            background: #4f46e5 !important; color: white !important;
            border: 2px solid white !important; padding: 12px 24px !important;
            font-size: 16px !important; font-weight: bold !important;
            border-radius: 50px !important; cursor: pointer !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;
        `;

        btn.onclick = function() {
            const data = extrairData();
            const turma = extrairTurma();
            const disciplina = extrairDisciplina();

            if (!data || !turma || !disciplina) {
                alert(`Erro ao identificar dados:\nData: ${data || '?'}\nTurma: ${turma || '?'}\nDisciplina: ${disciplina || '?'}`);
                return;
            }

            this.innerHTML = '⌛ BUSCANDO...';

            GM_xmlhttpRequest({
                method: "GET",
                url: `${API_BASE_URL}?data=${data}&turma=${turma}&disciplina=${encodeURIComponent(disciplina)}&token=${PROFESSOR_TOKEN}`,
                onload: (res) => {
                    try {
                        const json = JSON.parse(res.responseText);
                        if (json.texto) {
                            const ionTextarea = document.querySelector('ion-textarea[placeholder="Descreva aqui as atividades do dia."]');
                            if (ionTextarea) {
                                const textareaReal = ionTextarea.shadowRoot ? ionTextarea.shadowRoot.querySelector('textarea') : ionTextarea.querySelector('textarea');
                                if (textareaReal) {
                                    textareaReal.value = json.texto;
                                    textareaReal.dispatchEvent(new Event('input', { bubbles: true }));
                                    textareaReal.dispatchEvent(new Event('change', { bubbles: true }));
                                    ionTextarea.dispatchEvent(new Event('ionInput', { bubbles: true }));
                                    this.innerHTML = '✅ SUCESSO!';
                                }
                            }
                        } else {
                            alert("Erro: " + (json.error || "Sem conteúdo"));
                            this.innerHTML = '🚀 CARREGAR DADOS';
                        }
                    } catch(e) { alert("Erro na API."); this.innerHTML = '🚀 CARREGAR DADOS'; }
                },
                onerror: () => { alert("Erro de conexão."); this.innerHTML = '🚀 CARREGAR DADOS'; }
            });
        };
        document.body.appendChild(btn);
    }

    function extrairData() {
        const cal = document.querySelector('calendario-input');
        if (cal) {
            const m = cal.innerText.trim().match(/(\d{2})\/(\d{2})\/(\d{4})/);
            if (m) return `${m[3]}-${m[2]}-${m[1]}`;
        }
        return null;
    }

    function extrairTurma() {
        const cab = document.querySelector('app-cabecalho-informacoes-turma');
        if (!cab) return null;

        // 1. Tenta encontrar pelo padrão clássico de 3 dígitos (ex: 101)
        const matchDigitos = cab.innerText.match(/\b\d{3}\b/);
        if (matchDigitos) return matchDigitos[0];

        // 2. Se não achou dígitos, busca o texto após o traço final (ex: "Etapa 2")
        // O HTML enviado mostra: "Disciplina - Etapa 2"
        const partes = cab.innerText.split(' - ');
        if (partes.length > 1) {
            const possivelTurma = partes[partes.length - 1].trim();
            // Se o final for algo como "Etapa 2", pegamos ele todo
            if (possivelTurma.length > 0) {
                console.log("Turma detectada via sufixo:", possivelTurma);
                return possivelTurma;
            }
        }

        return null;
    }

    function extrairDisciplina() {
        const cab = document.querySelector('app-cabecalho-informacoes-turma');
        if (!cab) return null;

        const iconBook = cab.querySelector('ion-icon[name="book"]');
        if (iconBook) {
            let container = iconBook.closest('ion-col') || iconBook.parentElement;
            let texto = container.innerText.trim();
            
            // Lógica Inteligente: A disciplina é o que vem ANTES do " - Etapa X" ou da Turma
            // Exemplo: "Cult Dig, com e Multet Mund - Etapa 2"
            const partes = texto.split(' - ');
            if (partes.length > 1) {
                // Remove a última parte (que é a turma) e junta o resto
                partes.pop(); 
                texto = partes.join(' - ').trim();
            } else {
                // Fallback: remove apenas dígitos isolados se não houver o traço
                texto = texto.replace(/\b\d{3}\b/g, '').trim();
            }
            
            // Remove caracteres de pontuação do início/fim
            texto = texto.replace(/^[-\u2013\u2014\s\.]+|[-\u2013\u2014\s\.]+$|[:]/g, '').trim();
            
            console.log("Disciplina detectada:", texto);
            return texto;
        }

        return null;
    }

    setInterval(criarBotao, 1000);
})();
