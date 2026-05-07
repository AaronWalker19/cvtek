import { useAuth } from '../context/AuthContext';
import { mockUsers } from '../data/mockData';
import Sidebar from '../components/Sidebar';

export default function Profile() {
  const { currentUser, setCurrentUser } = useAuth();

  const handleRoleChange = (userId: string) => {
    const user = mockUsers.find(u => u.id === userId);
    if (user) {
      setCurrentUser(user);
      alert(`Connecté en tant que: ${user.name} (${user.role})`);
    }
  };

  return (
    <div className="bg-white content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor={currentUser.role === 'student' ? 'bg-[#b51621]' : 'bg-[#4b575f]'} showAdmin={currentUser.role === 'professor'} />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className={`absolute border-b-3 border-solid inset-0 pointer-events-none ${currentUser.role === 'student' ? 'border-[#b51621]' : 'border-[#4b575f]'}`} />
              <p className={`font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] whitespace-nowrap ${currentUser.role === 'student' ? 'text-[#b51621]' : 'text-[#4b575f]'}`}>
                Profil
              </p>
            </div>

            {/* User Info */}
            <div className="content-stretch flex flex-col gap-[30px] items-start relative shrink-0 w-full max-w-[600px]">
              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Nom</p>
                <div className="bg-white content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px]">{currentUser.name}</p>
                </div>
              </div>

              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Email</p>
                <div className="bg-white content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px]">{currentUser.email}</p>
                </div>
              </div>

              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Rôle</p>
                <div className="bg-white content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px] capitalize">{currentUser.role === 'student' ? 'Étudiant' : currentUser.role === 'professor' ? 'Professeur' : 'Administrateur'}</p>
                </div>
              </div>

              {currentUser.license && (
                <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                  <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Licence</p>
                  <div className="bg-white content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                    <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                    <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px]">{currentUser.license}</p>
                  </div>
                </div>
              )}

              {/* Role Switcher for Demo */}
              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full mt-[30px]">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Changer de rôle (Demo)</p>
                <div className="content-stretch flex gap-[10px] items-center relative shrink-0 w-full">
                  {mockUsers.map((user) => (
                    <button
                      key={user.id}
                      onClick={() => handleRoleChange(user.id)}
                      className={`flex-1 p-[10px] rounded-[10px] ${currentUser.id === user.id ? 'bg-[#4b575f] text-white' : 'bg-white text-[#36302a]'}`}
                    >
                      <div aria-hidden="true" className={`absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px] ${currentUser.id === user.id ? 'hidden' : ''}`} />
                      <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[16px]">
                        {user.role === 'student' ? 'Étudiant' : user.role === 'professor' ? 'Professeur' : 'Admin'}
                      </p>
                    </button>
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
