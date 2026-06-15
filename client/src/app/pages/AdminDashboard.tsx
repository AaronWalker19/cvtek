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
      navigate('/', { replace: true });
      return;
    }

    if (user.role !== 'admin') {
      // Pas admin - rediriger vers page d'accès refusé
      navigate('/access-denied', { replace: true });
      return;
    }
  }, [user, navigate]);

  // ⏳ Pendant que les vérifications se font
  if (!user || user.role !== 'admin') {
    return null;
  }

  const handleLogout = async () => {
    try {
      await logout();
      console.log('✅ Logout réussi, redirection...');
    } catch (err) {
      console.error('❌ Erreur logout:', err);
    }
    // Utiliser navigate au lieu de window.location.href pour garder le contexte React
    navigate('/cvtek/professor', { replace: true });
  };

  return <PageAdmin onLogout={handleLogout} />;
}
