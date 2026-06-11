import React, { createContext, useContext, useState, useEffect } from 'react';
import { login as apiLogin, register as apiRegister, getCurrentUser, logout as apiLogout, User, storeToken, getToken, clearToken, getUnilimAuthorizeUrl } from '../../api/client';

export interface DemoUser extends User {
  userId?: number;  // Compat avec ancien code
}

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  register: (username: string, email: string, password: string, role?: string) => Promise<void>;
  logout: () => Promise<void>;
  loginWithUnilim: () => Promise<void>;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// ===== Gestion sessionStorage pour la session utilisateur (supprimée à la fermeture du navigateur) =====
function saveUserToStorage(user: User): void {
  sessionStorage.setItem('auth_user', JSON.stringify(user));
}

function getUserFromStorage(): User | null {
  const stored = sessionStorage.getItem('auth_user');
  if (!stored) return null;
  try {
    return JSON.parse(stored);
  } catch {
    return null;
  }
}

function clearUserFromStorage(): void {
  sessionStorage.removeItem('auth_user');
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
        const currentUser = await getCurrentUser();
        if (currentUser) {
          setIsAuthenticated(true);
          setUser(currentUser);
          saveUserToStorage(currentUser);
        } else {
          // Essayer de récupérer l'utilisateur sauvegardé en session
          const storedUser = getUserFromStorage();
          if (storedUser) {
            setIsAuthenticated(true);
            setUser(storedUser);
            console.log('✅ Utilisateur restauré depuis sessionStorage:', storedUser.username);
          } else {
            // Pas connecté - attendre que l'utilisateur se connecte via Unilim
            setIsAuthenticated(false);
            setUser(null);
          }
        }
      } catch (err) {
        console.error('Erreur vérification auth:', err);
        // Essayer de récupérer l'utilisateur sauvegardé en session
        const storedUser = getUserFromStorage();
        if (storedUser) {
          setIsAuthenticated(true);
          setUser(storedUser);
          console.log('✅ Utilisateur restauré depuis sessionStorage (fallback):', storedUser.username);
        } else {
          // Pas connecté
          setIsAuthenticated(false);
          setUser(null);
        }
      } finally {
        setLoading(false);
      }
    };

    verifyAuth();
  }, []);

  const login = async (email: string, password: string) => {
    const currentUser = await apiLogin({ email, password });
    setIsAuthenticated(true);
    setUser(currentUser);
    saveUserToStorage(currentUser);
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

  const loginWithUnilim = async () => {
    try {
      console.log('🔐 Initiation de la connexion Unilim...');
      const { authorize_url } = await getUnilimAuthorizeUrl();
      console.log('📍 Redirection vers:', authorize_url);
      
      // Rediriger l'utilisateur vers Unilim
      window.location.href = authorize_url;
    } catch (error) {
      const errorMsg = error instanceof Error ? error.message : String(error);
      console.error('❌ Erreur initiation Unilim:', errorMsg);
      throw new Error(`Erreur lors de l'initiation de la connexion Unilim: ${errorMsg}`);
    }
  };

  return (
    <AuthContext.Provider value={{ 
      isAuthenticated, 
      user, 
      login, 
      register, 
      logout, 
      loginWithUnilim,
      loading
    }}>
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
