# ⚡ Quick Start - Unilim SSO

## 🎯 Objectif: Démarrer Unilim SSO en 30 min

### Prérequis
- Credentials Unilim (CLIENT_ID + CLIENT_SECRET)
- Accès base de données MySQL
- PHP + Node.js déjà configurés

---

## 3️⃣ Étapes ESSENTIELLES

### Étape 1: Configuration (2 min)

Créer `php-api/.env`:
```bash
cat > php-api/.env << 'EOF'
UNILIM_CLIENT_ID=votre_client_id_ici
UNILIM_CLIENT_SECRET=votre_client_secret_ici
UNILIM_AUTHORIZE_URL=https://cas.unilim.fr/authorize
UNILIM_TOKEN_URL=https://cas.unilim.fr/token
UNILIM_REDIRECT_URI=http://localhost:3000/cvtek/auth/callback
UNILIM_SCOPE=openid
EOF
```

Redémarrer PHP:
```bash
systemctl restart php-fpm
# OU
sudo systemctl restart php8.2-fpm  # selon version
```

### Étape 2: Ajouter Professeurs (2 min)

```bash
# Créer file: professors.sql
cat > professors.sql << 'EOF'
INSERT INTO users (username, email, role, created_at) VALUES
('jean.martin', 'jean.martin@unilim.fr', 'professor', NOW()),
('marie.dupont', 'marie.dupont@unilim.fr', 'professor', NOW());
EOF

# Exécuter
mysql -u root -p cvtek < professors.sql
```

Vérifier:
```bash
mysql -e "SELECT email, role FROM users WHERE email LIKE '%@unilim.fr%';"
```

### Étape 3: Redémarrer Services (2 min)

```bash
# Frontend
cd client && npm run build

# Backend  
cd php-api && composer install  # si pas fait

# Restart
systemctl restart php-fpm
systemctl restart nginx  # ou Apache selon setup
```

---

## ✅ Test Rapide (5 min)

### Test 1: Vérifier endpoint
```bash
curl http://localhost:8000/api/auth/unilim-authorize

# Résultat attendu:
{
  "authorize_url": "https://cas.unilim.fr/authorize?...",
  "state": "..."
}
```

### Test 2: Vérifier config
```bash
php -r "require 'php-api/config.php'; echo defined('UNILIM_CLIENT_ID') ? 'OK' : 'FAIL';"
```

### Test 3: Interface Web
```bash
# Ouvrir: http://localhost:3000/cvtek/
# Cliquer "Se connecter avec Unilim"
# Test avec:
# - Email: etudiant@etu.unilim.fr (auto-accept)
# - Email: professeur@unilim.fr (check DB)
```

---

## 🐛 Problèmes Courants

### ❌ "CLIENT_SECRET not configured"
```bash
# Solution:
cat php-api/.env | grep UNILIM_CLIENT_SECRET
# Doit retourner une valeur, pas vide

# Si vide: éditer .env directement
nano php-api/.env
# Redémarrer: systemctl restart php-fpm
```

### ❌ "Invalid redirect URI"
```bash
# Vérifier la config:
grep REDIRECT_URI php-api/config.php

# Doit être:
# http://localhost:3000/cvtek/auth/callback (dev)
# https://domaine.com/cvtek/auth/callback (prod)

# Confirmer avec Unilim que le URI est enregistré
```

### ❌ "Professeur non enregistré"
```bash
# Vérifier qu'il existe en base:
mysql -e "SELECT * FROM users WHERE email='prof@unilim.fr';"

# Si vide: l'ajouter:
mysql -e "INSERT INTO users (username, email, role) VALUES ('prof', 'prof@unilim.fr', 'professor');"

# Retry login
```

### ❌ "Domaine non autorisé"  
```bash
# C'est normal pour domains autres que @etu.unilim.fr ou @unilim.fr
# Cas d'usage: @gmail.com → Refusé (as expected)

# Si c'est un @unilim.fr légitime:
# 1. Vérifier l'email dans le JWT
# 2. Vérifier les logs: grep UNILIM_EMAIL_CHECK /var/log/cvtek.log
```

---

## 📊 Vérifier le Tout Fonctionne

```bash
# 1. Backend responding?
curl -s http://localhost:8000/api/auth/unilim-authorize | jq .

# 2. React build OK?
cd client && npm run build 2>&1 | grep -i error

# 3. DB accessible?
mysql -e "SELECT COUNT(*) FROM users;"

# 4. Logs look good?
tail -20 /var/log/cvtek.log | grep -i unilim
```

---

## 🔗 Ressources Rapides

| Besoin | Lien |
|--------|------|
| Vérifier config | `grep UNILIM php-api/config.php` |
| Voir les logs | `tail -f /var/log/cvtek.log \| grep UNILIM` |
| Tester endpoint | `curl http://localhost:8000/api/auth/unilim-authorize` |
| Rebuild frontend | `cd client && npm run build` |
| Redémarrer PHP | `systemctl restart php-fpm` |

---

## 📖 Documentation Complète

Pour infos détaillées, voir:
- **Configuration**: [DEPLOYMENT_TESTING_GUIDE.md](DEPLOYMENT_TESTING_GUIDE.md)
- **Troubleshooting**: [DEPLOYMENT_TESTING_GUIDE.md#-troubleshooting](DEPLOYMENT_TESTING_GUIDE.md#-troubleshooting)
- **Architecture**: [EMAIL_ACCESS_CONTROL.md](EMAIL_ACCESS_CONTROL.md)
- **Index**: [UNILIM_SSO_INDEX.md](UNILIM_SSO_INDEX.md)

---

## ⏱️ Temps Estimé

```
Configuration:     2 min
Ajout professeurs: 2 min  
Redémarrer:        2 min
Tests:             5 min
Debug:             10-15 min (au besoin)

TOTAL:             20-30 min ✅
```

---

## 🚀 Prêt!

Si tout est vert, l'app est:
✅ Connectée à Unilim SSO
✅ Contrôle d'accès actif  
✅ Prête pour tests utilisateurs

**Pour les tests complets**: Voir [DEPLOYMENT_TESTING_GUIDE.md](DEPLOYMENT_TESTING_GUIDE.md)

---

**Questions?** Voir [UNILIM_SSO_INDEX.md](UNILIM_SSO_INDEX.md)
