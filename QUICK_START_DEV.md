# 🚀 CVTEK - Démarrage Rapide

## ⚠️ IMPORTANT - Ordre de lancement CRITIQUE

setupProxy ne fonctionne **QUE SI** le serveur Node est déjà lancé!

## 📋 Étapes de démarrage

### 1️⃣ TERMINAL 1 - Lancer le serveur Node (PORT 5000)
```bash
cd C:\Users\kayri\Desktop\universite\stage\cvtek

# Windows
set NODE_ENV=development
npm start

# Linux/Mac
NODE_ENV=development npm start
```

**ATTENDRE**: Voir ce message
```
✓ Server running on port 5000
🌐 Access: http://localhost:5000
```

### 2️⃣ TERMINAL 2 - Lancer React (PORT 3000) 
**SEULEMENT APRÈS** que le serveur Node soit prêt
```bash
cd C:\Users\kayri\Desktop\universite\stage\cvtek\client
npm start
```

**React devrait afficher**:
```
🔧 ========================================
   SETUP PROXY - DEVELOPMENT MODE
   /api/*      → http://localhost:5000
   /uploads/*  → http://localhost:5000
   ========================================
```

## 🟢 Vérifier que tout fonctionne

### Test 1: Serveur Node
```bash
curl http://localhost:5000/api/health
# Devrait retourner: {"status":"OK",...}
```

### Test 2: setupProxy
```bash
curl http://localhost:3000/api/health
# setupProxy intercepte et redirige vers port 5000
# Devrait retourner du JSON
```

### Test 3: L'application React
- Ouvrir http://localhost:3000
- Se connecter
- Tester l'upload d'un fichier

## ❌ Erreur: "Unexpected token '<'"

```
Error: Unexpected token '<', "<!doctype" is not valid JSON
```

**Cause**: setupProxy ne redirige pas (serveur Node pas lancé)

**Solution**:
1. Arrêter React
2. Lancer le serveur Node en PREMIER
3. Attendre le message "✓ Server running on port 5000"
4. ENSUITE lancer React
5. React affichera les logs setupProxy

## 🔧 Scripts disponibles

```bash
# Lancer tout automatiquement (Windows)
start-all.bat

# Vérifier la configuration
check-env.bat

# Lancer manuellement
npm start                    # Serveur Node (port 5000)
cd client && npm start       # React (port 3000)
```

## 📊 Architecture finale

```
┌────────────────────────────────────────┐
│      Browser http://localhost:3000     │
│           (React App)                  │
└────────────────┬───────────────────────┘
                 │
          ┌──────▼──────┐
          │  setupProxy  │
          │ (intercepts) │
          └──────┬───────┘
                 │
                 ▼
    /api/* → localhost:5000
            (Node API Server)
                 │
                 ▼
        ┌────────────────┐
        │  SQLite DB     │
        │  ./db/cvtek.db │
        └────────────────┘
```

## 🆘 Troubleshooting

Si setupProxy n'affiche pas de logs:
```
1. Arrêter React: Ctrl+C
2. Vérifier: cat client/src/setupProxy.js
3. Vérifier: cat client/.env.local (doit avoir PORT=3000)
4. Supprimer: cd client && rm -rf node_modules
5. Réinstaller: npm install
6. Relancer: npm start
```

Si le serveur Node n'est pas accessible:
```
1. Vérifier le port: netstat -ano | findstr :5000
2. Vérifier .env.development: PORT=5000
3. Vérifier NODE_ENV: echo %NODE_ENV%
4. Relancer avec: set NODE_ENV=development && npm start
```

## ✅ Vous êtes prêt!

- Serveur: http://localhost:5000 ✓
- Client:  http://localhost:3000 ✓
- Proxy:   setupProxy → 5000 ✓

À vos fichiers! 🎉
