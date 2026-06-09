# 📚 CVTEK - Plateforme de Gestion de Documents Pédagogiques

**CVTEK** est une plateforme collaborative de gestion de documents pédagogiques développée pour l'Université de Limoges. Elle permet aux étudiants et professeurs de partager, commenter et organiser les ressources pédagogiques de manière efficace.

---

## 🎯 Fonctionnalités Principales

### 👥 Authentification & Autorisation
- ✅ **Authentification SSO Unilim** - Intégration OAuth2 avec le système d'authentification de l'Université de Limoges
- ✅ **Contrôle d'accès par rôle** - Étudiant, Professeur, Administrateur
- ✅ **Protection CSRF** - Sécurité renforcée avec jetons d'état

### 📄 Gestion de Documents
- ✅ **Upload de fichiers** - Support de multiples formats avec limite de taille
- ✅ **Stockage sécurisé** - Sauvegarde des fichiers en serveur
- ✅ **Téléchargement** - Accès facile aux ressources

### 💬 Collaboration
- ✅ **Système de commentaires** - Discussion sur chaque document
- ✅ **Abonnement aux documents** - Notifications sur les nouvelles ressources
- ✅ **Suivi des mises à jour** - Historique des modifications

### 🛡️ Sécurité
- ✅ **JWT (JSON Web Tokens)** - Authentification stateless
- ✅ **Validation des données** - Protection contre les injections SQL
- ✅ **CORS configuré** - Communication sécurisée entre frontend et backend
- ✅ **Hachage des mots de passe** - Avec bcryptjs

### 📱 Expérience Utilisateur
- ✅ **Design Responsive** - Interface adaptée aux ordinateurs
- ✅ **Blocage Mobile** - Restriction d'accès aux appareils mobiles pour une meilleure expérience
- ✅ **UI Modern** - Utilisation de composants Radix UI et Tailwind CSS

---

## 🛠️ Stack Technologique

### 🎨 Frontend
```
Framework:           React 19.2
Langage:             TypeScript
Routage:             React Router 7.1
Formulaires:         React Hook Form
Validation:          Zod
Styling:             Tailwind CSS
UI Components:       Radix UI (30+ composants)
Éditeur Rich Text:   TipTap
Graphiques:          Recharts
Notifications:       Sonner
Requêtes HTTP:       Fetch API
```

### 🔧 Backend
```
Runtime:             Node.js
Framework:           Express.js 5.2
API Gateway:         Express (REST API)
Authentification:    JWT + OAuth2 (Unilim)
Compression:         Helmet.js (sécurité)
Upload de fichiers:  Multer
Archivage:          Archiver
PDF:                 PDFKit
Email:              Nodemailer
Hachage:            Bcryptjs
Rate Limiting:      Express Rate Limit
Validation:         Express Validator
Sanitisation:       Sanitize HTML
```

### 📊 Base de Données
```
SGBDR:              MySQL (8.0+)
Driver:             MySQL2
ORM/Requêtes:       Requêtes SQL brutes (architecture Repository)
```

### 🔌 APIs & Services Externes
```
SSO Unilim:         OAuth2
Email:              SMTP (Nodemailer)
Archivage:          ZIP (Archiver)
```

### 🧪 Outils de Développement
```
Bundler:            Webpack (via react-scripts)
Testing:            Jest
Build Tool:         React Scripts 5.0
Package Manager:    npm
Linting:            ESLint
```

---

## 📦 Structure du Projet

```
cvtek/
├── 📁 client/                          # Frontend React
│   ├── src/
│   │   ├── app/
│   │   │   ├── App.tsx                 # Composant principal
│   │   │   ├── components/
│   │   │   │   ├── MobileBlocker.tsx   # Blocage des appareils mobiles
│   │   │   │   ├── Navigation.tsx
│   │   │   │   ├── FileUpload.tsx
│   │   │   │   └── ...
│   │   │   ├── context/
│   │   │   │   └── AuthContext.tsx     # Gestion de l'authentification
│   │   │   ├── pages/
│   │   │   │   ├── StudentDashboard.tsx
│   │   │   │   ├── ProfessorDashboard.tsx
│   │   │   │   ├── AdminDashboard.tsx
│   │   │   │   └── Callback.tsx        # Callback OAuth2
│   │   │   └── ...
│   │   ├── api/
│   │   │   └── client.ts               # Configuration Fetch API
│   │   ├── styles/
│   │   ├── constants/
│   │   ├── index.tsx
│   │   └── setupTests.ts
│   ├── public/
│   ├── package.json
│   ├── tsconfig.json
│   └── tailwind.config.js
│
├── 📁 api/                             # Backend Express
│   ├── index.php                       # Point d'entrée API
│   ├── config.php                      # Configuration
│   ├── db.php                          # Connexion MySQL
│   ├── composer.json
│   ├── schema.sql                      # Schéma de la base de données
│   ├── Controller/
│   │   ├── AuthController.php
│   │   ├── DocumentController.php
│   │   ├── CommentController.php
│   │   ├── AbonnementController.php
│   │   ├── AdminController.php
│   │   └── ...
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── DocumentRepository.php
│   │   ├── AuthRepository.php
│   │   └── ...
│   ├── Service/
│   │   └── EmailService.php
│   └── Class/
│       └── HttpRequest.php
│
├── 📁 uploads/                         # Répertoire de stockage des fichiers
│
├── .env                                # Variables d'environnement
├── .htaccess                           # Configuration Apache
├── package.json                        # Dépendances Node.js (root)
├── server.php                          # Serveur dev PHP
├── QUICK_START.md                      # Guide de démarrage rapide
├── README_UNILIM_SSO.md               # Documentation SSO
└── README.md                           # Ce fichier
```

---

## 🚀 Installation & Démarrage

### Prérequis
```
- Node.js 16+ et npm
- PHP 7.4+
- MySQL 8.0+
- Git
```

### 1️⃣ Cloner le Projet
```bash
git clone <url-du-repo> cvtek
cd cvtek
```

### 2️⃣ Configurer les Variables d'Environnement

**Backend (`api/.env`)**
```env
DB_HOST=localhost
DB_USER=cvtek_admin
DB_PASSWORD=votre_mot_de_passe
DB_NAME=cvtek
CORS_ORIGIN=https://mmi.unilim.fr

JWT_SECRET=votre_secret_jwt_complexe
NODE_ENV=production

ADMIN_USERNAME=admin
ADMIN_EMAIL=admin@cvtek.fr
ADMIN_PASSWORD=mot_de_passe_admin

UPLOAD_DIR=../uploads
MAX_UPLOAD_SIZE=52428800

# OAuth2 Unilim
UNILIM_CLIENT_ID=votre_client_id
UNILIM_CLIENT_SECRET=votre_client_secret
UNILIM_AUTHORIZE_URL=https://cas.unilim.fr/oauth2/authorize
UNILIM_TOKEN_URL=https://cas.unilim.fr/oauth2/token
UNILIM_REDIRECT_URI=https://votre-domaine.fr/cvtek/auth/callback
UNILIM_SCOPE=openid profile email
```

**Frontend (`client/.env`)**
```env
REACT_APP_API_URL=https://votre-domaine.fr/cvtek/api
REACT_APP_UNILIM_CLIENT_ID=votre_client_id
```

### 3️⃣ Installer les Dépendances

**Backend Node.js**
```bash
npm install
```

**Frontend React**
```bash
cd client
npm install
cd ..
```

### 4️⃣ Initialiser la Base de Données

```bash
# Créer la base de données
mysql -u root -p < api/schema.sql

# Ou importer via votre client MySQL préféré
```

### 5️⃣ Démarrer l'Application

**Mode Développement - Terminal 1 (Backend)**
```bash
npm start
# Démarre le serveur Express sur http://localhost:3000
```

**Mode Développement - Terminal 2 (Frontend)**
```bash
cd client
npm start
# Démarre React sur http://localhost:3001
```

**Production - Build Frontend**
```bash
cd client
npm run build
# Crée le dossier build/ avec les fichiers optimisés
```

---

## 📚 Architecture

### Architecture en Couches

```
┌─────────────────────────────────────────┐
│           Client Web (React)            │
│  - UI Components (Radix UI)             │
│  - State Management (Context API)       │
│  - Routing (React Router)               │
└──────────────────┬──────────────────────┘
                   │ HTTP/REST
┌──────────────────▼──────────────────────┐
│        Backend API (Express.js)         │
│  - Controllers (Routing)                │
│  - Business Logic (Services)            │
│  - Data Access (Repositories)           │
└──────────────────┬──────────────────────┘
                   │ SQL
┌──────────────────▼──────────────────────┐
│       Database (MySQL)                  │
│  - Users                                │
│  - Documents                            │
│  - Comments                             │
│  - Abonnements                          │
└─────────────────────────────────────────┘
```

### Pattern Repository
L'application utilise le pattern **Repository** pour abstraire la couche d'accès aux données :
- `UserRepository.php` - Gestion des utilisateurs
- `DocumentRepository.php` - Gestion des documents
- `CommentRepository.php` - Gestion des commentaires
- `AuthRepository.php` - Authentification
- `UploadRepository.php` - Gestion des uploads

---

## 🔐 Authentification OAuth2 Unilim

### Flux d'Authentification

```
1. Utilisateur clique "Se connecter"
   └─> Redirection vers https://cas.unilim.fr/oauth2/authorize

2. Unilim redirige vers /cvtek/auth/callback avec code
   └─> Backend reçoit le code

3. Backend échange code contre token Unilim
   └─> Récupération des informations utilisateur

4. Création/Mise à jour utilisateur en base de données
   └─> Génération d'un JWT local

5. Frontend reçoit JWT
   └─> Stockage en localStorage
   └─> Accès aux ressources protégées
```

---

## 📡 Points d'Extrémité API Principaux

### 🔑 Authentification
```
POST   /api/auth/callback         - Callback OAuth2
GET    /api/auth/me              - Récupérer l'utilisateur actuel
POST   /api/auth/logout          - Déconnexion
```

### 📄 Documents
```
GET    /api/documents            - Lister les documents
POST   /api/documents            - Créer un document
GET    /api/documents/:id        - Récupérer un document
PUT    /api/documents/:id        - Mettre à jour un document
DELETE /api/documents/:id        - Supprimer un document
GET    /api/documents/:id/download - Télécharger un fichier
```

### 💬 Commentaires
```
GET    /api/comments/:documentId - Lister les commentaires
POST   /api/comments             - Ajouter un commentaire
DELETE /api/comments/:id         - Supprimer un commentaire
```

### 🔔 Abonnements
```
POST   /api/abonnements          - S'abonner à un document
DELETE /api/abonnements/:id      - Se désabonner
```

### 👥 Admin
```
GET    /api/admin/users          - Lister les utilisateurs
GET    /api/admin/stats          - Statistiques du système
```

---

## 🧪 Tests

### Frontend (Jest)
```bash
cd client
npm test
```

### Tests API avec cURL
```bash
bash TEST_ENDPOINTS.sh
```

---

## 📋 Variables d'Environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `DB_HOST` | Hôte MySQL | `localhost` |
| `DB_USER` | Utilisateur MySQL | `cvtek_admin` |
| `DB_PASSWORD` | Mot de passe MySQL | `secure_password` |
| `DB_NAME` | Nom de la base de données | `cvtek` |
| `JWT_SECRET` | Secret pour signer les JWT | `long_random_string` |
| `CORS_ORIGIN` | Origine CORS autorisée | `https://mmi.unilim.fr` |
| `NODE_ENV` | Environnement | `production` ou `development` |
| `UPLOAD_DIR` | Répertoire des uploads | `../uploads` |
| `MAX_UPLOAD_SIZE` | Taille max d'upload (bytes) | `52428800` (50MB) |
| `UNILIM_CLIENT_ID` | Client ID Unilim OAuth2 | Fourni par Unilim |
| `UNILIM_CLIENT_SECRET` | Secret Unilim OAuth2 | Fourni par Unilim |

---

## 🔧 Commandes Utiles

```bash
# Frontend
cd client
npm start                    # Démarrer en mode dev
npm run build               # Builder pour production
npm test                    # Lancer les tests
npm run eject              # Ejecter la config (irréversible)

# Backend
npm install                 # Installer les dépendances
npm start                   # Démarrer le serveur

# Base de données
npm run setup-db           # Initialiser la BDD
npm run create-admin       # Créer l'admin
npm run check-db           # Vérifier la connexion
```

---

## 📖 Documentation Supplémentaire

- [🚀 QUICK_START.md](./QUICK_START.md) - Guide de démarrage rapide
- [🔐 README_UNILIM_SSO.md](./README_UNILIM_SSO.md) - Documentation OAuth2 Unilim
- [❓ UNILIM_SSO_TROUBLESHOOTING.md](./UNILIM_SSO_TROUBLESHOOTING.md) - Dépannage SSO
- [START_HERE.md](./START_HERE.md) - Point de départ

---

## 🎨 UI/UX Components

L'application utilise une suite complète de composants **Radix UI** :
- Boutons, formulaires, modales
- Dropdowns, popovers, tooltips
- Accordéons, onglets, carrousels
- Sliders, sélecteurs, toggle groups
- Et bien d'autres...

Styling avec **Tailwind CSS** pour un design moderne et responsive.

---

## 🔒 Sécurité

### Mesures de Sécurité Implémentées

✅ **Authentification**
- OAuth2 avec Unilim
- JWT pour les sessions
- Protection CSRF avec state token

✅ **Autorisation**
- Contrôle d'accès basé sur les rôles (RBAC)
- Vérification des permissions par endpoint

✅ **Données**
- Validation avec Express Validator et Zod
- Sanitisation HTML avec sanitize-html
- Hachage des mots de passe avec bcryptjs

✅ **Communication**
- CORS configuré de manière restrictive
- HTTPS en production
- Helmet.js pour les en-têtes de sécurité

✅ **Rate Limiting**
- Express Rate Limit pour prévenir les abus

---

## 📝 Contribution

Pour contribuer au projet :

1. Créer une branche : `git checkout -b feature/ma-fonctionnalite`
2. Committer vos changements : `git commit -am 'Ajouter ma fonctionnalité'`
3. Pousser la branche : `git push origin feature/ma-fonctionnalite`
4. Créer une Pull Request

---

## 📞 Support & Contact

Pour des questions ou signaler des bugs :
- 📧 Email : admin@cvtek.fr
- 🐛 Issues : [GitHub Issues](./issues)

---

## 📄 Licence

Projet développé pour l'Université de Limoges - Tous droits réservés

---

## 🙏 Remerciements

- **Université de Limoges** - Infrastructure et ressources
- **Radix UI** - Composants d'interface
- **Tailwind CSS** - Système de styling
- **React Community** - Outils et librairies
- **Express.js Community** - Framework backend

---

**Dernière mise à jour:** June 2026  
**Version:** 1.0.0  
**Status:** ✅ Production Ready
