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
        if (cab) {
            const m = cab.innerText.match(/\b\d{3}\b/);
            return m ? m[0] : null;
        }
        return null;
    }

    function extrairDisciplina() {
        const cab = document.querySelector('app-cabecalho-informacoes-turma');
        if (!cab) return null;

        // 1. Tenta encontrar especificamente o span ou div que contém o nome da disciplina
        // Olhando o HTML do Ionic, geralmente o texto está dentro de um elemento próximo ao ícone
        const iconBook = cab.querySelector('ion-icon[name="book"]');
        if (iconBook) {
            // Pegamos o texto do elemento pai, mas de forma mais cuidadosa
            let container = iconBook.closest('ion-item') || iconBook.parentElement;
            let texto = container.innerText.trim();
            
            // Remove o número da turma (ex: 101, 202)
            // Usamos uma regex que busca especificamente 3 dígitos isolados
            texto = texto.replace(/\b\d{3}\b/g, '');
            
            // Remove traços, pontos e espaços extras que sobram
            texto = texto.replace(/^[-\u2013\u2014\s\.]+|[-\u2013\u2014\s\.]+$|[:]/g, '').trim();
            
            console.log("Disciplina detectada via ícone:", texto);
            return texto;
        }

        // 2. Fallback: Se não achou pelo ícone, busca por palavras-chave comuns
        const textoTodo = cab.innerText;
        const matchComp = textoTodo.match(/(?:Componente Curricular|Disciplina):\s*([^-\n\r]+)/i);
        if (matchComp) return matchComp[1].trim();

        return null;
    }

    setInterval(criarBotao, 1000);
})();
