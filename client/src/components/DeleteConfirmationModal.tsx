interface DeleteConfirmationModalProps {
  show: boolean;
  title: string;
  message: string;
  onConfirm: () => void;
  onCancel: () => void;
  isDeleting?: boolean;
  itemName?: string;
}

export default function DeleteConfirmationModal({
  show,
  title,
  message,
  onConfirm,
  onCancel,
  isDeleting = false,
  itemName,
}: DeleteConfirmationModalProps) {
  if (!show) return null;

  return (
    <div className="fixed inset-0 bg-[#00000050] flex items-center justify-center z-50">
      <div className="bg-[#f7f7f7] rounded-lg p-8 shadow-2xl max-w-md border-2 border-[#36302a] w-full mx-4">
        <h3 className="text-2xl font-bold text-[#36302a] mb-2">{title}</h3>
        
        <div className="mb-6">
          <p className="text-[#36302a] text-base leading-relaxed">{message}</p>
          {itemName && (
            <p className="text-[#b51621] font-semibold mt-3">
              {itemName}
            </p>
          )}
        </div>

        {/* Warning message */}
        <div className="mb-6 p-3 bg-[#fde4e4] border-l-4 border-[#b51621] rounded">
          <p className="text-[#b51621] text-sm">
            Cette action est irréversible.
          </p>
        </div>

        {/* Buttons */}
        <div className="flex gap-4">
          <button
            onClick={onCancel}
            disabled={isDeleting}
            className="flex-1 px-4 py-2 bg-[#e0e0e0] text-[#36302a] rounded font-medium hover:bg-[#d0d0d0] transition-colors disabled:opacity-50"
          >
            Annuler
          </button>
          <button
            onClick={onConfirm}
            disabled={isDeleting}
            className="flex-1 px-4 py-2 bg-[#b51621] text-[#ffffff] rounded font-medium hover:bg-[#9a1319] transition-colors disabled:opacity-50"
          >
            {isDeleting ? 'Suppression...' : 'Supprimer'}
          </button>
        </div>
      </div>
    </div>
  );
}
