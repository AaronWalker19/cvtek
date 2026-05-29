import { useState } from 'react';
import { useAuth } from '../app/context/AuthContext';

interface AdminLoginModalProps {
  show: boolean;
  onClose: () => void;
  onSuccess?: () => void;
}

export default function AdminLoginModal({
  show,
  onClose,
  onSuccess,
}: AdminLoginModalProps) {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  if (!show) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      await login(email, password);
      setEmail('');
      setPassword('');
      onClose();
      onSuccess?.();
    } catch (err: any) {
      setError(err.message || 'Erreur de connexion. Vérifiez vos identifiants.');
      console.error('Erreur connexion admin:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleClose = () => {
    setEmail('');
    setPassword('');
    setError('');
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-[#00000050] flex items-center justify-center z-50">
      <div className="bg-[#f7f7f7] rounded-lg p-8 shadow-2xl max-w-md border-2 border-[#36302a] w-full mx-4">
        <h3 className="text-2xl font-bold text-[#36302a] mb-2">Connexion Admin</h3>
        <p className="text-sm text-[#666] mb-6">
          Entrez vos identifiants administrateur pour continuer
        </p>

        {error && (
          <div className="mb-6 p-4 bg-[#ffebee] border-l-4 border-[#c62828] rounded">
            <p className="text-sm text-[#c62828] font-medium">{error}</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Email */}
          <div>
            <label htmlFor="admin-email" className="block text-[#36302a] text-sm font-medium mb-2">
              Email
            </label>
            <input
              id="admin-email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="your@email.com"
              disabled={isLoading}
              className="w-full px-4 py-2 border-2 border-[#36302a] rounded font-['Inter:Regular',sans-serif] text-[#36302a] placeholder-[#999] focus:outline-none focus:border-[#b51621] transition-colors disabled:bg-[#f0f0f0] disabled:cursor-not-allowed"
              required
            />
          </div>

          {/* Password */}
          <div>
            <label htmlFor="admin-password" className="block text-[#36302a] text-sm font-medium mb-2">
              Mot de passe
            </label>
            <input
              id="admin-password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              disabled={isLoading}
              className="w-full px-4 py-2 border-2 border-[#36302a] rounded font-['Inter:Regular',sans-serif] text-[#36302a] placeholder-[#999] focus:outline-none focus:border-[#b51621] transition-colors disabled:bg-[#f0f0f0] disabled:cursor-not-allowed"
              required
            />
          </div>

          {/* Buttons */}
          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={handleClose}
              disabled={isLoading}
              className="flex-1 px-4 py-2 border-2 border-[#36302a] rounded font-['Inter:Medium',sans-serif] text-[#36302a] hover:bg-[#f0f0f0] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Annuler
            </button>
            <button
              type="submit"
              disabled={isLoading || !email || !password}
              className="flex-1 px-4 py-2 bg-[#b51621] rounded font-['Inter:Medium',sans-serif] text-white hover:bg-[#932117] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? 'Connexion...' : 'Connexion'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
