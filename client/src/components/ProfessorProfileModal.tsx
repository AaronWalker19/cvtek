import { useState, useEffect } from 'react';
import { Professor, Comment } from '../api/client';

interface ProfessorProfileModalProps {
  show: boolean;
  professor: Professor | null;
  comments: Comment[];
  onClose: () => void;
}

export default function ProfessorProfileModal({
  show,
  professor,
  comments,
  onClose,
}: ProfessorProfileModalProps) {
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
        <div className="mb-6">
          <h3 className="text-xl font-semibold text-[#36302a] mb-4">
            Commentaires ({comments.length})
          </h3>

          {comments.length === 0 ? (
            <div className="text-center py-8">
              <p className="text-[#999]">Aucun commentaire pour le moment</p>
            </div>
          ) : (
            <div className="space-y-4">
              {comments.map((comment) => (
                <div
                  key={comment.id}
                  className="bg-[#ffffff] p-4 rounded border-l-4 border-[#4b575f]"
                >
                  <div className="flex justify-between items-start mb-2">
                    <p className="font-semibold text-[#36302a]">{comment.username}</p>
                    <p className="text-sm text-[#999]">
                      {new Date(comment.date).toLocaleDateString('fr-FR')}
                    </p>
                  </div>
                  <p className="text-[#36302a] text-sm">{comment.text}</p>
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
