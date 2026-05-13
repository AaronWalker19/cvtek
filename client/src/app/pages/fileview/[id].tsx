import { useState, useEffect, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import NewVersionModal from '../../../components/NewVersionModal';
import { uploadFile, updateDocument } from '../../../api/client';
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
  id: string;
  authorName: string;
  content: string;
}

export default function FileView() {
  const { user } = useAuth();
  const { id, fileId } = useParams<{ id?: string; fileId?: string }>();
  const docId = id || fileId;
  const fileInputRef = useRef<HTMLInputElement>(null);
  const dropZoneRef = useRef<HTMLDivElement>(null);
  
  const [document, setDocument] = useState<DocumentWithVersions | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [newComment, setNewComment] = useState('');
  const [followStudent, setFollowStudent] = useState(false);
  const [editingCommentId, setEditingCommentId] = useState<string | null>(null);
  const [showVersionModal, setShowVersionModal] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [uploadingVersion, setUploadingVersion] = useState(false);
  const [selectedVersion, setSelectedVersion] = useState<number | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [newVersionModal, setNewVersionModal] = useState<{ show: boolean; doc: Document | null }>({ show: false, doc: null });

  useEffect(() => {
    const fetchDocument = async () => {
      try {
        setLoading(true);
        setError(null);
        console.log('📄 Fetching document:', docId);
        
        const response = await apiFetch(`/api/documents/${docId}`);
        if (response.ok) {
          const data = await response.json();
          console.log('✅ Document loaded:', data);
          console.log('   URL du fichier:', data.url_fichier);
          setDocument(data);
          // TODO: Fetch comments from API when endpoint is available
          setComments([]);
        } else {
          console.error('❌ Document not found:', response.status);
          setError('Document non trouvé');
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
  }, [docId]);

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
      alert('Veuillez sélectionner un fichier');
      return;
    }

    if (!newVersionModal.doc) {
      alert('Erreur: document non trouvé');
      return;
    }

    try {
      setUploadingVersion(true);

      // Uploader le fichier
      const formData = new FormData();
      formData.append('file', selectedFile);

      console.log('📤 Uploading file:', selectedFile.name);

      const uploadResponse = await fetch('http://localhost:5000/api/upload', {
        method: 'POST',
        body: formData,
        // Ne pas ajouter Content-Type, le navigateur le fera automatiquement
      });

      console.log('Upload response status:', uploadResponse.status);

      const uploadText = await uploadResponse.text();
      console.log('Upload response text:', uploadText);

      if (!uploadResponse.ok) {
        try {
          const errorData = JSON.parse(uploadText);
          console.error('❌ Upload error:', errorData);
          alert(`Erreur upload: ${errorData.error || 'Erreur inconnue'}`);
        } catch {
          console.error('❌ Upload error (raw):', uploadText);
          alert(`Erreur upload: ${uploadText || 'Erreur inconnue'}`);
        }
        return;
      }

      let uploadData;
      try {
        uploadData = JSON.parse(uploadText);
      } catch {
        console.error('❌ Invalid JSON response:', uploadText);
        alert('Erreur: réponse invalide du serveur');
        return;
      }

      console.log('✅ Upload success:', uploadData);

      if (!uploadData.url) {
        alert('Erreur: pas d\'URL retournée par le serveur');
        return;
      }

      const fileUrl = uploadData.url;

      // Créer la nouvelle version
      console.log('📝 Creating new version for document:', newVersionModal.doc.id);
      const response = await apiFetch(`/api/documents/${newVersionModal.doc.id}/version`, {
        method: 'POST',
        body: JSON.stringify({
          url_fichier: fileUrl,
        }),
      });

      console.log('Version creation response status:', response.status);

      if (response.ok) {
        const data = await response.json();
        console.log('✅ Version created:', data);
        alert(`${data.message}`);
        
        // Recharger le document
        const docResponse = await apiFetch(`/api/documents/${newVersionModal.doc.id}`);
        if (docResponse.ok) {
          const updatedDoc = await docResponse.json();
          setDocument(updatedDoc);
          setSelectedVersion(updatedDoc.version);
        }

        // Réinitialiser le modal
        setSelectedFile(null);
        setNewVersionModal({ show: false, doc: null });
        setIsDragging(false);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      } else {
        const errorText = await response.text();
        let errorMessage = 'Erreur lors de la création de la version';
        try {
          const errorData = JSON.parse(errorText);
          errorMessage = errorData.error || errorMessage;
        } catch {
          errorMessage = errorText || errorMessage;
        }
        console.error('❌ Version creation error:', errorMessage);
        alert(`Erreur: ${errorMessage}`);
      }
    } catch (err) {
      console.error('❌ Erreur upload version:', err);
      alert(`Erreur lors de l'upload de la nouvelle version: ${err instanceof Error ? err.message : 'Erreur inconnue'}`);
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

  const handleVersionChange = async (versionId: number, version: number) => {
    try {
      const response = await apiFetch(`/api/documents/${versionId}?version=${version}`);
      if (response.ok) {
        const data = await response.json();
        setDocument(data);
        setSelectedVersion(version);
      }
    } catch (err) {
      console.error('Erreur changement version:', err);
    }
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
  const currentVersionNumber = selectedVersion || document.version;

  const handleAddComment = () => {
    if (newComment.trim()) {
      alert(`Commentaire ajouté: ${newComment}`);
      setNewComment('');
    }
  };

  const handleDeleteComment = (commentId: string) => {
    // eslint-disable-next-line no-restricted-globals
    if (confirm('Voulez-vous vraiment supprimer ce commentaire?')) {
      alert(`Commentaire ${commentId} supprimé`);
    }
  };

  const handleNewVersion = () => {
    setNewVersionModal({ show: true, doc: document });
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
                    value={currentVersionNumber}
                    onChange={(e) => {
                      const version = parseInt(e.target.value);
                      const versionData = document.availableVersions.find(v => v.version === version);
                      if (versionData) {
                        handleVersionChange(versionData.id, version);
                      }
                    }}
                    className="px-[10px] py-[5px] border border-[#36302a] rounded text-[14px] font-['Inter:Regular',sans-serif]"
                    style={{ borderColor: accentColor }}
                  >
                    {document.availableVersions.map((v) => (
                      <option key={v.id} value={v.version}>
                        v{v.version}.0 ({new Date(v.created_at).toLocaleDateString('fr-FR')})
                      </option>
                    ))}
                  </select>
                )}
              </div>
              {!isStudent && (
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[24px] whitespace-nowrap" style={{ color: accentColor }}>
                  Étudiant
                </p>
              )}
            </div>

            {/* Content */}
            <div className="content-stretch flex flex-[1_0_0] gap-[20px] items-start min-h-px relative w-full">
              {/* PDF Preview */}
              <div className="bg-[#d9d9d9] content-stretch flex flex-col gap-[10px] h-[901px] items-center justify-center relative shrink-0 w-[701px] overflow-hidden">
                <div aria-hidden="true" className="absolute border-9 border-black border-solid inset-0 pointer-events-none" />
                {document.url_fichier ? (
                  document.url_fichier.toLowerCase().includes('.pdf') ? (
                    <object
                      data={document.url_fichier}
                      type="application/pdf"
                      className="absolute inset-0 w-full h-full"
                      title={document.nom_fichier}
                    >
                      <iframe
                        src={document.url_fichier}
                        className="absolute inset-0 w-full h-full"
                        title={document.nom_fichier}
                      />
                    </object>
                  ) : (
                    <iframe
                      src={document.url_fichier}
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
                                      {comment.authorName}
                                    </p>
                                  </div>
                                </div>
                              </div>
                              <div className="relative rounded-[20px] shrink-0 w-full" style={{ backgroundColor: accentColor }}>
                                <div className="flex flex-row justify-center size-full">
                                  <div className="content-stretch flex gap-[10px] items-start justify-center p-[10px] relative size-full">
                                    <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal leading-[normal] min-w-px not-italic relative text-[16px] text-[#ffffff]">
                                      {comment.content}
                                    </p>
                                    {!isStudent && (
                                      <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-[24px]">
                                        <button
                                          onClick={() => setEditingCommentId(comment.id)}
                                          className="relative shrink-0 size-[24px]"
                                        >
                                          <div className="absolute inset-[8.33%]">
                                            <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 20.0007 20.0007">
                                              <path d={professorSvgPaths.p28ddbb00} fill="var(--fill-0, #F7F7F7)" />
                                            </svg>
                                          </div>
                                        </button>
                                        <button
                                          onClick={() => handleDeleteComment(comment.id)}
                                          className="relative shrink-0 size-[24px]"
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
                        <button
                          onClick={() => setFollowStudent(!followStudent)}
                          className="relative shrink-0 size-[35px]"
                        >
                          <div className="absolute inset-[12.5%]">
                            <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 26.25 30.3537">
                              <g>
                                <g />
                                <path
                                  clipRule="evenodd"
                                  d={professorSvgPaths.p25feb300}
                                  fill={followStudent ? "var(--fill-0, #36302A)" : "none"}
                                  fillRule="evenodd"
                                  stroke={followStudent ? "none" : "#36302A"}
                                  strokeWidth={followStudent ? "0" : "2"}
                                />
                              </g>
                            </svg>
                          </div>
                        </button>
                        <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[16px] whitespace-nowrap">
                          Suivre l'étudiant
                        </p>
                      </div>
                      <button
                        onClick={handleAddComment}
                        className="flex-[1_0_0] min-w-px relative rounded-[4px]"
                        style={{ backgroundColor: accentColor }}
                      >
                        <div className="flex flex-row items-center justify-center size-full">
                          <div className="content-stretch flex items-center justify-center p-[10px] relative size-full">
                            <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[15px] text-[#ffffff] whitespace-nowrap">
                              Ajouter un commentaire
                            </p>
                          </div>
                        </div>
                      </button>
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
      </div>
    </div>
  );
}
