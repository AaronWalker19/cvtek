import { useState, useMemo } from "react";

interface TrashedStudent {
  name: string;
  license?: string;
  userId: number;
  email?: string;
}

interface TrashModalProps {
  isOpen: boolean;
  onClose: () => void;
  students: TrashedStudent[];
  onRestore: (studentIds: number[]) => Promise<void>;
  onPermanentDelete: (studentIds: number[]) => Promise<void>;
  isLoading?: boolean;
}

export default function TrashModal({
  isOpen,
  onClose,
  students,
  onRestore,
  onPermanentDelete,
  isLoading = false,
}: TrashModalProps) {
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedStudents, setSelectedStudents] = useState<Set<number>>(new Set());
  const [showConfirm, setShowConfirm] = useState(false);

  const filteredStudents = useMemo(() => {
    return students.filter(
      (student) =>
        student.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        student.email?.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [students, searchQuery]);

  const toggleStudent = (userId: number) => {
    const newSelected = new Set(selectedStudents);
    if (newSelected.has(userId)) {
      newSelected.delete(userId);
    } else {
      newSelected.add(userId);
    }
    setSelectedStudents(newSelected);
  };

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

  const handleRestore = async () => {
    if (selectedStudents.size === 0) return;
    await onRestore(Array.from(selectedStudents));
    setSelectedStudents(new Set());
  };

  const handlePermanentDelete = async () => {
    if (selectedStudents.size === 0) return;
    setShowConfirm(true);
  };

  const confirmPermanentDelete = async () => {
    setShowConfirm(false);
    await onPermanentDelete(Array.from(selectedStudents));
    setSelectedStudents(new Set());
  };

  if (!isOpen) return null;

  return (
    <>
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
              Corbeille
            </h2>
            <button
              onClick={onClose}
              disabled={isLoading}
              className="text-[#4b575f] text-[32px] font-bold hover:text-[#36302a] transition-colors disabled:opacity-50"
            >
              x
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
                  {students.length === 0 ? 'La corbeille est vide' : 'Aucun étudiant trouvé'}
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
                        {student.license || "N/A"} - {student.email}
                      </p>
                    </div>
                  </label>
                ))
              )}
            </div>

            {/* Info */}
            <div className="bg-[#e8f4f8] border border-[#4b575f] rounded p-[15px]">
              <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[14px]">
                <span className="font-medium">{selectedStudents.size}</span> étudiant(s) sélectionné(s)
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
              Fermer
            </button>
            <button
              onClick={handleRestore}
              disabled={isLoading || selectedStudents.size === 0}
              className="px-6 py-2 rounded font-['Inter:Medium',sans-serif] font-medium text-white bg-[#4b575f] hover:bg-[#36302a] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-[8px]"
            >
              {isLoading ? 'Restauration...' : 'Restaurer'}
            </button>
            <button
              onClick={handlePermanentDelete}
              disabled={isLoading || selectedStudents.size === 0}
              className="px-6 py-2 rounded font-['Inter:Medium',sans-serif] font-medium text-white bg-[#b51621] hover:bg-[#8e1119] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-[8px]"
            >
              {isLoading ? 'Suppression...' : 'Supprimer définitivement'}
            </button>
          </div>
        </div>
      </div>

      {/* Modal de confirmation */}
      {showConfirm && (
        <div
          onClick={() => setShowConfirm(false)}
          className="fixed inset-0 bg-[#000000] bg-opacity-60 flex items-center justify-center z-[60]"
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="bg-[#ffffff] rounded-lg shadow-xl w-[90%] max-w-md p-[30px] flex flex-col gap-[20px]"
          >
            <h3 className="font-['Inter:Bold',sans-serif] font-bold text-[20px] text-[#b51621]">
              Suppression définitive
            </h3>
            <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[15px] leading-relaxed">
              Êtes-vous sûr de vouloir supprimer définitivement <span className="font-bold">{selectedStudents.size} étudiant(s)</span> ?
            </p>
            <p className="font-['Inter:Regular',sans-serif] font-normal text-[#b51621] text-[14px] leading-relaxed">
              Cette action supprimera leur compte, leurs documents, commentaires et fichiers. Cette action est irréversible.
            </p>
            <div className="flex items-center justify-end gap-[15px] pt-[10px]">
              <button
                onClick={() => setShowConfirm(false)}
                className="px-6 py-2 rounded font-['Inter:Medium',sans-serif] font-medium bg-[#f0f0f0] text-[#36302a] hover:bg-[#e0e0e0] transition-colors"
              >
                Annuler
              </button>
              <button
                onClick={confirmPermanentDelete}
                className="px-6 py-2 rounded font-['Inter:Medium',sans-serif] font-medium text-white bg-[#b51621] hover:bg-[#8e1119] transition-colors"
              >
                Confirmer la suppression
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
