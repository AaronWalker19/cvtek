#!/bin/bash
# 🔍 Script de vérification - Nouveau système d'invitation par email

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   Vérification du système d'invitation par email               ║"
echo "║   CVTEK - Gestion des membres                               ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
CHECKS_PASSED=0
CHECKS_FAILED=0

# Fonction pour vérifier les fichiers
check_file() {
  local file=$1
  local description=$2
  
  if [ -f "$file" ]; then
    echo -e "${GREEN}✓${NC} $description"
    echo "  📍 $file"
    ((CHECKS_PASSED++))
  else
    echo -e "${RED}✗${NC} $description"
    echo "  📍 $file (MANQUANT)"
    ((CHECKS_FAILED++))
  fi
}

# Fonction pour vérifier les patterns dans un fichier
check_pattern() {
  local file=$1
  local pattern=$2
  local description=$3
  
  if grep -q "$pattern" "$file" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} $description"
    ((CHECKS_PASSED++))
  else
    echo -e "${RED}✗${NC} $description"
    ((CHECKS_FAILED++))
  fi
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📁 Fichiers critiques"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_file "db/schema.sql" "Schéma BD modifié (colonnes users)"
check_file "db/migrate-add-user-activation.sql" "Migration BD créée"
check_file "routes/auth.js" "Routes auth.js modifié"
check_file "services/emailService.js" "Service d'email créé"
check_file "client/src/app/pages/BackMemberPage.tsx" "Page BackMemberPage modifiée"
check_file "client/src/app/pages/ActivateAccountPage.tsx" "Page ActivateAccountPage créée"
check_file "client/src/app/routes.tsx" "Routes.tsx modifié"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 Documentation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_file "NEW_MEMBER_INVITATION_SYSTEM.md" "Documentation du système créée"
check_file "INSTALLATION_CHANGES.md" "Guide d'installation créé"
check_file ".env.email.example" "Exemple de configuration email créé"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Contenu des fichiers clés"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_pattern "db/schema.sql" "full_name VARCHAR" "Schema: Colonne full_name ajoutée"
check_pattern "db/schema.sql" "is_active BOOLEAN" "Schema: Colonne is_active ajoutée"
check_pattern "db/schema.sql" "activated_at TIMESTAMP" "Schema: Colonne activated_at ajoutée"
check_pattern "db/schema.sql" "user_invitations" "Schema: Table user_invitations créée"

check_pattern "routes/auth.js" "generateInvitationToken" "Route: Fonction generateInvitationToken"
check_pattern "routes/auth.js" "sendInvitationEmail" "Route: Fonction sendInvitationEmail"
check_pattern "routes/auth.js" "POST.*invite" "Route: POST /api/auth/invite créée"
check_pattern "routes/auth.js" "GET.*invite.*token" "Route: GET /api/auth/invite/:token créée"
check_pattern "routes/auth.js" "POST.*activate" "Route: POST /api/auth/activate créée"

check_pattern "client/src/app/pages/ActivateAccountPage.tsx" "useState" "Frontend: État React géré"
check_pattern "client/src/app/pages/ActivateAccountPage.tsx" "verifyToken" "Frontend: Vérification du token"
check_pattern "client/src/app/pages/ActivateAccountPage.tsx" "handleSubmit" "Frontend: Gestion de la soumission"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 Sécurité"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_pattern "routes/auth.js" "crypto.randomBytes" "Token: Génération cryptographique sécurisée"
check_pattern "routes/auth.js" "hashToken" "Token: Hash SHA256 du token"
check_pattern "routes/auth.js" "is_active" "Auth: Vérification is_active à la connexion"
check_pattern "routes/auth.js" "bcrypt.hash" "Password: Hash bcrypt du mot de passe"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Résumé"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

TOTAL=$((CHECKS_PASSED + CHECKS_FAILED))

echo "Vérifications passées: ${GREEN}${CHECKS_PASSED}${NC}"
echo "Vérifications échouées: ${RED}${CHECKS_FAILED}${NC}"
echo "Total: $TOTAL"

echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
  echo -e "${GREEN}✓ Tous les fichiers et vérifications sont en place!${NC}"
  echo ""
  echo "Prochaines étapes:"
  echo "1. ✅ Exécuter la migration BD:"
  echo "   mysql -h localhost -u root cvtek < db/migrate-add-user-activation.sql"
  echo ""
  echo "2. ✅ Configurer les emails (optionnel):"
  echo "   Copier .env.email.example dans .env et remplir les valeurs"
  echo ""
  echo "3. ✅ Redémarrer le serveur:"
  echo "   node server.js"
  echo ""
  echo "4. ✅ Tester le système:"
  echo "   - Aller à /backoffice/membres"
  echo "   - Inviter un nouveau membre par email"
  echo "   - Utiliser le lien d'activation pour créer le compte"
  echo ""
  exit 0
else
  echo -e "${RED}✗ Certains fichiers ou vérifications sont manquants${NC}"
  echo ""
  echo "Veuillez vérifier:"
  echo "- Que tous les fichiers ont été créés/modifiés"
  echo "- Que les modifications sont correctes"
  echo ""
  exit 1
fi
