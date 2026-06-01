import { useState } from 'react';
import { Professor, Comment, deleteComment } from '../api/client';

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

  if (!show || !professor) return null;

  return (
    <div
      className="fixed inset-0 bg-[#00000050] flex items-center justify-center z-50"
      onClick={onClose}
    >
      <div
        className="bg-[#f7f7f7] rounded-lg p-8 shadow-2xl max-w-2xl border-2 border-[#36302a] w-full mx-4 max-h-[90vh] overflow-y-auto"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="mb-6 pb-4 border-b-2 border-[#36302a]">
          <h2 className="text-3xl font-bold text-[#36302a]">{professor.username}</h2>
          <p className="text-[#666] mt-2">{professor.email}</p>
        </div>

        {/* Comments Section */}
        {comments.length > 0 && (
          <div className="mb-6">
            <h3 className="text-xl font-semibold text-[#36302a] mb-4">
              Commentaires ({comments.length})
            </h3>

            <div className="grid grid-cols-1 gap-4">
              {comments.map((comment) => (
                <div
                  key={comment.id}
                  className="bg-gradient-to-br from-[#ffffff] to-[#f9f9f9] p-5 rounded-lg border-l-4 border-[#4b575f] shadow-md hover:shadow-lg transition-shadow"
                >
                  <div className="flex justify-between items-start mb-3">
                    <div className="flex-1">
                      <p className="font-bold text-[#36302a] text-base">{comment.username}</p>
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
                  <p className="text-[#36302a] text-sm leading-relaxed bg-[#fafafa] p-3 rounded border border-[#e0e0e0]">
                    {comment.text}
                  </p>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Written Comments Section */}
        <div className="mb-6">
          <h3 className="text-xl font-semibold text-[#36302a] mb-4">
            Commentaires écrits ({writtenComments.length})
          </h3>

          {writtenComments.length === 0 ? (
            <div className="text-center py-12 bg-[#f5f5f5] rounded-lg border-2 border-dashed border-[#d0d0d0]">
              <p className="text-[#999] text-lg">Aucun commentaire écrit</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-4">
              {writtenComments.map((comment) => (
                <div
                  key={comment.id}
                  className="bg-gradient-to-br from-[#ffffff] to-[#f9f9f9] p-5 rounded-lg border-l-4 border-[#2ecc71] shadow-md hover:shadow-lg transition-shadow"
                >
                  <div className="flex justify-between items-start mb-3">
                    <div className="flex-1">
                      <p className="font-bold text-[#36302a] text-base">Réponse à l'étudiant</p>
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
                      onClick={() => handleDeleteComment(comment.id)}
                      disabled={deleting === comment.id}
                      className="ml-4 bg-[#b51621] text-white px-3 py-1 rounded text-sm hover:bg-[#8e1119] transition-colors disabled:opacity-50"
                    >
                      {deleting === comment.id ? '...' : 'Supprimer'}
                    </button>
                  </div>
                  <p className="text-[#36302a] text-sm leading-relaxed bg-[#fafafa] p-3 rounded border border-[#e0e0e0] mb-3">
                    {comment.text}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Close Button */}
        <div className="flex gap-4 justify-end pt-4 border-t-2 border-[#36302a]">
          <button
            onClick={onClose}
            className="px-6 py-2 bg-[#4b575f] text-[#ffffff] rounded font-medium hover:bg-[#3a444b] transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
}
