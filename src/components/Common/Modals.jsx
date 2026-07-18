import React, { useEffect, useRef } from 'react';
import { X, Edit2 } from 'lucide-react';

export const ManageListModal = ({ 
  title, 
  items, 
  inputValue, 
  onInputChange, 
  onAdd, 
  onDelete, 
  onClose, 
  placeholder,
  // Hierarchical tree selectors
  parentLabel,
  parents,
  selectedParent,
  onParentChange,
  subParentLabel,
  subParents,
  selectedSubParent,
  onSubParentChange,
  // Rename handler
  onRename
}) => {
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

  // Restore focus only on actual unmount, not every re-render
  useEffect(() => {
    return () => {
      if (previousFocusRef.current && typeof previousFocusRef.current.focus === 'function') {
        previousFocusRef.current.focus();
      }
    };
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
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [onClose]);

  const isInputDisabled = parents && (!selectedParent || (subParents && !selectedSubParent));

  return (
    <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label={title} onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
      <div ref={modalRef} className="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col" style={{ maxHeight: '85vh' }}>
        <div className="flex items-center justify-between p-6 border-b border-slate-100 shrink-0">
          <div>
            <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">{title}</h3>
            <p className="text-[10px] text-slate-400 font-bold mt-1">{items.length} items in list</p>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-700 transition-colors" aria-label="Close modal"><X size={20}/></button>
        </div>

        {/* Parent Category Selector */}
        {parents && (
          <div className="px-5 pt-3 shrink-0 space-y-3">
            <div>
              <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1 mb-1.5 block">
                {parentLabel || 'Parent Category'}
              </label>
              <select
                value={selectedParent}
                onChange={e => onParentChange(e.target.value)}
                className="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors bg-slate-50"
              >
                <option value="">-- Select Category --</option>
                {parents.map(p => <option key={p} value={p}>{p}</option>)}
              </select>
            </div>

            {/* Parent Subcategory Selector */}
            {subParents && (
              <div>
                <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1 mb-1.5 block">
                  {subParentLabel || 'Parent Subcategory'}
                </label>
                <select
                  value={selectedSubParent}
                  onChange={e => onSubParentChange(e.target.value)}
                  className="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors bg-slate-50"
                  disabled={!selectedParent}
                >
                  <option value="">-- Select Subcategory --</option>
                  {subParents.map(p => <option key={p} value={p}>{p}</option>)}
                </select>
              </div>
            )}
          </div>
        )}

        <div className="p-5 shrink-0">
          <div className="flex gap-2">
            <input 
              type="text" 
              value={inputValue} 
              onChange={e => onInputChange(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && !isInputDisabled && onAdd()}
              placeholder={placeholder}
              disabled={isInputDisabled}
              className="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors disabled:bg-slate-100 disabled:cursor-not-allowed"
            />
            <button 
              onClick={onAdd} 
              disabled={isInputDisabled}
              className="bg-red-600 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-red-700 transition-colors shrink-0 disabled:bg-slate-300 disabled:cursor-not-allowed"
            >
              Add
            </button>
          </div>
        </div>
        <div className="overflow-y-auto flex-1 px-5 pb-5">
          <div className="space-y-1.5">
            {items.map(item => (
              <div key={item} className="flex items-center justify-between px-4 py-2.5 bg-slate-50 rounded-xl group hover:bg-red-50 transition-colors">
                <span className="font-bold text-sm text-slate-900">{item}</span>
                <div className="flex items-center gap-2">
                  {onRename && (
                    <button 
                      onClick={() => {
                        const newName = window.prompt(`Rename "${item}" to:`, item);
                        if (newName && newName.trim() && newName.trim() !== item) {
                          onRename(item, newName.trim());
                        }
                      }} 
                      className="text-slate-600 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" 
                      aria-label={`Rename ${item}`}
                    >
                      <Edit2 size={12}/>
                    </button>
                  )}
                  <button onClick={() => onDelete(item)} className="text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" aria-label={`Remove ${item}`}><X size={14}/></button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
