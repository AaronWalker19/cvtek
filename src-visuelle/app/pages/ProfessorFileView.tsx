import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { mockDocuments } from '../data/mockData';
import Sidebar from '../components/Sidebar';
import svgPaths from '../../imports/PageDeFichierCoteProf/svg-uwrwsgjoxh';

export default function ProfessorFileView() {
  const { fileId } = useParams<{ fileId: string }>();
  const document = mockDocuments.find(doc => doc.id === fileId);
  const [newComment, setNewComment] = useState('');
  const [followStudent, setFollowStudent] = useState(false);
  const [editingCommentId, setEditingCommentId] = useState<string | null>(null);

  if (!document) {
    return <div>Document non trouvé</div>;
  }

  const handleAddComment = () => {
    if (newComment.trim()) {
      alert(`Commentaire ajouté: ${newComment}`);
      setNewComment('');
    }
  };

  const handleDeleteComment = (commentId: string) => {
    if (confirm('Voulez-vous vraiment supprimer ce commentaire?')) {
      alert(`Commentaire ${commentId} supprimé`);
    }
  };

  return (
    <div className="bg-white content-stretch flex items-start relative size-full">
      <Sidebar bgColor="bg-[#4b575f]" showAdmin />

      <div className="flex-[1_0_0] h-full min-w-px relative">
        <div className="flex flex-col items-center overflow-clip rounded-[inherit] size-full">
          <div className="content-stretch flex flex-col gap-[50px] items-center p-[40px] relative size-full">
            {/* Header */}
            <div className="content-stretch flex items-center justify-between py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#4b575f] border-b-3 border-solid inset-0 pointer-events-none" />
              <Link to="/professor" className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] whitespace-nowrap hover:underline">
                ← {document.name}
              </Link>
              <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[24px] whitespace-nowrap">{document.ownerName}</p>
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

              {/* Comments & Actions */}
              <div className="content-stretch flex flex-[1_0_0] flex-col gap-[10px] h-full items-start min-w-px relative">
                <div className="bg-[#f7f7f7] relative shrink-0 w-full">
                  <div className="content-stretch flex flex-col gap-[10px] items-start pl-[20px] pr-[10px] py-[20px] relative size-full">
                    <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] text-right whitespace-nowrap">Commentaires</p>

                    {/* Comments List */}
                    {document.comments.map((comment) => (
                      <div key={comment.id} className="relative shrink-0 w-full">
                        <div className="content-stretch flex flex-col items-start px-[10px] relative size-full">
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
                                  <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-[24px]">
                                    <button
                                      onClick={() => setEditingCommentId(comment.id)}
                                      className="relative shrink-0 size-[24px]"
                                    >
                                      <div className="absolute inset-[8.33%]">
                                        <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 20.0007 20.0007">
                                          <path d={svgPaths.p28ddbb00} fill="var(--fill-0, #F7F7F7)" />
                                        </svg>
                                      </div>
                                    </button>
                                    <button
                                      onClick={() => handleDeleteComment(comment.id)}
                                      className="relative shrink-0 size-[24px]"
                                    >
                                      <div className="absolute inset-[12.5%_20.83%]">
                                        <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 14 18">
                                          <path d={svgPaths.p2eb23700} fill="var(--fill-0, #F7F7F7)" />
                                        </svg>
                                      </div>
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}

                    {document.comments.length === 0 && (
                      <p className="font-['Inter:Regular',sans-serif] text-[#4b575f] text-[16px] px-[10px]">Aucun commentaire pour le moment</p>
                    )}
                  </div>
                </div>

                {/* Follow & Add Comment */}
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
                              d={svgPaths.p25feb300}
                              fill={followStudent ? "var(--fill-0, #36302A)" : "none"}
                              fillRule="evenodd"
                              stroke={followStudent ? "none" : "#36302A"}
                              strokeWidth={followStudent ? "0" : "2"}
                            />
                          </g>
                        </svg>
                      </div>
                    </button>
                    <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[16px] whitespace-nowrap">Suivre l'étudiant</p>
                  </div>
                  <button
                    onClick={handleAddComment}
                    className="bg-[#4b575f] flex-[1_0_0] min-w-px relative rounded-[4px]"
                  >
                    <div className="flex flex-row items-center justify-center size-full">
                      <div className="content-stretch flex items-center justify-center p-[10px] relative size-full">
                        <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[15px] text-white whitespace-nowrap">Ajouter un commentaire</p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
