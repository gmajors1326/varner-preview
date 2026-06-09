import React, { useState } from 'react';
import { Facebook, List, CheckCircle2, BarChart3, Copy, Check } from 'lucide-react';

export const MarketplaceTab = () => {
  const [copied, setCopied] = useState(false);
  const siteUrl = window.varnerData?.site_url || '';
  const feedUrl = `${siteUrl}facebook-catalog.csv`;

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(feedUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy feed URL: ', err);
    }
  };

  return (
    <div className="space-y-8 animate-in fade-in duration-500 text-slate-950 font-black">
      {/* Meta Commerce Engine Header */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-12 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10">
          <h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">Meta Commerce Engine</h3>
          <p className="text-white font-bold opacity-90 uppercase tracking-[0.3em] text-[10px]">API Health: Connected</p>
        </div>
        <Facebook size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* Facebook Catalog Feed URL Card */}
      <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 shadow-2xl border border-slate-200/60">
        <div className="flex items-center gap-4 mb-6 border-b border-slate-50 pb-4">
          <Facebook size={22} className="text-blue-600"/>
          <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Facebook Catalog Product Feed</h4>
        </div>
        <p className="text-sm font-bold text-slate-600 mb-6 uppercase tracking-tight">
          Use this product data feed URL to sync your inventory automatically with your Facebook Catalog / Commerce Manager Sandbox.
        </p>
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="flex-1 bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 font-mono text-sm text-slate-700 break-all select-all flex items-center">
            {feedUrl}
          </div>
          <button
            onClick={handleCopy}
            className={`px-6 py-4 rounded-2xl font-black text-sm uppercase tracking-wider transition-all flex items-center justify-center gap-3 cursor-pointer shrink-0 ${
              copied 
                ? 'bg-green-600 text-white shadow-lg shadow-green-100' 
                : 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-100'
            }`}
          >
            {copied ? (
              <>
                <Check size={16} />
                <span>Copied!</span>
              </>
            ) : (
              <>
                <Copy size={16} />
                <span>Copy Feed URL</span>
              </>
            )}
          </button>
        </div>
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
};
