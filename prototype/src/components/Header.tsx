import React from 'react';
import { motion } from 'motion/react';
import { CitizenProfile } from '../types';

interface HeaderProps {
  onOpenChatbot: () => void;
  isCitizenLoggedIn?: boolean;
  profile?: CitizenProfile;
  onOpenLoginModal?: () => void;
  onOpenProfile?: () => void;
  onOpenOfficeLogin?: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  onOpenChatbot,
  isCitizenLoggedIn = false,
  profile,
  onOpenLoginModal,
  onOpenProfile,
  onOpenOfficeLogin
}) => {
  return (
    <header className="sticky top-0 z-30 bg-white px-4 py-2.5 shadow-xs border-b border-slate-100">
      <div className="max-w-lg mx-auto flex items-center justify-between">
        
        {/* RIGHT SIDE: Ewano Brand Logo & Stylized Name (مطابق تصویر ارسالی) */}
        <div className="flex items-center gap-2 select-none" dir="ltr">
          {/* Ewano stylized brand mark */}
          <div className="flex items-center gap-2">
            {/* Persian & English Typography */}
            <div className="flex flex-col items-start leading-none">
              <span className="text-[9px] font-bold text-[#475d84] tracking-widest lowercase -mb-0.5 ml-0.5">
                pishkhano
              </span>
              <span className="text-xl sm:text-2xl font-black text-[#2e4368] tracking-tight font-sans">
                پیشخوانـو
              </span>
            </div>

            {/* Ewano Blue Rounded Emblem with Origami Lines */}
            <div className="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-[#5d739b] shadow-xs flex items-center justify-center p-1.5 shrink-0">
              <svg 
                viewBox="0 0 32 32" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg"
                className="w-full h-full text-white"
              >
                {/* Stylized Ewano Emblem Lines */}
                <path 
                  d="M16 4V28M16 28L6 20M16 28L26 20M6 20L16 12M26 20L16 12M16 12V4" 
                  stroke="currentColor" 
                  strokeWidth="2.5" 
                  strokeLinecap="round" 
                  strokeLinejoin="round" 
                />
              </svg>
            </div>
          </div>
        </div>

        {/* LEFT SIDE: Smart Chatbot Mascot Avatar (مطابق تصویر ارسالی سمت چپ) */}
        <div className="flex items-center gap-2" dir="rtl">
          {/* Smart Chatbot Mascot Avatar (مطابق تصویر ارسالی سمت چپ) */}
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.94 }}
            onClick={onOpenChatbot}
            className="relative group p-1 rounded-2xl hover:bg-slate-50 transition-all cursor-pointer focus:outline-none"
            title="دستیار هوشمند پیشخوان"
            aria-label="دستیار هوشمند پیشخوان"
          >
            {/* Robot Speech Bubble Avatar */}
            <div className="w-10 h-10 flex items-center justify-center relative">
              <svg 
                viewBox="0 0 40 40" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg"
                className="w-9 h-9 text-slate-500 group-hover:text-slate-700 transition-colors"
              >
                {/* Antenna */}
                <circle cx="20" cy="5" r="2" fill="currentColor" />
                <path d="M20 7v3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                
                {/* Left & Right Ears */}
                <rect x="5" y="15" width="3" height="8" rx="1.5" fill="currentColor" />
                <rect x="32" y="15" width="3" height="8" rx="1.5" fill="currentColor" />
                
                {/* Head / Chat Bubble Shape */}
                <path 
                  d="M9 13C9 10.7909 10.7909 9 13 9H27C29.2091 9 31 10.7909 31 13V22C31 24.2091 29.2091 26 27 26H16L11 31V26H13C10.7909 26 9 24.2091 9 22V13Z" 
                  stroke="currentColor" 
                  strokeWidth="2.5" 
                  strokeLinecap="round" 
                  strokeLinejoin="round" 
                  fill="none" 
                />
                
                {/* Eyes & Smile */}
                <circle cx="16" cy="17" r="1.5" fill="currentColor" />
                <circle cx="24" cy="17" r="1.5" fill="currentColor" />
                <path 
                  d="M16.5 21C17.5 22 22.5 22 23.5 21" 
                  stroke="currentColor" 
                  strokeWidth="2" 
                  strokeLinecap="round" 
                />
              </svg>

              {/* Glowing Teal/Green Circle Badge from the screenshot */}
              <div className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#d1fae5] flex items-center justify-center border-2 border-white">
                <span className="w-2.5 h-2.5 rounded-full bg-[#10b981] animate-pulse" />
              </div>
            </div>
          </motion.button>
        </div>

      </div>
    </header>
  );
};

