/**
 * Módulo de API para comunicação com o backend PHP
 */
export const API_BASE_URL = 'https://api-seduc.myrandainformatica.com.br/registro.php'; // Alterar para sua URL real

export async function fetchFromAPI(endpoint, method = 'GET', data = null, params = {}) {
  const { apiToken } = await chrome.storage.local.get(['apiToken']);

  if (!apiToken) {
    throw new Error('Token de API não configurado.');
  }

  // Adiciona o token aos parâmetros se a API esperar como parâmetro
  const queryParams = new URLSearchParams({ ...params, token: apiToken });
  
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json'
      // Removido o Authorization Header se a API preferir o token via parâmetro
      // 'Authorization': `Bearer ${apiToken}` 
    }
  };

  if (data && (method === 'POST' || method === 'PUT')) {
    options.body = JSON.stringify(data);
  }

  try {
    // Monta a URL com os Query Params
    const baseUrl = endpoint ? `${API_BASE_URL}/${endpoint}` : API_BASE_URL;
    const url = `${baseUrl}?${queryParams.toString()}`;
    
    console.log('Chamando URL:', url);
    const response = await fetch(url, options);
    if (!response.ok) {
      throw new Error(`Erro na API: ${response.statusText}`);
    }
    return await response.json();
  } catch (error) {
    console.error('Erro na chamada de API:', error);
    throw error;
  }
}
