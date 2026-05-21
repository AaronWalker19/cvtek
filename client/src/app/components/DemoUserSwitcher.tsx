import React from 'react';
import { useAuth, demoUsers } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

export default function DemoUserSwitcher() {
  const { user, switchUser } = useAuth();
  const navigate = useNavigate();

  if (!user || !switchUser) {
    return null; // Pas en mode démo
  }

  const handleSwitchUser = async (userId: number) => {
    await switchUser(userId.toString());
    // Utiliser React Router pour naviguer vers le bon dashboard
    const demoUser = demoUsers.find(u => u.id === userId);
    if (demoUser?.role === 'student') {
      navigate('/');
    } else if (demoUser?.role === 'professor') {
      navigate('/professor');
    } else if (demoUser?.role === 'admin') {
      navigate('/admin');
    }
  };

  return (
    <div className="fixed bottom-4 right-4 z-50 bg-white border border-gray-300 rounded-lg shadow-lg p-3">
      <div className="text-xs font-semibold text-gray-700 mb-2">Mode Démo</div>
      <div className="flex flex-col gap-2">
        {demoUsers.map((demoUser) => (
          <button
            key={demoUser.id}
            onClick={() => handleSwitchUser(demoUser.id)}
            className={`text-xs px-3 py-2 rounded transition-colors ${
              user.id === demoUser.id
                ? 'bg-blue-500 text-white font-semibold'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            {demoUser.username.toUpperCase()}
            <span className="text-xs opacity-75 ml-1">({demoUser.role})</span>
          </button>
        ))}
      </div>
    </div>
  );
}
