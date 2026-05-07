import { useState } from 'react';
import { mockUsers, User } from '../data/mockData';
import Sidebar from '../components/Sidebar';

export default function AdminDashboard() {
  const [newUserEmail, setNewUserEmail] = useState('');
  const [professors, setProfessors] = useState<User[]>(
    mockUsers.filter(u => u.role === 'professor')
  );

  const handleAddUser = () => {
    if (newUserEmail && newUserEmail.includes('@')) {
      alert(`Accès ajouté pour: ${newUserEmail}`);
      setNewUserEmail('');
    } else {
      alert('Veuillez entrer un email valide');
    }
  };

  const handleRemoveUser = (userId: string) => {
    // eslint-disable-next-line no-restricted-globals
    if (confirm('Voulez-vous vraiment supprimer cet utilisateur?')) {
      setProfessors(professors.filter(p => p.id !== userId));
      alert('Utilisateur supprimé');
    }
  };

  return (
    <div className="bg-white content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor="bg-[#4b575f]" showAdmin />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className="absolute border-[#4b575f] border-b-3 border-solid inset-0 pointer-events-none" />
              <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#4b575f] text-[32px] whitespace-nowrap">Membre</p>
            </div>

            {/* Add User Form */}
            <div className="content-stretch flex gap-[10px] items-center justify-center relative shrink-0">
              <div className="bg-white content-stretch flex gap-[10px] items-center p-[10px] relative rounded-[20px] shrink-0 w-[564px]">
                <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[20px]" />
                <input
                  type="email"
                  value={newUserEmail}
                  onChange={(e) => setNewUserEmail(e.target.value)}
                  placeholder="mail du nouvelle utilisateur"
                  className="flex-1 font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic bg-transparent outline-none text-[20px] text-[rgba(75,87,95,0.5)]"
                />
              </div>
              <button
                onClick={handleAddUser}
                className="bg-[#4b575f] content-stretch flex items-center justify-center p-[10px] relative rounded-[4px] shrink-0 w-[178px]"
              >
                <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[20px] text-white whitespace-nowrap">Ajouter l'accès</p>
              </button>
            </div>

            {/* Professors Table */}
            <div className="content-stretch flex flex-col gap-[13px] items-start relative shrink-0 w-full">
              {/* Header Row */}
              <div className="content-stretch flex items-center justify-between relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[24px] whitespace-nowrap">Professeur</p>
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[24px] whitespace-nowrap">mail</p>
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[#36302a] text-[24px] whitespace-nowrap">commentaires</p>
                <div className="bg-[#b51621] content-stretch flex items-center justify-center opacity-0 p-[3px] relative rounded-[4px] shrink-0">
                  <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[#f7f7f7] text-[16px] whitespace-nowrap">supprimmer</p>
                </div>
              </div>

              {/* Professor Rows */}
              {professors.map((prof) => (
                <div key={prof.id} className="content-stretch flex items-start justify-between py-[10px] relative shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border-[#36302a] border-b border-solid inset-0 pointer-events-none" />
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="leading-[normal]">{prof.name}</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="leading-[normal]">{prof.email}</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="decoration-solid leading-[normal] underline">5 commentaires</p>
                  </div>
                  <button
                    onClick={() => handleRemoveUser(prof.id)}
                    className="bg-[#b51621] content-stretch flex items-center justify-center p-[3px] relative rounded-[4px] shrink-0"
                  >
                    <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#f7f7f7] text-[16px] whitespace-nowrap">supprimmer</p>
                  </button>
                </div>
              ))}

              {/* Add more mock rows to match design */}
              {[...Array(Math.max(0, 5 - professors.length))].map((_, i) => (
                <div key={`mock-${i}`} className="content-stretch flex items-start justify-between py-[10px] relative shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border-[#36302a] border-b border-solid inset-0 pointer-events-none" />
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="leading-[normal]">Mr. Nival</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="leading-[normal]">nirval@etu.unilim</p>
                  </div>
                  <div className="flex flex-[1_0_0] flex-col font-['Inter:Regular',sans-serif] font-normal justify-center leading-[0] min-w-px not-italic relative text-[#36302a] text-[16px]">
                    <p className="decoration-solid leading-[normal] underline">5 commentaires</p>
                  </div>
                  <button
                    onClick={() => alert('Utilisateur de démonstration')}
                    className="bg-[#b51621] content-stretch flex items-center justify-center p-[3px] relative rounded-[4px] shrink-0"
                  >
                    <p className="font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[#f7f7f7] text-[16px] whitespace-nowrap">supprimmer</p>
                  </button>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
