import React from 'react';
import { Facebook, X, Image as ImageIcon, Plus } from 'lucide-react';

export const FBPreviewModal = ({ unitData, onClose }) => (
  <div className="fixed inset-0 bg-slate-950/90 backdrop-blur-xl z-50 flex items-center justify-center p-8">
    <div className="bg-white w-full max-w-[420px] rounded-[3.5rem] overflow-hidden shadow-2xl border-[12px] border-slate-950 relative h-[85vh] flex flex-col animate-in zoom-in duration-300">
      <div className="p-8 bg-white flex justify-between border-b items-center pt-10">
        <span className="font-black text-[11px] uppercase text-blue-600 flex items-center gap-3 tracking-[0.2em]"><Facebook size={20} fill="currentColor"/> Meta Marketplace Preview</span>
        <button onClick={onClose} className="p-2 hover:bg-slate-100 rounded-full"><X size={24} className="text-slate-400"/></button>
      </div>
      <div className="flex-1 overflow-y-auto no-scrollbar pb-12">
        <div className="aspect-[4/3] bg-slate-100 relative overflow-hidden">
          {unitData.images?.length > 0 ? (
            <img 
              src={unitData.images[0]} 
              className="w-full h-full object-cover" 
              onError={e => { 
                e.target.onerror=null; 
                e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; 
              }}
              alt={unitData.title}
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-slate-200">
              <ImageIcon size={64}/>
            </div>
          )}
        </div>
        <div className="p-8 space-y-8">
          <div className="text-slate-900">
            <h2 className="text-4xl font-black leading-none mb-2 tracking-tighter">${parseInt(unitData.price||0).toLocaleString()}</h2>
            <h3 className="text-2xl font-bold text-slate-800 leading-tight mb-2 tracking-tight">{unitData.year} {unitData.title}</h3>
            <p className="text-slate-400 text-sm font-black uppercase tracking-widest">Delta, CO · Posted now</p>
          </div>
          <div className="flex gap-3">
            <button className="flex-1 bg-[#0866FF] text-white py-4 rounded-[1.25rem] font-black text-sm shadow-xl">Message</button>
            <button className="p-4 bg-slate-100 rounded-[1.25rem] text-slate-600"><Plus size={24}/></button>
          </div>
          <div className="pt-8 border-t border-slate-100 text-slate-900">
            <h4 className="font-black text-[12px] uppercase text-slate-400 mb-5 tracking-[0.3em]">Description</h4>
            <div className="text-[16px] text-slate-800 font-medium leading-relaxed rich-text-content" dangerouslySetInnerHTML={{ __html: unitData.description }}/>
          </div>
        </div>
      </div>
      <div className="p-8 bg-slate-50 border-t border-slate-200 shadow-inner">
        <button onClick={onClose} className="w-full py-5 bg-slate-950 text-white font-black uppercase tracking-[0.4em] text-[11px] rounded-3xl hover:bg-black transition-all">Close Simulator</button>
      </div>
    </div>
  </div>
);
