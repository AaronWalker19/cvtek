import { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import Sidebar from '../components/Sidebar';
import NewVersionModal from '../components/NewVersionModal';
import svgPaths from '../../imports/PageDeBase/svg-m4lsbi1cy8';
import { apiFetch } from '../../api/client';

interface Document {
  id: number;
  user_id: number;
  nom_fichier: string;
  titre?: string;
  type_fichier: string;
  url_fichier: string;
  description?: string;
  version: number;
  comment_count: number;
  created_at: string;
  updated_at?: string;
}

export default function StudentDashboard() {
  const { currentUser } = useAuth();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const dropZoneRef = useRef<HTMLDivElement>(null);
  
  const [searchQuery, setSearchQuery] = useState('');
  const [sourceType, setSourceType] = useState('fichier'); // 'fichier' or 'url'
  const [newFileUrl, setNewFileUrl] = useState('');
  const [newFileName, setNewFileName] = useState('');
  const [newFileTitle, setNewFileTitle] = useState('');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [newFileType, setNewFileType] = useState('');
  const [newFileDescription, setNewFileDescription] = useState('');
  const [documents, setDocuments] = useState<Document[]>([]);
  const [loading, setLoading] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [deleteConfirm, setDeleteConfirm] = useState<{ show: boolean; docId: number | null }>({ show: false, docId: null });
  const [openMenuId, setOpenMenuId] = useState<number | null>(null);
  const [newVersionModal, setNewVersionModal] = useState<{ show: boolean; doc: Document | null }>({ show: false, doc: null });
  const [newVersionFile, setNewVersionFile] = useState<File | null>(null);
  const [isDraggingVersion, setIsDraggingVersion] = useState(false);
  const [uploadingVersion, setUploadingVersion] = useState(false);
  const newVersionFileInputRef = useRef<HTMLInputElement>(null);
  const [documentVersions, setDocumentVersions] = useState<{ [docId: number]: any[] }>({});
  const [openVersionDropdown, setOpenVersionDropdown] = useState<number | null>(null);

  // Charger les documents au démarrage
  useEffect(() => {
    loadDocuments();
  }, [currentUser.id]);

  const loadDocuments = async () => {
    try {
      setLoading(true);
      const response = await apiFetch(`/api/documents?user_id=${currentUser.id}`);
      if (response.ok) {
        const data = await response.json();
        // Grouper les documents par nom_fichier et garder que la version la plus récente
        const latestVersions = data.reduce((acc: any, doc: Document) => {
          const existing = acc.find((d: Document) => d.nom_fichier === doc.nom_fichier);
          if (!existing || doc.version > existing.version) {
            if (existing) {
              acc = acc.filter((d: Document) => d.nom_fichier !== doc.nom_fichier);
            }
            acc.push(doc);
          }
          return acc;
        }, []);
        setDocuments(latestVersions);
      }
    } catch (err) {
      console.error('Erreur chargement documents:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadVersionsForDocument = async (docId: number) => {
    try {
      const response = await apiFetch(`/api/documents/${docId}`);
      if (response.ok) {
        const data = await response.json();
        setDocumentVersions(prev => ({
          ...prev,
          [docId]: data.versions || []
        }));
      }
    } catch (err) {
      console.error('Erreur chargement versions:', err);
    }
  };

  const handleFileSelected = (file: File) => {
    setSelectedFile(file);
    setNewFileName(file.name);
  };

  const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      handleFileSelected(file);
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
      handleFileSelected(files[0]);
    }
  };

  const handleDropZoneClick = () => {
    fileInputRef.current?.click();
  };

  const filteredDocuments = documents.filter(doc =>
    doc.nom_fichier.toLowerCase().includes(searchQuery.toLowerCase()) ||
    doc.type_fichier.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handleAddFile = async () => {
    if (!newFileType) {
      alert('Veuillez sélectionner un type');
      return;
    }

    if (sourceType === 'fichier' && !selectedFile) {
      alert('Veuillez sélectionner un fichier');
      return;
    }

    if (sourceType === 'url' && !newFileUrl) {
      alert('Veuillez entrer une URL');
      return;
    }

    try {
      let fileUrl = newFileUrl;

      // Si c'est un fichier, l'uploader d'abord
      if (sourceType === 'fichier' && selectedFile) {
        const formData = new FormData();
        formData.append('file', selectedFile);

        const uploadResponse = await fetch('http://localhost:5000/api/upload', {
          method: 'POST',
          body: formData,
        });

        if (!uploadResponse.ok) {
          const error = await uploadResponse.json();
          alert(`Erreur upload: ${error.error}`);
          return;
        }

        const uploadData = await uploadResponse.json();
        fileUrl = uploadData.url;
      }

      // Créer le document avec l'URL du fichier uploadé
      const response = await apiFetch('/api/documents', {
        method: 'POST',
        body: JSON.stringify({
          user_id: currentUser.id,
          nom_fichier: sourceType === 'fichier' ? selectedFile!.name : newFileUrl,
          titre: newFileTitle,
          type_fichier: newFileType,
          url_fichier: fileUrl,
          description: newFileDescription,
        }),
      });

      if (response.ok) {
        alert(`Fichier "${selectedFile?.name || newFileUrl}" ajouté avec succès!`);
        setNewFileName('');
        setNewFileTitle('');
        setSelectedFile(null);
        setNewFileType('');
        setNewFileDescription('');
        setNewFileUrl('');
        setSourceType('fichier');
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
        await loadDocuments();
      } else {
        const error = await response.json();
        alert(`Erreur: ${error.error}`);
      }
    } catch (err) {
      console.error('Erreur ajout fichier:', err);
      alert('Erreur lors de l\'ajout du fichier');
    }
  };

  const handleDeleteDocument = async (docId: number) => {
    try {
      const response = await apiFetch(`/api/documents/${docId}`, {
        method: 'DELETE',
      });

      if (response.ok) {
        alert('Fichier supprimé avec succès');
        await loadDocuments();
      } else {
        alert('Erreur lors de la suppression du fichier');
      }
    } catch (err) {
      console.error('Erreur suppression fichier:', err);
      alert('Erreur lors de la suppression du fichier');
    }
  };

  const handleVersionDragOver = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDraggingVersion(true);
  };

  const handleVersionDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDraggingVersion(false);
  };

  const handleVersionDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDraggingVersion(false);
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      setNewVersionFile(files[0]);
    }
  };

  const handleNewVersion = async () => {
    if (!newVersionFile) {
      alert('Veuillez sélectionner un fichier');
      return;
    }

    if (!newVersionModal.doc) {
      alert('Erreur: document non trouvé');
      return;
    }

    try {
      setUploadingVersion(true);
      // Uploader le nouveau fichier
      const formData = new FormData();
      formData.append('file', newVersionFile);

      const uploadResponse = await fetch('http://localhost:5000/api/upload', {
        method: 'POST',
        body: formData,
      });

      if (!uploadResponse.ok) {
        const error = await uploadResponse.json();
        alert(`Erreur upload: ${error.error}`);
        return;
      }

      const uploadData = await uploadResponse.json();
      const fileUrl = uploadData.url;

      // Créer la nouvelle version
      const response = await apiFetch(`/api/documents/${newVersionModal.doc.id}/version`, {
        method: 'POST',
        body: JSON.stringify({
          url_fichier: fileUrl,
        }),
      });

      if (response.ok) {
        const data = await response.json();
        alert(`Nouvelle version ${data.version}.0 créée avec succès!`);
        setNewVersionModal({ show: false, doc: null });
        setNewVersionFile(null);
        setIsDraggingVersion(false);
        if (newVersionFileInputRef.current) {
          newVersionFileInputRef.current.value = '';
        }
        await loadDocuments();
      } else {
        const error = await response.json();
        alert(`Erreur: ${error.error}`);
      }
    } catch (err) {
      console.error('Erreur création nouvelle version:', err);
      alert('Erreur lors de la création de la nouvelle version');
    } finally {
      setUploadingVersion(false);
    }
  };

  const handleCloseVersionModal = () => {
    setNewVersionModal({ show: false, doc: null });
    setNewVersionFile(null);
    setIsDraggingVersion(false);
    if (newVersionFileInputRef.current) {
      newVersionFileInputRef.current.value = '';
    }
  };

  return (
    <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor="bg-[#b51621]" />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full">
            {/* Add File Section */}
            <div className="relative w-full flex flex-col">
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic text-[#b51621] text-[32px] pb-[10px]">Ajouter un fichier</p>
              <div className="h-[3px] bg-[#b51621] w-full" />
            </div>

            <div className="bg-[#f7f7fd] relative shrink-0 w-full">
              <div className="flex flex-col items-center size-full">
                <div className="content-stretch flex flex-col items-center p-[20px] relative size-full">
                  <div className="content-stretch flex gap-[50px] items-center justify-center relative shrink-0 w-full">
                    {/* Upload/URL Area */}
                    {sourceType === 'fichier' ? (
                      <div
                        ref={dropZoneRef}
                        onClick={handleDropZoneClick}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                        className={`bg-[#f7f7f7] content-stretch flex flex-col gap-[20px] h-[224px] items-center justify-center p-[10px] relative rounded-[4px] shrink-0 w-[568px] cursor-pointer transition-colors ${
                          isDragging ? 'bg-[#e8e8e8] border-[#b51621]' : ''
                        }`}
                      >
                        <div aria-hidden="true" className={`absolute border-2 border-dashed inset-0 pointer-events-none rounded-[4px] transition-colors ${
                          isDragging ? 'border-[#b51621]' : 'border-[#a4a4a4]'
                        }`} />
                        <div className="relative shrink-0 size-[104px]">
                          <div className="absolute inset-[16.67%]">
                            <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 69.3334 69.3334">
                              <path d={svgPaths.p8d4b580} fill={isDragging ? '#b51621' : '#A4A4A4'} />
                            </svg>
                          </div>
                        </div>
                        <div className="text-center">
                          {selectedFile ? (
                            <div>
                              <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[#b51621] text-[18px]">{selectedFile.name}</p>
                              <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#a4a4a4] text-[14px] mt-[5px]">Cliquez ou glissez pour changer</p>
                            </div>
                          ) : (
                            <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#a4a4a4] text-[24px] whitespace-nowrap">Uploader un fichier</p>
                          )}
                        </div>
                        <input
                          ref={fileInputRef}
                          type="file"
                          onChange={handleFileInputChange}
                          className="hidden"
                        />
                      </div>
                    ) : (
                      <div className="bg-[#f7f7f7] content-stretch flex flex-col gap-[20px] h-auto items-start justify-center p-[10px] relative rounded-[4px] shrink-0 w-[568px]">
                        <div aria-hidden="true" className="absolute border border-[#a4a4a4] border-dashed inset-0 pointer-events-none rounded-[4px]" />
                        <input
                          type="url"
                          value={newFileUrl}
                          onChange={(e) => setNewFileUrl(e.target.value)}
                          placeholder="Entrez l'URL du fichier"
                          className="w-full text-left bg-transparent outline-none font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[20px] text-[#36302a] placeholder:text-[#a4a4a4]"
                        />
                      </div>
                    )}

                    {/* Form */}
                    <div className="content-stretch flex flex-[1_0_0] flex-col gap-[20px] items-start min-w-px relative self-stretch">
                      <div className="content-stretch flex gap-[20px] items-start relative shrink-0 w-full">
                        <div className="content-stretch flex gap-[5px] items-center p-[10px] relative rounded-[4px] shrink-0">
                          <div aria-hidden="true" className="absolute border border-[#36302a] border-solid inset-0 pointer-events-none rounded-[4px]" />
                          <select
                            value={sourceType}
                            onChange={(e) => {
                              setSourceType(e.target.value as 'fichier' | 'url');
                              setNewFileUrl('');
                              setNewFileName('');
                              setSelectedFile(null);
                              if (fileInputRef.current) {
                                fileInputRef.current.value = '';
                              }
                            }}
                            className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#36302a] text-[16px] w-[100px]"
                          >
                            <option value="fichier">fichier</option>
                            <option value="url">url</option>
                          </select>
                        </div>
                        <div className="content-stretch flex gap-[5px] items-center p-[10px] relative rounded-[4px] shrink-0">
                          <div aria-hidden="true" className="absolute border border-[#36302a] border-solid inset-0 pointer-events-none rounded-[4px]" />
                          <select
                            value={newFileType}
                            onChange={(e) => setNewFileType(e.target.value)}
                            className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#36302a] text-[16px] w-[120px]"
                          >
                            <option value="">Sélectionner</option>
                            <option value="cv">cv</option>
                            <option value="portfolio">portfolio</option>
                            <option value="autre">autre</option>
                          </select>
                        </div>
                        <div className="content-stretch flex gap-[5px] items-center p-[10px] relative rounded-[4px] shrink-0 flex-1">
                          <div aria-hidden="true" className="absolute border border-[#36302a] border-solid inset-0 pointer-events-none rounded-[4px]" />
                          <input
                            type="text"
                            value={newFileTitle}
                            onChange={(e) => setNewFileTitle(e.target.value)}
                            placeholder="Titre (optionnel)"
                            className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#36302a] text-[16px] w-full"
                          />
                        </div>
                      </div>
                      <div className="h-[103px] relative rounded-[20px] shrink-0 w-full">
                        <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[20px]" />
                        <textarea
                          value={newFileDescription}
                          onChange={(e) => setNewFileDescription(e.target.value)}
                          placeholder="Ajouter une description"
                          className="content-stretch flex gap-[10px] items-start p-[10px] relative size-full bg-transparent outline-none resize-none font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[20px] text-[rgba(75,87,95,0.5)]"
                        />
                      </div>
                      <button
                        onClick={handleAddFile}
                        className="bg-[#b51621] relative rounded-[4px] shrink-0 w-full"
                      >
                        <div className="flex flex-row items-center justify-center size-full">
                          <div className="content-stretch flex items-center justify-center p-[10px] relative size-full">
                            <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[24px] text-white whitespace-nowrap">Ajouter</p>
                          </div>
                        </div>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* My Files Section */}
            <div className="relative w-full flex flex-col">
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic text-[#b51621] text-[32px] pb-[10px]">Mes fichiers</p>
              <div className="h-[3px] bg-[#b51621] w-full" />
            </div>

            {/* Files List */}
            <div className="content-stretch flex flex-col gap-[18px] items-start relative shrink-0 w-full">
              <div className="content-stretch flex font-['Inter:Medium',sans-serif] font-medium items-center justify-between leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[16px] w-full px-[12px]">
                <p className="flex-[2]">Nom du fichier</p>
                <p className="flex-1 text-center">type</p>
                <p className="flex-1 text-center">date</p>
                <p className="flex-1 text-center">commentaire</p>
                <p className="flex-1 text-center">version</p>
                <p className="flex-[0.6] text-center">modification</p>
              </div>

              {loading ? (
                <p className="text-center w-full text-[#36302a]">Chargement...</p>
              ) : filteredDocuments.length === 0 ? (
                <p className="text-center w-full text-[#36302a]">Aucun fichier pour le moment</p>
              ) : (
                filteredDocuments.map((doc) => (
                  <div
                    key={doc.id}
                    className="content-stretch flex items-center justify-between py-[12px] px-[12px] relative shrink-0 w-full hover:bg-gray-50 border-b border-[#36302a] border-solid group"
                  >
                    {/* Nom du fichier */}
                    <Link
                      to={`/file/${doc.id}`}
                      className="flex-[2] font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] hover:text-[#b51621] cursor-pointer truncate"
                    >
                      {doc.titre || doc.nom_fichier}
                    </Link>

                    {/* Type */}
                    <div className="flex-1 text-center">
                      <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        {doc.type_fichier}
                      </p>
                    </div>

                    {/* Date */}
                    <div className="flex-1 text-center">
                      <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        {new Date(doc.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}
                      </p>
                    </div>

                    {/* Nombre de commentaires */}
                    <div className="flex-1 text-center">
                      <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        {doc.comment_count}
                      </p>
                    </div>

                    {/* Version avec dropdown (seulement si plusieurs versions) */}
                    <div className="flex-1 text-center relative">
                      {documentVersions[doc.id] && documentVersions[doc.id].length > 1 ? (
                        <>
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              setOpenVersionDropdown(openVersionDropdown === doc.id ? null : doc.id);
                            }}
                            className="inline-flex items-center gap-2 px-3 py-1 hover:bg-[#f0f0f0] rounded transition-colors"
                          >
                            <span className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                              {doc.version || 1}.0
                            </span>
                            <svg
                              width="24"
                              height="16"
                              viewBox="0 0 16 16"
                              fill="none"
                              stroke="#36302a"
                              strokeWidth="2"
                              className={`transition-transform ${openVersionDropdown === doc.id ? 'rotate-180' : ''}`}
                            >
                              <polyline points="6 10 12 4 18 10" />
                            </svg>
                          </button>

                          {/* Version dropdown menu */}
                          {openVersionDropdown === doc.id && (
                            <div className="absolute top-full left-1/2 -translate-x-1/2 mt-1 bg-[#ffffff] border-2 border-[#36302a] rounded shadow-lg z-50 min-w-max overflow-hidden">
                              {documentVersions[doc.id].map((version: any) => (
                                <Link
                                  key={version.id}
                                  to={`/file/${version.id}`}
                                  onClick={() => setOpenVersionDropdown(null)}
                                  className="block px-4 py-2 text-[#36302a] text-[14px] hover:bg-[#f0f0f0] border-b border-[#e0e0e0] last:border-b-0 bg-white"
                                >
                                  {version.version}.0
                                </Link>
                              ))}
                            </div>
                          )}
                        </>
                      ) : (
                        <button
                          onClick={(e) => {
                            e.preventDefault();
                            if (!documentVersions[doc.id]) {
                              loadVersionsForDocument(doc.id);
                            }
                          }}
                          onMouseEnter={() => {
                            if (!documentVersions[doc.id]) {
                              loadVersionsForDocument(doc.id);
                            }
                          }}
                          className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px] hover:bg-[#f0f0f0] px-3 py-1 rounded transition-colors"
                        >
                          {doc.version || 1}.0
                        </button>
                      )}
                    </div>

                    {/* Menu Actions (3 dots) */}
                    <div className="flex-[0.6] flex justify-center relative">
                      <button
                        onClick={() => setOpenMenuId(openMenuId === doc.id ? null : doc.id)}
                        className="p-2 hover:bg-[#f0f0f0] rounded transition-colors"
                        title="Actions"
                      >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#36302a" strokeWidth="2">
                          <circle cx="12" cy="5" r="1" />
                          <circle cx="12" cy="12" r="1" />
                          <circle cx="12" cy="19" r="1" />
                        </svg>
                      </button>
                      
                      {/* Dropdown menu */}
                      {openMenuId === doc.id && (
                        <div className="absolute -right-8 top-full mt-1 bg-[#f7f7f7] border-2 border-[#36302a] rounded shadow-xl z-50 min-w-max overflow-hidden">
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              alert('Modifier le fichier');
                              setOpenMenuId(null);
                            }}
                            className="w-full text-left px-4 py-2 text-[#36302a] text-[14px] hover:bg-[#ebebeb] border-b border-[#e0e0e0] bg-[#ffffff]"
                          >
                            Modifier
                          </button>
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              setNewVersionModal({ show: true, doc });
                              setOpenMenuId(null);
                            }}
                            className="w-full text-left px-4 py-2 text-[#36302a] text-[14px] hover:bg-[#ebebeb] border-b border-[#e0e0e0] bg-[#ffffff]"
                          >
                            Nouvelle version
                          </button>
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              setDeleteConfirm({ show: true, docId: doc.id });
                              setOpenMenuId(null);
                            }}
                            className="w-full text-left px-4 py-2 text-red-600 text-[14px] hover:bg-[#fff5f5] bg-[#ffffff]"
                          >
                            Supprimer
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        {/* Modal de confirmation suppression */}
        {deleteConfirm.show && (
          <div className="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
            <div className="bg-[#f7f7f7]  rounded-lg p-8 shadow-2xl max-w-sm border border-[#36302a]">
              <h3 className="text-xl font-bold text-[#36302a] mb-4">Confirmer la suppression</h3>
              <p className="text-[#36302a] mb-6">Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.</p>
              <div className="flex gap-4">
                <button
                  onClick={() => setDeleteConfirm({ show: false, docId: null })}
                  className="flex-1 px-4 py-2 bg-gray-300 text-[#36302a] rounded hover:bg-gray-400 font-medium"
                >
                  Annuler
                </button>
                <button
                  onClick={() => {
                    if (deleteConfirm.docId) {
                      handleDeleteDocument(deleteConfirm.docId);
                    }
                    setDeleteConfirm({ show: false, docId: null });
                  }}
                  className="flex-1 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-medium"
                >
                  Supprimer
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Modal de nouvelle version */}
        <NewVersionModal
          show={newVersionModal.show}
          doc={newVersionModal.doc}
          selectedFile={newVersionFile}
          isDragging={isDraggingVersion}
          uploadingVersion={uploadingVersion}
          accentColor="#b51621"
          onClose={handleCloseVersionModal}
          onFileSelected={setNewVersionFile}
          onUpload={handleNewVersion}
          onDragOver={handleVersionDragOver}
          onDragLeave={handleVersionDragLeave}
          onDrop={handleVersionDrop}
        />
      </div>
    </div>
  );
}
