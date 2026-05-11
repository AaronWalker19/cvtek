import { useRef } from 'react';

interface Document {
  id: number;
  user_id: number;
  nom_fichier: string;
  titre?: string;
  type_fichier: string;
  url_fichier: string;
  description?: string;
  version: number;
  comment_count?: number;
  created_at: string;
  updated_at?: string;
}

interface NewVersionModalProps {
  show: boolean;
  doc: Document | null;
  selectedFile: File | null;
  isDragging: boolean;
  uploadingVersion: boolean;
  accentColor?: string;
  onClose: () => void;
  onFileSelected: (file: File) => void;
  onUpload: () => Promise<void>;
  onDragOver?: (e: React.DragEvent<HTMLDivElement>) => void;
  onDragLeave?: (e: React.DragEvent<HTMLDivElement>) => void;
  onDrop?: (e: React.DragEvent<HTMLDivElement>) => void;
}

export default function NewVersionModal({
  show,
  doc,
  selectedFile,
  isDragging,
  uploadingVersion,
  accentColor = '#b51621',
  onClose,
  onFileSelected,
  onUpload,
  onDragOver,
  onDragLeave,
  onDrop,
}: NewVersionModalProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const dropZoneRef = useRef<HTMLDivElement>(null);

  if (!show || !doc) return null;

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      onFileSelected(file);
    }
  };

  const handleDragOverInternal = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    onDragOver?.(e);
  };

  const handleDragLeaveInternal = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    onDragLeave?.(e);
  };

  const handleDropInternal = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    onDrop?.(e);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
      <div className="bg-[#f7f7f7] rounded-lg p-8 shadow-2xl max-w-md border-2 border-[#36302a]">
        <h3 className="text-2xl font-bold text-[#36302a] mb-6">Proposer une nouvelle version</h3>

        {/* Version actuelle */}
        <div className="mb-4">
          <p className="text-[#36302a] text-sm font-medium">
            Fichier actuel: <span style={{ color: accentColor }} className="font-bold">v{doc.version || 1}.0</span>
          </p>
        </div>

        {/* Nouvelle version */}
        <div className="mb-6">
          <p className="text-[#36302a] text-sm font-medium">
            La nouvelle version sera: <span style={{ color: accentColor }} className="font-bold">v{(doc.version || 1) + 1}.0</span>
          </p>
        </div>

        {/* Fichier actuel */}
        <div className="mb-6 p-4 bg-[#ffffff] border-2 border-[#e0e0e0] rounded">
          <p className="text-[#36302a] text-xs text-center font-medium mb-1">Fichier actuel</p>
          <p style={{ color: accentColor }} className="text-center font-bold text-sm break-all">
            {doc.nom_fichier}
          </p>
        </div>

        {/* Sélection du nouveau fichier avec drag and drop */}
        <div
          ref={dropZoneRef}
          onClick={() => fileInputRef.current?.click()}
          onDragOver={handleDragOverInternal}
          onDragLeave={handleDragLeaveInternal}
          onDrop={handleDropInternal}
          className={`border-2 border-dashed rounded-lg p-6 mb-6 text-center cursor-pointer transition-colors ${
            isDragging
              ? 'bg-gray-200 border-[#36302a]'
              : 'hover:bg-gray-50 border-[#a4a4a4]'
          } bg-[#ffffff]`}
        >
          {selectedFile ? (
            <div>
              <p style={{ color: accentColor }} className="font-bold text-sm">
                {selectedFile.name}
              </p>
              <p className="text-[#a4a4a4] text-xs mt-2">Cliquez ou glissez pour changer</p>
            </div>
          ) : (
            <div>
              <p className="text-[#a4a4a4] font-medium">Cliquez ou glissez pour changer</p>
            </div>
          )}
        </div>

        <input
          ref={fileInputRef}
          type="file"
          onChange={handleFileChange}
          className="hidden"
        />

        <div className="flex gap-4">
          <button
            onClick={onClose}
            disabled={uploadingVersion}
            className="flex-1 px-4 py-2 bg-[#e0e0e0] text-[#36302a] rounded font-medium hover:bg-[#d0d0d0] transition-colors disabled:opacity-50"
          >
            Annuler
          </button>
          <button
            onClick={onUpload}
            disabled={!selectedFile || uploadingVersion}
            className="flex-1 px-4 py-2 text-white rounded font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
            style={{ backgroundColor: accentColor }}
          >
            {uploadingVersion ? 'Upload...' : 'Valider'}
          </button>
        </div>
      </div>
    </div>
  );
}
