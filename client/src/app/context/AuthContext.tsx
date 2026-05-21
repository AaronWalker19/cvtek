import React, { createContext, useContext, useState, useEffect } from 'react';
import { login as apiLogin, register as apiRegister, getCurrentUser, logout as apiLogout, User, storeToken, getToken, clearToken, getDemoToken, initializeDemoUsers } from '../../api/client';

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
  {
    id: 18,
    userId: 18,
    username: 'admin',
    email: 'admin@cvtek.fr',
    role: 'admin',
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
        console.log('🔧 Initialisation des utilisateurs démo...');
        await initializeDemoUsers();

        const currentUser = await getCurrentUser();
        if (currentUser) {
          setIsAuthenticated(true);
          setUser(currentUser);
        } else {
          // En mode démo, charger le premier utilisateur et générer un token
          setIsAuthenticated(true);
          setUser(demoUsers[0] as User);
          
          // Générer un token de démo pour le premier utilisateur
          try {
            await getDemoToken(demoUsers[0].id);
            console.log(`✅ Token de démo initial généré pour ${demoUsers[0].username}`);
          } catch (err) {
            console.warn('❌ Erreur lors de la génération du token de démo initial:', err);
          }
        }
      } catch (err) {
        console.error('Erreur vérification auth:', err);
        // En mode démo, charger le premier utilisateur
        setIsAuthenticated(true);
        setUser(demoUsers[0] as User);
        
        // Générer un token de démo pour le premier utilisateur en cas d'erreur
        try {
          await getDemoToken(demoUsers[0].id);
          console.log(`✅ Token de démo d'erreur généré pour ${demoUsers[0].username}`);
        } catch (tokenErr) {
          console.error('❌ Erreur lors de la génération du token de démo en fallback:', tokenErr);
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
    } catch (error) {
      // Fallback mode démo
      console.warn('API login échoué, mode démo:', error);
      const demoUser = demoUsers.find(u => u.email === email);
      if (demoUser) {
        setIsAuthenticated(true);
        setUser(demoUser as User);
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
      setIsAuthenticated(false);
      setUser(null);
    }
  };

  const switchUser = async (userId: string) => {
    // Mode démo: changer d'utilisateur ET générer un token démo
    const selectedUser = demoUsers.find(u => u.userId?.toString() === userId || u.id.toString() === userId);
    if (selectedUser) {
      setUser(selectedUser as User);
      
      // Générer un token démo en appelant la fonction du client API
      try {
        await getDemoToken(selectedUser.id);
        console.log(`✅ Token de démo généré pour ${selectedUser.username}`);
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
