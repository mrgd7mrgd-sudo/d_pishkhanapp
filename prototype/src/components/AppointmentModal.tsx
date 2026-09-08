import React, { useState } from 'react';
import { 
  Calendar, 
  Clock, 
  Building2, 
  CheckCircle2, 
  QrCode, 
  X, 
  MapPin,
  Sparkles
} from 'lucide-react';
import { PishkhanOffice, Appointment } from '../types';

interface AppointmentModalProps {
  office: PishkhanOffice;
  onClose: () => void;
  onBook: (newAppointment: Appointment) => void;
}

export const AppointmentModal: React.FC<AppointmentModalProps> = ({
  office,
  onClose,
  onBook
}) => {
  const [selectedDate, setSelectedDate] = useState('۱۴۰۳/۰۶/۰۸ (سه‌شنبه)');
  const [selectedTimeSlot, setSelectedTimeSlot] = useState('۰۹:۳۰ الی ۱۰:۰۰');
  const [selectedService, setSelectedService] = useState('کارت هوشمند ملی و ثبت احوال');
  const [isSuccess, setIsSuccess] = useState(false);
  const [bookedItem, setBookedItem] = useState<Appointment | null>(null);

  const dates = [
    '۱۴۰۳/۰۶/۰۸ (سه‌شنبه)',
    '۱۴۰۳/۰۶/۰۹ (چهارشنبه)',
    '۱۴۰۳/۰۶/۱۰ (پنج‌شنبه)',
    '۱۴۰۳/۰۶/۱۲ (شنبه)'
  ];

  const timeSlots = [
    '۰۸:۳۰ الی ۰۹:۰۰',
    '۰۹:۳۰ الی ۱۰:۰۰',
    '۱۰:۳۰ الی ۱۱:۰۰',
    '۱۱:۳۰ الی ۱۲:۰۰',
    '۱۴:۰۰ الی ۱۴:۳۰',
    '۱۵:۳۰ الی ۱۶:۰۰'
  ];

  const serviceOptions = [
    'کارت هوشمند ملی و ثبت احوال',
    'تعویض شناسنامه و خدمات سجلی',
    'امور خودرویی و تعویض پلاک',
    'کارت بهداشت اصناف',
    'امور مالیاتی و پروانه کسب'
  ];

  const handleExecuteBooking = (e: React.FormEvent) => {
    e.preventDefault();
    const trackingCode = `NOBAT-${Math.floor(10000 + Math.random() * 90000)}`;

    const newApp: Appointment = {
      id: `app-${Date.now()}`,
      officeId: office.id,
      officeName: office.name,
      serviceTitle: selectedService,
      date: selectedDate,
      timeSlot: selectedTimeSlot,
      trackingCode,
      status: 'active'
    };

    setBookedItem(newApp);
    onBook(newApp);
    setIsSuccess(true);
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
      <div className="bg-white text-slate-900 w-full max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl border border-slate-100 relative">
        
        {/* Header */}
        <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
              <Calendar className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-sm font-extrabold text-slate-900">نوبت‌گیری آنلاین مراجعه حضوری</h3>
              <span className="text-[11px] text-slate-400">{office.name}</span>
            </div>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-1">
            <X className="w-5 h-5" />
          </button>
        </div>

        {isSuccess && bookedItem ? (
          <div className="text-center py-4 space-y-4 text-xs">
            <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto animate-bounce">
              <CheckCircle2 className="w-8 h-8" />
            </div>

            <div>
              <span className="bg-emerald-100 text-emerald-800 text-[11px] font-black px-2.5 py-0.5 rounded-full">
                نوبت با موفقیت رزرو شد
              </span>
              <h4 className="font-black text-base text-slate-900 mt-2">{bookedItem.serviceTitle}</h4>
              <p className="text-slate-500 mt-0.5">{bookedItem.officeName}</p>
            </div>

            {/* Ticket Card with barcode */}
            <div className="bg-slate-900 text-white rounded-2xl p-4 text-right space-y-2 border border-slate-800 shadow-md">
              <div className="flex items-center justify-between border-b border-slate-800 pb-2">
                <span className="text-[11px] text-slate-400">کارت نوبت دیجیتال باجه:</span>
                <span className="font-mono text-emerald-400 font-bold text-sm">{bookedItem.trackingCode}</span>
              </div>

              <div className="grid grid-cols-2 gap-2 text-[11px] text-slate-300">
                <div>
                  <span className="text-slate-500 block text-[10px]">تاریخ مراجعه:</span>
                  <span>{bookedItem.date}</span>
                </div>
                <div>
                  <span className="text-slate-500 block text-[10px]">ساعت حضور:</span>
                  <span>{bookedItem.timeSlot}</span>
                </div>
              </div>

              <div className="pt-2 border-t border-slate-800 flex items-center justify-center gap-2">
                <QrCode className="w-10 h-10 text-white bg-white/10 p-1 rounded-lg" />
                <span className="text-[10px] text-slate-400">هنگام ورود به دفتر، بارکد را به کیوسک نوبت‌دهی نشان دهید.</span>
              </div>
            </div>

            <button
              onClick={onClose}
              className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-3 rounded-xl shadow-md transition-all cursor-pointer"
            >
              مشاهده نوبت در حساب کاربری
            </button>
          </div>
        ) : (
          <form onSubmit={handleExecuteBooking} className="space-y-3.5 text-xs">
            <div>
              <label className="font-bold text-slate-700 block mb-1">نوع خدمت مورد نظر:</label>
              <select
                value={selectedService}
                onChange={(e) => setSelectedService(e.target.value)}
                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none focus:border-indigo-500"
              >
                {serviceOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="font-bold text-slate-700 block mb-1">انتخاب روز مراجعه:</label>
              <div className="grid grid-cols-2 gap-2">
                {dates.map((d, i) => (
                  <button
                    key={i}
                    type="button"
                    onClick={() => setSelectedDate(d)}
                    className={`p-2 rounded-xl border text-[11px] font-bold text-right transition-all ${
                      selectedDate === d
                        ? 'bg-indigo-50 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20'
                        : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    {d}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="font-bold text-slate-700 block mb-1">انتخاب بازه ساعتی:</label>
              <div className="grid grid-cols-3 gap-1.5">
                {timeSlots.map((ts, i) => (
                  <button
                    key={i}
                    type="button"
                    onClick={() => setSelectedTimeSlot(ts)}
                    className={`p-2 rounded-xl border text-[10px] font-bold text-center transition-all ${
                      selectedTimeSlot === ts
                        ? 'bg-indigo-50 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20'
                        : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    {ts}
                  </button>
                ))}
              </div>
            </div>

            <div className="bg-slate-50 p-2.5 rounded-xl text-slate-500 text-[11px] flex items-center gap-1.5">
              <Sparkles className="w-3.5 h-3.5 text-indigo-600 shrink-0" />
              <span>نوبت‌گیری رایگان است و به همراه پیامک تاییدیه برای شما ارسال می‌شود.</span>
            </div>

            <button
              type="submit"
              className="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold py-3.5 rounded-xl shadow-md transition-all cursor-pointer text-xs sm:text-sm"
            >
              ثبت قطعی نوبت و صدور بارکد
            </button>
          </form>
        )}

      </div>
    </div>
  );
};
