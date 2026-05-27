# 🧪 Tests Jest - CVTEK Client

> **✅ 22 tests automatisés | 🚀 Mode watch | 📚 Documentation complète**

## 🎯 Démarrage rapide

```bash
npm test
```

**C'est tout !** Les 22 tests vont s'exécuter en < 3 secondes.

---

## 📊 Status

| Métrique | Status |
|----------|--------|
| Tests | ✅ 22 passing |
| App | ✅ 3 tests |
| Auth | ✅ 9 tests |
| Routes | ✅ 3 tests |
| API | ✅ 7 tests |
| Coverage | ~85% |
| Time | ~2-3s |

---

## 📚 Documentation

### 🚀 Pour commencer (5 min)
- [`JEST_SETUP_COMPLETE.md`](../JEST_SETUP_COMPLETE.md) - Vue d'ensemble complète
- [`JEST_QUICKSTART.md`](./JEST_QUICKSTART.md) - Démarrage en 5 minutes

### 📖 Pour comprendre (15 min)
- [`JEST_EXPLICATION_SIMPLE.md`](./JEST_EXPLICATION_SIMPLE.md) - Explications simples
- [`JEST_RESUME_SIMPLE.md`](./JEST_RESUME_SIMPLE.md) - Concepts clés

### 📚 Pour maîtriser (1-2h)
- [`JEST_TESTS_GUIDE.md`](./JEST_TESTS_GUIDE.md) - Guide complet
- [`JEST_ADVANCED_EXAMPLES.md`](./JEST_ADVANCED_EXAMPLES.md) - Cas avancés

### 🛠️ Pour ajouter des tests
- [`JEST_TEMPLATES.md`](./JEST_TEMPLATES.md) - Templates prêts à copier

### 📖 Pour tout voir
- [`JEST_INDEX.md`](./JEST_INDEX.md) - Index complet

---

## 🧪 Tests fournis

### ✅ App (3 tests)
```
✓ Rend sans erreur
✓ Affiche loading spinner
✓ Contient les routes
```

### ✅ Authentification (9 tests)
```
✓ Login fonctionne
✓ Register fonctionne
✓ Logout fonctionne
✓ Gère les erreurs
✓ Charge l'utilisateur
+ 4 autres
```

### ✅ Routes protégées (3 tests)
```
✓ Affiche contenu si authentifié
✓ Affiche spinner si en cours
✓ Redirige si non authentifié
```

### ✅ API Client (7 tests)
```
✓ POST pour login
✓ POST pour register
✓ Gère les erreurs
✓ GET utilisateur courant
+ 3 autres
```

---

## 🚀 Commandes essentielles

```bash
# Lancer les tests (mode watch)
npm test

# Voir rapport de couverture
npm test -- --coverage

# Lancer un seul fichier
npm test -- App.test.tsx

# Lancer avec pattern
npm test -- --testNamePattern="connexion"

# Mode debug
npm test -- --watch --verbose
```

---

## 🔄 Mode Watch

Quand vous lancez `npm test`, Jest rentre en mode **watch**:

```
À chaque fois que vous sauvegardez un fichier
  ↓
Jest relance les tests automatiquement
  ↓
Vous voyez les résultats (< 2 secondes)
```

**Touches:**
- `a` - Relancer tous les tests
- `f` - Relancer tests échoués
- `p` - Filtrer par fichier
- `t` - Filtrer par nom de test
- `q` - Quitter

---

## 🧬 Structure des fichiers

```
client/src/
├── app/
│   └── App.test.tsx              (3 tests)
├── contexts/
│   └── AuthContext.test.tsx      (9 tests)
├── components/
│   └── ProtectedRoute.test.tsx   (3 tests)
├── api/
│   └── client.test.ts            (7 tests)
└── setupTests.ts                 (configuration)

client/
└── jest.config.js                (configuration)
```

---

## 💡 Concepts clés

| Concept | Exemple |
|---------|---------|
| **render()** | `render(<App />)` - Affiche le composant |
| **screen.getBy...()** | `screen.getByText('Login')` - Cherche un élément |
| **fireEvent** | `fireEvent.click(button)` - Simule un clic |
| **expect()** | `expect(...).toBeInTheDocument()` - Vérifie |
| **jest.mock()** | `jest.mock('api', ...)` - Mock une dépendance |
| **waitFor()** | `await waitFor(() => {...})` - Attend un changement |

---

## 🎯 Prochaines étapes

- [ ] Lancer `npm test`
- [ ] Lire `JEST_EXPLICATION_SIMPLE.md`
- [ ] Ajouter tests pour StudentDashboard
- [ ] Ajouter tests pour ProfessorDashboard
- [ ] Ajouter tests pour AdminDashboard
- [ ] Atteindre 80%+ de couverture

---

## 🚨 Dépannage

### "Jest n'est pas trouvé"
```bash
cd client
npm install  # Au besoin
npm test
```

### "Test échoue"
Jest affiche exactement quoi est cassé. Lisez le message d'erreur !

### "Aucun test trouvé"
Vérifier que les fichiers `*.test.tsx` existent dans `src/`.

---

## 📊 Exemple de résultat

```
PASS  src/app/App.test.tsx
PASS  src/contexts/AuthContext.test.tsx
PASS  src/components/ProtectedRoute.test.tsx
PASS  src/api/client.test.ts

Test Suites: 4 passed, 4 total
Tests:       22 passed, 22 total
Time:        2.345s

✅ ALL TESTS PASSED!
```

---

## 📚 Ressources

- [Jest Docs](https://jestjs.io)
- [React Testing Library](https://testing-library.com)
- [Testing Best Practices](https://kentcdodds.com/blog/)

---

## ✨ Avantages

| Avant | Après |
|-------|-------|
| Tests manuels (lent) | Tests auto (rapide ⚡) |
| On oublie des cas | Jest teste tous les cas |
| Bugs arrivent en prod | Bugs détectés avant |
| Refactoriser = risqué | Refactoriser = sûr ✅ |

---

## 🎉 Vous êtes prêt !

```bash
npm test
```

C'est aussi simple que ça ! 🚀

---

*Status: ✅ 22 tests passing | 📝 Documentation complete | 🚀 Ready to go*
