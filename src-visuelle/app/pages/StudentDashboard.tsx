import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { mockDocuments } from '../data/mockData';
import Sidebar from '../components/Sidebar';
import svgPaths from '../../imports/PageDeBase/svg-m4lsbi1cy8';

export default function StudentDashboard() {
  const { currentUser } = useAuth();
  const [searchQuery, setSearchQuery] = useState('');
  const [newFileName, setNewFileName] = useState('');
  const [newFileType, setNewFileType] = useState('');
  const [newFileDescription, setNewFileDescription] = useState('');

  // Filter documents owned by current user
  const userDocuments = mockDocuments.filter(doc => doc.ownerId === currentUser.id);

  const filteredDocuments = userDocuments.filter(doc =>
    doc.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    doc.type.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handleAddFile = () => {
    if (newFileName && newFileType) {
      alert(`Fichier "${newFileName}" ajouté avec succès!`);
      setNewFileName('');
      setNewFileType('');
      setNewFileDescription('');
    }
  };

  return (
    <div className="bg-white content-stretch flex items-start relative size-full">
      <Sidebar bgColor="bg-[#b51621]" />

      <div className="flex-[1_0_0] h-full min-w-px relative">
        <div className="flex flex-col items-center overflow-clip rounded-[inherit] size-full">
          <div className="content-stretch flex flex-col gap-[50px] items-center p-[40px] relative size-full">
            {/* Add File Section */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#b51621] border-b-3 border-solid inset-0 pointer-events-none" />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#b51621] text-[32px] whitespace-nowrap">Ajouter un fichier</p>
            </div>

            <div className="bg-[#f7f7fd] relative shrink-0 w-full">
              <div className="flex flex-col items-center size-full">
                <div className="content-stretch flex flex-col items-center p-[20px] relative size-full">
                  <div className="content-stretch flex gap-[50px] items-start justify-center relative shrink-0 w-full">
                    {/* Upload Area */}
                    <div className="bg-[#f7f7f7] content-stretch flex flex-col gap-[20px] h-[224px] items-center justify-center p-[10px] relative rounded-[4px] shrink-0 w-[568px]">
                      <div aria-hidden="true" className="absolute border border-[#a4a4a4] border-dashed inset-0 pointer-events-none rounded-[4px]" />
                      <div className="relative shrink-0 size-[104px]">
                        <div className="absolute inset-[16.67%]">
                          <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 69.3334 69.3334">
                            <path d={svgPaths.p8d4b580} fill="var(--fill-0, #A4A4A4)" />
                          </svg>
                        </div>
                      </div>
                      <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#a4a4a4] text-[24px] whitespace-nowrap">Uploader un fichier</p>
                    </div>

                    {/* Form */}
                    <div className="content-stretch flex flex-[1_0_0] flex-col gap-[20px] items-start min-w-px relative self-stretch">
                      <div className="content-stretch flex gap-[20px] items-start relative shrink-0 w-full">
                        <div className="content-stretch flex gap-[5px] items-center p-[10px] relative rounded-[4px] shrink-0">
                          <div aria-hidden="true" className="absolute border border-[#36302a] border-solid inset-0 pointer-events-none rounded-[4px]" />
                          <input
                            type="text"
                            value={newFileName}
                            onChange={(e) => setNewFileName(e.target.value)}
                            placeholder="fichier"
                            className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#36302a] text-[16px] w-[100px]"
                          />
                        </div>
                        <div className="content-stretch flex gap-[5px] items-center p-[10px] relative rounded-[4px] shrink-0">
                          <div aria-hidden="true" className="absolute border border-[#36302a] border-solid inset-0 pointer-events-none rounded-[4px]" />
                          <input
                            type="text"
                            value={newFileType}
                            onChange={(e) => setNewFileType(e.target.value)}
                            placeholder="cv"
                            className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[#36302a] text-[16px] w-[60px]"
                          />
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
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#b51621] border-b-3 border-solid inset-0 pointer-events-none" />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#b51621] text-[32px] whitespace-nowrap">Mes fichiers</p>
            </div>

            {/* Files List */}
            <div className="content-stretch flex flex-col gap-[18px] items-start relative shrink-0 w-full">
              <div className="content-stretch flex font-['Inter:Medium',sans-serif] font-medium items-center justify-between leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[24px] w-full whitespace-nowrap">
                <p className="relative shrink-0">Nom du fichier</p>
                <p className="relative shrink-0">type</p>
                <p className="relative shrink-0">date</p>
                <p className="relative shrink-0">commentaire</p>
                <p className="relative shrink-0">version</p>
                <p className="relative shrink-0">modification</p>
              </div>

              {filteredDocuments.map((doc) => (
                <Link
                  key={doc.id}
                  to={`/file/${doc.id}`}
                  className="content-stretch flex items-start justify-between py-[10px] relative shrink-0 w-full hover:bg-gray-50"
                >
                  <div aria-hidden="true" className="absolute border-[#36302a] border-b border-solid inset-0 pointer-events-none" />
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="leading-[normal]">{doc.name}</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-center">
                    <p className="leading-[normal]">{doc.type}</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-center">
                    <p className="leading-[normal]">{doc.uploadDate.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-right">
                    <p className="leading-[normal]">{doc.comments.length}</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-right">
                    <p className="leading-[normal]">{doc.version}</p>
                  </div>
                  <div className="flex flex-[1_0_0] gap-[10px] items-center justify-end min-w-px relative self-stretch">
                    <div className="relative shrink-0 size-[24px]">
                      <div className="absolute inset-[12.5%_41.67%]">
                        <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 4 18">
                          <path d={svgPaths.p156409f0} fill="var(--fill-0, #36302A)" />
                        </svg>
                      </div>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
