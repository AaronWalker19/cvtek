/**
 * API Client wrapper
 * 
 * En développement: appel direct vers localhost:5000
 * En production: utilise des URLs relatives (même serveur)
 */

// Déterminer l'URL de base de l'API
function getApiBaseUrl(): string {
  if (process.env.NODE_ENV === 'production') {
    return 'http://mmi.unilim.fr:3000';
  }

  return 'http://localhost:5000';
}

const API_BASE_URL = getApiBaseUrl();

export async function apiFetch(endpoint: string, options?: RequestInit) {
  // Construire l'URL complète
  const url = API_BASE_URL + endpoint;
  
  console.log(`🔗 API Call: ${options?.method || 'GET'} ${url}`);
  
  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options?.headers,
    },
  });

  if (!response.ok) {
    // Essayer de parser le JSON pour le message d'erreur
    let errorMsg = '';
    try {
      const errorData = await response.json();
      errorMsg = errorData.error || errorData.message || '';
    } catch (e) {
      const text = await response.text();
      // Si la réponse commence par <, c'est probablement du HTML (React ou autre)
      if (text.startsWith('<')) {
        errorMsg = 'Received HTML instead of JSON - setupProxy may not be intercepting requests. Make sure:';
        errorMsg += '\n  1. Node API server is running on port 5000';
        errorMsg += '\n  2. React Dev server is running on port 3000';
        errorMsg += '\n  3. Try: curl http://localhost:5000/api/health';
      } else {
        errorMsg = text;
      }
    }
    
    console.error(`❌ API Error (${response.status}):`, errorMsg);
    throw new Error(`API Error: ${response.status} - ${errorMsg}`);
  }

  return response;
}
