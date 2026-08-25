import React from 'react';
import { Zap, Plus, History, CheckCircle2 } from 'lucide-react';

const METRIC_COLORS = {
  blue:  { text: 'bg-blue-100 text-blue-800',  bg: 'bg-blue-50'  },
  red:   { text: 'bg-red-100 text-red-800',    bg: 'bg-red-50'   },
  green: { text: 'bg-emerald-100 text-emerald-800', bg: 'bg-emerald-50' },
  amber: { text: 'bg-amber-100 text-amber-900', bg: 'bg-amber-50' },
};

export const MetricCard = ({ icon, label, value, subtext, color }) => {
  const c = METRIC_COLORS[color] || METRIC_COLORS.blue;
  return (
    <div className="rounded-[2rem] p-5 sm:p-8 border bg-white border-slate-200 shadow-xl relative overflow-hidden group transition-all">
      <div className="flex items-center gap-3 sm:gap-4 mb-5 sm:mb-8 relative z-10">
        <div className={`p-3 sm:p-4 rounded-xl ${c.text} shadow-md group-hover:scale-110 transition-transform`}>{icon}</div>
        <h2 className="font-black text-xs uppercase tracking-widest text-slate-600 leading-none min-w-0 break-words">{label}</h2>
      </div>
      <p className="text-4xl sm:text-5xl font-black text-slate-950 mb-3 tracking-tighter relative z-10 leading-none">{value}</p>
      <p className={`text-xs font-black uppercase tracking-[0.1em] px-2.5 py-1 rounded-md inline-block relative z-10 ${c.text}`}>{subtext}</p>
      <div className={`absolute -right-6 -bottom-6 w-32 h-32 rounded-full opacity-10 ${c.bg} group-hover:scale-150 transition-transform duration-700`}></div>
    </div>
  );
};

export const QuickActions = ({ onAdd }) => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200 shadow-xl">
    <h2 className="text-xs font-black uppercase tracking-[0.2em] text-slate-700 mb-6 flex items-center gap-2"><Zap size={16} className="text-red-600"/>Quick Operations</h2>
    <div className="grid grid-cols-1 max-w-sm gap-4">
      <button onClick={onAdd} className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Plus size={20} className="text-red-600"/></div>
        <span className="text-xs font-black uppercase tracking-widest text-slate-800">Add Unit</span>
      </button>
    </div>
  </div>
);

export const ActivityItem = ({ icon, title, desc, time, color }) => {
  const colors = { green:'text-emerald-800 bg-emerald-100', blue:'text-blue-800 bg-blue-100', red:'text-red-800 bg-red-100' };
  return (
    <div className="flex gap-4">
      <div className={`mt-1 p-2 rounded-lg ${colors[color] || colors.blue} h-fit`}>{icon}</div>
      <div className="flex-1 border-b border-slate-100 pb-4 last:border-0">
        <div className="flex justify-between items-start mb-1">
          <h3 className="text-xs font-black uppercase tracking-tight text-slate-900">{title}</h3>
          <span className="text-xs font-bold text-slate-600 uppercase">{time}</span>
        </div>
        <p className="text-xs font-bold text-slate-700 uppercase tracking-wide">{desc}</p>
      </div>
    </div>
  );
};

export const RecentActivity = () => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200 shadow-xl">
    <h2 className="text-xs font-black uppercase tracking-[0.2em] text-slate-700 mb-6 flex items-center gap-2"><History size={16} className="text-blue-600"/>Activity Stream</h2>
    <div className="space-y-6">
      <ActivityItem icon={<CheckCircle2 size={16}/>} title="Database Connected" desc="Inventory loading from WordPress" time="Live" color="green"/>
    </div>
  </div>
);


