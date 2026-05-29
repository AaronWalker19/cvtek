import { useState, useEffect } from 'react';
import { getProfessors, createProfessor, deleteProfessor, getProfessor, Professor, Comment } from '../../api/client';
import ProfessorProfileModal from '../../components/ProfessorProfileModal';

interface PageAdminProps {
  onLogout: () => void;
}

export default function PageAdmin({ onLogout }: PageAdminProps) {
  const [professors, setProfessors] = useState<Professor[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [newEmail, setNewEmail] = useState('');
  const [isAdding, setIsAdding] = useState(false);
  const [selectedProfessor, setSelectedProfessor] = useState<Professor | null>(null);
  const [profComments, setProfComments] = useState<Comment[]>([]);
  const [showProfileModal, setShowProfileModal] = useState(false);
  const [deleting, setDeleting] = useState<number | null>(null);

  // Charger la liste des professeurs au montage
  useEffect(() => {
    loadProfessors();
  }, []);

  const loadProfessors = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getProfessors();
      setProfessors(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur lors du chargement');
      console.error('Erreur lors du chargement des professeurs:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAddProfessor = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newEmail.trim()) return;

    try {
      setIsAdding(true);
      const newProf = await createProfessor(newEmail);
      setProfessors([...professors, newProf]);
      setNewEmail('');
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Erreur lors de l\'ajout');
      console.error('Erreur:', err);
    } finally {
      setIsAdding(false);
    }
  };

  const handleDeleteProfessor = async (profId: number) => {
    // eslint-disable-next-line no-restricted-globals
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce professeur?')) return;

    try {
      setDeleting(profId);
      await deleteProfessor(profId);
      setProfessors(professors.filter(p => p.id !== profId));
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Erreur lors de la suppression');
      console.error('Erreur:', err);
    } finally {
      setDeleting(null);
    }
  };

  const handleRowClick = async (prof: Professor) => {
    try {
      setSelectedProfessor(prof);
      // Récupérer les détails du professeur avec les commentaires
      const data = await getProfessor(prof.id);
      setProfComments(data.comments);
      setShowProfileModal(true);
    } catch (err) {
      console.error('Erreur:', err);
      alert('Erreur lors de la récupération des détails');
    }
  };



  if (loading) {
    return (
      <div className="bg-[#ffffff] content-stretch flex items-center justify-center relative size-full">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#4b575f] mx-auto mb-4"></div>
          <p className="text-[#4b575f]">Chargement...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-[#ffffff] content-stretch flex items-stretch relative h-screen w-full">
      {/* Sidebar */}
      <div className="bg-[#4b575f] h-full relative shrink-0 flex flex-col items-center justify-end py-[20px] px-[30px] w-[220px]">
        <button
          onClick={onLogout}
          className="bg-[#b51621] content-stretch flex items-center justify-center p-[10px] relative rounded-[4px] shrink-0 w-full hover:bg-[#8e1119] transition-colors"
        >
          <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic text-[#ffffff] text-[18px] whitespace-nowrap">
            Déconnexion
          </p>
        </button>
      </div>

      {/* Main Content */}
      <div className="flex-[1_0_0] h-full min-w-px relative">
        <div className="flex flex-col items-center overflow-clip rounded-[inherit] size-full">
          <div className="content-stretch flex flex-col gap-[50px] items-center p-[40px] relative size-full overflow-y-auto">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#4b575f] border-b-3 border-solid inset-0 pointer-events-none" />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] whitespace-nowrap">
                Membre
              </p>
            </div>

            {/* Add Professor Form */}
            <div className="content-stretch flex gap-[10px] items-center justify-center relative shrink-0">
              <div className="bg-[#ffffff] content-stretch flex gap-[10px] items-center p-[10px] relative rounded-[10px] shrink-0 w-full max-w-[564px]">
                <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                <input
                  type="email"
                  value={newEmail}
                  onChange={(e) => setNewEmail(e.target.value)}
                  placeholder="mail du nouvelle utilisateur"
                  className="flex-1 bg-transparent font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[20px] text-[#36302a] placeholder-[rgba(75,87,95,0.5)] outline-none"
                />
              </div>
              <button
                onClick={handleAddProfessor}
                disabled={isAdding || !newEmail.trim()}
                className="bg-[#4b575f] content-stretch flex items-center justify-center p-[10px] relative rounded-[4px] shrink-0 w-[178px] hover:bg-[#3a444b] transition-colors disabled:opacity-50"
              >
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[20px] text-[#ffffff] whitespace-nowrap">
                  {isAdding ? 'Ajout...' : 'Ajouter l\'accès'}
                </p>
              </button>
            </div>

            {/* Error Message */}
            {error && (
              <div className="bg-[#ffebee] border-l-4 border-[#b51621] p-4 relative shrink-0 w-full">
                <p className="text-[#b51621] font-medium">{error}</p>
              </div>
            )}

            {/* Professors List */}
            <div className="content-stretch flex flex-col gap-[13px] items-start relative shrink-0 w-full">
              {/* Header Row */}
              <div className="content-stretch flex items-center justify-between relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic flex-1 text-[#36302a] text-[24px] whitespace-nowrap">
                  Professeur
                </p>
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic flex-1 text-[#36302a] text-[24px] whitespace-nowrap">
                  mail
                </p>
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic flex-1 text-[#36302a] text-[24px] whitespace-nowrap">
                  commentaires
                </p>
                <div className="w-[120px]" />
              </div>

              {/* Professors Rows */}
              {professors.length === 0 ? (
                <div className="content-stretch flex items-center justify-center py-[40px] relative shrink-0 w-full">
                  <p className="text-[#999] text-center">Aucun professeur pour le moment</p>
                </div>
              ) : (
                professors.map((prof) => (
                  <div
                    key={prof.id}
                    onClick={() => handleRowClick(prof)}
                    className="content-stretch flex items-start justify-between py-[10px] px-[10px] relative shrink-0 w-full cursor-pointer hover:bg-[#f5f5f5] rounded transition-colors group"
                  >
                    <div aria-hidden="true" className="absolute border-[#36302a] border-b border-solid inset-0 pointer-events-none" />
                    
                    <div className="flex flex-1 flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] z-10">
                      <p className="leading-[normal]">{prof.username}</p>
                    </div>
                    
                    <div className="flex flex-1 flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] z-10">
                      <p className="leading-[normal] truncate">{prof.email}</p>
                    </div>
                    
                    <div className="flex flex-1 flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] z-10">
                      <p className="decoration-solid leading-[normal] underline">
                        {prof.comment_count ?? 0} commentaire{(prof.comment_count ?? 0) !== 1 ? 's' : ''}
                      </p>
                    </div>

                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeleteProfessor(prof.id);
                      }}
                      disabled={deleting === prof.id}
                      className="bg-[#b51621] content-stretch flex items-center justify-center p-[3px] relative rounded-[4px] shrink-0 w-[120px] hover:bg-[#8e1119] transition-colors disabled:opacity-50 z-10"
                    >
                      <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic text-[#f7f7f7] text-[16px] whitespace-nowrap">
                        {deleting === prof.id ? 'Suppression...' : 'supprimer'}
                      </p>
                    </button>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Modal Profil */}
      {selectedProfessor && (
        <ProfessorProfileModal
          show={showProfileModal}
          professor={selectedProfessor}
          comments={profComments}
          onClose={() => {
            setShowProfileModal(false);
            setSelectedProfessor(null);
            setProfComments([]);
          }}
        />
      )}
    </div>
  );
}
