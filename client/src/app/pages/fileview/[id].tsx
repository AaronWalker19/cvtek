import { useState, useEffect, useRef } from 'react';
import { useParams, Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import NewVersionModal from '../../../components/NewVersionModal';
import { 
  uploadFile, 
  getDocument,
  addVersion,
  getUserById,
  getDocuments,
  getCommentsByDocVersion,
  addComment,
  updateComment,
  deleteComment,
  getCurrentUser,
  checkSubscription,
  createSubscription,
  deleteSubscription,
  Document as ApiDocument 
} from '../../../api/client';
import Sidebar from '../../components/Sidebar';
import studentSvgPaths from '../../../imports/PageDeFichier/svg-g1nozp2mpd';
import professorSvgPaths from '../../../imports/PageDeFichierCoteProf/svg-uwrwsgjoxh';

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

interface DocumentWithVersions extends Document {
  versions: Array<{
    id: number;
    version: number;
    created_at: string;
  }>;
  availableVersions: Array<{
    id: number;
    version: number;
    created_at: string;
  }>;
}

interface Comment {
  id: number;
  id_user: number;
  id_docversion: number;
  text: string;
  date: string;
  username: string;
  email?: string;
}

export default function FileView() {
  const { user } = useAuth();
  const { id, fileId } = useParams<{ id?: string; fileId?: string }>();
  const [searchParams] = useSearchParams();
  const docId = id || fileId;
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  const [document, setDocument] = useState<DocumentWithVersions | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [newComment, setNewComment] = useState('');
  const [followStudent, setFollowStudent] = useState(false);
  const [studentUsername, setStudentUsername] = useState<string | null>(null);
  const [loadingSubscription, setLoadingSubscription] = useState(false);
  const [allStudents, setAllStudents] = useState<Array<{
    name: string;
    license?: string;
    userId: number;
    email?: string;
  }>>([]);
  const [documentVersions, setDocumentVersions] = useState<{ [docId: number]: any[] }>({});
  const [showVersionModal, setShowVersionModal] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [uploadingVersion, setUploadingVersion] = useState(false);
  const [selectedVersion, setSelectedVersion] = useState<number | null>(null);
  const [selectedVersionId, setSelectedVersionId] = useState<number | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [newVersionModal, setNewVersionModal] = useState<{ show: boolean; doc: Document | null }>({ show: false, doc: null });
  const [otherStudentDocuments, setOtherStudentDocuments] = useState<Document[]>([]);
  const [loadingComments, setLoadingComments] = useState(false);
  const [addingComment, setAddingComment] = useState(false);
  const [editingCommentId, setEditingCommentId] = useState<number | null>(null);
  const [editCommentText, setEditCommentText] = useState<string>('');

  useEffect(() => {
    const fetchDocument = async () => {
      try {
        setLoading(true);
        setError(null);
        
        if (!docId) {
          setError('ID du document manquant');
          return;
        }

        const doc = await getDocument(parseInt(docId));
        
        setDocument(doc as any);
        
        // Charger le username de l'étudiant
        try {
          const studentData = await getUserById(doc.user_id);
          setStudentUsername(studentData.username);
        } catch (err) {
          console.error('Erreur lors de la récupération du username:', err);
        }
        
        // Charger les autres fichiers de l'étudiant
        try {
          const allDocs = await getDocuments(doc.user_id);
          const otherDocs = allDocs.filter(d => d.id !== doc.id);
          setOtherStudentDocuments(otherDocs);
        } catch (err) {
          console.error('Erreur lors de la récupération des autres fichiers:', err);
          setOtherStudentDocuments([]);
        }
        
        // Lire la version depuis le query param si elle existe
        const versionParam = searchParams.get('version');
        if (versionParam) {
          const versionId = parseInt(versionParam);
          const versionObj = (doc as any).availableVersions?.find((v: any) => v.id === versionId);
          if (versionObj) {

            setSelectedVersion(versionObj.version);
            setSelectedVersionId(versionId);
          }
        } else {
          // Charger la première version par défaut
          const firstVersion = (doc as any).availableVersions?.[0];
          if (firstVersion) {
            setSelectedVersionId(firstVersion.id);
          }
        }
        
        // Charger les commentaires de la première version
        if ((doc as any).availableVersions && (doc as any).availableVersions.length > 0) {
          const firstVersionId = (doc as any).availableVersions[0].id;
          try {
            const loadedComments = await getCommentsByDocVersion(firstVersionId);
            setComments(loadedComments);
          } catch (err) {
            console.error('❌ Erreur lors du chargement des commentaires:', err);
            setComments([]);
          }
        }
      } catch (err) {
        console.error('❌ Error loading document:', err);
        setError('Erreur lors du chargement du document');
      } finally {
        setLoading(false);
      }
    };

    if (docId) {
      fetchDocument();
    }
  }, [docId, searchParams]);

  // Charger l'utilisateur connecté et vérifier l'abonnement
  useEffect(() => {
    const checkStudentSubscription = async () => {
      try {
        if (user) {

          // Vérifier si ce prof suit déjà cet étudiant
          if (document && document.user_id && user.id) {
            const isSubscribed = await checkSubscription(user.id, document.user_id);
            setFollowStudent(isSubscribed);
          } else {
            // Pas de document ou pas d'id
          }
        } else {
          // Aucun utilisateur connecté
        }
      } catch (error) {
        console.error('[CheckSubscription] Erreur lors du chargement de l\'utilisateur:', error);
      }
    };

    if (document) {
      checkStudentSubscription();
    }
  }, [document, user, docId]);

  // Charger les commentaires quand la version sélectionnée change
  useEffect(() => {
    const loadCommentsForVersion = async () => {
      if (!selectedVersionId) {
        return;
      }

      try {
        setLoadingComments(true);
        const loadedComments = await getCommentsByDocVersion(selectedVersionId);
        setComments(loadedComments);
      } catch (err) {
        console.error('❌ Erreur lors du chargement des commentaires:', err);
      } finally {
        setLoadingComments(false);
      }
    };

    loadCommentsForVersion();
  }, [selectedVersionId]);

  const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setSelectedFile(file);
    }
  };

  const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);

    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
      setSelectedFile(files[0]);
    }
  };

  const handleUploadVersion = async () => {
    if (!selectedFile) {
      return;
    }

    if (!newVersionModal.doc) {
      return;
    }

    try {
      setUploadingVersion(true);

      // Utiliser la nouvelle API pour uploader
      const uploadResponse = await uploadFile(selectedFile, newVersionModal.doc.user_id);

      if (!uploadResponse.url) {
        console.error('❌ Erreur: pas d\'URL retournée par le serveur');
        return;
      }

      const fileUrl = uploadResponse.url;

      // Créer une nouvelle version du document
      
      await addVersion(newVersionModal.doc.id, fileUrl);

      // Recharger le document
      try {
        const updatedDoc = await getDocument(newVersionModal.doc.id);
        setDocument(updatedDoc as any);
        setSelectedVersion(updatedDoc.version);
      } catch (err) {
        // Erreur lors de la recharge
      }

      // Réinitialiser le modal
      setSelectedFile(null);
      setNewVersionModal({ show: false, doc: null });
      setIsDragging(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    } catch (err) {
      console.error('❌ Erreur upload version:', err);
      console.error(`❌ Erreur lors de l'upload de la nouvelle version: ${err instanceof Error ? err.message : 'Erreur inconnue'}`);
    } finally {
      setUploadingVersion(false);
    }
  };

  const handleVersionDragOver = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleVersionDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleVersionDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      setSelectedFile(files[0]);
    }
  };

  const handleCloseVersionModal = () => {
    setNewVersionModal({ show: false, doc: null });
    setSelectedFile(null);
    setIsDragging(false);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleVersionChange = (versionId: number, version: number) => {
    setSelectedVersion(version);
    setSelectedVersionId(versionId);
  };

  if (loading) {
    return (
      <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
        <Sidebar bgColor="bg-[#b51621]" />
        <div className="flex-[1_0_0] h-screen overflow-y-auto w-full flex items-center justify-center">
          <p className="text-[#36302a] text-[18px]">Chargement...</p>
        </div>
      </div>
    );
  }

  if (error || !document) {
    return (
      <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
        <Sidebar bgColor="bg-[#b51621]" />
        <div className="flex-[1_0_0] h-screen overflow-y-auto w-full flex flex-col items-center justify-center gap-[20px]">
          <p className="text-[#36302a] text-[18px]">{error || 'Document non trouvé'}</p>
          <Link to="/" className="text-[#b51621] hover:underline text-[16px]">← Retour</Link>
        </div>
      </div>
    );
  }

  const isStudent = user?.role === 'student';
  const svgPaths = isStudent ? studentSvgPaths : professorSvgPaths;
  const sidebarColor = isStudent ? 'bg-[#b51621]' : 'bg-[#4b575f]';
  const accentColor = isStudent ? '#b51621' : '#4b575f';
  const backLink = isStudent ? '/' : '/professor';
  const currentVersionNumber = selectedVersion || document?.availableVersions?.[0]?.version || document?.version;

  const handleAddComment = async () => {
    if (!newComment.trim() || !selectedVersionId) {
      return;
    }

    try {
      console.group('💬 PUBLICATION COMMENTAIRE');
      console.log('📝 Texte:', newComment.trim());
      console.log('📄 Version ID:', selectedVersionId);
      console.log('📋 Document ID:', document?.id);
      console.log('👤 ID Utilisateur:', user?.id);
      console.log('🎯 Rôle utilisateur:', user?.role);

      setAddingComment(true);
      console.log('⏳ Envoi du commentaire en cours...');
      
      const newCommentData = await addComment(selectedVersionId, newComment.trim());
      
      console.log('✅ Commentaire créé avec succès!');
      console.log('   ID:', newCommentData.id);
      console.log('   Auteur:', newCommentData.username);
      console.log('   Contenu:', newCommentData.text?.substring(0, 50) + '...');

      // Ajouter le commentaire à la liste
      setComments([newCommentData, ...comments]);
      setNewComment('');

      console.log('🎉 Un email de notification devrait avoir été envoyé à l\'étudiant!');
      console.log('   Vérifiez les logs PHP pour voir l\'état du serveur');
      console.groupEnd();
    } catch (err) {
      console.group('❌ ERREUR PUBLICATION COMMENTAIRE');
      console.error('Erreur:', err);
      console.log('💡 Conseil: Vérifiez la console PHP et les logs serveur');
      console.groupEnd();
      alert('Erreur lors de l\'ajout du commentaire');
    } finally {
      setAddingComment(false);
    }
  };

  const handleDeleteComment = async (commentId: number) => {
    // eslint-disable-next-line no-restricted-globals
    if (confirm('Voulez-vous vraiment supprimer ce commentaire?')) {
      try {
        await deleteComment(commentId);
        
        // Supprimer le commentaire de la liste
        setComments(comments.filter(c => c.id !== commentId));
      } catch (err) {
        console.error('Erreur lors de la suppression du commentaire:', err);
        alert('Erreur lors de la suppression du commentaire');
      }
    }
  };

  const handleUpdateComment = async (commentId: number) => {
    if (!editCommentText.trim()) {
      return;
    }

    try {
      const updatedComment = await updateComment(commentId, editCommentText.trim());
      
      // Mettre a jour le commentaire dans la liste
      setComments(comments.map(c => c.id === commentId ? updatedComment : c));
      setEditingCommentId(null);
      setEditCommentText('');
    } catch (err) {
      console.error('Erreur lors de la mise a jour du commentaire:', err);
      alert('Erreur lors de la mise à jour du commentaire');
    }
  };

  const handleNewVersion = () => {
    setNewVersionModal({ show: true, doc: document });
  };

  // Fonction pour s'abonner/se désabonner
  const toggleSubscription = async (shouldSubscribe: boolean) => {
    // Vérifier que c'est un professeur
    if (!user || user.role !== 'professor') {
      console.error("Seul un professeur peut s'abonner à un étudiant");
      alert("Erreur : Seul un professeur peut suivre un étudiant.");
      return;
    }
    
    if (!document) {
      console.error("document manquant");
      return;
    }

    setLoadingSubscription(true);

    try {
      if (shouldSubscribe) {
        // S'abonner
        await createSubscription(user.id, document.user_id);
        setFollowStudent(true);
      } else {
        // Se désabonner
        await deleteSubscription(user.id, document.user_id);
        setFollowStudent(false);
      }
    } catch (error) {
      console.error(`Erreur lors du changement d'abonnement:`, error);
      // Revenir à l'état précédent
      setFollowStudent(!shouldSubscribe);
    } finally {
      setLoadingSubscription(false);
    }
  };

  /**
   * Récupère l'URL du fichier basé sur la version sélectionnée
   */
  const getDisplayFileUrl = (): string => {
    if (!document || !document.availableVersions || document.availableVersions.length === 0) {
      return document?.url_fichier || '';
    }

    // Si une version est sélectionnée, trouver son URL
    if (selectedVersion) {
      const selectedVersionObj = document.availableVersions.find(
        (v: any) => String(v.version) === String(selectedVersion)
      );
      if (selectedVersionObj) {
        return selectedVersionObj.url_fichier;
      }
    }

    // Sinon, utiliser la dernière version (première dans la liste, triée DESC)
    return document.availableVersions[0]?.url_fichier || document.url_fichier || '';
  };

  return (
    <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor={sidebarColor} />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
            {/* Header */}
            <div className="content-stretch flex items-center justify-between py-[10px] relative shrink-0 w-full">
              <div
                aria-hidden="true"
                className="absolute border-b-3 border-solid inset-0 pointer-events-none"
                style={{ borderColor: accentColor }}
              />
              <div className="flex items-center gap-[20px]">
                <Link
                  to={backLink}
                  className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] whitespace-nowrap hover:underline"
                  style={{ color: accentColor }}
                >
                  ← {document.titre || document.nom_fichier}
                </Link>
                {document.availableVersions && document.availableVersions.length > 1 && (
                  <select
                    value={currentVersionNumber || ''}
                    onChange={(e) => {
                      const version = e.target.value; // Garder comme string pour comparaison
                      const versionData = document.availableVersions.find(v => String(v.version) === String(version));
                      if (versionData) {
                        handleVersionChange(versionData.id, versionData.version);
                      }
                    }}
                    className="px-[10px] py-[5px] border border-[#36302a] rounded text-[14px] font-['Inter:Regular',sans-serif]"
                    style={{ borderColor: accentColor }}
                  >
                    {document.availableVersions.map((v) => (
                      <option key={v.id} value={v.version}>
                        v{v.version} ({new Date(v.created_at).toLocaleDateString('fr-FR')})
                      </option>
                    ))}
                  </select>
                )}
              </div>
              {!isStudent && (
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[24px] whitespace-nowrap" style={{ color: accentColor }}>
                  {studentUsername || 'Chargement...'}
                </p>
              )}
            </div>

            {/* Content */}
            <div className="content-stretch flex flex-[1_0_0] gap-[20px] items-start min-h-px relative w-full">
              {/* PDF Preview */}
              <div className="bg-[#d9d9d9] content-stretch flex flex-col gap-[10px] h-[901px] items-center justify-center relative shrink-0 w-[701px] overflow-hidden">
                <div aria-hidden="true" className="absolute border-9 border-black border-solid inset-0 pointer-events-none" />
                {getDisplayFileUrl() ? (
                  getDisplayFileUrl().toLowerCase().includes('.pdf') ? (
                    <object
                      data={getDisplayFileUrl()}
                      type="application/pdf"
                      className="absolute inset-0 w-full h-full"
                      title={document.nom_fichier}
                    >
                      <iframe
                        src={getDisplayFileUrl()}
                        className="absolute inset-0 w-full h-full"
                        title={document.nom_fichier}
                      />
                    </object>
                  ) : (
                    <iframe
                      src={getDisplayFileUrl()}
                      className="absolute inset-0 w-full h-full"
                      title={document.nom_fichier}
                      allow="autoplay"
                    />
                  )
                ) : (
                  <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] text-black whitespace-nowrap">
                    Pas de fichier
                  </p>
                )}
              </div>

              {/* Comments Section */}
              <div className="content-stretch flex flex-[1_0_0] flex-col gap-[10px] h-full items-start min-w-px relative">
                {/* Description Section */}
                {document.description && (
                  <div className="bg-[#f7f7f7] relative shrink-0 w-full rounded-[4px]">
                    <div className="content-stretch flex flex-col gap-[10px] items-start pl-[20px] pr-[10px] py-[20px] relative size-full">
                      <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] text-right whitespace-nowrap" style={{ color: accentColor }}>
                        Description
                      </p>
                      <div className="w-full p-[15px]">
                        <p className="font-['Inter:Regular',sans-serif] font-normal leading-[1.5] not-italic relative text-[16px] text-[#36302a] whitespace-pre-wrap break-words">
                          {document.description}
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Comments Section */}
                <div className="bg-[#f7f7f7] relative shrink-0 w-full">
                  <div className="content-stretch flex flex-col gap-[10px] items-start pl-[20px] pr-[10px] py-[20px] relative size-full">
                    <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] text-right whitespace-nowrap" style={{ color: accentColor }}>
                      Commentaires
                    </p>

                    {/* Comments List */}
                    <div className="content-stretch flex flex-col gap-[20px] items-start relative shrink-0 w-full">
                      {comments.map((comment) => (
                        <div key={comment.id} className="relative shrink-0 w-full">
                          <div className="content-stretch flex flex-col items-start px-[10px] relative size-full">
                            <div className="content-stretch flex flex-col items-end relative shrink-0 w-full">
                              <div className="relative shrink-0 w-full">
                                <div className="flex flex-row items-center justify-center size-full">
                                  <div className="content-stretch flex items-center justify-center px-[10px] relative size-full">
                                    <p className="flex-[1_0_0] font-['Inter:Medium',sans-serif] font-medium leading-[normal] min-w-px not-italic relative text-[16px] text-right" style={{ color: accentColor }}>
                                      {comment.username} - {new Date(comment.date).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                    </p>
                                  </div>
                                </div>
                              </div>
                              <div className="relative rounded-[10px] shrink-0 w-full" style={{ backgroundColor: accentColor }}>
                                <div className="flex flex-row justify-center size-full">
                                  <div className="content-stretch flex gap-[10px] items-start justify-center p-[10px] relative size-full">
                                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal leading-[normal] min-w-px not-italic relative text-[16px] text-[#ffffff]">
                                      {comment.text}
                                    </p>
                                    {!isStudent && (
                                      <div className="content-stretch flex flex-row gap-[5px] items-center relative shrink-0">
                                        <button
                                          onClick={() => {
                                            setEditingCommentId(comment.id);
                                            setEditCommentText(comment.text);
                                          }}
                                          className="relative shrink-0 size-[16px]"
                                        >
                                          <div className="absolute inset-[8.33%]">
                                            <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 20.0007 20.0007">
                                              <path d={professorSvgPaths.p28ddbb00} fill="var(--fill-0, #F7F7F7)" />
                                            </svg>
                                          </div>
                                        </button>
                                        <button
                                          onClick={() => handleDeleteComment(comment.id)}
                                          className="relative shrink-0 size-[16px]"
                                        >
                                          <div className="absolute inset-[12.5%_20.83%]">
                                            <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 14 18">
                                              <path d={professorSvgPaths.p2eb23700} fill="var(--fill-0, #F7F7F7)" />
                                            </svg>
                                          </div>
                                        </button>
                                      </div>
                                    )}
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}

                      {comments.length === 0 && (
                        <p className="font-['Inter:Regular',sans-serif] text-[16px] px-[10px]" style={{ color: accentColor }}>
                          Aucun commentaire pour le moment
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                {/* Student: New Version / Professor: Comment & Follow */}
                {isStudent ? (
                  <button
                    onClick={handleNewVersion}
                    className="relative rounded-[4px] shrink-0 w-full"
                    style={{ backgroundColor: accentColor }}
                  >
                    <div className="flex flex-row items-center justify-center size-full">
                      <div className="content-stretch flex items-center justify-center p-[10px] relative size-full">
                        <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[24px] text-[#ffffff] whitespace-nowrap">
                          Proposer une nouvelle version
                        </p>
                      </div>
                    </div>
                  </button>
                ) : (
                  <div className="content-stretch flex flex-col gap-[15px] relative shrink-0 w-full">
                    <textarea
                      value={newComment}
                      onChange={(e) => setNewComment(e.target.value)}
                      placeholder="Ajouter un commentaire..."
                      className="w-full p-[10px] border border-[#36302a] rounded text-[14px] font-['Inter:Regular',sans-serif] focus:outline-none focus:border-2 focus:border-[#4b575f] resize-none"
                      rows={3}
                    />
                    <div className="content-stretch flex gap-[40px] items-center justify-center relative shrink-0 w-full">
                      <div className="content-stretch flex flex-[1_0_0] gap-[6px] items-center min-w-px relative">
                        <input
                          type="checkbox"
                          checked={followStudent}
                          onChange={(e) => {
                            toggleSubscription(e.target.checked);
                          }}
                          disabled={loadingSubscription}
                          className="relative shrink-0 w-[20px] h-[20px] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        />
                        <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[16px] whitespace-nowrap">
                          Suivre l'étudiant
                        </p>
                      </div>
                      <button
                        onClick={handleAddComment}
                        disabled={addingComment || !newComment.trim()}
                        className="flex-[1_0_0] min-w-px relative rounded-[4px] disabled:opacity-50 disabled:cursor-not-allowed"
                        style={{ backgroundColor: accentColor }}
                      >
                        <div className="flex flex-row items-center justify-center size-full">
                          <div className="content-stretch flex items-center justify-center p-[10px] relative size-full">
                            <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[15px] text-[#ffffff] whitespace-nowrap">
                              {addingComment ? 'Ajout en cours...' : 'Ajouter un commentaire'}
                            </p>
                          </div>
                        </div>
                      </button>
                    </div>
                  </div>
                )}

                {/* Autres fichiers de l'étudiant (Professor only) */}
                {!isStudent && otherStudentDocuments.length > 0 && (
                  <div className="bg-[#f7f7f7] relative shrink-0 w-full rounded-[4px]">
                    <div className="content-stretch flex flex-col gap-[10px] items-start pl-[20px] pr-[10px] py-[20px] relative size-full">
                      <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[20px] text-right whitespace-nowrap" style={{ color: accentColor }}>
                        Autres fichiers de {studentUsername || 'l\'étudiant'}
                      </p>
                      <div className="content-stretch flex flex-col gap-[8px] items-start relative shrink-0 w-full">
                        {otherStudentDocuments.map((doc) => (
                          <Link
                            key={doc.id}
                            to={`/professor/file/${doc.id}`}
                            className="content-stretch flex items-center justify-between py-[8px] px-[10px] relative shrink-0 w-full hover:bg-gray-100 border-b border-[#d9d9d9] cursor-pointer rounded transition-colors"
                          >
                            <div className="flex-[1_0_0] flex flex-col">
                              <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[14px] truncate">
                                {doc.nom_fichier}
                              </p>
                              <p className="font-['Inter:Regular',sans-serif] font-normal text-[#999999] text-[12px]">
                                {new Date(doc.created_at).toLocaleDateString('fr-FR')}
                              </p>
                            </div>
                            <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[12px] text-right ml-[10px]">
                              {doc.version}
                            </p>
                          </Link>
                        ))}
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Modal Nouvelle Version */}
        <NewVersionModal
          show={newVersionModal.show}
          doc={newVersionModal.doc}
          selectedFile={selectedFile}
          isDragging={isDragging}
          uploadingVersion={uploadingVersion}
          accentColor={accentColor}
          onClose={handleCloseVersionModal}
          onFileSelected={setSelectedFile}
          onUpload={handleUploadVersion}
          onDragOver={handleVersionDragOver}
          onDragLeave={handleVersionDragLeave}
          onDrop={handleVersionDrop}
        />

        {/* Modal Édition Commentaire */}
        {editingCommentId !== null && (
          <div className="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
            <div className="bg-[#ffffff] rounded-[8px] shadow-lg p-[20px] max-w-[500px] w-[90%]">
              <h3 className="font-['Inter:Bold',sans-serif] font-bold text-[20px] mb-[15px]" style={{ color: accentColor }}>
                Modifier le commentaire
              </h3>
              <textarea
                value={editCommentText}
                onChange={(e) => setEditCommentText(e.target.value)}
                placeholder="Modifier votre commentaire..."
                className="w-full p-[10px] border border-[#d9d9d9] rounded text-[14px] font-['Inter:Regular',sans-serif] focus:outline-none focus:border-2 resize-none"
                rows={4}
              />
              <div className="flex gap-[10px] mt-[15px] justify-end">
                <button
                  onClick={() => {
                    setEditingCommentId(null);
                    setEditCommentText('');
                  }}
                  className="px-[15px] py-[8px] border border-[#d9d9d9] rounded text-[14px] font-['Inter:Medium',sans-serif] text-[#36302a] hover:bg-[#f7f7f7]"
                >
                  Annuler
                </button>
                <button
                  onClick={() => handleUpdateComment(editingCommentId)}
                  className="px-[15px] py-[8px] rounded text-[14px] font-['Inter:Medium',sans-serif] text-[#ffffff]"
                  style={{ backgroundColor: accentColor }}
                >
                  Sauvegarder
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
