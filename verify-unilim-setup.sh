#!/bin/bash

# Script de vérification de la configuration Unilim SSO

echo "🔍 Vérification de la configuration Unilim SSO"
echo "================================================"
echo ""

# Vérifier que le config.php a été modifié
if grep -q "UNILIM_CLIENT_ID" php-api/config.php; then
    echo "✅ Config Unilim trouvée dans php-api/config.php"
else
    echo "❌ Config Unilim manquante dans php-api/config.php"
fi

# Vérifier que AuthController a été modifié
if grep -q "handleCallback" php-api/Controller/AuthController.php; then
    echo "✅ Endpoints Unilim trouvés dans AuthController.php"
else
    echo "❌ Endpoints Unilim manquants dans AuthController.php"
fi

# Vérifier que le client.ts a été modifié
if grep -q "getUnilimAuthorizeUrl" client/src/api/client.ts; then
    echo "✅ Fonctions Unilim trouvées dans client.ts"
else
    echo "❌ Fonctions Unilim manquantes dans client.ts"
fi

# Vérifier que AuthContext a été modifié
if grep -q "loginWithUnilim" client/src/app/context/AuthContext.tsx; then
    echo "✅ Méthode loginWithUnilim trouvée dans AuthContext.tsx"
else
    echo "❌ Méthode loginWithUnilim manquante dans AuthContext.tsx"
fi

# Vérifier que la page callback existe
if [ -f "client/src/app/pages/Callback.tsx" ]; then
    echo "✅ Page Callback créée"
else
    echo "❌ Page Callback manquante"
fi

# Vérifier que la route callback est dans App.tsx
if grep -q "Callback" client/src/app/App.tsx; then
    echo "✅ Route callback Unilim trouvée dans App.tsx"
else
    echo "❌ Route callback Unilim manquante dans App.tsx"
fi

echo ""
echo "🔧 Prochaines étapes:"
echo "1. Récupérer les credentials Unilim (CLIENT_ID, CLIENT_SECRET)"
echo "2. Configurer les variables d'environnement dans php-api/.env"
echo "3. Configurer UNILIM_REDIRECT_URI selon votre environnement"
echo "4. Redémarrer le serveur PHP"
echo "5. Tester le flow d'authentification"
echo ""
echo "📖 Voir UNILIM_SSO_SETUP.md pour plus de détails"
