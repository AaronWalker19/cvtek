import React, { createContext, useContext, useState, useEffect } from 'react';
import { login as apiLogin, register as apiRegister, getCurrentUser, logout as apiLogout, User } from '../../api/client';

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
const demoUsers: DemoUser[] = [
  {
    id: 1,
    userId: 1,
    username: 'mael',
    email: 'mael@mael.fr',
    role: 'student',
  },
  {
    id: 2,
    userId: 2,
    username: 'professor',
    email: 'professor@cvtek.fr',
    role: 'professor',
  },
  {
    id: 3,
    userId: 3,
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
        const currentUser = await getCurrentUser();
        if (currentUser) {
          setIsAuthenticated(true);
          setUser(currentUser);
        } else {
          // En mode démo, charger le premier utilisateur
          setIsAuthenticated(true);
          setUser(demoUsers[0] as User);
        }
      } catch (err) {
        console.error('Erreur vérification auth:', err);
        // En mode démo, charger le premier utilisateur
        setIsAuthenticated(true);
        setUser(demoUsers[0] as User);
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
      setIsAuthenticated(false);
      setUser(null);
    }
  };

  const switchUser = (userId: string) => {
    // Mode démo: permets de changer d'utilisateur
    const selectedUser = demoUsers.find(u => u.userId?.toString() === userId || u.id.toString() === userId);
    if (selectedUser) {
      setUser(selectedUser as User);
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
