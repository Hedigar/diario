// ==UserScript==
// @name         Automacao SEDUC - MULTI USUARIO
// @version      3.0
// @description  Automação com suporte a múltiplos professores via Token
// @author       Assistant & Hedigar
// @match        https://professor.escola.rs.gov.br/*
// @grant        GM_xmlhttpRequest
// @connect      localhost
// @run-at       document-start
// ==/UserScript==

(function() {
    'use strict';

    // ==========================================
    // CONFIGURAÇÃO DO PROFESSOR
    // Pegue seu token na aba "Script Token" do painel admin
    const PROFESSOR_TOKEN = 'token_mestre_123'; 
    const API_PORT = '8088'; 
    // ==========================================

    const API_BASE_URL = `http://localhost:${API_PORT}/registro.php`;

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

            if (!data || !turma) {
                alert(`Erro: Data (${data}) ou Turma (${turma}) não encontrada.`);
                return;
            }

            this.innerHTML = '⌛ BUSCANDO...';
            
            GM_xmlhttpRequest({
                method: "GET",
                url: `${API_BASE_URL}?data=${data}&turma=${turma}&token=${PROFESSOR_TOKEN}`,
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

    setInterval(criarBotao, 1000);
})();
