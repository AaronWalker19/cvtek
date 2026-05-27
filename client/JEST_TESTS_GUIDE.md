# Guide Jest et Tests - CVTEK

## 📋 Vue d'ensemble

Jest est un framework de test JavaScript/TypeScript créé par Meta. Il permet de vérifier que votre application fonctionne correctement en testant chaque partie individuellement.

## 🚀 Commandes de test

```bash
# Lancer tous les tests
npm test

# Lancer les tests en mode watch (re-test à chaque changement)
npm test -- --watch

# Lancer les tests avec rapport de couverture
npm test -- --coverage

# Lancer un fichier de test spécifique
npm test -- App.test.tsx

# Lancer les tests et mettre à jour les snapshots
npm test -- -u
```

## 🏗️ Structure des tests

Les fichiers de test doivent être nommés avec le suffixe `.test.tsx` ou `.test.ts` et placés à côté des fichiers qu'ils testent.

```
src/
├── contexts/
│   ├── AuthContext.tsx          (fichier source)
│   └── AuthContext.test.tsx     (fichier de test)
├── components/
│   ├── ProtectedRoute.tsx
│   └── ProtectedRoute.test.tsx
└── api/
    ├── client.ts
    └── client.test.ts
```

## 📚 Types de tests créés

### 1. **Tests App.test.tsx** (Application principale)

**Ce qu'on teste:**
- ✅ L'application rend sans erreur
- ✅ Affichage du loader pendant le chargement
- ✅ Présence des routes principales

```typescript
it('doit rendre sans erreur', () => {
  render(<App />);
  expect(screen.getByText(/Student Dashboard/i)).toBeInTheDocument();
});
```

### 2. **Tests AuthContext.test.tsx** (Authentification)

**Ce qu'on teste:**
- ✅ Connexion (login)
- ✅ Inscription (register)
- ✅ Déconnexion (logout)
- ✅ Chargement de l'utilisateur au démarrage
- ✅ Gestion des erreurs

```typescript
it('doit permettre la connexion', async () => {
  const mockUser = { id: '1', email: 'test@example.com', role: 'student' };
  (authClient.login as jest.Mock).mockResolvedValue(mockUser);
  
  render(<AuthProvider><TestComponent /></AuthProvider>);
  fireEvent.click(screen.getByText('Login'));
  
  await waitFor(() => {
    expect(authClient.login).toHaveBeenCalled();
  });
});
```

### 3. **Tests ProtectedRoute.test.tsx** (Routes protégées)

**Ce qu'on teste:**
- ✅ Affichage du contenu quand authentifié
- ✅ Affichage du loader quand en cours de chargement
- ✅ Redirection quand non authentifié

### 4. **Tests client.test.ts** (Appels API)

**Ce qu'on teste:**
- ✅ Requête POST pour login
- ✅ Requête POST pour register
- ✅ Requête GET pour getCurrentUser
- ✅ Gestion des erreurs

## 🧬 Concepts clés

### **render()** - Dessine un composant
```typescript
render(<App />);
// Affiche le composant dans un environnement de test
```

### **screen** - Cherche des éléments
```typescript
screen.getByText('Login');  // Par texte
screen.getByTestId('user');  // Par id de test
screen.getByRole('button');  // Par rôle
```

### **fireEvent** - Simule des actions
```typescript
fireEvent.click(button);  // Clic sur un bouton
fireEvent.change(input, { target: { value: 'test' } });  // Changement input
```

### **waitFor()** - Attend les changements asynchrones
```typescript
await waitFor(() => {
  expect(screen.getByText('Loaded')).toBeInTheDocument();
});
```

### **jest.mock()** - Mock (simule) des modules
```typescript
jest.mock('../api/client', () => ({
  login: jest.fn(),  // Fonction simulée
  logout: jest.fn(),
}));
```

### **expect()** - Assertions (vérifications)
```typescript
expect(element).toBeInTheDocument();
expect(value).toBe(5);
expect(func).toHaveBeenCalled();
expect(array).toContain('test');
```

## 📊 Rapport de couverture

```bash
npm test -- --coverage
```

Cela génère un rapport montrant le pourcentage de code testé:
- **Statements**: % de lignes exécutées
- **Branches**: % de décisions (if/else) testées
- **Functions**: % de fonctions testées
- **Lines**: % de lignes couvertes

```
File      | Stmts | Branch | Funcs | Lines
----------|-------|--------|-------|-------
App       | 85%   | 80%    | 90%   | 85%
Auth      | 95%   | 92%    | 100%  | 95%
```

## 🔧 Mocks expliqués

### Mock d'une fonction API
```typescript
jest.mock('../api/client', () => ({
  login: jest.fn().mockResolvedValue({ id: '1' }),
}));

// Maintenant login est simulée, on contrôle sa réponse
```

### Mock de localStorage
```typescript
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
};
global.localStorage = localStorageMock;
```

### Mock d'un composant
```typescript
jest.mock('./StudentDashboard', () => {
  return function MockComponent() {
    return <div>Mock Dashboard</div>;
  };
});
```

## ✅ Assertions les plus utiles

```typescript
// Existence
expect(element).toBeInTheDocument();
expect(element).toExist();

// Texte
expect(element).toHaveTextContent('Hello');

// Valeurs
expect(value).toBe(5);
expect(value).toEqual({ id: 1 });

// Classes CSS
expect(element).toHaveClass('active');

// Attributs
expect(element).toHaveAttribute('href', '/page');

// Fonctions
expect(mockFn).toHaveBeenCalled();
expect(mockFn).toHaveBeenCalledWith('arg1');
expect(mockFn).toHaveReturnedWith({ id: 1 });

// Arrays
expect(array).toContain('item');
expect(array).toHaveLength(3);
```

## 🐛 Déboguer les tests

### Mode watch interactif
```bash
npm test -- --watch
# Tapez 'p' pour filtrer par nom de fichier
# Tapez 'a' pour relancer tous les tests
# Tapez 'q' pour quitter
```

### Afficher l'HTML rendu
```typescript
it('doit afficher quelque chose', () => {
  const { debug } = render(<App />);
  debug();  // Affiche le HTML dans la console
});
```

### Console.log dans les tests
```typescript
it('test', () => {
  const element = screen.getByText('Test');
  console.log(element);  // Affiche dans les logs de test
});
```

## 📝 Ajouter un nouveau test

**Étape 1**: Créer le fichier
```typescript
// src/pages/StudentDashboard.test.tsx
import { render, screen } from '@testing-library/react';
import StudentDashboard from './StudentDashboard';

describe('StudentDashboard', () => {
  it('doit afficher le titre', () => {
    render(<StudentDashboard />);
    expect(screen.getByText(/Dashboard/i)).toBeInTheDocument();
  });
});
```

**Étape 2**: Lancer les tests
```bash
npm test
```

**Étape 3**: Ajouter plus de tests
```typescript
it('doit afficher les documents', () => {
  render(<StudentDashboard />);
  expect(screen.getByRole('heading', { name: /Documents/i })).toBeInTheDocument();
});

it('doit permettre de créer un document', () => {
  render(<StudentDashboard />);
  const button = screen.getByRole('button', { name: /Nouveau/i });
  fireEvent.click(button);
  // Vérifier que la modale s'ouvre, etc.
});
```

## 🎯 Bonnes pratiques

1. **Un test = Une responsabilité**
   ```typescript
   // ❌ Mauvais: teste plusieurs choses
   it('doit tout faire', () => {
     render(<App />);
     fireEvent.click(...);
     expect(...);
     expect(...);
   });
   
   // ✅ Bon: un comportement à tester
   it('doit afficher le bouton', () => {
     render(<App />);
     expect(screen.getByRole('button')).toBeInTheDocument();
   });
   ```

2. **Nommer les tests clairement**
   ```typescript
   // ❌ Vague
   it('works', () => {});
   
   // ✅ Clair
   it('doit afficher le message d\'erreur quand login échoue', () => {});
   ```

3. **Utiliser des data-testid pour les éléments difficiles**
   ```typescript
   // Composant
   <div data-testid="user-profile">...</div>
   
   // Test
   expect(screen.getByTestId('user-profile')).toBeInTheDocument();
   ```

4. **Vérifier le comportement, pas l'implémentation**
   ```typescript
   // ❌ Teste l'implémentation
   expect(state.user).toBe('John');
   
   // ✅ Teste le comportement visible
   expect(screen.getByText('John')).toBeInTheDocument();
   ```

## 🚨 Erreurs courantes

| Erreur | Cause | Solution |
|--------|-------|----------|
| `Cannot find module` | Import mal écrit | Vérifier le chemin |
| `TypeError: render is not a function` | Oubli du import | `import { render } from '@testing-library/react'` |
| `Timeout waiting for element` | Element ne s'affiche pas | Ajouter `await waitFor()` |
| `act() warning` | State update en dehors de render | Utiliser `waitFor()` |

## 📚 Ressources

- [Testing Library Docs](https://testing-library.com/docs/react-testing-library/intro/)
- [Jest Docs](https://jestjs.io/docs/getting-started)
- [React Testing Best Practices](https://kentcdodds.com/blog/common-mistakes-with-react-testing-library)

## ✨ Prochaines étapes

1. ✅ Tests créés pour: App, Auth, ProtectedRoute, API Client
2. 📝 À ajouter: Tests pour chaque page (StudentDashboard, etc.)
3. 🔄 À ajouter: Tests d'intégration (plusieurs composants ensemble)
4. 📊 À ajouter: CI/CD (tests automatiques à chaque commit)
