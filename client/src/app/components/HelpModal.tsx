import { useState } from "react";
import { createPortal } from "react-dom";

interface HelpItem {
  question: string;
  videoUrl: string;
}

const studentHelpItems: HelpItem[] = [
  {
    question: "Comment déposer un CV ?",
    videoUrl: "/cvtek/video/vidéo1.mp4",
  },
  {
    question: "Comment ajouter une nouvelle version de mon CV ?",
    videoUrl: "/cvtek/video/vidéo2.mp4",
  },
  {
    question: "Comment modifier les informations d'un document ?",
    videoUrl: "/cvtek/video/vidéo3.mp4",
  },
  {
    question: "Comment consulter les commentaires de mon enseignant ?",
    videoUrl: "/cvtek/video/vidéo4.mp4",
  },
];

const professorHelpItems: HelpItem[] = [
  {
    question: "Comment consulter les CV de mes étudiants ?",
    videoUrl: "/cvtek/video/vidéo5.mp4",
  },
  {
    question: "Comment laisser un commentaire sur un CV ?",
    videoUrl: "/cvtek/video/vidéo6.mp4",
  },
  {
    question: "Comment exporter les fichiers des étudiants ?",
    videoUrl: "/cvtek/video/vidéo7.mp4",
  },
];

interface HelpModalProps {
  isOpen: boolean;
  onClose: () => void;
  role: "student" | "professor" | "admin";
}

export default function HelpModal({ isOpen, onClose, role }: HelpModalProps) {
  const [selectedItem, setSelectedItem] = useState<HelpItem | null>(null);

  if (!isOpen) return null;

  const helpItems = role === "student" ? studentHelpItems : professorHelpItems;

  const handleClose = () => {
    setSelectedItem(null);
    onClose();
  };

  const handleBack = () => {
    setSelectedItem(null);
  };

  return createPortal(
    <div
      className="fixed inset-0 bg-[#000000] bg-opacity-50 flex items-center justify-center z-[60]"
      onClick={handleClose}
    >
      <div
        className="bg-[#ffffff] rounded-[16px] w-[600px] max-h-[80vh] overflow-hidden shadow-2xl flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-[24px] py-[20px] border-b border-[#e9ebef]">
          <div className="flex items-center gap-[12px]">
            {selectedItem && (
              <button
                onClick={handleBack}
                className="p-[4px] rounded-[6px] hover:bg-[#f3f3f5] transition-colors"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#183542" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M19 12H5" />
                  <path d="m12 19-7-7 7-7" />
                </svg>
              </button>
            )}
            <div className="flex items-center gap-[10px]">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#183542" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                <path d="M12 17h.01" />
              </svg>
              <h2 className="font-['Inter:Bold',sans-serif] font-bold text-[20px] text-[#183542]">
                {selectedItem ? selectedItem.question : "Centre d'aide"}
              </h2>
            </div>
          </div>
          <button
            onClick={handleClose}
            className="p-[6px] rounded-[8px] hover:bg-[#f3f3f5] transition-colors"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#717182" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto">
          {selectedItem ? (
            <div className="p-[24px]">
              <div className="w-full aspect-video rounded-[12px] overflow-hidden bg-[#0d1f28]">
                <video
                  src={selectedItem.videoUrl}
                  controls
                  className="w-full h-full"
                />
              </div>
            </div>
          ) : (
            <div className="p-[16px] flex flex-col gap-[8px]">
              {helpItems.map((item, index) => (
                <button
                  key={index}
                  onClick={() => setSelectedItem(item)}
                  className="w-full text-left px-[20px] py-[16px] rounded-[12px] hover:bg-[#f3f3f5] transition-colors flex items-center gap-[14px] group"
                >
                  <div className="shrink-0 w-[40px] h-[40px] rounded-[10px] bg-[#183542] bg-opacity-10 flex items-center justify-center group-hover:bg-opacity-20 transition-colors">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#183542" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <polygon points="6 3 20 12 6 21 6 3" />
                    </svg>
                  </div>
                  <span className="font-['Inter:Medium',sans-serif] font-medium text-[15px] text-[#183542]">
                    {item.question}
                  </span>
                  <svg className="ml-auto shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#717182" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="m9 18 6-6-6-6" />
                  </svg>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>,
    document.body
  );
}
