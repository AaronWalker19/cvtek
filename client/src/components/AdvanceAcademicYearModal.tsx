import React from 'react';

interface AdvanceAcademicYearModalProps {
  isOpen: boolean;
  isLoading: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

export default function AdvanceAcademicYearModal({
  isOpen,
  isLoading,
  onConfirm,
  onCancel,
}: AdvanceAcademicYearModalProps) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-[#00000050] flex items-center justify-center z-50">
      <div className="bg-[#f7f7f7] rounded-lg p-8 shadow-2xl max-w-md border-2 border-[#36302a] w-full mx-4">
        <h3 className="text-2xl font-bold text-[#36302a] mb-4">⚠️ Avancer l'année universitaire</h3>

        <div className="space-y-4">
          {/* Yellow Alert Box */}
          <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <p className="font-semibold text-yellow-900 mb-3">Cette action va:</p>
            <ul className="space-y-2 text-yellow-800 text-sm">
              <li>✓ Augmenter d'1 l'année de tous les étudiants</li>
              <li>
                <strong>✗ Supprimer définitivement</strong> les étudiants en année 4:
                <ul className="ml-6 mt-2 space-y-1">
                  <li>✗ Compte utilisateur</li>
                  <li>✗ Tous les documents</li>
                  <li>✗ Toutes les versions</li>
                  <li>✗ Tous les commentaires</li>
                  <li>✗ Les fichiers uploads</li>
                </ul>
              </li>
            </ul>
          </div>

          {/* Red Alert Box */}
          <div className="bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <p className="font-semibold text-red-900">⚠️ Cette action est irréversible!</p>
          </div>

          <p className="text-[#36302a] font-semibold">Êtes-vous sûr de vouloir continuer?</p>
        </div>

        {/* Buttons */}
        <div className="flex gap-3 justify-end mt-6">
          <button
            onClick={onCancel}
            disabled={isLoading}
            className="px-4 py-2 bg-[#36302a] text-[#ffffff] rounded hover:bg-[#2a2420] disabled:opacity-50 font-semibold"
          >
            Annuler
          </button>
          <button
            onClick={onConfirm}
            disabled={isLoading}
            className="px-4 py-2 bg-[#b51621] text-[#ffffff] rounded hover:bg-[#8e1119] disabled:opacity-50 font-semibold"
          >
            {isLoading ? '⏳ Traitement...' : 'Confirmer l\'avancement'}
          </button>
        </div>
      </div>
    </div>
  );
}
