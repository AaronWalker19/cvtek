/**
 * API Client wrapper
 * Redirige les appels /api/* vers le backend sur localhost:3000
 */

const API_BASE_URL = 'http://localhost:3000';

export async function apiFetch(endpoint: string, options?: RequestInit) {
  const url = `${API_BASE_URL}${endpoint}`;
  
  console.log(`🔗 API Call: ${options?.method || 'GET'} ${url}`);
  
  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options?.headers,
    },
  });

  if (!response.ok) {
    const error = await response.text();
    console.error(`❌ API Error (${response.status}):`, error);
    throw new Error(`API Error: ${response.status} - ${error}`);
  }

  return response;
}
