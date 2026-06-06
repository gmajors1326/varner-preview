import React, { useRef, useState } from 'react';
import { Image as ImageIcon, X, Camera, Plus } from 'lucide-react';

export const AttachmentsSection = ({ attachments = [], onAdd, onChange, onRemove, onImageUpload }) => {
  const ref = useRef(null);
  const [editingIndex, setEditingIndex] = useState(null);

  return (
    <div className="bg-white rounded-[2rem] p-4 sm:p-6 lg:p-10 shadow-xl border border-slate-200/60">
      <div className="flex justify-between items-center mb-6 sm:mb-10">
        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2"><ImageIcon size={14} className="text-red-600"/>Implements / Attachments</h3>
        <span className="hidden sm:block bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-4 py-2 rounded-full border border-slate-100 tracking-widest shadow-sm">Add-on Products</span>
      </div>
      <input type="file" accept="image/*" className="hidden" ref={ref} onChange={e => { if (e.target.files?.[0] && editingIndex !== null) onImageUpload(editingIndex, e.target.files[0]); e.target.value = null; setEditingIndex(null); }}/>
      <div className="space-y-6">
        {attachments.map((imp, i) => (
          <div key={i} className="bg-slate-50 rounded-[1.5rem] p-6 border-2 border-slate-100 flex flex-col md:flex-row gap-6 relative group">
            <button onClick={() => onRemove(i)} className="absolute -top-3 -right-3 bg-red-600 text-white p-2 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10"><X size={16}/></button>
            <div className="w-full md:w-40 aspect-square bg-white rounded-xl overflow-hidden border-2 border-slate-200 shrink-0 relative">
              <img src={imp.image} className="w-full h-full object-cover" onError={e => { e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }} alt={imp.title} />
              <div onClick={() => { setEditingIndex(i); ref.current?.click(); }} className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity cursor-pointer"><Camera size={20} className="text-white"/></div>
            </div>
            <div className="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="sm:col-span-2">
                <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Implement Title</label>
                <input placeholder="e.g. Front End Loader" value={imp.title} onChange={e => onChange(i,'title',e.target.value)} className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"/>
              </div>
              <div>
                <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Price (USD)</label>
                <input placeholder="0.00" value={imp.price} onChange={e => onChange(i,'price',e.target.value)} className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"/>
              </div>
              <div className="sm:col-span-2">
                <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Short Description</label>
                <textarea placeholder="Brief specs or features..." value={imp.description} onChange={e => onChange(i,'description',e.target.value)} className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm h-20 resize-none"/>
              </div>
            </div>
          </div>
        ))}
        <button onClick={onAdd} className="w-full py-4 border-2 border-dashed border-slate-200 rounded-[1.5rem] text-slate-400 font-black uppercase tracking-widest text-[11px] hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center gap-2">
          <Plus size={16}/> Add Implement
        </button>
      </div>
    </div>
  );
};
