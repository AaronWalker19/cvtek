import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { handleUnilimCallback } from '../../api/client';

/**
 * UnilimCallback - Page de redirection Unilim
 * 
 * Cette page traite le callback de Unilim avec les query params:
 * - code: code d'autorisation d'Unilim
 * - state: state pour vérifier la sécurité CSRF
 */
export default function UnilimCallback() {
  const [searchParams] = useSearchParams();
  const [error, setError] = useState<string | null>(null);
  const [accessDenied, setAccessDenied] = useState(false);
  const [denialReason, setDenialReason] = useState<string>('');

  useEffect(() => {
    const processCallback = async () => {
      try {
        // Récupérer les params de la query string
        const code = searchParams.get('code');
        const state = searchParams.get('state');
        const errorParam = searchParams.get('error');
        const errorDescription = searchParams.get('error_description');

        console.log('🔄 Traitement du callback Unilim:', { code, state });

        // Vérifier les erreurs Unilim
        if (errorParam) {
          const msg = `Erreur Unilim: ${errorParam} - ${errorDescription || ''}`;
          console.error('❌', msg);
          setError(msg);
          return;
        }

        // Vérifier les params obligatoires
        if (!code) {
          setError('Code d\'autorisation manquant');
          return;
        }

        if (!state) {
          setError('État de sécurité manquant');
          return;
        }

        // Appeler l'endpoint de callback du backend
        console.log('📤 Envoi du callback au backend...');
        await handleUnilimCallback(code, state);

        console.log('✅ Authentification Unilim réussie');

        // Attendre un moment pour que le token soit bien stocké, puis rediriger
        setTimeout(() => {
          window.location.href = '/cvtek/';
        }, 100);
      } catch (err: any) {
        // Vérifier si c'est une erreur d'accès refusé
        if (err.access_denied) {
          console.log('🚫 Accès refusé:', err.reason);
          setAccessDenied(true);
          setDenialReason(err.reason || err.message);
        } else {
          const errorMsg = err instanceof Error ? err.message : String(err);
          console.error('❌ Erreur traitement callback:', errorMsg);
          setError(errorMsg || 'Erreur lors de l\'authentification Unilim');
        }
      }
    };

    processCallback();
  }, [searchParams]);

  // Si accès refusé, rediriger vers la page d'accès refusé
  if (accessDenied) {
    // Rediriger vers la page AccessDenied
    window.location.href = `/cvtek/access-denied?reason=${encodeURIComponent(denialReason)}`;
    return null;
  }

  // Afficher l'écran de chargement ou d'erreur
  if (error) {
    return (
      <div className="flex items-center justify-center h-screen bg-gray-50">
        <div className="bg-white p-8 rounded-lg shadow-md max-w-md">
          <div className="text-center">
            <div className="inline-flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
              <svg
                className="h-6 w-6 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">Erreur d'authentification</h3>
            <p className="text-gray-600 mb-6">{error}</p>
            <button
              onClick={() => (window.location.href = '/cvtek')}
              className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Retourner à l'accueil
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Écran de chargement
  return (
    <div className="flex items-center justify-center h-screen bg-gray-50">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p className="text-gray-600">Authentification Unilim en cours...</p>
        <p className="text-sm text-gray-500 mt-2">Veuillez patienter</p>
      </div>
    </div>
  );
}
