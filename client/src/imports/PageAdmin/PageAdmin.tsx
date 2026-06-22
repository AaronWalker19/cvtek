import { useState, useEffect } from 'react';
import { getProfessors, createProfessor, deleteProfessor, getProfessor, getCommentsByUserId, getStudents, trashStudents, getTrashedStudents, restoreStudents, permanentDeleteStudents, Professor, Comment } from '../../api/client';
import ProfessorProfileModal from '../../components/ProfessorProfileModal';
import DeleteConfirmationModal from '../../components/DeleteConfirmationModal';
import ExportModal from '../../app/components/ExportModal';
import TrashModal from '../../app/components/TrashModal';

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
  const [profWrittenComments, setProfWrittenComments] = useState<Comment[]>([]);
  const [showProfileModal, setShowProfileModal] = useState(false);
  const [deleting, setDeleting] = useState<number | null>(null);
  const [showDeleteConfirmation, setShowDeleteConfirmation] = useState(false);
  const [selectedProfessorToDelete, setSelectedProfessorToDelete] = useState<Professor | null>(null);
  const [students, setStudents] = useState<Array<{ name: string; license?: string; userId: number; email?: string }>>([]);
  const [showDeleteStudentsModal, setShowDeleteStudentsModal] = useState(false);
  const [deletingStudents, setDeletingStudents] = useState(false);
  const [trashedStudents, setTrashedStudents] = useState<Array<{ name: string; license?: string; userId: number; email?: string }>>([]);
  const [showTrashModal, setShowTrashModal] = useState(false);
  const [trashLoading, setTrashLoading] = useState(false);

  // Charger la liste des professeurs et des étudiants au montage
  useEffect(() => {
    loadProfessors();
    loadStudents();
    loadTrashedStudents();
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

  const loadStudents = async () => {
    try {
      const data = await getStudents();
      setStudents(data.map((s) => ({
        name: s.username,
        license: s.parcour || 'N/A',
        userId: s.id,
        email: s.email,
      })));
    } catch (err) {
      console.error('Erreur lors du chargement des étudiants:', err);
    }
  };

  const loadTrashedStudents = async () => {
    try {
      const data = await getTrashedStudents();
      setTrashedStudents(data.map((s) => ({
        name: s.username,
        license: s.parcour || 'N/A',
        userId: s.id,
        email: s.email,
      })));
    } catch (err) {
      console.error('Erreur lors du chargement de la corbeille:', err);
    }
  };

  const handleAddProfessor = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!newEmail.trim()) {
      return;
    }

    try {
      setIsAdding(true);
      await createProfessor(newEmail);
      
      // Recharger la liste des profs
      const updatedProfs = await getProfessors();
      setProfessors(updatedProfs);
      setNewEmail('');
    } catch (err) {
      console.error('[ERROR] Failed to add professor:', err);
      
      // Recharger la liste des profs même en cas d'erreur
      try {
        const updatedProfs = await getProfessors();
        setProfessors(updatedProfs);
        setNewEmail('');
      } catch (reloadErr) {
        console.error('[ERROR] Failed to reload professors:', reloadErr);
      }
    } finally {
      setIsAdding(false);
    }
  };

  const handleDeleteProfessor = (prof: Professor) => {
    setSelectedProfessorToDelete(prof);
    setShowDeleteConfirmation(true);
  };

  const handleConfirmDelete = async () => {
    if (!selectedProfessorToDelete) return;

    try {
      console.log('[DEBUG] handleConfirmDelete called with profId:', selectedProfessorToDelete.id, 'type:', typeof selectedProfessorToDelete.id);
      setDeleting(selectedProfessorToDelete.id);
      await deleteProfessor(selectedProfessorToDelete.id);
      console.log('[DEBUG] Professor deleted successfully');
      
      // Recharger la liste des profs
      const updatedProfs = await getProfessors();
      setProfessors(updatedProfs);
      
      // Fermer la modal
      setShowDeleteConfirmation(false);
      setSelectedProfessorToDelete(null);
    } catch (err) {
      console.error('[ERROR] Failed to delete professor:', err);
      console.error('[ERROR] Details - profId:', selectedProfessorToDelete.id, 'type:', typeof selectedProfessorToDelete.id);
    } finally {
      setDeleting(null);
    }
  };

  const handleRowClick = async (prof: Professor) => {
    try {
      setSelectedProfessor(prof);
      // Récupérer les détails du professeur avec les commentaires reçus
      const data = await getProfessor(prof.id);
      setProfComments(data.comments);
      
      // Récupérer les commentaires écrits par le professeur
      const writtenComments = await getCommentsByUserId(prof.id);
      setProfWrittenComments(writtenComments);
      
      // Afficher les informations dans la console
      console.group('👨‍🏫 Profil Professeur');
      console.log('Nom:', prof.username);
      console.log('Email:', prof.email);
      console.log('ID:', prof.id);
      console.log('Rôle:', prof.role);
      console.groupEnd();
      
      console.group('💬 Commentaires reçus');
      console.log('Nombre total:', data.comment_count);
      if (data.comments && data.comments.length > 0) {
        data.comments.forEach((comment, index) => {
          console.log(`\n📝 Commentaire ${index + 1}:`);
          console.log('Auteur:', comment.username);
          console.log('Email auteur:', comment.email);
          console.log('Date:', new Date(comment.date).toLocaleDateString('fr-FR'));
          console.log('Texte:', comment.text);
        });
      } else {
        console.log('Aucun commentaire');
      }
      console.groupEnd();
      
      setShowProfileModal(true);
    } catch (err) {
      console.error('Erreur:', err);
      alert('Erreur lors de la récupération des détails');
    }
  };

  const handleTrashStudents = async (studentIds: number[]) => {
    try {
      setDeletingStudents(true);
      await trashStudents(studentIds);
      setShowDeleteStudentsModal(false);
      await loadStudents();
      await loadTrashedStudents();
    } catch (err) {
      console.error('Erreur lors de la mise à la corbeille:', err);
    } finally {
      setDeletingStudents(false);
    }
  };

  const handleRestoreStudents = async (studentIds: number[]) => {
    try {
      setTrashLoading(true);
      await restoreStudents(studentIds);
      await loadStudents();
      await loadTrashedStudents();
    } catch (err) {
      console.error('Erreur lors de la restauration:', err);
    } finally {
      setTrashLoading(false);
    }
  };

  const handlePermanentDeleteStudents = async (studentIds: number[]) => {
    try {
      setTrashLoading(true);
      await permanentDeleteStudents(studentIds);
      await loadTrashedStudents();
    } catch (err) {
      console.error('Erreur lors de la suppression définitive:', err);
    } finally {
      setTrashLoading(false);
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
      <div className="bg-[#4b575f] h-full relative shrink-0 flex flex-col items-center justify-between py-[20px] px-[30px] w-[220px]">
        {/* Bouton de gestion étudiants */}
        <button
          onClick={() => setShowDeleteStudentsModal(true)}
          disabled={deletingStudents}
          className="bg-[#e5e7eb] content-stretch flex items-center justify-center p-[10px] relative rounded-[4px] shrink-0 w-full hover:bg-[#d1d5db] transition-colors disabled:opacity-50"
          title="Gérer les étudiants"
        >
          <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic text-[#374151] text-[16px] whitespace-nowrap">
            {deletingStudents ? 'Traitement...' : 'Gestions étudiants'}
          </p>
        </button>

        {/* Bouton Déconnexion */}
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
                Membres
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
                        handleDeleteProfessor(prof);
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
          writtenComments={profWrittenComments}
          onClose={() => {
            setShowProfileModal(false);
            setSelectedProfessor(null);
            setProfComments([]);
            setProfWrittenComments([]);
          }}
        />
      )}

      {/* Modal de confirmation de suppression */}
      <DeleteConfirmationModal
        show={showDeleteConfirmation}
        title="Supprimer un professeur"
        message="Êtes-vous sûr de vouloir supprimer ce professeur ?"
        itemName={selectedProfessorToDelete ? `${selectedProfessorToDelete.username} (${selectedProfessorToDelete.email})` : undefined}
        isDeleting={deleting === selectedProfessorToDelete?.id}
        onConfirm={handleConfirmDelete}
        onCancel={() => {
          setShowDeleteConfirmation(false);
          setSelectedProfessorToDelete(null);
        }}
      />

      {/* Modal de gestion étudiants (mise à la corbeille) */}
      <ExportModal
        isOpen={showDeleteStudentsModal}
        onClose={() => setShowDeleteStudentsModal(false)}
        students={students}
        onDelete={handleTrashStudents}
        onOpenTrash={() => { setShowTrashModal(true); loadTrashedStudents(); }}
        isLoading={deletingStudents}
        mode="delete"
      />

      {/* Modal corbeille */}
      <TrashModal
        isOpen={showTrashModal}
        onClose={() => setShowTrashModal(false)}
        students={trashedStudents}
        onRestore={handleRestoreStudents}
        onPermanentDelete={handlePermanentDeleteStudents}
        isLoading={trashLoading}
      />
    </div>
  );
}
