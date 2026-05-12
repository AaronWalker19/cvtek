import { useState, useEffect } from 'react';

interface Document {
  id: number;
  user_id: number;
  nom_fichier: string;
  titre?: string;
  type_fichier: string;
  url_fichier: string;
  description?: string;
  version: number;
  created_at: string;
  updated_at?: string;
}

interface EditDocumentModalProps {
  show: boolean;
  doc: Document | null;
  onClose: () => void;
  onSave: (titre: string, description: string) => Promise<void>;
  isSaving?: boolean;
}

export default function EditDocumentModal({
  show,
  doc,
  onClose,
  onSave,
  isSaving = false,
}: EditDocumentModalProps) {
  const [titre, setTitre] = useState('');
  const [description, setDescription] = useState('');

  useEffect(() => {
    if (doc) {
      setTitre(doc.titre || '');
      setDescription(doc.description || '');
    }
  }, [doc, show]);

  if (!show || !doc) return null;

  const handleSave = async () => {
    await onSave(titre, description);
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
      <div className="bg-[#f7f7f7] rounded-lg p-8 shadow-2xl max-w-md border-2 border-[#36302a] w-full mx-4">
        <h3 className="text-2xl font-bold text-[#36302a] mb-6">Modifier le fichier</h3>

        {/* Nom du fichier (lecture seule) */}
        <div className="mb-6">
          <label className="block text-[#36302a] text-sm font-medium mb-2">Nom du fichier</label>
          <div className="p-3 bg-[#e0e0e0] rounded text-[#36302a] text-sm">
            {doc.nom_fichier}
          </div>
        </div>

        {/* Titre */}
        <div className="mb-6">
          <label htmlFor="titre" className="block text-[#36302a] text-sm font-medium mb-2">
            Titre
          </label>
          <input
            id="titre"
            type="text"
            value={titre}
            onChange={(e) => setTitre(e.target.value)}
            placeholder="Ajouter un titre (optionnel)"
            className="w-full px-4 py-2 border-2 border-[#36302a] rounded font-['Inter:Regular',sans-serif] text-[#36302a] focus:outline-none focus:border-[#b51621] transition-colors"
          />
        </div>

        {/* Description */}
        <div className="mb-6">
          <label htmlFor="description" className="block text-[#36302a] text-sm font-medium mb-2">
            Description
          </label>
          <textarea
            id="description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Ajouter une description (optionnelle)"
            className="w-full px-4 py-2 border-2 border-[#36302a] rounded font-['Inter:Regular',sans-serif] text-[#36302a] focus:outline-none focus:border-[#b51621] transition-colors resize-none"
            rows={4}
          />
        </div>

        {/* Buttons */}
        <div className="flex gap-4">
          <button
            onClick={onClose}
            disabled={isSaving}
            className="flex-1 px-4 py-2 bg-[#e0e0e0] text-[#36302a] rounded font-medium hover:bg-[#d0d0d0] transition-colors disabled:opacity-50"
          >
            Annuler
          </button>
          <button
            onClick={handleSave}
            disabled={isSaving}
            className="flex-1 px-4 py-2 bg-[#b51621] text-white rounded font-medium hover:bg-[#9a1319] transition-colors disabled:opacity-50"
          >
            {isSaving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </div>
      </div>
    </div>
  );
}
