import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import Sidebar from "../components/Sidebar";
import AdminLoginModal from "../../components/AdminLoginModal";
import svgPaths from "../../imports/PageDeBaseCoteProf/svg-9gqyfpru0n";
import { getDocuments, getUserById, getDocument, checkSubscription, createSubscription, deleteSubscription } from '../../api/client';

export default function ProfessorDashboard() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [searchQuery, setSearchQuery] = useState("");
  const [showFilters, setShowFilters] = useState(false);
  const [showAdminModal, setShowAdminModal] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState<{
    name: string;
    license?: string;
    userId: number;
    email?: string;
  } | null>(null);
  const [selectedStudentDetails, setSelectedStudentDetails] = useState<{
    id: number;
    username: string;
    email: string;
    role: string;
    parcour?: string;
  } | null>(null);
  const [allDocuments, setAllDocuments] = useState<any[]>([]);
  const [allStudents, setAllStudents] = useState<Array<{
    name: string;
    license?: string;
    userId: number;
    email?: string;
  }>>([]);
  const [documentVersions, setDocumentVersions] = useState<{ [docId: number]: any[] }>({});
  const [subscriptions, setSubscriptions] = useState<Set<number>>(new Set());
  const [loadingSubscription, setLoadingSubscription] = useState(false);

  // Charger l'état d'abonnement quand un étudiant est sélectionné
  useEffect(() => {
    const checkStudentSubscription = async () => {
      if (!user || !selectedStudent) {
        setSubscriptions(new Set());
        return;
      }

      try {
        const isSubscribed = await checkSubscription(user.id, selectedStudent.userId);
        setSubscriptions(new Set(isSubscribed ? [selectedStudent.userId] : []));
      } catch (error) {
        console.error('❌ [CheckSubscription] Erreur:', error);
      }
    };

    checkStudentSubscription();
  }, [user, selectedStudent]);

  // Fonction pour s'abonner/se désabonner
  const toggleSubscription = async () => {
    if (!user || !selectedStudent) {
      console.error('❌ [toggleSubscription] user ou selectedStudent manquant');
      return;
    }

    const isCurrentlySubscribed = subscriptions.has(selectedStudent.userId);
    setLoadingSubscription(true);

    try {
      if (isCurrentlySubscribed) {
        // Désabonner
        await deleteSubscription(user.id, selectedStudent.userId);
        setSubscriptions(new Set());
      } else {
        // S'abonner
        await createSubscription(user.id, selectedStudent.userId);
        setSubscriptions(new Set([selectedStudent.userId]));
      }
    } catch (error) {
      console.error(`❌ [toggleSubscription] Erreur:`, error);
      // En cas d'erreur, recharger l'état d'abonnement
      try {
        const isSubscribed = await checkSubscription(user.id, selectedStudent.userId);
        setSubscriptions(new Set(isSubscribed ? [selectedStudent.userId] : []));
      } catch (checkError) {
        console.error(`❌ Erreur lors du re-check:`, checkError);
      }
    } finally {
      setLoadingSubscription(false);
    }
  };

  // Charger tous les documents de la base de données
  useEffect(() => {
    const loadAllDocuments = async () => {
      try {
        const docs = await getDocuments();
        setAllDocuments(docs);
      } catch (error) {
        console.error('❌ Erreur lors du chargement des documents:', error);
        setAllDocuments([]);
      }
    };

    loadAllDocuments();
  }, []);

  // Extraire les étudiants uniques et créer la liste
  useEffect(() => {
    const loadStudents = async () => {
      const studentsMap = new Map<number, { name: string; license?: string; userId: number; email?: string }>();

      // D'abord, créer la liste basique avec les données des documents
      allDocuments.forEach((doc) => {
        if (!studentsMap.has(doc.user_id)) {
          studentsMap.set(doc.user_id, {
            name: `Étudiant ${doc.user_id}`,
            license: 'N/A',
            userId: doc.user_id,
            email: 'N/A',
          });
        }
      });

      // Ensuite, charger les vraies infos de chaque utilisateur depuis la BDD
      const updatedStudents: Array<{ name: string; license?: string; userId: number; email?: string }> = [];
      
      for (const student of studentsMap.values()) {
        try {
          const userDetails = await getUserById(student.userId);
          updatedStudents.push({
            name: userDetails.username,
            license: userDetails.parcour || 'N/A',
            userId: student.userId,
            email: userDetails.email,
          });
        } catch (error) {
          console.error(`❌ Erreur chargement étudiant ${student.userId}:`, error);
          // Garder l'étudiant même si on ne peut pas charger ses détails
          updatedStudents.push(student);
        }
      }

      setAllStudents(updatedStudents);
    };

    if (allDocuments.length > 0) {
      loadStudents();
    } else {
      setAllStudents([]);
    }
  }, [allDocuments]);

  // Charger les détails de l'utilisateur quand on sélectionne un étudiant
  useEffect(() => {
    const loadUserDetails = async () => {
      if (!selectedStudent) {
        setSelectedStudentDetails(null);
        return;
      }

      try {
        const userDetails = await getUserById(selectedStudent.userId);
        setSelectedStudentDetails(userDetails);
      } catch (error) {
        console.error('❌ Erreur lors du chargement des détails utilisateur:', error);
        // Garder les infos basiques même si les détails ne se chargent pas
        setSelectedStudentDetails({
          id: selectedStudent.userId,
          username: selectedStudent.name,
          email: selectedStudent.email || 'N/A',
          role: 'student',
          parcour: selectedStudent.license,
        });
      }
    };

    loadUserDetails();
  }, [selectedStudent]);

  // Charger les versions des fichiers de l'étudiant sélectionné
  useEffect(() => {
    const loadDocumentVersions = async () => {
      if (!selectedStudent) {
        setDocumentVersions({});
        return;
      }

      const studentDocs = getStudentDocuments(selectedStudent.userId);
      const versions: { [docId: number]: any[] } = {};

      for (const doc of studentDocs) {
        try {
          const fullDoc = await getDocument(doc.id);
          versions[doc.id] = fullDoc.availableVersions || [{ version: doc.version }];
        } catch (error) {
          console.error(`❌ Erreur chargement versions doc ${doc.id}:`, error);
          versions[doc.id] = [{ version: doc.version }];
        }
      }

      setDocumentVersions(versions);
    };

    loadDocumentVersions();
  }, [selectedStudent, allDocuments]);

  // Filtrer les étudiants selon la recherche
  const filteredStudents = allStudents.filter(
    (student) =>
      student.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      student.license?.toLowerCase().includes(searchQuery.toLowerCase()),
  );

  // Récupérer les documents d'un étudiant
  const getStudentDocuments = (userId: number) => {
    return allDocuments.filter((doc) => doc.user_id === userId);
  };

  // Obtenir la dernière version en date pour un document
  const getLatestVersion = (doc: any) => {
    const versions = documentVersions[doc.id];
    if (versions && versions.length > 0) {
      // Trier par date décroissante et prendre la première (la plus récente)
      const sorted = [...versions].sort((a: any, b: any) => {
        const dateA = new Date(a.created_at || 0).getTime();
        const dateB = new Date(b.created_at || 0).getTime();
        return dateB - dateA;
      });
      return sorted[0].version || '1.0';
    }
    return doc.version || '1.0';
  };

  return (
    <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor="bg-[#4b575f]" />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
            {/* Header */}
            <div className="content-stretch flex items-center justify-between py-[10px] relative shrink-0 w-full">
              <div
                aria-hidden="true"
                className="absolute border-[#4b575f] border-b-3 border-solid inset-0 pointer-events-none"
              />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] whitespace-nowrap">
                Documents postée
              </p>
              <button
                onClick={() => setShowAdminModal(true)}
                className="relative shrink-0 px-4 py-2 bg-[#b51621] text-white rounded font-['Inter:Medium',sans-serif] font-medium hover:bg-[#932117] transition-colors"
              >
                Passer en Admin
              </button>
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
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[20px] text-[#ffffff] whitespace-nowrap">
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
      {selectedStudent && selectedStudentDetails && (
        <div
          onClick={() => setSelectedStudent(null)}
          className="fixed inset-0 bg-[#000000] bg-opacity-50 flex items-center justify-center z-50"
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
                    {selectedStudentDetails.username}
                  </h2>
                  <p className="font-['Inter:Regular',sans-serif] text-[16px] text-[#36302a]">
                    {selectedStudentDetails.parcour || selectedStudent.license}
                  </p>
                  <p className="font-['Inter:Regular',sans-serif] text-[16px] text-[#36302a]">
                    {selectedStudentDetails.email}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-[15px]">
                <button
                  onClick={() => {

                    toggleSubscription();
                  }}
                  disabled={loadingSubscription}
                  className={`px-6 py-2 rounded font-['Inter:Medium',sans-serif] text-base font-medium transition-all whitespace-nowrap ${
                    subscriptions.has(selectedStudent.userId)
                      ? 'bg-red-500 hover:bg-red-600 text-[#ffffff]'
                      : 'bg-[#4b575f] hover:bg-[#36302a] text-[#ffffff]'
                  } ${loadingSubscription ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                  title={subscriptions.has(selectedStudent.userId) ? 'Cliquez pour arrêter de suivre' : 'Cliquez pour suivre cet étudiant'}
                >
                  <span className="inline-block">
                    {loadingSubscription ? '⏳ ...' : (subscriptions.has(selectedStudent.userId) ? '✓ Suivi' : '+ Suivre')}
                  </span>
                </button>

                <button
                  onClick={() => setSelectedStudent(null)}
                  className="text-[#4b575f] text-[32px] font-bold hover:text-[#36302a] transition-colors"
                >
                  ×
                </button>
              </div>
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
                    onClick={() => navigate(`/professor/file/${doc.id}`)}
                    className="content-stretch flex items-center justify-between py-[10px] px-[10px] relative shrink-0 w-full hover:bg-gray-50 border-b border-[#d9d9d9] cursor-pointer"
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
                      {selectedStudentDetails.parcour || selectedStudent.license}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {doc.comment_count || 0}
                    </p>
                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                      {getLatestVersion(doc)}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Admin Login Modal */}
      <AdminLoginModal
        show={showAdminModal}
        onClose={() => setShowAdminModal(false)}
        onSuccess={() => {
          setShowAdminModal(false);
          navigate('/admin');
        }}
      />
    </div>
  );
}
