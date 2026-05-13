import { useState, useEffect } from "react";
import { mockUsers } from '../data/mockData';
import Sidebar from "../components/Sidebar";
import svgPaths from "../../imports/PageDeBaseCoteProf/svg-9gqyfpru0n";

export default function ProfessorDashboard() {
  const [searchQuery, setSearchQuery] = useState("");
  const [showFilters, setShowFilters] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState<{
    name: string;
    license?: string;
    userId: number;
    email?: string;
  } | null>(null);
  const [allDocuments, setAllDocuments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Charger les documents (mode démo)
  useEffect(() => {
    try {
      // Données mock des documents pour tous les étudiants
      const mockDocs = [
        {
          id: 1,
          user_id: 1,
          nom_fichier: 'CV_JeanDupont',
          titre: 'CV - Première année',
          type_fichier: 'CV',
          url_fichier: '/uploads/cv_2026.pdf',
          description: 'Mon curriculum vitae pour la première année',
          version: 2,
          comment_count: 2,
          user: { name: 'Jean Dupont', email: 'jean.dupont@example.com' },
          created_at: '2026-01-28',
        },
        {
          id: 2,
          user_id: 1,
          nom_fichier: 'ProjetMMI',
          titre: 'Projet MMI - Site Web',
          type_fichier: 'Projet',
          url_fichier: '/uploads/projet_mmi.zip',
          description: 'Site web responsive avec React et Tailwind',
          version: 1,
          comment_count: 1,
          user: { name: 'Jean Dupont', email: 'jean.dupont@example.com' },
          created_at: '2026-02-10',
        }
      ];
      setAllDocuments(mockDocs);
    } catch (error) {
      console.error('Erreur lors du chargement des documents:', error);
      setAllDocuments([]);
    } finally {
      setLoading(false);
    }
  }, []);

  // Obtenir les étudiants uniques qui ont au moins 1 commentaire
  const studentsWithComments = Array.from(
    allDocuments
      .filter((doc) => doc.comment_count && doc.comment_count > 0)
      .reduce(
        (acc: Map<number, { name: string; license?: string; userId: number; email?: string }>, doc) => {
          if (!acc.has(doc.user_id)) {
            const user = mockUsers.find((u) => u.id === String(doc.user_id));
            acc.set(doc.user_id, {
              name: doc.user?.name || 'Utilisateur inconnu',
              license: user?.license || doc.user?.license,
              userId: doc.user_id,
              email: user?.email || doc.user?.email,
            });
          }
          return acc;
        },
        new Map(),
      )
      .values(),
  );

  const filteredStudents = studentsWithComments.filter(
    (student) =>
      student.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      student.license?.toLowerCase().includes(searchQuery.toLowerCase()),
  );

  // Récupérer les documents d'un étudiant
  const getStudentDocuments = (userId: number) => {
    return allDocuments.filter((doc) => doc.user_id === userId);
  };

  return (
    <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor="bg-[#4b575f]" />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div
                aria-hidden="true"
                className="absolute border-[#4b575f] border-b-3 border-solid inset-0 pointer-events-none"
              />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] whitespace-nowrap">
                Documents postée
              </p>
            </div>

            {/* Search and Filter */}
            <div className="content-stretch flex gap-[17px] items-center relative shrink-0 w-full">
              <div className="bg-[#ffffff] flex-[1_0_0] min-w-px relative rounded-[51px]">
                <div
                  aria-hidden="true"
                  className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[51px]"
                />
                <div className="flex flex-row items-center size-full">
                  <div className="content-stretch flex gap-[10px] items-center p-[10px] relative size-full">
                    <div className="relative shrink-0 size-[24px]">
                      <div className="absolute inset-[12.5%_14.27%_14.27%_12.5%]">
                        <svg
                          className="absolute block inset-0 size-full"
                          fill="none"
                          preserveAspectRatio="none"
                          viewBox="0 0 17.575 17.575"
                        >
                          <path
                            d={svgPaths.p1351f980}
                            fill="var(--fill-0, #4B575F)"
                          />
                        </svg>
                      </div>
                    </div>
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      placeholder="barre de recherche"
                      className="flex-1 font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#4b575f] text-[20px]"
                    />
                  </div>
                </div>
              </div>
              <button
                onClick={() => setShowFilters(!showFilters)}
                className="bg-[#4b575f] content-stretch flex gap-[10px] items-center p-[10px] relative rounded-[76px] shrink-0"
              >
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[20px] text-white whitespace-nowrap">
                  Filtre
                </p>
                <div className="relative shrink-0 size-[24px]">
                  <div className="absolute inset-[12.5%_16.66%_12.49%_16.66%]">
                    <svg
                      className="absolute block inset-0 size-full"
                      fill="none"
                      preserveAspectRatio="none"
                      viewBox="0 0 16.0022 18.0024"
                    >
                      <path
                        d={svgPaths.p1954e540}
                        fill="var(--fill-0, white)"
                      />
                    </svg>
                  </div>
                </div>
              </button>
            </div>

            {/* Documents Table */}
            <div className="relative shrink-0 w-full">
              <div className="content-stretch flex flex-col gap-[28px] items-start px-[20px] relative size-full">
                <div className="content-stretch flex font-['Inter:Medium',sans-serif] font-medium items-center justify-between leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[24px] w-full whitespace-nowrap">
                  <p className="relative shrink-0">étudiant</p>
                  <p className="relative shrink-0">licence</p>
                </div>

                <div className="content-stretch flex flex-col gap-[15px] items-start relative shrink-0 w-full">
                  {filteredStudents.map((student, index) => (
                    <div
                      key={index}
                      onClick={() => setSelectedStudent(student)}
                      className="content-stretch flex items-start py-[10px] relative shrink-0 w-full hover:bg-gray-50 cursor-pointer"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border-[#36302a] border-b border-solid inset-0 pointer-events-none"
                      />
                      <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                        <p className="leading-[normal]">{student.name}</p>
                      </div>
                      <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-end">
                        <p className="leading-[normal]">{student.license}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Modal Popup */}
      {selectedStudent && (
        <div
          onClick={() => setSelectedStudent(null)}
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="bg-[#ffffff] rounded-lg shadow-lg max-w-4xl w-[90%] max-h-[90vh] overflow-y-auto"
          >
            {/* Modal Header */}
            <div className="flex items-center justify-between p-[30px] border-b-2 border-[#4b575f]">
              <div className="flex flex-row items-center">
                <div className="overflow-clip relative shrink-0 size-[140px]">
                  <div className="absolute inset-[8.33%]">
                    <svg
                      className="absolute block inset-0 size-full"
                      fill="none"
                      preserveAspectRatio="none"
                      viewBox="0 0 33.3334 33.3334"
                    >
                      <path
                        clipRule="evenodd"
                        d={svgPaths.pc3f900}
                        fill="var(--fill-0, #4b575f)"
                        fillRule="evenodd"
                      />
                    </svg>
                  </div>
                </div>
                <div className="flex flex-col gap-[5px]">
                  <h2 className="font-['Inter:Bold',sans-serif] font-bold text-[24px] text-[#4b575f]">
                    {selectedStudent.name}
                  </h2>
                  <p className="font-['Inter:Regular',sans-serif] text-[16px] text-[#36302a]">
                    {selectedStudent.license}
                  </p>
                  <p className="font-['Inter:Regular',sans-serif] text-[16px] text-[#36302a]">
                    {selectedStudent.email}
                  </p>
                </div>
              </div>

              <button
                onClick={() => setSelectedStudent(null)}
                className="text-[#4b575f] text-[32px] font-bold hover:text-[#36302a]"
              >
                ×
              </button>
            </div>

            {/* Modal Content - Files List */}
            <div className="p-[30px]">
              <div className="content-stretch flex font-['Inter:Medium',sans-serif] font-medium items-center justify-between leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[18px] w-full whitespace-nowrap mb-[20px]">
                <p className="flex-[1.5_0_0] relative shrink-0">
                  Nom du fichier
                </p>
                <p className="flex-[1_0_0] relative shrink-0 text-center">
                  type
                </p>
                <p className="flex-[1_0_0] relative shrink-0 text-center">
                  date
                </p>
                <p className="flex-[1_0_0] relative shrink-0 text-center">
                  licence
                </p>
                <p className="flex-[1_0_0] relative shrink-0 text-center">
                  commentaire
                </p>
                <p className="flex-[1_0_0] relative shrink-0 text-center">
                  version
                </p>
              </div>

              <div className="content-stretch flex flex-col gap-[15px] items-start relative shrink-0 w-full">
                {getStudentDocuments(selectedStudent.userId).map((doc) => (
                  <div
                    key={doc.id}
                    className="content-stretch flex items-center justify-between py-[10px] px-[10px] relative shrink-0 w-full hover:bg-gray-50 border-b border-[#d9d9d9]"
                  >
                    <p className="flex-[1.5_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                      {doc.nom_fichier}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {doc.type_fichier}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {new Date(doc.created_at).toLocaleDateString("fr-FR", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                      })}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {selectedStudent.license}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {doc.comment_count || 0}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {doc.version || '1.0'}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
