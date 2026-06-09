#!/bin/bash

# Installation rapide du système de montée d'année
# Usage: bash setup-year-advancement.sh

echo "📚 Installation du système de montée d'année universitaire"
echo "=========================================================="

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-cvtek_admin}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="${DB_NAME:-cvtek}"

echo ""
echo -e "${BLUE}1️⃣ Vérification des prérequis${NC}"
echo "=================================="

# Vérifier PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP n'est pas installé${NC}"
    exit 1
fi
echo -e "${GREEN}✓ PHP trouvé: $(php --version | head -n1)${NC}"

# Vérifier MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}⚠️  MySQL CLI n'est pas trouvé, utilisant php à la place${NC}"
else
    echo -e "${GREEN}✓ MySQL trouvé${NC}"
fi

echo ""
echo -e "${BLUE}2️⃣ Ajouter la colonne 'année' à la table users${NC}"
echo "================================================="

# Demander confirmation
read -p "Êtes-vous sûr de vouloir modifier la base de données? (y/n) " -n 1 -r
echo
if ! [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Annulé"
    exit 1
fi

# Exécuter la migration PHP
echo "Exécution de la migration..."
php api/db/add-annee-column.php

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migration effectuée avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors de la migration${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}3️⃣ Vérification des fichiers${NC}"
echo "=============================="

files=(
    "api/Controller/AdminController.php"
    "api/cron/send-year-advancement-reminder.php"
    "client/src/components/AdvanceAcademicYearModal.tsx"
    "client/src/imports/PageAdmin/PageAdmin.tsx"
    "YEAR_ADVANCEMENT_SETUP.md"
)

all_exist=true
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file - NON TROUVÉ${NC}"
        all_exist=false
    fi
done

if ! $all_exist; then
    echo -e "${RED}❌ Certains fichiers sont manquants${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}4️⃣ Installation des dépendances${NC}"
echo "=================================="

# Frontend
echo "Installation des dépendances frontend..."
cd client
npm install 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dépendances frontend installées${NC}"
else
    echo -e "${YELLOW}⚠️  Erreur d'installation frontend (peut être normal)${NC}"
fi
cd ..

echo ""
echo -e "${BLUE}5️⃣ Configuration du .env${NC}"
echo "========================"

if ! grep -q "CRON_SECRET_KEY" api/.env 2>/dev/null; then
    echo "Génération d'une clé secrète pour le cron..."
    CRON_SECRET=$(openssl rand -base64 32 || echo "$(date +%s)random$(shuf -i 1-100000 -n 1)")
    
    echo "" >> api/.env
    echo "# Cron Secret Key" >> api/.env
    echo "CRON_SECRET_KEY=$CRON_SECRET" >> api/.env
    
    echo -e "${GREEN}✓ Clé secrète générée: $CRON_SECRET${NC}"
    echo "   Ajoutée à api/.env"
else
    echo -e "${GREEN}✓ CRON_SECRET_KEY déjà configurée${NC}"
fi

echo ""
echo -e "${BLUE}6️⃣ Configuration du cron (Linux/Unix)${NC}"
echo "======================================"

echo ""
echo -e "${YELLOW}Pour configurer le cron automatique:${NC}"
echo ""
echo "1. Ouvrez crontab:"
echo "   crontab -e"
echo ""
echo "2. Ajoutez cette ligne (pour 8h du 1er août):"
echo "   0 8 1 8 * /usr/bin/php $(pwd)/api/cron/send-year-advancement-reminder.php >> /var/log/cvtek-year.log 2>&1"
echo ""
echo "   Ou adaptez le chemin si nécessaire:"
FULL_PATH="$(pwd)/api/cron/send-year-advancement-reminder.php"
echo "   Chemin complet: $FULL_PATH"
echo ""

# Demander si configurer le cron
read -p "Voulez-vous que je tente de configurer le cron automatiquement? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Créer une entrée cron
    CRON_CMD="0 8 1 8 * /usr/bin/php $FULL_PATH >> /var/log/cvtek-year.log 2>&1"
    
    # Ajouter à crontab (si disponible)
    if command -v crontab &> /dev/null; then
        (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
        echo -e "${GREEN}✓ Entrée cron ajoutée${NC}"
    else
        echo -e "${YELLOW}⚠️  crontab non disponible sur ce système${NC}"
        echo "Vous pouvez le configurer manuellement en utilisant le guide"
    fi
fi

echo ""
echo -e "${BLUE}7️⃣ Build du frontend (optionnel)${NC}"
echo "===================================="

read -p "Voulez-vous builder le frontend React? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    cd client
    npm run build
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Build frontend terminé${NC}"
    else
        echo -e "${RED}❌ Erreur lors du build${NC}"
    fi
    cd ..
fi

echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Installation terminée avec succès!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"

echo ""
echo -e "${YELLOW}📋 Prochaines étapes:${NC}"
echo ""
echo "1. Redémarrer votre serveur API/PHP"
echo "2. Se connecter en tant qu'admin"
echo "3. Le bouton '📅 Montée d'année' doit apparaître dans la barre latérale"
echo ""
echo "Pour la documentation complète:"
echo "  cat YEAR_ADVANCEMENT_SETUP.md"
echo ""
echo "Pour tester:"
echo "  bash test-year-advancement.sh"
echo ""
