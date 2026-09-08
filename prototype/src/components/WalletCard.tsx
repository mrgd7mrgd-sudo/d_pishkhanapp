import React, { useState } from 'react';
import { 
  Eye, 
  EyeOff, 
  ArrowLeft,
  LogIn,
  Wallet
} from 'lucide-react';
import { CitizenProfile, WalletTransaction } from '../types';

interface WalletCardProps {
  profile: CitizenProfile;
  isCitizenLoggedIn?: boolean;
  transactions?: WalletTransaction[];
  onTopUp?: (amount: number) => void;
  onOpenDetails: () => void;
  onOpenLoginModal?: () => void;
  onOpenProfile?: () => void;
  onOpenNotifications?: () => void;
  onOpenScanner?: () => void;
  onOpenHistory?: () => void;
}

export const WalletCard: React.FC<WalletCardProps> = ({
  profile,
  isCitizenLoggedIn = true,
  onOpenDetails,
  onOpenLoginModal
}) => {
  const [hideBalance, setHideBalance] = useState<boolean>(false);

  // Balance in Rial (1 Toman = 10 Rials)
  const balanceRials = profile.walletBalance * 10;

  return (
    <div className="relative overflow-hidden bg-gradient-to-r from-[#2e4368] to-[#475d84] text-white rounded-t-none rounded-b-[2.5rem] px-5 py-3.5 shadow-md shadow-slate-900/10 flex items-center justify-between -mt-3.5 -mx-3.5 sm:-mt-4 sm:-mx-4 mb-2">
      
      {/* Decorative ambient subtle lights */}
      <div className="absolute -left-10 -top-10 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none" />
      <div className="absolute -right-10 -bottom-10 w-24 h-24 bg-blue-300/10 rounded-full blur-xl pointer-events-none" />

      {/* Right Side: Title & Balance */}
      <div className="relative z-10 flex flex-col justify-center">
        {isCitizenLoggedIn ? (
          <>
            <div className="flex items-center gap-1.5 text-white/80 mb-0.5">
              <span className="text-[10px] font-bold">موجودی کیف پول</span>
              <button
                onClick={() => setHideBalance(!hideBalance)}
                className="hover:text-white p-0.5 rounded transition-colors cursor-pointer"
                title={hideBalance ? 'نمایش موجودی' : 'مخفی‌سازی موجودی'}
              >
                {hideBalance ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
              </button>
            </div>
            
            {hideBalance ? (
              <div className="text-sm sm:text-base font-black text-white tracking-widest mt-0.5">
                ••••••••
              </div>
            ) : (
              <div className="flex items-center gap-1 font-black text-white tracking-tight">
                <span className="text-base sm:text-lg">{balanceRials.toLocaleString('fa-IR')}</span>
                <span className="text-[10px] sm:text-xs font-bold text-white/90">ریال</span>
              </div>
            )}
          </>
        ) : (
          <>
            <div className="flex items-center gap-1.5 text-amber-300/90 mb-0.5">
              <Wallet className="w-3 h-3" />
              <span className="text-[10px] font-bold">کیف پول پیشخوان (کاربر مهمان)</span>
            </div>
            <div className="text-xs sm:text-sm font-black text-white/95 tracking-tight">
              جهت فعال‌سازی و شارژ وارد شوید
            </div>
          </>
        )}
      </div>

      {/* Left Side: Details or Login Button */}
      <div className="relative z-10">
        {isCitizenLoggedIn ? (
          <button
            onClick={onOpenDetails}
            className="flex items-center justify-center gap-1 bg-white/15 hover:bg-white/25 active:scale-95 text-white text-[11px] font-bold px-4 py-2 rounded-xl transition-all cursor-pointer shadow-xs border border-white/10"
          >
            <span>جزئیات و شارژ</span>
            <ArrowLeft className="w-3.5 h-3.5" />
          </button>
        ) : (
          <button
            onClick={onOpenLoginModal || onOpenDetails}
            className="flex items-center justify-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 text-[11px] font-black px-3.5 py-2 rounded-xl transition-all cursor-pointer shadow-md active:scale-95"
          >
            <LogIn className="w-3.5 h-3.5 text-slate-950" />
            <span>ورود به حساب</span>
          </button>
        )}
      </div>

    </div>
  );
};

