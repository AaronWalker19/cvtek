#!/bin/bash
# Test d'export simple pour diagnostiquer le problème

echo "🔍 Test d'export - Diagnostic"
echo "================================"

# Vérifier si PHP est disponible
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

echo "✅ PHP trouvé"

# Créer un test payload JSON
PAYLOAD='{"student_ids": [1]}'

# Encoder les données pour l'envoi HTTP
echo "📤 Envoi requête d'export pour étudiant ID=1..."
echo "Payload: $PAYLOAD"

# Essai 1: Test local avec PHP
echo ""
echo "Test 1: Vérification locale des données..."
php api/diagnostic-export.php

echo ""
echo "================================"
echo "✅ Diagnostic complété"
