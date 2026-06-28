import { useState, useEffect } from "react";
import { createPortal } from "react-dom";

interface WelcomeModalProps {
  username: string;
  role: "student" | "professor" | "admin";
}

export default function WelcomeModal({ username, role }: WelcomeModalProps) {
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    const alreadySeen = sessionStorage.getItem("welcome_modal_seen");
    if (!alreadySeen) {
      setIsOpen(true);
    }
  }, []);

  if (!isOpen) return null;

  const handleClose = () => {
    sessionStorage.setItem("welcome_modal_seen", "true");
    setIsOpen(false);
  };

  const roleLabel = role === "student" ? "étudiant" : role === "professor" ? "enseignant" : "administrateur";
  const accentColor = role === "student" ? "#b51621" : "#4b575f";
  const accentHover = role === "student" ? "#932117" : "#36302a";

  const features =
    role === "student"
      ? [
          "Déposer et gérer vos CV en toute simplicité",
          "Ajouter de nouvelles versions à vos documents",
          "Consulter les commentaires et retours de vos enseignants",
        ]
      : [
          "Consulter les CV déposés par vos étudiants",
          "Laisser des commentaires et annotations sur les documents",
          "Exporter les données et suivre la progression de vos étudiants",
        ];

  return createPortal(
    <div
      className="fixed inset-0 bg-[#000000] bg-opacity-50 flex items-center justify-center z-[70]"
      onClick={handleClose}
    >
      <div
        className="bg-[#ffffff] rounded-[16px] w-[500px] overflow-hidden shadow-2xl flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="px-[32px] py-[28px] text-center" style={{ backgroundColor: accentColor }}>
          <h2 className="font-['Inter:Bold',sans-serif] font-bold text-[24px] text-[#ffffff]">
            Bienvenue sur CVTek !
          </h2>
          <p className="font-['Inter:Regular',sans-serif] font-normal text-[14px] text-[#ffffff] opacity-80 mt-[8px]">
            Bonjour {username}, vous êtes connecté en tant qu'{roleLabel}
          </p>
        </div>

        {/* Content */}
        <div className="px-[32px] py-[24px] flex flex-col gap-[20px]">
          <p className="font-['Inter:Medium',sans-serif] font-medium text-[15px]" style={{ color: accentColor }}>
            Ici vous pourrez :
          </p>

          <div className="flex flex-col gap-[12px]">
            {features.map((feature, index) => (
              <div key={index} className="flex items-start gap-[12px]">
                <div className="shrink-0 w-[24px] h-[24px] rounded-full flex items-center justify-center mt-[1px]" style={{ backgroundColor: accentColor + "1a" }}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke={accentColor} strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>
                <span className="font-['Inter:Regular',sans-serif] font-normal text-[14px] text-[#453c3e] leading-[1.5]">
                  {feature}
                </span>
              </div>
            ))}
          </div>

          {/* Help hint */}
          <div className="flex items-center gap-[12px] bg-[#f3f3f5] rounded-[12px] px-[16px] py-[14px]">
            <div className="shrink-0 w-[36px] h-[36px] rounded-full flex items-center justify-center" style={{ backgroundColor: accentColor }}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                <path d="M12 17h.01" />
              </svg>
            </div>
            <p className="font-['Inter:Regular',sans-serif] font-normal text-[13px] text-[#453c3e] leading-[1.5]">
              À la moindre interrogation, consultez le bouton d'aide <strong>en bas à droite</strong> de votre écran.
            </p>
          </div>
        </div>

        {/* Footer */}
        <div className="px-[32px] pb-[24px]">
          <button
            onClick={handleClose}
            className="w-full py-[12px] rounded-[10px] text-[#ffffff] font-['Inter:Medium',sans-serif] font-medium text-[15px] transition-colors"
            style={{ backgroundColor: accentColor }}
            onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = accentHover)}
            onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = accentColor)}
          >
            C'est parti !
          </button>
        </div>
      </div>
    </div>,
    document.body
  );
}
