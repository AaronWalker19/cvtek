import { useSearchParams } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/**
 * AccessDenied - Page d'accès refusé
 * 
 * Affichée quand l'utilisateur n'a pas les droits d'accès
 */
export default function AccessDenied() {
  const [searchParams] = useSearchParams();
  const { logout } = useAuth();
  const reason = searchParams.get('reason') || 'Vous n\'avez pas accès à cette application.';

  const handleLogout = async () => {
    try {
      // Déconnecter l'utilisateur localement
      await logout();
    } catch (err) {
      console.error('Erreur lors de la déconnexion:', err);
    }
    // Rediriger vers Unilim logout
    window.location.href = 'https://cas.unilim.fr/logout?service=' + encodeURIComponent(window.location.origin + '/cvtek');
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-red-50 to-red-100 p-4">
      <div className="bg-white rounded-lg shadow-2xl max-w-md w-full p-8">
        {/* Icône d'erreur */}
        <div className="flex justify-center mb-6">
          <div className="flex items-center justify-center w-16 h-16 rounded-full bg-red-100">
            <svg
              className="w-8 h-8 text-red-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
        </div>

        {/* Titre */}
        <h1 className="text-2xl font-bold text-gray-900 text-center mb-2">
          Accès Refusé
        </h1>

        {/* Message d'erreur */}
        <div className="bg-red-50 border-l-4 border-red-600 p-4 mb-6 rounded">
          <p className="text-red-800 text-sm">
            {reason}
          </p>
        </div>

        {/* Détails */}
        <div className="bg-gray-50 p-4 rounded mb-6">
          <p className="text-gray-700 text-sm mb-3">
            <strong>Informations requises:</strong>
          </p>
          <ul className="text-gray-600 text-sm space-y-2">
            <li>✅ Email: @etu.unilim.fr (accès automatique)</li>
            <li>✅ Email: @unilim.fr (sur inscription préalable)</li>
            <li>❌ Autres domaines: non autorisés</li>
          </ul>
        </div>

        {/* Message d'information */}
        <div className="bg-blue-50 border border-blue-200 p-4 rounded mb-6">
          <p className="text-blue-900 text-sm">
            Si vous êtes professeur et votre email est en @unilim.fr, veuillez contacter l'administrateur du site pour être enregistré.
          </p>
        </div>

        {/* Bouton de retour */}
        <button
          onClick={handleLogout}
          className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200"
        >
          Déconnexion Unilim
        </button>

        {/* Lien d'aide */}
        <p className="text-center text-gray-600 text-xs mt-6">
          Questions? Contactez{' '}
          <a
            href="mailto:support@unilim.fr"
            className="text-blue-600 hover:underline"
          >
            support@unilim.fr
          </a>
        </p>
      </div>
    </div>
  );
}
