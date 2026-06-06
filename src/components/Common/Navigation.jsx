import React from 'react';
import {
  Box, LayoutDashboard, List, Facebook, History, Sliders, Camera, Smartphone, Settings
} from 'lucide-react';

export const SidebarLogo = () => (
  <div className="flex items-center gap-3">
    <div className="bg-red-600 p-2 rounded-xl"><Box size={22} /></div>
    <div>
      <span className="font-black text-xl tracking-tighter block leading-none">VARNER</span>
      <span className="text-red-500 text-[9px] font-black uppercase tracking-[0.3em] mt-0.5 block">Equipment</span>
    </div>
  </div>
);

export const NavItem = ({ icon, label, active = false, badge = null, onClick }) => (
  <div 
    onClick={onClick} 
    className={`flex items-center justify-between p-4 rounded-xl cursor-pointer transition-all duration-300 ${active ? 'bg-red-600 text-white shadow-xl shadow-red-900/50 border-b-2 border-red-700' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-100'}`}
  >
    <div className="flex items-center gap-4">
      {icon}
      <span className="font-black text-[13px] uppercase tracking-wider">{label}</span>
    </div>
    {badge !== null && badge !== undefined && (
      <span className={`px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest shadow-md ${active ? 'bg-white text-red-600' : 'bg-green-500 text-white'}`}>
        {badge}
      </span>
    )}
  </div>
);

export const SidebarContent = ({ activeTab, inventoryList, deletedHistory, onNav }) => (
  <>
    <nav className="space-y-2 flex-1">
      <NavItem icon={<LayoutDashboard size={20}/>} label="Dashboard"      active={activeTab==='dashboard'}     onClick={() => onNav('dashboard')} />
      <NavItem icon={<List size={20}/>}            label="Inventory List" active={activeTab==='all-inventory'} onClick={() => onNav('all-inventory')} badge={inventoryList.length} />
      <NavItem icon={<Box size={20}/>}             label="Add / Edit"     active={activeTab==='inventory'}     onClick={() => onNav('inventory')} />
      <NavItem icon={<Facebook size={20}/>}        label="Meta Sync"      active={activeTab==='marketplace'}   onClick={() => onNav('marketplace')} badge="Live" />
      <NavItem icon={<History size={20}/>}         label="History"        active={activeTab==='history'}       onClick={() => onNav('history')} badge={deletedHistory.length > 0 ? deletedHistory.length : null} />
      <NavItem icon={<Sliders size={20}/>}         label="Page Editor"    active={activeTab==='settings'}      onClick={() => onNav('settings')} />
      <NavItem icon={<Camera size={20}/>}          label="Videos Manager" active={activeTab==='videos'}        onClick={() => onNav('videos')} />
      <NavItem icon={<Smartphone size={20}/>}      label="Mobile Companion" active={activeTab==='mobile'}        onClick={() => onNav('mobile')} />
    </nav>
    <div className="mt-auto pt-4 border-t border-slate-800">
      <NavItem icon={<Settings size={18}/>} label="Configuration" active={activeTab==='config'} onClick={() => onNav('config')} />
    </div>
  </>
);

export const FilterTag = ({ label, onRemove }) => (
  <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
    <button onClick={onRemove} className="font-black leading-none hover:text-red-200">×</button>
    {label}
  </span>
);

export const MappingRow = ({ label, value }) => (
  <div className="flex justify-between items-center py-1.5 border-b border-slate-50 pb-4 last:border-0 last:pb-0">
    <span className="text-[11px] font-black text-slate-400 uppercase tracking-widest">{label}</span>
    <span className="text-[11px] font-black text-slate-950 uppercase tracking-tight flex items-center gap-3">
      <div className="w-1.5 h-1.5 rounded-full bg-blue-600"></div>{value}
    </span>
  </div>
);
