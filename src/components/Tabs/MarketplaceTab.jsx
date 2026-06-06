import React from 'react';
import { Facebook, List, CheckCircle2, BarChart3 } from 'lucide-react';

export const MarketplaceTab = () => (
  <div className="space-y-8 animate-in fade-in duration-500 text-slate-950 font-black">
    <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-12 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
      <div className="relative z-10">
        <h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">Meta Commerce Engine</h3>
        <p className="text-white font-bold opacity-90 uppercase tracking-[0.3em] text-[10px]">API Health: Connected</p>
      </div>
      <Facebook size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
    </div>
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">
      <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-5 sm:p-10 shadow-2xl border border-slate-200/60">
        <div className="flex items-center gap-4 mb-10 border-b border-slate-50 pb-6">
          <List size={22} className="text-blue-600"/>
          <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Sync Activity Logs</h4>
        </div>
        <div className="space-y-2">
          {['Price Sync: Mahindra 2638 HST','New Media: Big Tex 14LP Dump','Inventory Update checked','Lead Captured: Marketplace Messenger','Batch Update: Compact Tractors','API Handshake: Success'].map((msg, i) => (
            <div key={i} className="flex justify-between items-center p-6 bg-slate-50/50 rounded-2xl border-2 border-white mb-4 hover:bg-white transition-all shadow-sm group">
              <div className="flex items-center gap-6">
                <div className="p-2.5 bg-green-100 rounded-xl">
                  <CheckCircle2 size={20} className="text-green-600"/>
                </div>
                <span className="text-base font-black tracking-tight leading-none">{msg}</span>
              </div>
              <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{['2m','14m','1h','3h','5h','12h'][i]} ago</span>
            </div>
          ))}
        </div>
      </div>
      <div className="space-y-8">
        <div className="bg-white rounded-[2.5rem] p-10 shadow-2xl border border-slate-200/60">
          <div className="flex items-center gap-4 mb-8">
            <BarChart3 size={22} className="text-blue-600"/>
            <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Distribution Health</h4>
          </div>
          <div className="space-y-6">
            {[
              ['Catalog Match Rate', '98%', 'blue'],
              ['Image Optimization', '100%', 'green'],
              ['Sync Latency', '1.2s', 'blue']
            ].map(([l,v,c]) => (
              <div key={l} className="space-y-2">
                <div className="flex justify-between">
                  <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{l}</span>
                  <span className="text-[10px] font-black text-slate-900 uppercase">{v}</span>
                </div>
                <div className="h-1.5 w-full bg-slate-50 rounded-full overflow-hidden border border-slate-100">
                  <div className={`h-full ${c==='blue'?'bg-blue-600':'bg-green-600'} rounded-full`} style={{ width: v.includes('%') ? v : '100%' }}></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  </div>
);
