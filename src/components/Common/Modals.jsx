import React, { useEffect, useRef } from 'react';
import { X } from 'lucide-react';

export const ManageListModal = ({ title, items, inputValue, onInputChange, onAdd, onDelete, onClose, placeholder }) => {
  const modalRef = useRef(null);
  const previousFocusRef = useRef(null);

  useEffect(() => {
    previousFocusRef.current = document.activeElement;
    const timer = setTimeout(() => {
      if (modalRef.current) {
        const first = modalRef.current.querySelector('input, button, [tabindex]:not([tabindex="-1"])');
        if (first) first.focus();
      }
    }, 50);
    return () => { clearTimeout(timer); };
  }, []);

  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') { onClose(); return; }
      if (e.key !== 'Tab' || !modalRef.current) return;
      const focusable = modalRef.current.querySelectorAll('input, button, [tabindex]:not([tabindex="-1"])');
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      if (previousFocusRef.current && typeof previousFocusRef.current.focus === 'function') {
        previousFocusRef.current.focus();
      }
    };
  }, [onClose]);

  return (
    <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label={title} onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
      <div ref={modalRef} className="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col" style={{ maxHeight: '85vh' }}>
        <div className="flex items-center justify-between p-6 border-b border-slate-100 shrink-0">
          <div>
            <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">{title}</h3>
            <p className="text-[10px] text-slate-400 font-bold mt-1">{items.length} {title.toLowerCase().split(' ')[1]} in list</p>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-700 transition-colors" aria-label="Close modal"><X size={20}/></button>
        </div>
        <div className="p-5 shrink-0">
          <div className="flex gap-2">
            <input 
              type="text" 
              value={inputValue} 
              onChange={e => onInputChange(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && onAdd()}
              placeholder={placeholder}
              className="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors"
            />
            <button onClick={onAdd} className="bg-red-600 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-red-700 transition-colors shrink-0">Add</button>
          </div>
        </div>
        <div className="overflow-y-auto flex-1 px-5 pb-5">
          <div className="space-y-1.5">
            {items.map(item => (
              <div key={item} className="flex items-center justify-between px-4 py-2.5 bg-slate-50 rounded-xl group hover:bg-red-50 transition-colors">
                <span className="font-bold text-sm text-slate-900">{item}</span>
                <button onClick={() => onDelete(item)} className="text-slate-300 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" aria-label={`Remove ${item}`}><X size={14}/></button>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
