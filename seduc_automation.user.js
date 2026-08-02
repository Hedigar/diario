// ==UserScript==
// @name         Automacao SEDUC - MULTI USUARIO
// @version      3.7
// @description  Diagnóstico avançado e extração precisa de disciplina
// @author       Assistant & Hedigar
// @match        https://professor.escola.rs.gov.br/*
// @grant        GM_xmlhttpRequest
// @connect      api-seduc.myrandainformatica.com.br
// @run-at       document-start
// ==/UserScript==

(function() {
    'use strict';

    const PROFESSOR_TOKEN = 'token_mestre_123'; 
    const API_BASE_URL = `https://api-seduc.myrandainformatica.com.br/registro.php`;
    let ultimaChaveAula = null;
    let ultimoSlotUsado = 0;

    function criarBotao() {
        if (document.getElementById('btn-automacao-fixo')) return;
        const btn = document.createElement('button');
        btn.id = 'btn-automacao-fixo';
        btn.innerHTML = '🚀 CARREGAR DADOS';
        btn.style.cssText = `position: fixed !important; top: 10px !important; left: 50% !important; transform: translateX(-50%) !important; z-index: 9999999 !important; background: #4f46e5 !important; color: white !important; border: 2px solid white !important; padding: 12px 24px !important; font-size: 16px !important; font-weight: bold !important; border-radius: 50px !important; cursor: pointer !important; box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;`;
        btn.onclick = carregarDados;
        document.body.appendChild(btn);
    }

    function carregarDados() {
        const data = extrairData();
        const turma = extrairTurma();
        const disciplina = extrairDisciplina();

        // LOG DE DIAGNÓSTICO - Isso vai nos dizer o que está acontecendo
        console.log("%c--- DIAGNÓSTICO DE CAPTURA ---", "color: blue; font-size: 14px; font-weight: bold;");
        console.log("%c📅 Data capturada: " + data, "color: black;");
        console.log("%c👥 Turma capturada: " + turma, "color: black;");
        console.log("%c📚 Disciplina capturada: " + disciplina, "color: black;");
        console.log("%c------------------------------", "color: blue; font-size: 14px; font-weight: bold;");

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

        this.innerHTML = '⌛ BUSCANDO...';
        GM_xmlhttpRequest({
            method: "GET",
            url: `${API_BASE_URL}?data=${data}&turma=${encodeURIComponent(turma)}&disciplina=${encodeURIComponent(disciplina)}&slot=${slot}&token=${PROFESSOR_TOKEN}`,
            onload: (res) => {
                try {
                    const json = JSON.parse(res.responseText);
                    if (json.texto) {
                        const ionTextarea = document.querySelector('ion-textarea[placeholder="Descreva aqui as atividades do dia."]');
                        if (ionTextarea) {
                            const textareaReal = ionTextarea.shadowRoot ? ionTextarea.shadowRoot.querySelector('textarea') : ionTextarea.querySelector('textarea');
                            if (textareaReal) {
                                const textoNovo = (json.texto ?? '').toString();
                                if (modoPreenchimento === 'append') {
                                    const atual = (textareaReal.value ?? '').toString();
                                    const atualTrim = atual.trim();
                                    const novoTrim = textoNovo.trim();
                                    if (novoTrim.length > 0 && !atual.includes(novoTrim)) {
                                        textareaReal.value = atualTrim.length > 0 ? `${atualTrim}\n\n${novoTrim}` : novoTrim;
                                    }
                                } else {
                                    textareaReal.value = textoNovo;
                                }
                                textareaReal.dispatchEvent(new Event('input', { bubbles: true }));
                                textareaReal.dispatchEvent(new Event('change', { bubbles: true }));
                                ionTextarea.dispatchEvent(new Event('ionInput', { bubbles: true }));
                                ultimoSlotUsado = slot;
                                this.innerHTML = '✅ SUCESSO!';
                                setTimeout(() => { this.innerHTML = '🚀 CARREGAR DADOS'; }, 2000);
                                return;
                            }
                        }
                    }
                    alert("Erro API: " + (json.error || "Sem conteúdo"));
                    this.innerHTML = '🚀 CARREGAR DADOS';
                } catch(e) { 
                    console.error("Erro na resposta:", res.responseText);
                    alert("Erro na resposta da API."); 
                    this.innerHTML = '🚀 CARREGAR DADOS'; 
                }
            }
        });
    }

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

        // Busca o elemento que contém o ícone de livro
        const iconBook = cab.querySelector('ion-icon[name="book"]');
        if (iconBook) {
            // No Ionic, o texto da disciplina geralmente está no mesmo nível ou no pai do ícone
            let container = iconBook.closest('ion-col') || iconBook.parentElement;
            
            // Pegamos apenas o texto direto desse container, tentando ignorar sub-elementos se houver
            let texto = container.innerText.split('\n')[0].trim(); 
            
            // Remove a turma (Etapa X ou 101) do nome da disciplina se ela estiver grudada
            const turmaCapturada = extrairTurma();
            if (turmaCapturada) {
                texto = texto.replace(turmaCapturada, '');
            }

            // Limpeza final de traços e espaços
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

    setInterval(criarBotao, 1000);
})();
