import React, { createContext, useContext, useState, useEffect } from 'react';
import { login as apiLogin, register as apiRegister, getCurrentUser, logout as apiLogout, User, storeToken, getToken, clearToken, getDemoToken, initializeDemoUsers, initializeAdmin } from '../../api/client';

export interface DemoUser extends User {
  userId?: number;  // Compat avec ancien code
}

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  register: (username: string, email: string, password: string, role?: string) => Promise<void>;
  logout: () => Promise<void>;
  loading: boolean;
  switchUser?: (userId: string) => void;  // Mode démo seulement
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// ===== Gestion localStorage pour persister la session utilisateur =====
function saveUserToStorage(user: User): void {
  localStorage.setItem('auth_user', JSON.stringify(user));
}

function getUserFromStorage(): User | null {
  const stored = localStorage.getItem('auth_user');
  if (!stored) return null;
  try {
    return JSON.parse(stored);
  } catch {
    return null;
  }
}

function clearUserFromStorage(): void {
  localStorage.removeItem('auth_user');
}

// Convertir les utilisateurs démo en format User pour l'API
// ⚠️ Les IDs doivent correspondre aux IDs en base de données (vérifier phpMyAdmin)
const demoUsers: DemoUser[] = [
  {
    id: 16,
    userId: 16,
    username: 'mael',
    email: 'mael@mael.fr',
    role: 'student',
  },
  {
    id: 17,
    userId: 17,
    username: 'professor',
    email: 'professor@cvtek.fr',
    role: 'professor',
  },
];

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Vérifier l'authentification au chargement
  useEffect(() => {
    const verifyAuth = async () => {
      try {
        // D'abord, initialiser les utilisateurs démo
        await initializeDemoUsers();
        
        // Initialiser l'admin (crée l'admin en base si n'existe pas)
        await initializeAdmin();

        const currentUser = await getCurrentUser();
        if (currentUser) {
          setIsAuthenticated(true);
          setUser(currentUser);
          saveUserToStorage(currentUser);
        } else {
          // Essayer de récupérer l'utilisateur sauvegardé en local
          const storedUser = getUserFromStorage();
          if (storedUser) {
            setIsAuthenticated(true);
            setUser(storedUser);
            console.log('✅ Utilisateur restauré depuis localStorage:', storedUser.username);
          } else {
            // En mode démo, charger le premier utilisateur et générer un token
            setIsAuthenticated(true);
            setUser(demoUsers[0] as User);
            
            // Générer un token de démo pour le premier utilisateur
            try {
              await getDemoToken(demoUsers[0].id);
            } catch (err) {
              console.error('❌ Erreur lors de la génération du token de démo initial:', err);
            }
          }
        }
      } catch (err) {
        console.error('Erreur vérification auth:', err);
        // Essayer de récupérer l'utilisateur sauvegardé en local
        const storedUser = getUserFromStorage();
        if (storedUser) {
          setIsAuthenticated(true);
          setUser(storedUser);
          console.log('✅ Utilisateur restauré depuis localStorage (fallback):', storedUser.username);
        } else {
          // En mode démo, charger le premier utilisateur
          setIsAuthenticated(true);
          setUser(demoUsers[0] as User);
          
          // Générer un token de démo pour le premier utilisateur en cas d'erreur
          try {
            await getDemoToken(demoUsers[0].id);
          } catch (tokenErr) {
            console.error('❌ Erreur lors de la génération du token de démo en fallback:', tokenErr);
          }
        }
      } finally {
        setLoading(false);
      }
    };

    verifyAuth();
  }, []);

  const login = async (email: string, password: string) => {
    try {
      const currentUser = await apiLogin({ email, password });
      setIsAuthenticated(true);
      setUser(currentUser);
      saveUserToStorage(currentUser);
    } catch (error) {
      // Fallback mode démo
      const demoUser = demoUsers.find(u => u.email === email);
      if (demoUser) {
        setIsAuthenticated(true);
        setUser(demoUser as User);
        saveUserToStorage(demoUser as User);
      } else {
        throw error;
      }
    }
  };

  const register = async (username: string, email: string, password: string, role: string = 'student') => {
    try {
      const currentUser = await apiRegister({ username, email, password, role: role as any });
      setIsAuthenticated(true);
      setUser(currentUser);
      saveUserToStorage(currentUser);
    } catch (error) {
      throw error;
    }
  };

  const logout = async () => {
    try {
      await apiLogout();
    } catch (err) {
      console.error('Erreur logout:', err);
    } finally {
      clearToken();
      clearUserFromStorage();
      setIsAuthenticated(false);
      setUser(null);
    }
  };

  const switchUser = async (userId: string) => {
    // Mode démo: changer d'utilisateur ET générer un token démo
    const selectedUser = demoUsers.find(u => u.userId?.toString() === userId || u.id.toString() === userId);
    if (selectedUser) {
      setUser(selectedUser as User);
      saveUserToStorage(selectedUser as User);
      
      // Générer un token démo en appelant la fonction du client API
      try {
        await getDemoToken(selectedUser.id);
      } catch (err) {
        console.error('❌ Erreur lors de la génération du token de démo:', err);
      }
    }
  };

  return (
    <AuthContext.Provider value={{ isAuthenticated, user, login, register, logout, loading, switchUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}

export { demoUsers };
