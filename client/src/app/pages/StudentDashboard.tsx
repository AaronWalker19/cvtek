import { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import Sidebar from '../components/Sidebar';
import svgPaths from '../../imports/PageDeBase/svg-m4lsbi1cy8';
import { apiFetch } from '../../api/client';

interface Document {
  id: number;
  user_id: number;
  nom_fichier: string;
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
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [newFileType, setNewFileType] = useState('');
  const [newFileDescription, setNewFileDescription] = useState('');
  const [documents, setDocuments] = useState<Document[]>([]);
  const [loading, setLoading] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [deleteConfirm, setDeleteConfirm] = useState<{ show: boolean; docId: number | null }>({ show: false, docId: null });
  const [openMenuId, setOpenMenuId] = useState<number | null>(null);

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
        setDocuments(data);
      }
    } catch (err) {
      console.error('Erreur chargement documents:', err);
    } finally {
      setLoading(false);
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

        const uploadResponse = await fetch('http://localhost:3000/api/upload', {
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
          type_fichier: newFileType,
          url_fichier: fileUrl,
          description: newFileDescription,
        }),
      });

      if (response.ok) {
        alert(`Fichier "${selectedFile?.name || newFileUrl}" ajouté avec succès!`);
        setNewFileName('');
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

  return (
    <div className="bg-white content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor="bg-[#b51621]" />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
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
                      </div>
                      <div className="bg-white h-[103px] relative rounded-[20px] shrink-0 w-full">
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
                      {doc.nom_fichier}
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

                    {/* Version avec dropdown */}
                    <div className="flex-1 text-center">
                      <p className="font-['Inter:Regular',sans-serif] font-normal text-[#36302a] text-[16px]">
                        {doc.version || 1}.0
                      </p>
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
                        <div className="absolute -right-8 top-full mt-1 bg-white border border-[#36302a] rounded shadow-lg z-50 min-w-max">
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              alert('Modifier le fichier');
                              setOpenMenuId(null);
                            }}
                            className="w-full text-left px-4 py-2 text-[#36302a] text-[14px] hover:bg-[#f0f0f0] border-b border-gray-200"
                          >
                            Modifier
                          </button>
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              alert('Proposer une nouvelle version');
                              setOpenMenuId(null);
                            }}
                            className="w-full text-left px-4 py-2 text-[#36302a] text-[14px] hover:bg-[#f0f0f0] border-b border-gray-200"
                          >
                            Nouvelle version
                          </button>
                          <button
                            onClick={(e) => {
                              e.preventDefault();
                              setDeleteConfirm({ show: true, docId: doc.id });
                              setOpenMenuId(null);
                            }}
                            className="w-full text-left px-4 py-2 text-red-600 text-[14px] hover:bg-[#f0f0f0]"
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
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 shadow-lg max-w-sm">
              <h3 className="text-xl font-bold text-[#36302a] mb-4">Confirmer la suppression</h3>
              <p className="text-[#36302a] mb-6">Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.</p>
              <div className="flex gap-4">
                <button
                  onClick={() => setDeleteConfirm({ show: false, docId: null })}
                  className="flex-1 px-4 py-2 bg-gray-300 text-[#36302a] rounded hover:bg-gray-400"
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
                  className="flex-1 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                >
                  Supprimer
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
