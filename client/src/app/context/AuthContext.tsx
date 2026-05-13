import React, { createContext, useContext, useState } from 'react';
import { mockUsers } from '../data/mockData';

export interface User {
  userId: string;
  username: string;
  email: string;
  role: 'student' | 'professor' | 'admin';
  login_at?: string;
}

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  switchUser: (userId: string) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Convertir mockUsers en format User pour l'API
const demoUsers: User[] = [
  {
    userId: 'student1',
    username: 'Mael Valin',
    email: 'mael.valin@etu.unilim.fr',
    role: 'student',
    login_at: new Date().toISOString(),
  },
  {
    userId: 'prof1',
    username: 'M. Nival',
    email: 'nival@etu.unilim.fr',
    role: 'professor',
    login_at: new Date().toISOString(),
  },
  {
    userId: 'admin1',
    username: 'Admin User',
    email: 'admin@unilim.fr',
    role: 'admin',
    login_at: new Date().toISOString(),
  },
];

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User>(demoUsers[0]); // Commencer avec l'étudiant

  const switchUser = (userId: string) => {
    const selectedUser = demoUsers.find(u => u.userId === userId);
    if (selectedUser) {
      setUser(selectedUser);
    }
  };

  const logout = () => {
    // En mode démo, reset au premier utilisateur
    setUser(demoUsers[0]);
  };

  return (
    <AuthContext.Provider value={{ isAuthenticated: true, user, switchUser, logout }}>
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
