# 📅 Configuration du Système de Montée d'Année Universitaire

## Vue d'ensemble

Le système de montée d'année permite de:
- ✅ Avancer l'année de tous les étudiants (+1 année)
- 🗑️ Supprimer automatiquement les étudiants en année 4 avec toutes leurs données
- 📧 Envoyer un email de rappel le 1er août à tous les admins

---

## 🚀 Utilisation du Bouton Admin

### Accès
1. Connectez-vous en tant qu'admin
2. Cliquez sur le bouton bleu **"📅 Montée d'année"** dans la barre latérale gauche

### Processus
1. Une modal de confirmation s'affiche avec les détails de l'action
2. Relisez bien le contenu
3. Cliquez sur **"Confirmer l'avancement"** pour procéder

### Résultat
- Les étudiants année 1→2, 2→3, 3→4 sont promus
- Les étudiants année 4 sont supprimés avec:
  - Leur compte utilisateur
  - Tous leurs documents et versions
  - Tous leurs commentaires
  - Les fichiers uploads associés
  - Les enregistrements d'abonnement

---

## 📧 Configuration du Cron Email (1er août)

### Option 1: Via Cron Linux/Unix (Recommandé)

#### Configuration

1. **Éditer la crontab:**
```bash
crontab -e
```

2. **Ajouter cette ligne** (envoie un email à 8h00 le 1er août):
```bash
0 8 1 8 * /usr/bin/php /chemin/vers/api/cron/send-year-advancement-reminder.php
```

**Explication de la ligne cron:**
- `0` = Minute (0)
- `8` = Heure (8h du matin)
- `1` = Jour du mois (1er)
- `8` = Mois (août = mois 8)
- `*` = Jour de la semaine (tous les jours)

#### Vérifier les chemins

```bash
# Trouver le chemin de PHP
which php

# Trouver le chemin du projet
pwd
```

#### Exemple complet
```bash
0 8 1 8 * cd /var/www/cvtek && /usr/bin/php api/cron/send-year-advancement-reminder.php >> /var/log/cvtek-cron.log 2>&1
```

---

### Option 2: Via Variable d'Environnement (Hébergement mutualisé)

Si vous n'avez pas accès à crontab, utilisez une variable d'environnement secrète:

#### Étape 1: Ajouter à `.env`
```env
CRON_SECRET_KEY=votre_clé_secrète_très_complexe_12345
```

#### Étape 2: Accéder via URL
```
https://mmi.unilim.fr/cvtek/api/cron/send-year-advancement-reminder.php?key=votre_clé_secrète_très_complexe_12345
```

#### Étape 3: Configuration via un service externe
Utilisez un service comme **cron-job.org** ou **EasyCron**:
- URL: `https://mmi.unilim.fr/cvtek/api/cron/send-year-advancement-reminder.php?key=VOTRE_CLE`
- Fréquence: 1er août à 8h00
- Timeout: 60 secondes

---

### Option 3: Via PHP dans le Backend

Ajouter cette vérification au **démarrage du serveur API**:

```php
// Dans api/index.php ou au démarrage
if (date('d/m') === '01/08' && !isset($_SESSION['year_reminder_sent'])) {
    require_once __DIR__ . '/cron/send-year-advancement-reminder.php';
    $_SESSION['year_reminder_sent'] = true;
}
```

---

## ⚙️ Configuration de l'Email

### Variables nécessaires dans `.env`

```env
# Service SMTP (si utilisant EmailService)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=votre_email@example.com
SMTP_PASSWORD=votre_mot_de_passe
SMTP_FROM=noreply@cvtek.fr
```

### Ou utiliser Nodemailer (Node.js)

```javascript
// Dans votre serveur Node.js
const nodemailer = require('nodemailer');

const transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: process.env.SMTP_PORT,
    secure: true,
    auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASSWORD
    }
});
```

---

## 📋 Contenu de l'Email

L'email de rappel contient:

✅ **Titre:** "📅 Montée d'année universitaire"

✅ **Contenu:**
- Rappel de la date (1er août)
- Statistiques actuelles (nombre d'étudiants par année)
- Checklist avant de procéder:
  - Vérifier les notes et évaluations
  - Faire une sauvegarde
  - Prévenir les utilisateurs
- Explication du processus
- Lien direct vers l'interface admin
- ⚠️ Avertissement sur l'irréversibilité

✅ **Format:** HTML et texte brut pour compatibilité

---

## 🔍 Vérification & Logs

### Vérifier que le cron s'est exécuté

#### Logs Linux
```bash
# Vérifier les logs de cron
tail -f /var/log/syslog | grep CRON

# Ou avec cron personnalisé
tail -f /var/log/cvtek-cron.log
```

#### Fichier de log PHP
```bash
tail -f /var/log/php_errors.log
```

#### Logs de la base de données
```sql
SELECT * FROM logs WHERE action LIKE '%YEAR_ADVANCEMENT%' ORDER BY created_at DESC LIMIT 10;
```

### Test Manuel

```bash
# Lancer le script directement
php /chemin/vers/api/cron/send-year-advancement-reminder.php

# Ou via curl avec clé secrète
curl "https://mmi.unilim.fr/cvtek/api/cron/send-year-advancement-reminder.php?key=CLE_SECRETE"
```

---

## 🆘 Dépannage

### Le cron ne s'exécute pas

**Vérifier:**
1. ✓ Le chemin PHP est correct: `which php`
2. ✓ Le fichier existe et est exécutable: `ls -la file.php`
3. ✓ Les permissions: `chmod 755 file.php`
4. ✓ La crontab est correcte: `crontab -l`

### L'email ne part pas

**Vérifier:**
1. ✓ Les variables SMTP sont configurées dans `.env`
2. ✓ Le service SMTP est accessible
3. ✓ Les logs PHP pour les erreurs
4. ✓ Le fichier `EmailService.php` existe

### Erreur "Accès refusé"

**Vérifier:**
1. ✓ La clé secrète est correcte
2. ✓ La variable `CRON_SECRET_KEY` est définie dans `.env`
3. ✓ L'URL est correcte

---

## 📊 API de Montée d'Année

### Endpoint
```
POST /api/admin/advance-academic-year
```

### Authentification
Authentification admin requise (JWT)

### Réponse
```json
{
  "success": true,
  "message": "Année scolaire avancée avec succès",
  "data": {
    "promoted": [
      {
        "student_id": 123,
        "email": "etudiant@etu.unilim.fr",
        "from_year": 1,
        "to_year": 2
      }
    ],
    "deleted": [
      {
        "student_id": 456,
        "email": "ancien@etu.unilim.fr",
        "files_deleted": 5,
        "status": "success"
      }
    ],
    "total_processed": 10,
    "errors": []
  }
}
```

---

## 🔒 Sécurité

### Points importants

✅ **Action irréversible:** Les étudiants de l'année 4 sont définitivement supprimés
✅ **Sauvegarde recommandée:** Faire une backup avant
✅ **Accès admin:** Seuls les admins peuvent déclencher
✅ **Logs:** Toutes les actions sont enregistrées
✅ **Clé secrète:** Pour le cron, utiliser une clé complexe

### Contrôles de sécurité

- ✓ Vérification du rôle admin
- ✓ Validation des données
- ✓ Protection CSRF via JWT
- ✓ Logging des actions
- ✓ Gestion des erreurs transactionnelles

---

## 📌 À Savoir

### Ordre de traitement
1. Récupération de tous les étudiants
2. Traitement en ordre décroissant d'année (année 4 en premier)
3. Pour chaque étudiant: promotion ou suppression
4. Suppression des fichiers physiques
5. Logging de chaque action

### Statut des autres rôles
- 👨‍🏫 **Professeurs:** Pas affectés
- 🔐 **Admins:** Pas affectés
- 📚 **Étudiants:** Voir section ci-dessus

### Données supprimées (année 4)
- ✗ Compte utilisateur
- ✗ Documents et métadonnées
- ✗ Versions de documents
- ✗ Fichiers (uploads)
- ✗ Commentaires
- ✗ Abonnements

---

## 📞 Support

Pour des problèmes:
1. Vérifier les logs du serveur
2. Consulter la section Dépannage
3. Contacter l'administrateur système

---

**Dernière mise à jour:** Juin 2026
