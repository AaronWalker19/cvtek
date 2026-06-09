import React, { useState, useEffect } from 'react';

interface MobileBlockerProps {
  children: React.ReactNode;
}

export const MobileBlocker: React.FC<MobileBlockerProps> = ({ children }) => {
  const [isMobile, setIsMobile] = useState<boolean | null>(null);

  useEffect(() => {
    // Détecte si l'appareil est mobile
    const detectMobile = () => {
      const userAgent = navigator.userAgent || navigator.vendor || (window as any).opera;
      
      // Regex pour détecter les appareils mobiles
      const mobileRegex = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobi|mob/i;
      const isMobileDevice = mobileRegex.test(userAgent.toLowerCase());
      
      // Vérifie aussi la taille de l'écran
      const isSmallScreen = window.innerWidth < 768;
      
      setIsMobile(isMobileDevice || isSmallScreen);
    };

    detectMobile();

    // Redétecte lors du redimensionnement
    window.addEventListener('resize', detectMobile);
    return () => window.removeEventListener('resize', detectMobile);
  }, []);

  // Attendre que la détection soit complète
  if (isMobile === null) {
    return null;
  }

  // Si mobile, afficher le message de blocage
  if (isMobile) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <div className="bg-white rounded-lg shadow-2xl p-8 max-w-md w-full mx-4">
          <div className="text-center">
            {/* Icon */}
            <div className="mb-6">
              <svg
                className="w-20 h-20 mx-auto text-indigo-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={1.5}
                  d="M9.75 17L9 20m0 0l-.75 3M9 20H5m4 0h10m0-4V7a2 2 0 00-2-2H7a2 2 0 00-2 2v9m11-11V5a2 2 0 10-4 0v1m-4 0a2 2 0 104 0m0 0V7a2 2 0 10-4 0v4m0 6v3m0 0v.75"
                />
              </svg>
            </div>

            {/* Title */}
            <h1 className="text-3xl font-bold text-gray-900 mb-4">
              Accès ordinateur requis
            </h1>

            {/* Message */}
            <p className="text-gray-600 mb-6 leading-relaxed">
              Cette application est optimisée pour les ordinateurs et tablettes. 
              Veuillez accéder au site depuis un ordinateur pour une meilleure expérience.
            </p>

            {/* Additional Info */}
            <div className="bg-indigo-50 rounded-lg p-4 mb-6">
              <p className="text-sm text-indigo-800">
                💻 Utilisez votre navigateur web sur ordinateur pour continuer
              </p>
            </div>

            {/* Footer */}
            <p className="text-xs text-gray-500">
              Si vous pensez que c'est une erreur, essayez de basculer en mode paysage ou d'accéder depuis un navigateur de bureau.
            </p>
          </div>
        </div>
      </div>
    );
  }

  // Si desktop, afficher le contenu normal
  return <>{children}</>;
};
