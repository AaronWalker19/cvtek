import { useAuth } from '../context/AuthContext';
import PageAdmin from '../../imports/PageAdmin/PageAdmin';

export default function AdminDashboard() {
  const { logout } = useAuth();

  const handleLogout = async () => {
    await logout();
    // Redirection directe vers la racine (recharge la page)
    window.location.href = '/cvtek/';
  };

  return <PageAdmin onLogout={handleLogout} />;
}
