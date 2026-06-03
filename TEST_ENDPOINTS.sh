#!/bin/bash

# Endpoints à tester - SSO Unilim

echo "📋 Liste des endpoints à tester"
echo "==============================="
echo ""

echo "## Endpoint 1: Obtenir l'URL de redirection Unilim"
echo "Méthode: GET"
echo "URL: http://localhost:8000/api/auth/unilim-authorize"
echo ""
echo "Commande curl:"
echo 'curl -X GET "http://localhost:8000/api/auth/unilim-authorize"'
echo ""
echo "Réponse attendue:"
echo '{
  "success": true,
  "data": {
    "authorize_url": "https://cas.unilim.fr/authorize?client_id=gupp&response_type=code&...",
    "state": "..."
  }
}'
echo ""
echo ""

echo "## Endpoint 2: Traiter le callback Unilim"
echo "Méthode: POST"
echo "URL: http://localhost:8000/api/auth/unilim-callback"
echo ""
echo "Commande curl (exemple):"
echo 'curl -X POST "http://localhost:8000/api/auth/unilim-callback" \\'
echo '  -H "Content-Type: application/json" \\'
echo '  -d "{\"code\": \"AUTH_CODE_FROM_UNILIM\", \"state\": \"STATE_FROM_UNILIM\"}"'
echo ""
echo "Réponse attendue:"
echo '{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "username": "john.doe",
      "email": "john.doe@unilim.fr",
      "role": "student",
      "parcour": null
    },
    "unilim_payload": {
      "sub": "123456",
      "email": "john.doe@unilim.fr",
      "name": "John Doe",
      "preferred_username": "john.doe",
      "iat": 1234567890,
      "exp": 1234571490
    }
  }
}'
echo ""
echo ""

echo "## Frontend Routes"
echo "===================="
echo ""
echo "Public Route:"
echo "- /cvtek/auth/callback → Page de traitement du callback Unilim"
echo ""
echo "Protected Routes (après authentification):"
echo "- /cvtek/ → Student Dashboard (role: student)"
echo "- /cvtek/professor → Professor Dashboard (role: professor)"
echo "- /cvtek/admin → Admin Dashboard (role: admin)"
echo ""

echo "## Flow d'authentification complet"
echo "====================================="
echo ""
echo "1. Utilisateur accède à: http://localhost:3000/cvtek/"
echo "2. L'app appelle: GET /api/auth/unilim-authorize"
echo "3. L'app redirige vers: https://cas.unilim.fr/authorize?..."
echo "4. Utilisateur se connecte à Unilim"
echo "5. Unilim redirige vers: http://localhost:3000/cvtek/auth/callback?code=X&state=Y"
echo "6. UnilimCallback appelle: POST /api/auth/unilim-callback"
echo "7. Token CVTEK est retourné et sauvegardé"
echo "8. Redirection vers: http://localhost:3000/cvtek/"
echo "9. Utilisateur est connecté ✅"
echo ""

echo "## Notes de test"
echo "================="
echo "- Avoir un compte Unilim valide pour tester"
echo "- Utiliser les URLs de développement local en premier"
echo "- Vérifier la console du navigateur pour les erreurs"
echo "- Vérifier les logs du serveur PHP"
echo "- Utiliser l'onglet Network du DevTools pour voir les requêtes"
echo ""
