import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import svgPaths from '../../imports/PageDeBase/svg-m4lsbi1cy8';

interface SidebarProps {
  bgColor: string;
  showAdmin?: boolean;
}

export default function Sidebar({ bgColor, showAdmin = false }: SidebarProps) {
  const { currentUser } = useAuth();

  return (
    <div className={`${bgColor} h-full relative shrink-0`}>
      <div className="flex flex-col items-center justify-center size-full">
        <div className="content-stretch flex flex-col items-center justify-between px-[30px] py-[20px] relative size-full">
          <div className="content-stretch flex flex-col gap-[15px] items-start relative shrink-0">
            <Link to={currentUser.role === 'student' ? '/' : '/professor'} className="relative shrink-0 w-full">
              <div className="flex flex-row items-center justify-center size-full">
                <div className="content-stretch flex items-center justify-center p-[2px] relative size-full">
                  <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[32px] text-white whitespace-nowrap">
                    {currentUser.role === 'student' ? 'Mes fichiers' : 'Documents'}
                  </p>
                </div>
              </div>
            </Link>
            {showAdmin && (
              <Link to="/admin" className="relative shrink-0 w-full">
                <div className="flex flex-row items-center justify-center size-full">
                  <div className="content-stretch flex items-center justify-center p-[2px] relative size-full">
                    <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[32px] text-white whitespace-nowrap">admin</p>
                  </div>
                </div>
              </Link>
            )}
          </div>
          <Link to="/profile" className="content-stretch flex items-center justify-center relative rounded-[4px] shrink-0">
            <div className="overflow-clip relative shrink-0 size-[40px]">
              <div className="absolute inset-[8.33%]">
                <svg className="absolute block inset-0 size-full" fill="none" preserveAspectRatio="none" viewBox="0 0 33.3334 33.3334">
                  <path clipRule="evenodd" d={svgPaths.pc3f900} fill="var(--fill-0, white)" fillRule="evenodd" />
                </svg>
              </div>
            </div>
            <p className="font-['Inter:Regular',sans-serif] font-normal leading-[normal] not-italic relative shrink-0 text-[32px] text-white whitespace-nowrap">Profil</p>
          </Link>
        </div>
      </div>
    </div>
  );
}
