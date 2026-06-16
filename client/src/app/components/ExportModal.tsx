import { useState, useMemo } from "react";

interface Student {
  name: string;
  license?: string;
  userId: number;
  email?: string;
}

interface ExportModalProps {
  isOpen: boolean;
  onClose: () => void;
  students: Student[];
  onExport?: (studentIds: number[]) => Promise<void>;
  onDelete?: (studentIds: number[]) => Promise<void>;
  isLoading?: boolean;
  subscriptions?: Set<number>;
  mode?: 'export' | 'delete';
}

export default function ExportModal({
  isOpen,
  onClose,
  students,
  onExport,
  onDelete,
  isLoading = false,
  subscriptions = new Set(),
  mode = 'export',
}: ExportModalProps) {
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedLicenses, setSelectedLicenses] = useState<Set<string>>(new Set());
  const [selectedFollowStatus, setSelectedFollowStatus] = useState<Set<'followed' | 'unfollowed'>>(new Set());
  // En mode suppression, ne rien présélectionner par sécurité (action destructive)
  const [selectedStudents, setSelectedStudents] = useState<Set<number>>(
    mode === 'delete' ? new Set() : new Set(students.map(s => s.userId))
  );

  // Récupérer les licences uniques
  const uniqueLicenses = useMemo(() => {
    const licenses = new Set<string>();
    students.forEach((student) => {
      if (student.license && student.license !== "N/A") {
        licenses.add(student.license);
      }
    });
    return Array.from(licenses).sort();
  }, [students]);

  // Filtrer les étudiants
  const filteredStudents = useMemo(() => {
    let result = students.filter(
      (student) =>
        student.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        student.email?.toLowerCase().includes(searchQuery.toLowerCase())
    );

    if (selectedLicenses.size > 0) {
      result = result.filter((student) =>
        selectedLicenses.has(student.license || "")
      );
    }

    if (selectedFollowStatus.size > 0) {
      result = result.filter((student) => {
        const isFollowed = subscriptions.has(student.userId);
        if (selectedFollowStatus.has('followed') && isFollowed) return true;
        if (selectedFollowStatus.has('unfollowed') && !isFollowed) return true;
        return false;
      });
    }

    return result;
  }, [students, searchQuery, selectedLicenses, selectedFollowStatus, subscriptions]);

  // Basculer la sélection d'un étudiant
  const toggleStudent = (userId: number) => {
    const newSelected = new Set(selectedStudents);
    if (newSelected.has(userId)) {
      newSelected.delete(userId);
    } else {
      newSelected.add(userId);
    }
    setSelectedStudents(newSelected);
  };

  // Basculer la licence
  const toggleLicense = (license: string) => {
    const newSelected = new Set(selectedLicenses);
    if (newSelected.has(license)) {
      newSelected.delete(license);
    } else {
      newSelected.add(license);
    }
    setSelectedLicenses(newSelected);
  };

  // Basculer le statut de suivi
  const toggleFollowStatus = (status: 'followed' | 'unfollowed') => {
    const newSelected = new Set(selectedFollowStatus);
    if (newSelected.has(status)) {
      newSelected.delete(status);
    } else {
      newSelected.add(status);
    }
    setSelectedFollowStatus(newSelected);
  };

  // Sélectionner/désélectionner tous les étudiants filtrés
  const toggleAllFiltered = () => {
    const newSelected = new Set(selectedStudents);
    const allFiltered = filteredStudents.every(s => newSelected.has(s.userId));

    if (allFiltered) {
      filteredStudents.forEach(s => newSelected.delete(s.userId));
    } else {
      filteredStudents.forEach(s => newSelected.add(s.userId));
    }
    setSelectedStudents(newSelected);
  };

  // Gérer l'action principale (export ou suppression)
  const handleAction = async () => {
    if (selectedStudents.size === 0) {
      alert("Veuillez sélectionner au moins un étudiant");
      return;
    }

    if (mode === 'delete') {
      const confirmed = window.confirm(
        `⚠️ Êtes-vous sûr de vouloir supprimer définitivement ${selectedStudents.size} étudiant(s) ?\n\n` +
        `Cette action supprimera leur compte, leurs documents, commentaires et fichiers. Cette action est irréversible.`
      );
      if (!confirmed) return;
      await onDelete?.(Array.from(selectedStudents));
    } else {
      await onExport?.(Array.from(selectedStudents));
    }
  };

  if (!isOpen) return null;

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 bg-[#000000] bg-opacity-50 flex items-center justify-center z-50"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="bg-[#ffffff] rounded-lg shadow-lg w-[90%] max-w-2xl max-h-[85vh] overflow-y-auto flex flex-col"
      >
        {/* Header */}
        <div className="flex items-center justify-between p-[30px] border-b-2 border-[#4b575f] shrink-0">
          <h2 className="font-['Inter:Bold',sans-serif] font-bold text-[24px] text-[#4b575f]">
            {mode === 'delete' ? 'Supprimer des étudiants' : 'Exporter les fichiers des étudiants'}
          </h2>
          <button
            onClick={onClose}
            disabled={isLoading}
            className="text-[#4b575f] text-[32px] font-bold hover:text-[#36302a] transition-colors disabled:opacity-50"
          >
            ×
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-[30px] flex flex-col gap-[20px]">
          {/* Search Bar */}
          <div className="bg-[#ffffff] rounded-[51px] border border-[#4b575f]">
            <div className="flex flex-row items-center size-full">
              <div className="content-stretch flex gap-[10px] items-center px-[15px] py-[10px] relative size-full">
                <div className="relative shrink-0 size-[24px]">
                  <svg
                    className="absolute block inset-0 size-full"
                    fill="none"
                    preserveAspectRatio="none"
                    viewBox="0 0 17.575 17.575"
                  >
                    <path
                      d="M14.4 15.6l2.9 2.9m-6.325-2.025a5.35 5.35 0 1 1 0-10.7 5.35 5.35 0 0 1 0 10.7Z"
                      fill="none"
                      stroke="#4B575F"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="1.5"
                    />
                  </svg>
                </div>
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Rechercher un étudiant..."
                  className="flex-1 font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#4b575f] text-[16px]"
                  disabled={isLoading}
                />
              </div>
            </div>
          </div>

          {/* Filters */}
          {uniqueLicenses.length > 0 && (
            <div className="bg-[#f5f5f5] rounded-lg p-[15px] border border-[#d9d9d9]">
              <p className="font-['Inter:Medium',sans-serif] font-medium text-[#36302a] text-[14px] mb-[10px]">
                Filtrer par licence/parcours :
              </p>
              <div className="flex flex-wrap gap-[10px]">
                {uniqueLicenses.map((license) => (
                  <label
                    key={license}
                    className="flex items-center gap-[8px] cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      checked={selectedLicenses.has(license)}
                      onChange={() => toggleLicense(license)}
                      className="w-[18px] h-[18px] cursor-pointer"
                      disabled={isLoading}
                    />
                    <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[14px]">
                      {license}
                    </span>
                  </label>
                ))}
              </div>
            </div>
          )}

          {/* Filtre Suivi */}
          {mode !== 'delete' && (
            <div className="bg-[#f5f5f5] rounded-lg p-[15px] border border-[#d9d9d9]">
              <p className="font-['Inter:Medium',sans-serif] font-medium text-[#36302a] text-[14px] mb-[10px]">
                Statut de suivi :
              </p>
              <div className="flex flex-wrap gap-[15px]">
                <label className="flex items-center gap-[8px] cursor-pointer">
                  <input
                    type="checkbox"
                    checked={selectedFollowStatus.has('followed')}
                    onChange={() => toggleFollowStatus('followed')}
                    className="w-[18px] h-[18px] cursor-pointer"
                    disabled={isLoading}
                  />
                  <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[14px]">
                    Suivi
                  </span>
                </label>
                <label className="flex items-center gap-[8px] cursor-pointer">
                  <input
                    type="checkbox"
                    checked={selectedFollowStatus.has('unfollowed')}
                    onChange={() => toggleFollowStatus('unfollowed')}
                    className="w-[18px] h-[18px] cursor-pointer"
                    disabled={isLoading}
                  />
                  <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[14px]">
                    Non suivi
                  </span>
                </label>
              </div>
            </div>
          )}

          {/* Reset Filters Button */}
          {(selectedLicenses.size > 0 || selectedFollowStatus.size > 0) && (
            <button
              onClick={() => {
                setSelectedLicenses(new Set());
                setSelectedFollowStatus(new Set());
              }}
              className="w-full px-4 py-2 bg-[#4b575f] text-white rounded font-['Inter:Medium',sans-serif] font-medium hover:bg-[#36302a] transition-colors text-[14px]"
              disabled={isLoading}
            >
              Réinitialiser les filtres
            </button>
          )}

          {/* Select All */}
          {filteredStudents.length > 0 && (
            <div className="flex items-center gap-[10px] p-[10px] bg-[#f5f5f5] rounded border border-[#d9d9d9]">
              <input
                type="checkbox"
                checked={filteredStudents.every(s => selectedStudents.has(s.userId))}
                onChange={toggleAllFiltered}
                className="w-[18px] h-[18px] cursor-pointer"
                disabled={isLoading}
              />
              <span className="font-['Inter:Medium',sans-serif] font-medium text-[#36302a] text-[14px]">
                Sélectionner tous ({filteredStudents.length})
              </span>
            </div>
          )}

          {/* Students List */}
          <div className="flex flex-col gap-[10px] border border-[#d9d9d9] rounded">
            {filteredStudents.length === 0 ? (
              <div className="p-[20px] text-center text-[#36302a] font-['Inter:Regular',sans-serif]">
                Aucun étudiant trouvé
              </div>
            ) : (
              filteredStudents.map((student) => (
                <label
                  key={student.userId}
                  className="flex items-center gap-[12px] px-[15px] py-[12px] border-b border-[#e0e0e0] last:border-b-0 cursor-pointer hover:bg-[#f9f9f9] transition-colors"
                >
                  <input
                    type="checkbox"
                    checked={selectedStudents.has(student.userId)}
                    onChange={() => toggleStudent(student.userId)}
                    className="w-[18px] h-[18px] cursor-pointer"
                    disabled={isLoading}
                  />
                  <div className="flex-1 flex flex-col gap-[2px]">
                    <p className="font-['Inter:Medium',sans-serif] font-medium text-[#36302a] text-[14px]">
                      {student.name}
                    </p>
                    <p className="font-['Inter:Regular',sans-serif] font-normal text-[#888888] text-[12px]">
                      {student.license || "N/A"} • {student.email}
                    </p>
                  </div>
                </label>
              ))
            )}
          </div>

          {/* Info */}
          <div className="bg-[#e8f4f8] border border-[#4b575f] rounded p-[15px]">
            <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[14px]">
              <span className="font-medium">{selectedStudents.size}</span> étudiant(s) sélectionné(s) pour {mode === 'delete' ? 'la suppression' : "l'export"}
            </p>
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end gap-[15px] p-[30px] border-t border-[#d9d9d9] shrink-0">
          <button
            onClick={onClose}
            disabled={isLoading}
            className="px-6 py-2 rounded font-['Inter:Medium',sans-serif] font-medium bg-[#f0f0f0] text-[#36302a] hover:bg-[#e0e0e0] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Annuler
          </button>
          <button
            onClick={handleAction}
            disabled={isLoading || selectedStudents.size === 0}
            className={`px-6 py-2 rounded font-['Inter:Medium',sans-serif] font-medium text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-[8px] ${
              mode === 'delete' ? 'bg-[#b51621] hover:bg-[#8e1119]' : 'bg-[#4b575f] hover:bg-[#36302a]'
            }`}
          >
            {isLoading ? (
              <>
                <span className="inline-block animate-spin">⏳</span>
                {mode === 'delete' ? 'Suppression...' : 'Préparation...'}
              </>
            ) : mode === 'delete' ? (
              <>
                🗑️ Supprimer
              </>
            ) : (
              <>
                📥 Télécharger ZIP
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
