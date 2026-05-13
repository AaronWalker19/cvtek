import { useAuth, demoUsers } from '../context/AuthContext';
import Sidebar from '../components/Sidebar';

export default function Profile() {
  const { user, switchUser } = useAuth();

  return (
    <div className="bg-[#ffffff] content-stretch flex items-start relative h-full ml-[225px]">
      <Sidebar bgColor={user.role === 'student' ? 'bg-[#b51621]' : 'bg-[#4b575f]'} showAdmin={user.role === 'professor'} />

      <div className="flex-[1_0_0] h-screen overflow-y-auto w-full min-w-px relative">
        <div className="flex flex-col items-stretch w-full h-full">
          <div className="content-stretch flex flex-col gap-[50px] items-stretch p-[40px] relative w-full h-full">
            {/* Header */}
            <div className="content-stretch flex items-center py-[10px] relative shrink-0 w-full">
              <div aria-hidden="true" className={`absolute border-b-3 border-solid inset-0 pointer-events-none ${user.role === 'student' ? 'border-[#b51621]' : 'border-[#4b575f]'}`} />
              <p className={`font-['Inter:Bold',sans-serif] font-bold leading-[normal] not-italic relative shrink-0 text-[32px] whitespace-nowrap ${user.role === 'student' ? 'text-[#b51621]' : 'text-[#4b575f]'}`}>
                Profil
              </p>
            </div>

            {/* User Info */}
            <div className="content-stretch flex flex-col gap-[30px] items-start relative shrink-0 w-full max-w-[600px]">
              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Nom</p>
                <div className="bg-[#ffffff] content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px]">{user.username}</p>
                </div>
              </div>

              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Email</p>
                <div className="bg-[#ffffff] content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px]">{user.email}</p>
                </div>
              </div>

              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Rôle</p>
                <div className="bg-[#ffffff] content-stretch flex items-center p-[10px] relative rounded-[10px] shrink-0 w-full">
                  <div aria-hidden="true" className="absolute border border-[#4b575f] border-solid inset-0 pointer-events-none rounded-[10px]" />
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic text-[#36302a] text-[18px] capitalize">{user.role === 'student' ? 'Étudiant' : user.role === 'professor' ? 'Professeur' : 'Administrateur'}</p>
                </div>
              </div>

              {/* Compte Selector for Demo */}
              <div className="content-stretch flex flex-col gap-[10px] items-start relative shrink-0 w-full mt-[30px]">
                <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic text-[#36302a] text-[20px]">Changer de compte (Mode Démo)</p>
                <div className="content-stretch flex flex-col gap-[10px] items-stretch relative shrink-0 w-full">
                  {demoUsers.map((demoUser) => (
                    <button
                      key={demoUser.userId}
                      onClick={() => switchUser(demoUser.userId)}
                      className={`flex items-start justify-between p-[15px] rounded-[10px] transition-colors ${
                        user.userId === demoUser.userId
                          ? 'bg-[#4b575f] text-white'
                          : 'bg-[#f0f0f0] text-[#36302a] hover:bg-[#e0e0e0]'
                      }`}
                    >
                      <div className="flex flex-col gap-[5px] items-start">
                        <p className="font-['Inter:Medium',sans-serif] font-medium text-[16px]">
                          {demoUser.username}
                        </p>
                        <p className="font-['Inter:Regular',sans-serif] font-normal text-[14px]">
                          {demoUser.email}
                        </p>
                        <p className="font-['Inter:Regular',sans-serif] font-normal text-[12px] opacity-75 capitalize">
                          {demoUser.role === 'student' ? 'Étudiant' : demoUser.role === 'professor' ? 'Professeur' : 'Administrateur'}
                        </p>
                      </div>
                      {user.userId === demoUser.userId && (
                        <p className="font-['Inter:Medium',sans-serif] font-medium text-[14px]">✓</p>
                      )}
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
