import React from 'react';
import { Zap, Plus, Upload, Download, History, CheckCircle2 } from 'lucide-react';

const METRIC_COLORS = {
  blue:  { text: 'bg-blue-50 text-blue-600',  bg: 'bg-blue-50'  },
  red:   { text: 'bg-red-50 text-red-600',    bg: 'bg-red-50'   },
  green: { text: 'bg-green-50 text-green-600',bg: 'bg-green-50' },
  amber: { text: 'bg-amber-50 text-amber-600',bg: 'bg-amber-50' },
};

export const MetricCard = ({ icon, label, value, subtext, color }) => {
  const c = METRIC_COLORS[color] || METRIC_COLORS.blue;
  return (
    <div className="rounded-[2rem] p-5 sm:p-8 border bg-white border-slate-200/60 shadow-xl relative overflow-hidden group transition-all">
      <div className="flex items-center gap-3 sm:gap-4 mb-5 sm:mb-8 relative z-10">
        <div className={`p-3 sm:p-4 rounded-xl ${c.text} shadow-md group-hover:scale-110 transition-transform`}>{icon}</div>
        <h4 className="font-black text-[10px] uppercase tracking-widest text-slate-400 leading-none min-w-0 break-words">{label}</h4>
      </div>
      <p className="text-4xl sm:text-5xl font-black text-slate-950 mb-3 tracking-tighter relative z-10 leading-none">{value}</p>
      <p className={`text-[10px] font-black uppercase tracking-[0.1em] relative z-10 ${c.text}`}>{subtext}</p>
      <div className={`absolute -right-6 -bottom-6 w-32 h-32 rounded-full opacity-10 ${c.bg} group-hover:scale-150 transition-transform duration-700`}></div>
    </div>
  );
};

export const QuickActions = ({ onAdd }) => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2"><Zap size={14} className="text-red-600"/>Quick Operations</h4>
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button onClick={onAdd} className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Plus size={20} className="text-red-600"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Add Unit</span>
      </button>
      <a href="/wp-admin/admin.php?page=pmxi-admin-import" className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Upload size={20} className="text-slate-700"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Import Inventory</span>
      </a>
      <a href="/wp-admin/admin.php?page=pmxe-admin-manage" className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Download size={20} className="text-slate-700"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Export Inventory</span>
      </a>
    </div>
  </div>
);

export const ActivityItem = ({ icon, title, desc, time, color }) => {
  const colors = { green:'text-green-600 bg-green-50', blue:'text-blue-600 bg-blue-50', red:'text-red-600 bg-red-50' };
  return (
    <div className="flex gap-4">
      <div className={`mt-1 p-2 rounded-lg ${colors[color]} h-fit`}>{icon}</div>
      <div className="flex-1 border-b border-slate-50 pb-4 last:border-0">
        <div className="flex justify-between items-start mb-1">
          <h5 className="text-[11px] font-black uppercase tracking-tight text-slate-900">{title}</h5>
          <span className="text-[9px] font-bold text-slate-400 uppercase">{time}</span>
        </div>
        <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wide">{desc}</p>
      </div>
    </div>
  );
};

export const RecentActivity = () => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2"><History size={14} className="text-blue-600"/>Activity Stream</h4>
    <div className="space-y-6">
      <ActivityItem icon={<CheckCircle2 size={14}/>} title="Database Connected" desc="Inventory loading from WordPress" time="Live" color="green"/>
    </div>
  </div>
);
