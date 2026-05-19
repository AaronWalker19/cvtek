import React, { createContext, useContext, useState, useEffect } from 'react';
import { login as apiLogin, register as apiRegister, getCurrentUser, logout as apiLogout, User } from '../api/client';

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  login: (email: string, password: string) => Promise<void>;  // CHANGÉ: username → email
  register: (username: string, email: string, password: string, role?: string) => Promise<void>;  // CHANGÉ: ajout role
  logout: () => Promise<void>;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

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
          setIsAuthenticated(false);
          setUser(null);
        }
      } catch (err) {
        console.error('Erreur vérification auth:', err);
        setIsAuthenticated(false);
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    verifyAuth();
  }, []);

  const login = async (email: string, password: string) => {  // CHANGÉ: username → email
    try {
      const currentUser = await apiLogin({ email, password });  // CHANGÉ: username → email
      setIsAuthenticated(true);
      setUser(currentUser);
    } catch (error) {
      throw error;
    }
  };

  const register = async (username: string, email: string, password: string, role: string = 'student') => {  // CHANGÉ: ajout role
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

  return (
    <AuthContext.Provider value={{ isAuthenticated, user, login, register, logout, loading }}>
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
