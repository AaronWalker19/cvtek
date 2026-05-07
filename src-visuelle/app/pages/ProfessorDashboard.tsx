import { useState } from 'react';
import { Link } from 'react-router-dom';
import { mockDocuments } from '../data/mockData';
import Sidebar from '../components/Sidebar';
import svgPaths from '../../imports/PageDeBaseCoteProf/svg-9gqyfpru0n';

export default function ProfessorDashboard() {
  const [searchQuery, setSearchQuery] = useState('');
  const [showFilters, setShowFilters] = useState(false);

  const filteredDocuments = mockDocuments.filter(doc =>
    doc.ownerName.toLowerCase().includes(searchQuery.toLowerCase()) ||
    doc.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    doc.ownerLicense?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="bg-white content-stretch flex items-start relative size-full">
      <Sidebar bgColor="bg-[#4b575f]" showAdmin />

      <div className="flex-[1_0_0] h-full min-w-px relative">
        <div className="flex flex-col items-center overflow-clip rounded-[inherit] size-full">
          <div className="content-stretch flex flex-col gap-[50px] items-center p-[40px] relative size-full">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#4b575f] border-b-3 border-solid inset-0 pointer-events-none" />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] whitespace-nowrap">Documents postée</p>
            </div>

            {/* Search and Filter */}
            <div className="content-stretch flex gap-[17px] items-center relative shrink-0 w-full">
              <div className="bg-white flex-[1_0_0] min-w-px relative rounded-[51px]">
                <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[51px]" />
                <div className="flex flex-row items-center size-full">
                  <div className="content-stretch flex gap-[10px] items-center p-[10px] relative size-full">
                    <div className="relative shrink-0 size-[24px]">
                      <div className="absolute inset-[12.5%_14.27%_14.27%_12.5%]">
                        <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 17.575 17.575">
                          <path d={svgPaths.p1351f980} fill="var(--fill-0, #4B575F)" />
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
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[20px] text-white whitespace-nowrap">Filtre</p>
                <div className="relative shrink-0 size-[24px]">
                  <div className="absolute inset-[12.5%_16.66%_12.49%_16.66%]">
                    <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 16.0022 18.0024">
                      <path d={svgPaths.p1954e540} fill="var(--fill-0, white)" />
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
                  <p className="relative shrink-0">nom fichier</p>
                  <p className="relative shrink-0">type</p>
                  <p className="relative shrink-0">date</p>
                </div>

                <div className="content-stretch flex flex-col gap-[15px] items-start relative shrink-0 w-full">
                  {filteredDocuments.map((doc) => (
                    <Link
                      key={doc.id}
                      to={`/professor/file/${doc.id}`}
                      className="content-stretch flex items-start py-[10px] relative shrink-0 w-full hover:bg-gray-50"
                    >
                      <div aria-hidden="true" className="absolute border-[#36302a] border-b border-solid inset-0 pointer-events-none" />
                      <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                        <p className="leading-[normal]">{doc.ownerName}</p>
                      </div>
                      <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                        <p className="leading-[normal]">{doc.ownerLicense}</p>
                      </div>
                      <div className="flex flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] not-italic relative shrink-0 text-[#36302a] text-[16px] text-right whitespace-nowrap">
                        <p className="leading-[normal]">{doc.name}</p>
                      </div>
                      <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-right">
                        <p className="leading-[normal]">{doc.type}</p>
                      </div>
                      <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px] text-right">
                        <p className="leading-[normal]">{doc.uploadDate.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                      </div>
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
