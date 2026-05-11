import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { mockDocuments } from '../data/mockData';
import Sidebar from '../components/Sidebar';
import svgPaths from '../../imports/PageDeFichier/svg-g1nozp2mpd';

export default function StudentFileView() {
  const { fileId } = useParams<{ fileId: string }>();
  const document = mockDocuments.find(doc => doc.id === fileId);

  if (!document) {
    return <div>Document non trouvé</div>;
  }

  return (
    <div className="bg-[#ffffff] content-stretch flex items-start relative size-full">
      <Sidebar bgColor="bg-[#b51621]" />

      <div className="flex-[1_0_0] h-full min-w-px relative">
        <div className="flex flex-col items-center overflow-clip rounded-[inherit] size-full">
          <div className="content-stretch flex flex-col gap-[50px] items-center p-[40px] relative size-full">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#b51621] border-b-3 border-solid inset-0 pointer-events-none" />
              <Link to="/" className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#b51621] text-[32px] whitespace-nowrap hover:underline">
                ← {document.name}
              </Link>
            </div>

            {/* Content */}
            <div className="content-stretch flex flex-[1_0_0] gap-[20px] items-start min-h-px relative w-full">
              {/* PDF Preview */}
              <div className="bg-[#d9d9d9] content-stretch flex flex-col gap-[10px] h-[901px] items-center justify-center pb-[412px] pl-[270px] pr-[269px] pt-[450px] relative shrink-0 w-[701px]">
                <div aria-hidden="true" className="absolute border-9 border-black border-solid inset-0 pointer-events-none" />
                <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] text-black whitespace-nowrap">fichier pdf</p>
                <button className="absolute bg-[#4b575f] left-[633px] rounded-[40px] size-[54px] top-[14px]">
                  <div className="absolute inset-[16.67%]">
                    <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 36 36">
                      <path d={svgPaths.p213ae500} fill="var(--fill-0, white)" />
                    </svg>
                  </div>
                </button>
              </div>

              {/* Comments Section */}
              <div className="content-stretch flex flex-[1_0_0] flex-col gap-[40px] h-full items-center min-w-px relative">
                <div className="bg-[#f7f7f7] relative shrink-0 w-full">
                  <div className="content-stretch flex flex-col gap-[10px] items-start pl-[20px] pr-[10px] py-[20px] relative size-full">
                    <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] text-right whitespace-nowrap">Commentaires</p>

                    {/* Comments List */}
                    <div className="content-stretch flex flex-col gap-[20px] items-start relative shrink-0 w-full">
                      {document.comments.map((comment) => (
                        <div key={comment.id} className="content-stretch flex flex-col items-start relative shrink-0 w-full">
                          <div className="relative shrink-0 w-full">
                            <div className="flex flex-col items-end size-full">
                              <div className="content-stretch flex flex-col items-end px-[10px] relative size-full">
                                <div className="content-stretch flex flex-col items-end relative shrink-0 w-full">
                                  <div className="relative shrink-0 w-full">
                                    <div className="flex flex-row items-center justify-center size-full">
                                      <div className="content-stretch flex items-center justify-center px-[10px] relative size-full">
                                        <p className="flex-[1_0_0] font-['Inter:Medium',sans-serif] font-medium leading-[normal] min-w-px not-italic relative text-[#4b575f] text-[16px] text-right">{comment.authorName}</p>
                                      </div>
                                    </div>
                                  </div>
                                  <div className="bg-[#4b575f] relative rounded-[20px] shrink-0 w-full">
                                    <div className="flex flex-row justify-center size-full">
                                      <div className="content-stretch flex gap-[10px] items-start justify-center p-[10px] relative size-full">
                                        <p className="flex-[1_0_0] font-['Inter:Regular',sans-serif] font-normal leading-[normal] min-w-px not-italic relative text-[16px] text-white">{comment.content}</p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}

                      {document.comments.length === 0 && (
                        <p className="font-['Inter:Regular',sans-serif] text-[#4b575f] text-[16px]">Aucun commentaire pour le moment</p>
                      )}
                    </div>
                  </div>
                </div>

                {/* Upload New Version Button */}
                <button
                  onClick={() => alert('Fonctionnalité de nouvelle version à venir!')}
                  className="bg-[#b51621] relative rounded-[4px] shrink-0 w-full"
                >
                  <div className="flex flex-row items-center justify-center size-full">
                    <div className="content-stretch flex items-center justify-center p-[10px] relative size-full">
                      <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[24px] text-white whitespace-nowrap">Proposer une nouvelle version</p>
                    </div>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
