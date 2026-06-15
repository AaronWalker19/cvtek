import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import Sidebar from "../components/Sidebar";
import AdminLoginModal from "../../components/AdminLoginModal";
import ExportModal from "../components/ExportModal";
import svgPaths from "../../imports/PageDeBaseCoteProf/svg-9gqyfpru0n";
import { getDocuments, getUserById, getDocument, checkSubscription, createSubscription, deleteSubscription, getCommentsByDocVersion, Comment, exportDocuments } from '../../api/client';

export default function ProfessorDashboard() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [searchQuery, setSearchQuery] = useState("");
  const [showFilters, setShowFilters] = useState(false);
  const [showAdminModal, setShowAdminModal] = useState(false);
  const [showExportModal, setShowExportModal] = useState(false);
  const [exportLoading, setExportLoading] = useState(false);
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
  const [documentComments, setDocumentComments] = useState<{ [docVersionId: number]: Comment[] }>({});
  const [subscriptions, setSubscriptions] = useState<Set<number>>(new Set());
  const [loadingSubscription, setLoadingSubscription] = useState(false);
  const [sortColumn, setSortColumn] = useState<'name' | 'lastDeposit' | 'lastComment'>('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const [selectedLicenses, setSelectedLicenses] = useState<Set<string>>(new Set());
  const [selectedFollowStatus, setSelectedFollowStatus] = useState<Set<'followed' | 'unfollowed'>>(new Set());
  const [allSubscriptions, setAllSubscriptions] = useState<Set<number>>(new Set());

  // Charger les abonnements de TOUS les étudiants pour le filtrage
  useEffect(() => {
    const loadAllSubscriptions = async () => {
      if (!user || allStudents.length === 0) {
        setAllSubscriptions(new Set());
        return;
      }

      try {
        const followedStudentIds = new Set<number>();
        for (const student of allStudents) {
          try {
            const isSubscribed = await checkSubscription(user.id, student.userId);
            if (isSubscribed) {
              followedStudentIds.add(student.userId);
            }
          } catch (error) {
            console.error(`❌ Erreur vérification abonnement étudiant ${student.userId}:`, error);
          }
        }
        setAllSubscriptions(followedStudentIds);
      } catch (error) {
        console.error('❌ [loadAllSubscriptions] Erreur:', error);
      }
    };

    loadAllSubscriptions();
  }, [user, allStudents]);

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
        // Mettre à jour allSubscriptions
        const newAllSubs = new Set(allSubscriptions);
        newAllSubs.delete(selectedStudent.userId);
        setAllSubscriptions(newAllSubs);
      } else {
        // S'abonner
        await createSubscription(user.id, selectedStudent.userId);
        setSubscriptions(new Set([selectedStudent.userId]));
        // Mettre à jour allSubscriptions
        const newAllSubs = new Set(allSubscriptions);
        newAllSubs.add(selectedStudent.userId);
        setAllSubscriptions(newAllSubs);
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

  // Charger les commentaires pour tous les documents au démarrage
  useEffect(() => {
    const loadInitialComments = async () => {
      const allComments: { [docVersionId: number]: Comment[] } = {};

      for (const doc of allDocuments) {
        try {
          // Charger le document complet pour obtenir les versions
          const fullDoc = await getDocument(doc.id);
          const versions = (fullDoc as any).availableVersions || [];
          
          // Pour chaque version, charger les commentaires
          for (const version of versions) {
            if (version.id) {
              try {
                const comments = await getCommentsByDocVersion(version.id);
                allComments[version.id] = comments;
              } catch (error) {
                console.error(`❌ Erreur chargement commentaires version ${version.id}:`, error);
                allComments[version.id] = [];
              }
            }
          }
        } catch (error) {
          console.error(`❌ Erreur chargement document ${doc.id}:`, error);
        }
      }

      setDocumentComments(allComments);
    };

    if (allDocuments.length > 0) {
      loadInitialComments();
    }
  }, [allDocuments]);

  // Charger les versions complètes pour TOUS les documents au démarrage
  useEffect(() => {
    const loadAllDocumentVersions = async () => {
      const allVersions: { [docId: number]: any[] } = {};

      for (const doc of allDocuments) {
        try {
          const fullDoc = await getDocument(doc.id);
          allVersions[doc.id] = (fullDoc as any).availableVersions || [{ version: doc.version }];
        } catch (error) {
          console.error(`❌ Erreur chargement versions doc ${doc.id}:`, error);
          allVersions[doc.id] = [{ version: doc.version }];
        }
      }

      setDocumentVersions(allVersions);
    };

    if (allDocuments.length > 0) {
      loadAllDocumentVersions();
    }
  }, [allDocuments]);

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

  // Charger les commentaires pour tous les documents
  useEffect(() => {
    const loadAllComments = async () => {
      const allComments: { [docVersionId: number]: Comment[] } = {};

      for (const doc of allDocuments) {
        try {
          const fullDoc = await getDocument(doc.id);
          const versions = (fullDoc as any).availableVersions || [{ version: doc.version, id: doc.id }];

          for (const version of versions) {
            if (version.id) {
              try {
                const comments = await getCommentsByDocVersion(version.id);
                allComments[version.id] = comments;
              } catch (error) {
                console.error(`❌ Erreur chargement commentaires version ${version.id}:`, error);
                allComments[version.id] = [];
              }
            }
          }
        } catch (error) {
          console.error(`❌ Erreur chargement versions doc ${doc.id}:`, error);
        }
      }

      setDocumentComments(allComments);
    };

    if (allDocuments.length > 0) {
      loadAllComments();
    }
  }, [allDocuments]);

  // Charger les versions des fichiers de l'étudiant sélectionné
  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => {
    const loadDocumentVersions = async () => {
      if (!selectedStudent) {
        return; // Ne pas vider, garder les versions en cache
      }

      const studentDocs = getStudentDocuments(selectedStudent.userId);
      const newVersions: { [docId: number]: any[] } = { ...documentVersions }; // Garder les versions existantes

      for (const doc of studentDocs) {
        if (!newVersions[doc.id]) { // Ne charger que si pas encore en cache
          try {
            const fullDoc = await getDocument(doc.id);
            newVersions[doc.id] = (fullDoc as any).availableVersions || [{ version: doc.version }];
          } catch (error) {
            console.error(`❌ Erreur chargement versions doc ${doc.id}:`, error);
            newVersions[doc.id] = [{ version: doc.version }];
          }
        }
      }

      setDocumentVersions(newVersions);
    };

    loadDocumentVersions();
  }, [selectedStudent, allDocuments]); // eslint-disable-line react-hooks/exhaustive-deps

  // eslint-disable-next-line react-hooks/exhaustive-deps
  // Charger les commentaires pour les versions des documents de l'étudiant sélectionné
  useEffect(() => {
    const loadCommentsForVersions = async () => {
      const allComments: { [docVersionId: number]: Comment[] } = { ...documentComments };

      for (const docId in documentVersions) {
        const versions = documentVersions[parseInt(docId)];
        if (versions && versions.length > 0) {
          for (const version of versions) {
            if (version.id && !allComments[version.id]) {
              try {
                const comments = await getCommentsByDocVersion(version.id);
                allComments[version.id] = comments;
              } catch (error) {
                console.error(`❌ Erreur chargement commentaires version ${version.id}:`, error);
                allComments[version.id] = [];
              }
            }
          }
        }
      }

      setDocumentComments(allComments);
    };

    if (Object.keys(documentVersions).length > 0) {
      loadCommentsForVersions();
    }
  }, [documentVersions]);

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

  // Obtenir la date du dernier dépôt pour un étudiant
  const getLatestDepositDate = (userId: number): Date | null => {
    const studentDocs = allDocuments.filter((doc) => doc.user_id === userId);
    if (studentDocs.length === 0) return null;
    
    const latestDoc = studentDocs.reduce((latest: any, current: any) => {
      const latestDate = new Date(latest.created_at).getTime();
      const currentDate = new Date(current.created_at).getTime();
      return currentDate > latestDate ? current : latest;
    });
    
    return new Date(latestDoc.created_at);
  };

  // Obtenir la date du dernier commentaire pour un étudiant
  const getLatestCommentDate = (userId: number): Date | null => {
    const studentDocs = allDocuments.filter((doc) => doc.user_id === userId);
    if (studentDocs.length === 0) return null;
    
    let latestCommentDate: Date | null = null;
    
    for (const doc of studentDocs) {
      // Obtenir les versions complètes si disponibles
      const versions = documentVersions[doc.id] || [];
      
      // Si pas de versions chargées, essayer avec juste l'ID du document
      const versionsToCheck = versions.length > 0 ? versions : [{ id: doc.id }];
      
      for (const version of versionsToCheck) {
        const versionId = version.id || doc.id;
        const comments = documentComments[versionId];
        
        if (comments && comments.length > 0) {
          for (const comment of comments) {
            const commentDate = new Date(comment.date);
            if (!latestCommentDate || commentDate > latestCommentDate) {
              latestCommentDate = commentDate;
            }
          }
        }
      }
    }
    
    return latestCommentDate;
  };

  // Gérer le changement de colonne de tri
  const handleSort = (column: 'name' | 'lastDeposit' | 'lastComment') => {
    if (sortColumn === column) {
      // Si on clique sur la même colonne, inverser la direction
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      // Si on clique sur une nouvelle colonne, l'ordre par défaut est ascendant
      setSortColumn(column);
      setSortDirection('asc');
    }
  };

  // Obtenir les licences uniques
  const getUniqueLicenses = (): string[] => {
    const licenses = new Set<string>();
    allStudents.forEach((student) => {
      if (student.license && student.license !== 'N/A') {
        licenses.add(student.license);
      }
    });
    return Array.from(licenses).sort();
  };

  // Basculer la sélection d'une licence
  const toggleLicenseFilter = (license: string) => {
    const newSelected = new Set(selectedLicenses);
    if (newSelected.has(license)) {
      newSelected.delete(license);
    } else {
      newSelected.add(license);
    }
    setSelectedLicenses(newSelected);
  };

  // Basculer la sélection du statut de suivi
  const toggleFollowStatusFilter = (status: 'followed' | 'unfollowed') => {
    const newSelected = new Set(selectedFollowStatus);
    if (newSelected.has(status)) {
      newSelected.delete(status);
    } else {
      newSelected.add(status);
    }
    setSelectedFollowStatus(newSelected);
  };

  // Filtrer les étudiants selon la recherche, les licences ET le statut de suivi
  const getFilteredStudents = () => {
    let result = allStudents.filter(
      (student) =>
        student.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        student.license?.toLowerCase().includes(searchQuery.toLowerCase()),
    );

    // Si des licences sont sélectionnées, filtrer par licence
    if (selectedLicenses.size > 0) {
      result = result.filter((student) => selectedLicenses.has(student.license || ''));
    }

    // Si des statuts de suivi sont sélectionnés, filtrer par statut
    if (selectedFollowStatus.size > 0) {
      result = result.filter((student) => {
        const isFollowed = allSubscriptions.has(student.userId);
        if (selectedFollowStatus.has('followed') && isFollowed) return true;
        if (selectedFollowStatus.has('unfollowed') && !isFollowed) return true;
        return false;
      });
    }

    return result;
  };

  // Obtenir les étudiants triés
  const getSortedStudents = (students: ReturnType<typeof getFilteredStudents>) => {
    const sorted = [...students];
    
    sorted.sort((a, b) => {
      let compareValue = 0;
      
      switch (sortColumn) {
        case 'name':
          compareValue = a.name.localeCompare(b.name);
          break;
        case 'lastDeposit': {
          const dateA = getLatestDepositDate(a.userId)?.getTime() || 0;
          const dateB = getLatestDepositDate(b.userId)?.getTime() || 0;
          compareValue = dateA - dateB;
          break;
        }
        case 'lastComment': {
          const dateA = getLatestCommentDate(a.userId)?.getTime() || 0;
          const dateB = getLatestCommentDate(b.userId)?.getTime() || 0;
          compareValue = dateA - dateB;
          break;
        }
      }
      
      return sortDirection === 'asc' ? compareValue : -compareValue;
    });
    
    return sorted;
  };

  // Gérer l'export des fichiers
  const handleExport = async (studentIds: number[]) => {
    setExportLoading(true);
    try {
      const blob = await exportDocuments(studentIds);
      
      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `export_documents_${new Date().toISOString().split('T')[0]}.zip`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      alert('✅ Export réussi ! Votre fichier ZIP a été téléchargé.');
      setShowExportModal(false);
    } catch (error) {
      console.error('❌ Erreur lors de l\'export:', error);
      alert('❌ Erreur lors de l\'export des fichiers');
    } finally {
      setExportLoading(false);
    }
  };

  // Composant pour afficher la flèche de tri
  const SortArrow = ({ column }: { column: 'name' | 'lastDeposit' | 'lastComment' }) => {
    if (sortColumn === column) {
      return <span className="ml-2">{sortDirection === 'asc' ? '↑' : '↓'}</span>;
    }
    return <span className="ml-2 opacity-30">↕</span>;
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
                Documents postés
              </p>
              <div className="flex items-center gap-[15px]">
              <button
                onClick={() => setShowExportModal(true)}
                className="relative shrink-0 px-4 py-2 bg-[#4b575f] text-white rounded font-['Inter:Medium',sans-serif] font-medium hover:bg-[#36302a] transition-colors flex items-center gap-[8px]"
              >
                📥 Exporter
              </button>
              {user?.role !== 'admin' && (
                <button
                  onClick={() => setShowAdminModal(true)}
                  className="relative shrink-0 px-4 py-2 bg-[#b51621] text-white rounded font-['Inter:Medium',sans-serif] font-medium hover:bg-[#932117] transition-colors"
                >
                  Passer en Admin
                </button>
              )}
              </div>
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

            {/* Filter Panel */}
            {showFilters && (
              <div className="bg-[#f5f5f5] rounded-lg p-[20px] relative shrink-0 w-full border border-[#d9d9d9]">
                <div className="flex flex-col gap-[15px]">
                  <p className="font-['Inter:Medium',sans-serif] font-medium text-[#36302a] text-[16px]">
                    Filtrer par licence/parcours :
                  </p>
                  <div className="flex flex-col gap-[10px]">
                    {getUniqueLicenses().map((license) => (
                      <label key={license} className="flex items-center gap-[10px] cursor-pointer">
                        <input
                          type="checkbox"
                          checked={selectedLicenses.has(license)}
                          onChange={() => toggleLicenseFilter(license)}
                          className="w-[18px] h-[18px] cursor-pointer"
                        />
                        <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                          {license}
                        </span>
                      </label>
                    ))}
                  </div>
                  
                  {/* Filtre Suivi */}
                  <p className="font-['Inter:Medium',sans-serif] font-medium text-[#36302a] text-[16px] mt-[15px]">
                    Statut de suivi :
                  </p>
                  <div className="flex flex-col gap-[10px]">
                    <label className="flex items-center gap-[10px] cursor-pointer">
                      <input
                        type="checkbox"
                        checked={selectedFollowStatus.has('followed')}
                        onChange={() => toggleFollowStatusFilter('followed')}
                        className="w-[18px] h-[18px] cursor-pointer"
                      />
                      <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        Suivi
                      </span>
                    </label>
                    <label className="flex items-center gap-[10px] cursor-pointer">
                      <input
                        type="checkbox"
                        checked={selectedFollowStatus.has('unfollowed')}
                        onChange={() => toggleFollowStatusFilter('unfollowed')}
                        className="w-[18px] h-[18px] cursor-pointer"
                      />
                      <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        Non suivi
                      </span>
                    </label>
                  </div>
                  
                  {(selectedLicenses.size > 0 || selectedFollowStatus.size > 0) && (
                    <button
                      onClick={() => {
                        setSelectedLicenses(new Set());
                        setSelectedFollowStatus(new Set());
                      }}
                      className="mt-[10px] px-4 py-2 bg-[#4b575f] text-white rounded font-['Inter:Medium',sans-serif] font-medium hover:bg-[#36302a] transition-colors text-[14px]"
                    >
                      Réinitialiser les filtres
                    </button>
                  )}
                </div>
              </div>
            )}

            {/* Documents Table */}
            <div className="relative shrink-0 w-full">
              <div className="content-stretch flex flex-col gap-[28px] items-start px-[20px] relative size-full">
                <div className="content-stretch flex font-['Inter:Medium',sans-serif] font-medium items-center justify-between leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[18px] w-full">
                  <button
                    onClick={() => handleSort('name')}
                    className="flex-[1.5_0_0] relative shrink-0 text-left hover:text-[#4b575f] transition-colors cursor-pointer flex items-center"
                  >
                    étudiant
                    <SortArrow column="name" />
                  </button>
                  <p className="flex-[1_0_0] relative shrink-0 text-center">
                    licence
                  </p>
                  <button
                    onClick={() => handleSort('lastDeposit')}
                    className="flex-[1_0_0] relative shrink-0 text-center hover:text-[#4b575f] transition-colors cursor-pointer flex items-center justify-center"
                  >
                    dernier dépôt
                    <SortArrow column="lastDeposit" />
                  </button>
                  <button
                    onClick={() => handleSort('lastComment')}
                    className="flex-[1_0_0] relative shrink-0 text-center hover:text-[#4b575f] transition-colors cursor-pointer flex items-center justify-center"
                  >
                    dernier commentaire
                    <SortArrow column="lastComment" />
                  </button>
                </div>

                <div className="content-stretch flex flex-col gap-[15px] items-start relative shrink-0 w-full">
                  {getSortedStudents(getFilteredStudents()).map((student, index) => (
                    <div
                      key={index}
                      onClick={() => setSelectedStudent(student)}
                      className="content-stretch flex items-center justify-between py-[10px] px-[10px] relative shrink-0 w-full hover:bg-gray-50 border-b border-[#d9d9d9] cursor-pointer"
                    >
                      <p className="flex-[1.5_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        {student.name}
                      </p>
                      <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                        {student.license}
                      </p>
                      <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                        {getLatestDepositDate(student.userId)?.toLocaleDateString("fr-FR", {
                          day: "numeric",
                          month: "short",
                          year: "numeric",
                        }) || '-'}
                      </p>
                      <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] text-center">
                        {getLatestCommentDate(student.userId)?.toLocaleDateString("fr-FR", {
                          day: "numeric",
                          month: "short",
                          year: "numeric",
                        }) || '-'}
                      </p>
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
                    <p className="flex-[1.5_0_0] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] truncate" title={doc.titre || doc.nom_fichier}>
                      {doc.titre || doc.nom_fichier}
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

      {/* Export Modal */}
      <ExportModal
        isOpen={showExportModal}
        onClose={() => setShowExportModal(false)}
        students={allStudents}
        onExport={handleExport}
        isLoading={exportLoading}
        subscriptions={allSubscriptions}
      />
    </div>
  );
}
