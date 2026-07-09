import { fetchFromAPI } from './api.js';

/**
 * Background Service Worker
 * Gerencia a comunicação entre o content script e a API PHP
 */

chrome.runtime.onInstalled.addListener(() => {
  console.log('Extensão SEDUC RS instalada com sucesso.');
});

// Listener para mensagens vindas do content.js ou popup.js
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'getDataFromAPI') {
    handleAPICall(request.endpoint, 'GET', null, request.params, sendResponse);
    return true; // Mantém o canal aberto para resposta assíncrona
  }

  if (request.action === 'postDataToAPI') {
    handleAPICall(request.endpoint, 'POST', request.data, request.params, sendResponse);
    return true;
  }
});

/**
 * Helper para lidar com chamadas de API e retornar resposta para o chamador
 */
async function handleAPICall(endpoint, method, data, params, sendResponse) {
  try {
    const result = await fetchFromAPI(endpoint, method, data, params);
    sendResponse({ success: true, data: result });
  } catch (error) {
    sendResponse({ success: false, error: error.message });
  }
}
