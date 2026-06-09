#!/bin/bash

# Script de test pour la montée d'année universitaire
# Usage: bash test-year-advancement.sh

echo "🧪 Test du système de montée d'année universitaire"
echo "=================================================="

# Configuration
API_URL="${API_URL:-http://localhost:8000/api}"
ADMIN_TOKEN="${ADMIN_TOKEN:-}"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les tests
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4

    echo ""
    echo -e "${BLUE}TEST:${NC} $description"
    echo "Endpoint: $method $endpoint"

    if [ -z "$data" ]; then
        response=$(curl -s -X "$method" \
            -H "Authorization: Bearer $ADMIN_TOKEN" \
            -H "Content-Type: application/json" \
            "$API_URL$endpoint")
    else
        response=$(curl -s -X "$method" \
            -H "Authorization: Bearer $ADMIN_TOKEN" \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$API_URL$endpoint")
    fi

    # Vérifier si la réponse contient "success"
    if echo "$response" | grep -q '"success":true'; then
        echo -e "${GREEN}✅ SUCCÈS${NC}"
        echo "$response" | jq '.' 2>/dev/null || echo "$response"
    elif echo "$response" | grep -q '"error"'; then
        echo -e "${RED}❌ ERREUR${NC}"
        echo "$response" | jq '.' 2>/dev/null || echo "$response"
    else
        echo -e "${YELLOW}⚠️  RÉPONSE INCONNUE${NC}"
        echo "$response"
    fi
}

echo ""
echo -e "${YELLOW}1️⃣ Configuration:${NC}"
echo "API URL: $API_URL"
echo "Admin Token: ${ADMIN_TOKEN:0:20}..."

# Test 1: Vérifier la base de données
echo ""
echo -e "${BLUE}════════════════════════════════════════${NC}"
echo -e "${YELLOW}2️⃣ Diagnostic de la base de données:${NC}"
echo -e "${BLUE}════════════════════════════════════════${NC}"

test_endpoint "GET" "/admin/diagnostic" "" "Récupérer les infos de diagnostic"

# Test 2: Vérifier les étudiants
echo ""
echo -e "${BLUE}════════════════════════════════════════${NC}"
echo -e "${YELLOW}3️⃣ Vérifier les étudiants:${NC}"
echo -e "${BLUE}════════════════════════════════════════${NC}"

echo ""
echo "Récupération des étudiants (vous devez query la BD manuellement):"
echo "SELECT id, username, email, année FROM users WHERE role = 'student';"

# Test 3: Simulation de la montée d'année
echo ""
echo -e "${BLUE}════════════════════════════════════════${NC}"
echo -e "${YELLOW}4️⃣ Tester la montée d'année:${NC}"
echo -e "${BLUE}════════════════════════════════════════${NC}"

read -p "Êtes-vous sûr de vouloir tester la montée d'année? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}⚠️  Attention: Cette action modifiera la base de données!${NC}"
    read -p "Êtes-vous vraiment sûr? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        test_endpoint "POST" "/admin/advance-academic-year" "" "Effectuer la montée d'année"
    else
        echo -e "${YELLOW}Test annulé${NC}"
    fi
else
    echo -e "${YELLOW}Test annulé${NC}"
fi

# Test 4: Vérifier les changements
echo ""
echo -e "${BLUE}════════════════════════════════════════${NC}"
echo -e "${YELLOW}5️⃣ Vérifier les résultats:${NC}"
echo -e "${BLUE}════════════════════════════════════════${NC}"

echo ""
echo "Vérifiez manuellement en SQL:"
echo "SELECT id, username, email, année FROM users WHERE role = 'student';"
echo ""
echo "Les changements devraient être visibles:"
echo "- Année 1 → 2"
echo "- Année 2 → 3"
echo "- Année 3 → 4"
echo "- Année 4 → Supprimé"

# Test 5: Tester le cron email
echo ""
echo -e "${BLUE}════════════════════════════════════════${NC}"
echo -e "${YELLOW}6️⃣ Tester l'email de rappel:${NC}"
echo -e "${BLUE}════════════════════════════════════════${NC}"

CRON_SECRET_KEY="${CRON_SECRET_KEY:-}"

if [ -z "$CRON_SECRET_KEY" ]; then
    echo -e "${YELLOW}⚠️  CRON_SECRET_KEY non définie${NC}"
    echo "Pour tester le cron, définissez:"
    echo "export CRON_SECRET_KEY=votre_clé_secrète"
else
    echo ""
    echo -e "${BLUE}Appel du cron avec clé secrète...${NC}"
    
    cron_response=$(curl -s "$API_URL/../cron/send-year-advancement-reminder.php?key=$CRON_SECRET_KEY")
    echo "$cron_response"
fi

echo ""
echo -e "${BLUE}════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Tests terminés!${NC}"
echo -e "${BLUE}════════════════════════════════════════${NC}"
