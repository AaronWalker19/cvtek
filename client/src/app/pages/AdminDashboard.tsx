import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import PageAdmin from '../../imports/PageAdmin/PageAdmin';

export default function AdminDashboard() {
  const { logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return <PageAdmin onLogout={handleLogout} />;
}
