import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';

/**
 * Callback - Page de redirection d'Unilim
 * 
 * Reçoit directement les paramètres de Unilim:
 * - code: code d'autorisation d'Unilim
 * - state: state pour CSRF protection (validé en PHP)
 * - error: erreur éventuelle
 */
export default function Callback() {
  const [searchParams] = useSearchParams();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const processCallback = async () => {
      try {
        // Récupérer les params
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
          setLoading(false);
          return;
        }

        // Vérifier les params obligatoires
        if (!code || !state) {
          setError('Code ou state manquant');
          setLoading(false);
          return;
        }

        // TODO: Traiter le callback ici
        // Pour l'instant, afficher le chargement
        console.log('✅ Code et state reçus:', { code, state });
        
        // À faire: appel à l'API backend
        
        setLoading(false);

      } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : String(err);
        console.error('❌ Erreur traitement callback:', errorMsg);
        setError(errorMsg || 'Erreur lors de l\'authentification Unilim');
        setLoading(false);
      }
    };

    processCallback();
  }, [searchParams]);

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
  if (loading) {
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

  return null;
}
