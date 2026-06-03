import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../app/context/AuthContext';
import { Professor, Comment, deleteComment } from '../api/client';
import svgPaths from '../imports/PageDeBaseCoteProf/svg-9gqyfpru0n';

interface ProfessorProfileModalProps {
  show: boolean;
  professor: Professor | null;
  comments: Comment[];
  writtenComments?: Comment[];
  onClose: () => void;
}

export default function ProfessorProfileModal({
  show,
  professor,
  comments = [],
  writtenComments = [],
  onClose,
}: ProfessorProfileModalProps) {
  const [deleting, setDeleting] = useState<number | null>(null);
  const navigate = useNavigate();
  const { user } = useAuth();

  const handleDeleteComment = async (commentId: number) => {
    try {
      setDeleting(commentId);
      await deleteComment(commentId);
      // La suppression est gérée par le parent qui rechargerait les données
      alert('Commentaire supprimé avec succès');
      onClose(); // Fermer et recharger
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Erreur lors de la suppression');
      console.error('Erreur:', err);
    } finally {
      setDeleting(null);
    }
  };

  const handleCommentClick = (docVersionId: number) => {
    // Passer juste le versionId en tant que paramètre de query
    // Le système cherchera le document associé à cette version
    const route = user?.role === 'professor' ? `/professor/file/0?version=${docVersionId}` : `/file/0?version=${docVersionId}`;
    navigate(route);
    onClose();
  };

  if (!show || !professor) return null;

  return (
    <div
      className="fixed inset-0 bg-[#000000] bg-opacity-50 flex items-center justify-center z-50"
      onClick={onClose}
    >
      <div
        className="bg-[#ffffff] rounded-lg shadow-lg max-w-4xl w-[90%] max-h-[90vh] overflow-y-auto"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Modal Header */}
        <div className="flex items-center justify-between p-[30px] border-b-2 border-[#4b575f]">
          <div className="flex flex-row items-center">
            <div className="overflow-clip relative shrink-0 size-[140px]">
              <div className="absolute inset-[8.33%]">
                <svg
                  className="absolute block inset-0 size-full"
                  fill="none"
                  preserveAspectRatio="none"
                  viewBox="0 0 33.3334 33.3334"
                >
                  <path
                    clipRule="evenodd"
                    d={svgPaths.pc3f900}
                    fill="var(--fill-0, #4b575f)"
                    fillRule="evenodd"
                  />
                </svg>
              </div>
            </div>
            <div className="flex flex-col gap-[5px]">
              <h2 className="font-['Inter:Bold',sans-serif] font-bold text-[24px] text-[#4b575f]">
                {professor.username}
              </h2>
              <p className="font-['Inter:Regular',sans-serif] text-[16px] text-[#36302a]">
                {professor.email}
              </p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="text-[#4b575f] text-[32px] font-bold hover:text-[#36302a] transition-colors"
          >
            ×
          </button>
        </div>

        {/* Modal Content */}
        <div className="p-[30px]">
          {/* Comments Section */}
          {comments.length > 0 && (
            <div className="mb-8">
              <h3 className="text-lg font-semibold text-[#36302a] mb-4 font-['Inter:Medium',sans-serif]">
                Commentaires reçus ({comments.length})
              </h3>

              <div className="grid grid-cols-3 gap-4">
                {comments.map((comment) => (
                  <div
                    key={comment.id}
                    onClick={() => handleCommentClick(comment.id_docversion)}
                    className="bg-gradient-to-br from-[#ffffff] to-[#f9f9f9] p-4 rounded-lg border-l-4 border-[#4b575f] shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                  >
                    <div className="flex justify-between items-start mb-3">
                      <div className="flex-1">
                        <p className="font-bold text-[#36302a] text-sm">{comment.username}</p>
                        <p className="text-xs text-[#999] mt-1">
                          {new Date(comment.date).toLocaleDateString('fr-FR', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                          })}
                        </p>
                      </div>
                    </div>
                    <p className="text-[#36302a] text-xs leading-relaxed bg-[#fafafa] p-2 rounded border border-[#e0e0e0]">
                      {comment.text}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Written Comments Section */}
          <div>
            <h3 className="text-lg font-semibold text-[#36302a] mb-4 font-['Inter:Medium',sans-serif]">
              Commentaires écrits ({writtenComments.length})
            </h3>

            {writtenComments.length === 0 ? (
              <div className="text-center py-12 bg-[#f5f5f5] rounded-lg border border-[#e0e0e0]">
                <p className="text-[#999]">Aucun commentaire écrit</p>
              </div>
            ) : (
              <div className="grid grid-cols-3 gap-4">
                {writtenComments.map((comment) => (
                  <div
                    key={comment.id}
                    onClick={() => handleCommentClick(comment.id_docversion)}
                    className="bg-gradient-to-br from-[#ffffff] to-[#f9f9f9] p-4 rounded-lg border-l-4 border-[#4b575f] shadow-sm hover:shadow-md transition-shadow cursor-pointer relative group"
                  >
                    <div className="flex justify-between items-start mb-3">
                      <div className="flex-1">
                        <p className="font-bold text-[#36302a] text-sm">Réponse à l'étudiant</p>
                        <p className="text-xs text-[#999] mt-1">
                          {new Date(comment.date).toLocaleDateString('fr-FR', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                          })}
                        </p>
                      </div>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDeleteComment(comment.id);
                        }}
                        disabled={deleting === comment.id}
                        className="ml-2 bg-[#b51621] text-white px-2 py-1 rounded text-xs hover:bg-[#8e1119] transition-colors disabled:opacity-50"
                      >
                        {deleting === comment.id ? '...' : 'Supprimer'}
                      </button>
                    </div>
                    <p className="text-[#36302a] text-xs leading-relaxed bg-[#fafafa] p-2 rounded border border-[#e0e0e0]">
                      {comment.text}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
