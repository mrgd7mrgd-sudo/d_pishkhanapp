import React, { useEffect, useState } from 'react';
import { useQueueStore } from '../model/useQueueStore';
import { OfficeQueueData } from '../types';
import { realtimeManager } from '../../../shared/realtime/echo';
import { Button } from '@pishkhan/ui-kit';
import { Users, Clock, Monitor, Volume2, ArrowRightLeft, CheckCircle } from 'lucide-react';

export interface QueuePageProps {
  officeId?: string | undefined;
  autoFetch?: boolean | undefined;
}

export const QueuePage: React.FC<QueuePageProps> = ({
  officeId = 'off_thr_01',
  autoFetch = true,
}) => {
  const {
    queue,
    tickets,
    counterNumber,
    currentCallingTicket,
    loading,
    callingLoading,
    setCounterNumber,
    fetchQueue,
    updateQueueData,
    callNext,
  } = useQueueStore();

  const [counterInput, setCounterInput] = useState(counterNumber);

  useEffect(() => {
    if (autoFetch) {
      void fetchQueue();
    }
  }, [autoFetch, fetchQueue]);

  // Realtime subscription to private-office.{id} for queue.updated events (§5.7, TASK-076)
  useEffect(() => {
    if (!officeId) return;

    const channelName = `office.${officeId}`;
    const channel = realtimeManager.private(channelName);

    const handleQueueUpdate = (payload: unknown) => {
      const data = payload as {
        waiting_queue?: number;
        active_counters?: number;
        estimated_wait_minutes?: number;
      };
      if (typeof data.waiting_queue === 'number') {
        const update: Partial<OfficeQueueData> = {
          waiting_queue: data.waiting_queue,
        };
        if (typeof data.active_counters === 'number') {
          update.active_counters = data.active_counters;
        }
        if (typeof data.estimated_wait_minutes === 'number') {
          update.estimated_wait_minutes = data.estimated_wait_minutes;
        }
        updateQueueData(update);
      }
    };

    channel.listen('.queue.updated', handleQueueUpdate);
    channel.listen('queue.updated', handleQueueUpdate);

    return () => {
      channel.stopListening('.queue.updated');
      channel.stopListening('queue.updated');
      realtimeManager.leave(channelName);
    };
  }, [officeId, updateQueueData]);

  const handleSetCounter = (e: React.FormEvent) => {
    e.preventDefault();
    setCounterNumber(Math.max(1, counterInput));
  };

  const toPersian = (num: number | string): string => {
    return String(num).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)] ?? d);
  };

  return (
    <div className="p-6 space-y-6 max-w-7xl mx-auto text-start">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
        <div>
          <h1 className="text-lg font-bold text-slate-900 flex items-center gap-2">
            <Users className="w-5 h-5 text-blue-600" />
            <span>مدیریت صف زنده مراجعین دفتر</span>
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            نوبت‌دهی، شماره باجه و به‌روزرسانی Real-Time نوبت مراجعین حضوری
          </p>
        </div>

        {/* Counter selector form */}
        <form onSubmit={handleSetCounter} className="flex items-center gap-2 bg-white p-1.5 border border-slate-200 rounded-xl shadow-2xs">
          <label htmlFor="counter-num-input" className="text-xs font-bold text-slate-700 px-2 flex items-center gap-1.5">
            <Monitor className="w-4 h-4 text-blue-600" />
            <span>شماره باجه من:</span>
          </label>
          <input
            id="counter-num-input"
            type="number"
            min={1}
            max={20}
            value={counterInput}
            onChange={(e) => setCounterInput(parseInt(e.target.value, 10) || 1)}
            className="w-16 bg-slate-50 border border-slate-300 rounded-lg p-1 text-center font-bold font-mono text-sm focus:bg-white focus:ring-2 focus:ring-blue-500"
          />
          <Button type="submit" size="sm" variant="secondary">
            ثبت
          </Button>
        </form>
      </div>

      {/* Overview Stat Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
          <div className="flex items-center justify-between text-slate-500 text-xs mb-2">
            <span>افراد در صف انتظار</span>
            <Users className="w-4 h-4 text-blue-500" />
          </div>
          <div className="text-2xl font-bold text-slate-900 font-mono">
            {toPersian(queue?.waiting_queue ?? tickets.length)} <span className="text-xs font-normal text-slate-500">نفر</span>
          </div>
          <div className="text-[11px] text-emerald-600 mt-1 flex items-center gap-1">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
            <span>صف زنده و همگام با سامانه</span>
          </div>
        </div>

        <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
          <div className="flex items-center justify-between text-slate-500 text-xs mb-2">
            <span>زمان تقریبی انتظار</span>
            <Clock className="w-4 h-4 text-amber-500" />
          </div>
          <div className="text-2xl font-bold text-slate-900 font-mono">
            {toPersian(queue?.estimated_wait_minutes ?? 15)} <span className="text-xs font-normal text-slate-500">دقیقه</span>
          </div>
          <p className="text-[11px] text-slate-400 mt-1">میانگین ۱۵ دقیقه به‌ازای هر پرونده</p>
        </div>

        <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
          <div className="flex items-center justify-between text-slate-500 text-xs mb-2">
            <span>باجه‌های فعال دفتر</span>
            <Monitor className="w-4 h-4 text-purple-500" />
          </div>
          <div className="text-2xl font-bold text-slate-900 font-mono">
            {toPersian(queue?.active_counters ?? 2)} <span className="text-xs font-normal text-slate-500">باجه فعال</span>
          </div>
          <p className="text-[11px] text-slate-400 mt-1">شما در باجه {toPersian(counterNumber)} مستقر هستید</p>
        </div>
      </div>

      {/* Call Next Banner */}
      <div className="bg-gradient-to-r from-blue-700 to-indigo-800 text-white rounded-2xl p-6 shadow-md flex flex-wrap items-center justify-between gap-4">
        <div>
          <span className="text-xs text-blue-200 block mb-1">فراخوان نوبت جدید جهت باجه {toPersian(counterNumber)}</span>
          <div className="text-3xl font-bold tracking-wide font-mono">
            {currentCallingTicket ? `نوبت ${currentCallingTicket}` : 'آماده فراخوانی نوبت بعدی'}
          </div>
          <p className="text-xs text-blue-100 mt-1">
            با کلیک بر روی دکمه فراخوان، زنگ نوبت پخش و شماره روی مانیتور سالن دفتر درج می‌گردد.
          </p>
        </div>

        <Button
          size="lg"
          variant="primary"
          className="bg-white text-blue-900 hover:bg-blue-50 active:bg-blue-100 font-bold px-6 shadow-md"
          isLoading={callingLoading}
          onClick={() => void callNext()}
        >
          <Volume2 className="w-5 h-5 ml-1.5 text-blue-700" />
          فراخوانی نوبت بعدی
        </Button>
      </div>

      {/* Waiting Tickets Table */}
      <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
        <div className="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h2 className="text-xs font-bold text-slate-700">فهرست نوبت‌های در صف حضور</h2>
          <span className="text-xs text-slate-400">{tickets.length} نوبت</span>
        </div>

        <div className="divide-y divide-slate-100">
          {tickets.map((t) => (
            <div
              key={t.ticket_number}
              className={`p-4 flex items-center justify-between gap-3 transition-colors ${
                t.status === 'called' ? 'bg-amber-50/60' : 'hover:bg-slate-50'
              }`}
            >
              <div className="flex items-center gap-3">
                <div
                  className={`w-10 h-10 rounded-xl flex items-center justify-center font-mono font-bold text-sm ${
                    t.status === 'called'
                      ? 'bg-amber-500 text-white animate-pulse'
                      : 'bg-slate-100 text-slate-700'
                  }`}
                >
                  {t.ticket_number}
                </div>
                <div>
                  <span className="text-sm font-bold text-slate-900 block">{t.service_title}</span>
                  <span className="text-xs text-slate-400 font-mono">{t.tracking_code}</span>
                </div>
              </div>

              <div className="flex items-center gap-4">
                <div className="text-xs text-slate-500 text-end">
                  <span className="block font-mono font-semibold">{toPersian(t.waiting_minutes)} دقیقه قبل</span>
                  <span className="text-[10px] text-slate-400">زمان صدور قبض</span>
                </div>

                {t.status === 'called' ? (
                  <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    باجه {toPersian(t.counter_number || counterNumber)}
                  </span>
                ) : (
                  <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                    در صف انتظار
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
