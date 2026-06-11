import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import PageAdmin from '../../imports/PageAdmin/PageAdmin';

export default function AdminDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  // 🔒 Protéger l'accès - rediriger si pas admin
  useEffect(() => {
    if (!user) {
      // Pas connecté - rediriger vers login
      navigate('/cvtek/');
      return;
    }

    if (user.role !== 'admin') {
      // Pas admin - rediriger vers page d'accès refusé
      navigate('/cvtek/access-denied');
      return;
    }
  }, [user, navigate]);

  // ⏳ Pendant que les vérifications se font
  if (!user || user.role !== 'admin') {
    return null;
  }

  const handleLogout = async () => {
    await logout();
    // Redirection directe vers la racine (recharge la page)
    window.location.href = '/cvtek/';
  };

  return <PageAdmin onLogout={handleLogout} />;
}
