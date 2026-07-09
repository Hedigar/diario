document.addEventListener('DOMContentLoaded', () => {
  const apiTokenInput = document.getElementById('apiToken');
  const saveBtn = document.getElementById('saveBtn');
  const status = document.getElementById('status');

  // Carregar token salvo anteriormente
  chrome.storage.local.get(['apiToken'], (result) => {
    if (result.apiToken) {
      apiTokenInput.value = result.apiToken;
    }
  });

  // Salvar novo token
  saveBtn.addEventListener('click', () => {
    const token = apiTokenInput.value.trim();
    
    if (!token) {
      showStatus('Por favor, insira um token.', 'error');
      return;
    }

    chrome.storage.local.set({ apiToken: token }, () => {
      showStatus('Configurações salvas com sucesso!', 'success');
    });
  });

  function showStatus(message, type) {
    status.textContent = message;
    status.className = type;
    setTimeout(() => {
      status.textContent = '';
      status.className = '';
    }, 3000);
  }
});
