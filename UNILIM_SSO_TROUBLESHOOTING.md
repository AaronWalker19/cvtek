# 🔧 Guide de Dépannage Unilim SSO - Erreur 400

**État:** Connexion au serveur Unilim (https://cas.unilim.fr/oauth2/token) retourne **HTTP 400 Bad Request**

---

## 🎯 Diagnostic Rapide

### ✅ Qu'est-ce qui fonctionne:
- La connexion au serveur Unilim est possible (headers HTTP reçus)
- Le serveur Unilim répond (pas de timeout)
- CORS acceptés

### ❌ Qu'est-ce qui ne fonctionne pas:
- Serveur Unilim refuse la requête (400 Bad Request)
- Probablement un problème de paramètres ou credentials

---

## 🔍 Causes Probables (Par Ordre de Probabilité)

### 1️⃣ **CLIENT_SECRET manquant ou incorrect** (90% de probabilité)

**Symptôme:** Erreur `invalid_client` ou juste `400 Bad Request`

**Vérification:**
```bash
# Vérifier que .env contient:
cat api/.env | grep UNILIM_CLIENT_SECRET
```

**Solution:**
```env
# api/.env
UNILIM_CLIENT_ID=gupp
UNILIM_CLIENT_SECRET=votre_secret_exact_ici
UNILIM_AUTHORIZE_URL=https://cas.unilim.fr/oauth2/authorize
UNILIM_TOKEN_URL=https://cas.unilim.fr/oauth2/token
UNILIM_REDIRECT_URI=https://mmi.unilim.fr/cvtek/auth/callback
```

**⚠️ Points Importants:**
- Pas d'espaces avant/après le secret
- Secret doit être le **exact** fourni par Unilim
- Après modification, **redémarrer le serveur PHP**

---

### 2️⃣ **REDIRECT_URI ne correspond pas**

**Symptôme:** Erreur `invalid_grant` ou `redirect_uri_mismatch`

**Vérification:**
1. Aller sur le portail d'administration Unilim
2. Vérifier la REDIRECT_URI enregistrée
3. Comparer avec `UNILIM_REDIRECT_URI` dans config.php

**Points à Vérifier:**
- ✓ Protocole: `http://` ou `https://`?
- ✓ Domaine exact
- ✓ Port (3000, 8000, 80, 443)?
- ✓ Chemin exact: `/cvtek/auth/callback`

**Exemple:**
```
Enregistré chez Unilim: https://mmi.unilim.fr/cvtek/auth/callback
Config actuelle:        http://localhost:3000/cvtek/auth/callback
❌ Ne correspond pas!
```

---

### 3️⃣ **CLIENT_ID incorrect**

**Symptôme:** Erreur `invalid_client`

**Vérification:**
```php
// api/test-unilim.php ou api/test-unilim-exchange.php
// Affiche CLIENT_ID utilisé
```

**Solution:**
- Vérifier auprès de l'administrateur Unilim
- Confirmer que c'est bien "gupp" ou une autre valeur

---

### 4️⃣ **allow_url_fopen désactivé**

**Symptôme:** Pas de réponse du serveur, erreur `Cannot open stream`

**Vérification:**
```bash
php -i | grep allow_url_fopen
```

Ou: Aller sur `http://localhost:3000/cvtek/api/test-unilim.php` 
→ Section "allow_url_fopen" affiche `Désactivé`

**Solution:**

#### Sous Linux/Apache:
```bash
# Éditer php.ini
sudo nano /etc/php/8.1/apache2/php.ini

# Chercher et changer:
allow_url_fopen = On

# Redémarrer Apache:
sudo systemctl restart apache2
```

#### Sous Windows/IIS:
```
C:\Program Files\PHP\php.ini
Changer: allow_url_fopen = On
Redémarrer IIS: iisreset
```

#### Développement local (PHP intégré):
```bash
php -d allow_url_fopen=On -S localhost:8000
```

---

### 5️⃣ **Certificat SSL invalide**

**Symptôme:** Erreur `SSL certificate problem`, `certificate verify failed`

**Causes:**
- Certificat auto-signé de Unilim
- Certificat expiré
- Certificats CA du système pas à jour

**Solution:**

#### Option 1: Mettre à jour les certificats CA (RECOMMANDÉ)
```bash
# Sous Debian/Ubuntu:
sudo apt-get update && sudo apt-get install ca-certificates

# Sous Red Hat/CentOS:
sudo yum update ca-certificates

# Sous macOS:
# Exécuter: Install Certificates.command
```

#### Option 2: Vérifier le certificat de Unilim (diagnostic)
```bash
# Tester la connexion SSL:
openssl s_client -connect cas.unilim.fr:443

# Ou avec curl:
curl -v https://cas.unilim.fr/oauth2/token
```

#### Option 3: Configurer PHP pour les certificats (développement seulement)
```php
$context = stream_context_create([
    'ssl' => [
        'verify_peer'       => false,  // ⚠️ NON sécurisé!
        'verify_peer_name'  => false,  // ⚠️ À éviter en production!
    ]
]);
```

---

### 6️⃣ **Firewall ou réseau bloque la connexion**

**Symptôme:** Timeout, "Connection refused", "Network unreachable"

**Vérification:**
```bash
# Tester la connectivité:
ping cas.unilim.fr

# Tester le port 443:
telnet cas.unilim.fr 443

# Ou avec nc:
nc -zv cas.unilim.fr 443
```

**Solutions:**
- Vérifier auprès de l'administrateur réseau
- Vérifier les règles de firewall
- Si sur VPN, vérifier que c'est connecté
- Si derrière un proxy, configurer PHP pour l'utiliser

---

## 🚀 Outils de Diagnostic

### 1. Page de Test Simple
```
http://localhost:3000/cvtek/api/test-unilim.php
```
Affiche la configuration et teste les extensions PHP.

### 2. Page de Diagnostic Avancé
```
http://localhost:3000/cvtek/api/diagnostic-unilim-advanced.php
```
Diagnostic complet:
- Configuration
- Extensions PHP et SSL
- Connexion réseau
- Paramètres de requête
- Causes et solutions

### 3. Test Interactif d'Échange de Code
```
http://localhost:3000/cvtek/api/test-unilim-exchange.php
```
Permet de:
- Voir la configuration actuelle
- Tester l'échange de code réel
- Voir exactement ce qui est envoyé
- Voir la réponse complète du serveur
- Obtenir des conseils spécifiques selon l'erreur

---

## 📋 Checklist de Dépannage

**Étape 1: Configuration**
- [ ] Vérifier que `api/.env` existe et contient `UNILIM_CLIENT_SECRET`
- [ ] Vérifier que `CLIENT_SECRET` n'est pas vide
- [ ] Redémarrer le serveur PHP après modification

**Étape 2: Extensions PHP**
- [ ] Vérifier `allow_url_fopen = On` (aller sur test-unilim.php)
- [ ] Vérifier que OpenSSL est disponible
- [ ] Vérifier que certificats CA sont à jour

**Étape 3: Certificats SSL**
- [ ] Tester: `curl -v https://cas.unilim.fr/oauth2/token`
- [ ] Si erreur SSL, mettre à jour certificats CA

**Étape 4: Réseau**
- [ ] Tester: `ping cas.unilim.fr` (doit répondre)
- [ ] Tester: `telnet cas.unilim.fr 443` (doit se connecter)
- [ ] Si timeout, vérifier firewall

**Étape 5: Paramètres**
- [ ] Vérifier CLIENT_ID auprès de Unilim (actuellement: "gupp")
- [ ] Vérifier REDIRECT_URI enregistrée chez Unilim
- [ ] Vérifier que REDIRECT_URI dans config correspond exactement

**Étape 6: Test Interactive**
- [ ] Aller sur `test-unilim-exchange.php`
- [ ] Regarder les paramètres envoyés
- [ ] Regarder la réponse du serveur
- [ ] Lire les conseils spécifiques

---

## 🔍 Commandes de Diagnostic Utiles

### Tester la Configuration PHP
```bash
# Voir tous les paramètres PHP:
php -i

# Voir les extensions chargées:
php -m

# Tester allow_url_fopen:
php -r "echo ini_get('allow_url_fopen') ? 'ON' : 'OFF';"
```

### Tester la Connexion Réseau
```bash
# Résolution DNS:
nslookup cas.unilim.fr
dig cas.unilim.fr

# Ping:
ping cas.unilim.fr

# Port HTTP/HTTPS:
telnet cas.unilim.fr 443
curl -I https://cas.unilim.fr
```

### Tester Unilim Directement
```bash
# Avec curl (montre tous les détails):
curl -v https://cas.unilim.fr/oauth2/token \
  -d "grant_type=authorization_code" \
  -d "code=TEST_CODE" \
  -d "client_id=gupp" \
  -d "client_secret=YOUR_SECRET_HERE" \
  -d "redirect_uri=YOUR_REDIRECT_URI_HERE"

# Avec wget:
wget --verbose https://cas.unilim.fr/oauth2/token
```

### Vérifier le Certificat SSL
```bash
# Voir le certificat:
openssl s_client -connect cas.unilim.fr:443

# Vérifier l'expiration:
openssl s_client -connect cas.unilim.fr:443 -showcerts | grep -A5 "notAfter"

# Tester la validation:
curl --cacert /etc/ssl/certs/ca-certificates.crt https://cas.unilim.fr
```

---

## 📞 Quand Contacter le Support Unilim

Préparez ces informations:
1. Screenshot de `diagnostic-unilim-advanced.php`
2. Screenshot de `test-unilim-exchange.php` (avec la réponse du serveur)
3. CLIENT_ID utilisé
4. REDIRECT_URI configurée
5. Exact error message reçu
6. Résultat de `curl -v https://cas.unilim.fr/oauth2/token`

---

## 📚 Ressources

- [Documentation OAuth2 Unilim](https://cas.unilim.fr/oauth2/)
- [RFC 6749 - OAuth 2.0](https://tools.ietf.org/html/rfc6749)
- [PHP stream_context_create()](https://www.php.net/manual/en/function.stream-context-create.php)
- [cURL Documentation](https://curl.se/docs/)

---

## 💡 Prochaines Étapes

1. **Accéder aux pages de diagnostic:**
   - http://localhost:3000/cvtek/api/diagnostic-unilim-advanced.php
   - http://localhost:3000/cvtek/api/test-unilim-exchange.php

2. **Suivre la checklist de dépannage ci-dessus**

3. **Si vous arrivez à un message d'erreur spécifique (invalid_client, invalid_grant, etc.),** voir les sections ci-dessus pour la solution

4. **Si tout semble OK mais l'erreur 400 persiste,** contacter le support Unilim avec les infos au-dessus

---

Dernière mise à jour: 8 juin 2026
