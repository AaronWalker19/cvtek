# 🔧 Explication: Correction du problème Unilim OAuth2

## 📋 Résumé des problèmes et solutions

J'ai corrigé **2 problèmes majeurs** dans l'authentification Unilim:

1. ❌ **Erreur: `invalid_grant`** → ✅ Causée par réutilisation du code OAuth
2. ❌ **Boucle infinie de redirection** → ✅ Causée par absence de session utilisateur
3. ❌ **Erreur: "Email manquant du JWT"** → ✅ Causée par appel manquant à `/userinfo`

---

## 🚨 Problème 1: `invalid_grant` Error

### Qu'est-ce qui s'est passé?

Quand vous cliquiez sur "Se connecter avec Unilim":

```
1. Frontend → "Allez sur: https://cas.unilim.fr/oauth2/authorize?code=..."
2. Vous autorisiez
3. Unilim → "Voici le code: ABC123"
4. Backend appelle: POST /oauth2/token avec code=ABC123
   ❌ ERREUR: invalid_grant
```

### Pourquoi?

Le backend appelait **deux fois** l'API `/oauth2/token` avec le **même code ABC123**:

```php
// Premier appel: OK ✅
POST /oauth2/token?code=ABC123
→ Retour: access_token, id_token

// Deuxième appel: ERREUR ❌
POST /oauth2/token?code=ABC123
→ "invalid_grant" (le code a été utilisé une fois!)
```

**Raison:** C'est une **sécurité OAuth2** - chaque code ne peut être utilisé qu'UNE FOIS.

### ✅ Solution 1: Éviter la réutilisation du code

J'ai ajouté un **fichier de log** pour tracer les codes utilisés:

```php
// api/callback.php - Ligne ~40

$codesLogFile = __DIR__ . '/logs/used-codes.log';

// Hash du code actuel
$codeHash = hash('sha256', $code);

// Lire tous les codes utilisés
$usedCodes = [];
if (file_exists($codesLogFile)) {
    $lines = file($codesLogFile);
    foreach ($lines as $line) {
        [$hash, $timestamp] = explode('|', trim($line));
        
        // Si le code a été utilisé il y a moins de 5 minutes
        if ($hash === $codeHash && time() - (int)$timestamp < 300) {
            die("Ce code a déjà été utilisé!");
        }
    }
}

// Ajouter ce code au log
file_put_contents($codesLogFile, $codeHash . '|' . time() . "\n", FILE_APPEND);
```

**Résultat:** Si callback.php est appelée 2 fois avec le même code, le 2ème appel détecte la réutilisation et s'arrête. ✅

---

## 🔄 Problème 2: Boucle infinie de redirection

### Qu'est-ce qui s'est passé?

Après la correction 1, vous aviez une **boucle infinie**:

```
1. callback.php reçoit le code
2. Échange le code contre le JWT
3. Stocke le JWT en session
4. Redirige vers: /cvtek/?unilim=success&code=ABC123
5. Frontend charge
6. Frontend appelle: GET /api/auth/user
7. ❌ API dit: "Pas d'utilisateur connecté"
8. Frontend redirige vers: /api/auth/unilim-authorize
9. Retour à Unilim (boucle de 3-8)
```

### Pourquoi?

Le callback stockait **seulement le JWT**, pas l'**utilisateur connecté**:

```php
// ❌ INCORRECT
$_SESSION['unilim_jwt'] = $token; // Juste le JWT
// ... pas d'utilisateur

// Plus tard, API /auth/user:
if (!isset($_SESSION['user'])) {
    die("Non authentifié"); // ← Relance le login!
}
```

### ✅ Solution 2: Créer l'utilisateur dans callback.php

J'ai modifié callback.php pour **complèter l'authentification avant de rediriger**:

```php
// ✅ CORRECT
// Étape 1: Extraire username du JWT
$username = $payloadData['sub']; // valin6

// Étape 2: Créer/récupérer l'utilisateur en BD
$auth = new AuthRepository();
$user = $auth->findOrCreateByEmail($email, $username, $role);

// Étape 3: Générer un token APP
$appToken = generateToken($user['id'], $user['username'], $user['role']);

// Étape 4: Stocker en SESSION
$_SESSION['app_token'] = $appToken;
$_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role']
];

// Étape 5: Redirection simple
header("Location: /cvtek/");
exit;
```

**Résultat:** 
- Utilisateur créé en BD ✅
- Session PHP établie ✅
- Redirection simple (pas de `?unilim=success`) ✅
- Frontend appelle `/api/auth/user` → trouve l'utilisateur ✅
- Pas de boucle! ✅

---

## 👤 Problème 3: "Email manquant du JWT"

### Qu'est-ce qui s'est passé?

Quand j'ai inspecté le JWT reçu d'Unilim:

```json
{
    "iat": 1781093987,
    "exp": 1781097587,
    "sub": "valin6",        // ← username uniquement
    "iss": "https://cas.unilim.fr",
    "sid": "OLGHPrcjN+u...",
    "nonce": "98bc893f...",
    // ❌ PAS D'EMAIL !
}
```

Le JWT ne contenait **que le username** (`sub`), pas l'email.

### Pourquoi?

C'est **normal pour OAuth2**:
- Le `id_token` (JWT) contient juste l'**identité de base** (sub)
- L'**email et autres infos** se récupèrent via l'endpoint `/userinfo`

### ✅ Solution 3: Appeler /userinfo

J'ai ajouté un **2ème appel** après le token exchange:

```php
// Étape 1: Échanger le code contre le JWT
// (on reçoit aussi access_token)
POST https://cas.unilim.fr/oauth2/token
    code=ABC123
    client_id=cvtek
    client_secret=...
→ Retour: {
    "id_token": "eyJhbG...",      // JWT
    "access_token": "Bearer xyz...", // ← À utiliser pour /userinfo!
    "token_type": "Bearer"
}

// Étape 2: Utiliser access_token pour appeler /userinfo
GET https://cas.unilim.fr/oauth2/userinfo
    Header: Authorization: Bearer xyz...
→ Retour: {
    "sub": "valin6",
    "email": "valin6@etu.unilim.fr",  // ← Email récupéré!
    "name": "Jean Valin"
}
```

**Code PHP:**

```php
// Récupérer l'access_token du token exchange
$accessToken = $data['access_token'];

// Appeler /userinfo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://cas.unilim.fr/oauth2/userinfo');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($response, true);
$email = $userInfo['email']; // ✅ Email récupéré!
```

**Résultat:** Email disponible, utilisateur créé correctement ✅

---

## 📊 Flux final corrigé

```
┌─────────────────────────────────────────────────────────┐
│ Frontend: Clic "Se connecter avec Unilim"              │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ Redirect vers: https://cas.unilim.fr/oauth2/authorize  │
│   - client_id=cvtek                                     │
│   - redirect_uri=.../auth/callback                      │
│   - state=random                                        │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ Utilisateur: Entre ses identifiants Unilim             │
│ Utilisateur: Autorise l'accès à cvtek                  │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ Unilim redirige vers: .../auth/callback?code=ABC123    │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 1 - Vérifier le code               │
│   ✅ Code non utilisé (check log used-codes.log)        │
│   ✅ Paramètres valides                                 │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 2 - POST /oauth2/token             │
│   POST data: code=ABC123, client_id=cvtek, ...         │
│   Réception: id_token (JWT) + access_token             │
│   ✅ Enregistrer code comme utilisé                      │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 3 - Décoder JWT                    │
│   Récupère: sub (valin6)                               │
│   Récupère: autres claims                              │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 4 - GET /userinfo                  │
│   Header: Authorization: Bearer access_token           │
│   Récupère: email, name, etc.                          │
│   ✅ Email: valin6@etu.unilim.fr                        │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 5 - Créer utilisateur en BD        │
│   AuthRepository::findOrCreateByEmail()                │
│   ✅ Utilisateur créé/récupéré                          │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 6 - Générer token APP              │
│   generateToken($userId, $username, $role)            │
│   ✅ Token JWT créé pour l'app                          │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 7 - Stocker en SESSION             │
│   $_SESSION['app_token'] = ...                         │
│   $_SESSION['user'] = {...}                            │
│   ✅ Session établie                                    │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ callback.php: ÉTAPE 8 - Rediriger                      │
│   header("Location: /cvtek/")                          │
│   ✅ Pas de paramètres (évite confusions)              │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ Frontend: Recharge page /cvtek/                        │
│ Frontend: Appelle GET /api/auth/user                   │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ API: Retourne $_SESSION['user']                        │
│   ✅ Utilisateur trouvé!                               │
│   ✅ Pas de boucle de redirection                      │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ ✅ CONNECTÉ! Dashboard affiché                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🧪 Résumé des changements dans callback.php

| Avant | Après |
|-------|-------|
| ❌ Code pas surveillé | ✅ Vérif code dans `used-codes.log` |
| ❌ JWT seulement stocké | ✅ Utilisateur créé en BD |
| ❌ Redirige `/?unilim=success` | ✅ Redirige `/cvtek/` |
| ❌ Pas de session utilisateur | ✅ `$_SESSION['user']` défini |
| ❌ Email pas récupéré | ✅ Appel `/userinfo` après token |
| ❌ Boucle infinie | ✅ Flux complet en callback |

---

## 📝 Fichiers modifiés

- `api/callback.php` - Ajout de:
  - Code reuse detection (`used-codes.log`)
  - User creation after `/userinfo` call
  - Complete session setup
  - Clean redirect

- `api/callback.php` - Imports ajoutés:
  ```php
  require_once __DIR__ . '/Repository/Repository.php';
  require_once __DIR__ . '/Repository/AuthRepository.php';
  ```

---

## ✅ Vérification

Pour confirmer que tout fonctionne:

```bash
# 1. Regarder les logs
tail -f /var/log/php-errors.log

# 2. Chercher les messages
# ✅ "UNILIM_TOKEN_EXCHANGE_SUCCESS"
# ✅ "UNILIM_USER_SUCCESS"
# ✅ "UNILIM_CALLBACK_COMPLETE"

# 3. Pas de message d'erreur
# ❌ "invalid_grant"
# ❌ "UNILIM_USER_CREATION_FAILED"
# ❌ "Email manquant"
```

---

## 🎯 Conclusion

Les **3 corrections** travaillent ensemble:

1. **Évite l'invalid_grant** → Code peut-il être réutilisé?
2. **Récupère l'email** → Appel `/userinfo` avec le token
3. **Crée la session** → Utilisateur authentifié avant redirection

Résultat: **Flux OAuth2 complet et correct** ✅
