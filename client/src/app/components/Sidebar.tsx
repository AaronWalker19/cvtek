import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

interface SidebarProps {
  bgColor: string;
  showAdmin?: boolean;
}

export default function Sidebar({ bgColor, showAdmin = false }: SidebarProps) {
  const { user } = useAuth();

  if (!user) return null;

  const getRoleLabel = (role: string) => {
    switch (role) {
      case 'student':
        return 'Étudiant';
      case 'professor':
        return 'Professeur';
      case 'admin':
        return 'Admin';
      default:
        return role;
    }
  };

  return (
    <div className={`${bgColor} fixed left-0 top-0 h-screen w-[225px] z-50 !text-[#ffffff]`}>
      <div className="flex flex-col items-center justify-center size-full">
        <div className="content-stretch flex flex-col items-center justify-between px-[30px] py-[20px] relative size-full !text-[#ffffff]">
          {/* Top: Status Badge + Navigation Links */}
          <div className="content-stretch flex flex-col gap-[15px] items-start relative shrink-0 w-full">
            {/* Status Badge */}
            <div className="content-stretch flex items-start relative shrink-0 w-full">
              <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[12px] !text-[#ffffff] uppercase tracking-wide px-[10px] py-[5px] bg-white bg-opacity-20 rounded-[4px]">
                {getRoleLabel(user.role)}
              </p>
            </div>

            {/* Navigation Links */}
            <Link to={user.role === 'student' ? '/' : '/professor'} className="relative shrink-0 w-full !text-[#ffffff] hover:!text-[#ffffff]">
              <div className="flex flex-row items-center justify-center size-full">
                <div className="content-stretch flex items-center justify-center p-[2px] relative size-full">
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[32px] !text-[#ffffff] whitespace-nowrap">
                    {user.role === 'student' ? 'Mes fichiers' : 'Documents'}
                  </p>
                </div>
              </div>
            </Link>
            {showAdmin && (
              <Link to="/admin" className="relative shrink-0 w-full !text-[#ffffff] hover:!text-[#ffffff]">
                <div className="flex flex-row items-center justify-center size-full">
                  <div className="content-stretch flex items-center justify-center p-[2px] relative size-full">
                    <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[32px] !text-[#ffffff] whitespace-nowrap">admin</p>
                  </div>
                </div>
              </Link>
            )}
          </div>

          {/* Bottom: User Info */}
          <div className="content-stretch flex flex-col gap-[5px] items-start relative shrink-0 w-full">
            <p className="font-['Inter:Medium',sans-serif] font-medium leading-[normal] not-italic relative shrink-0 text-[18px] !text-[#ffffff] break-words">
              {user.username}
            </p>
            <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[12px] !text-[#ffffff] break-words opacity-90">
              {user.email}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
