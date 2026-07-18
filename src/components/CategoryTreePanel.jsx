import React, { useState, useEffect, useRef } from 'react';
import { X, Edit2, Plus, ChevronRight } from 'lucide-react';

export const CategoryTreePanel = ({
  categoryTree,
  onAddCategory,
  onAddSubcategory,
  onRename,
  onDelete,
  onClose,
}) => {
  const [newCategoryName, setNewCategoryName] = useState('');
  const [newSubName, setNewSubName] = useState('');
  const [addingSubFor, setAddingSubFor] = useState(null);
  const [renaming, setRenaming] = useState(null);
  const [reassign, setReassign] = useState(null);
  const panelRef = useRef(null);
  const prevFocusRef = useRef(null);

  useEffect(() => {
    prevFocusRef.current = document.activeElement;
    const timer = setTimeout(() => {
      if (panelRef.current) {
        const first = panelRef.current.querySelector('input, button, [tabindex]:not([tabindex="-1"])');
        if (first) first.focus();
      }
    }, 50);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    return () => {
      if (prevFocusRef.current && typeof prevFocusRef.current.focus === 'function') {
        prevFocusRef.current.focus();
      }
    };
  }, []);

  useEffect(() => {
    const handler = (e) => {
      if (e.key === 'Escape') { onClose(); return; }
      if (e.key !== 'Tab' || !panelRef.current) return;
      const focusable = panelRef.current.querySelectorAll('input, button, [tabindex]:not([tabindex="-1"])');
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, [onClose]);

  const handleAddCategory = async () => {
    const name = newCategoryName.trim();
    if (!name) return;
    const ok = await onAddCategory('category', name);
    if (ok) setNewCategoryName('');
  };

  const handleAddSubcategory = async (parentCat) => {
    const name = newSubName.trim();
    if (!name || !parentCat) return;
    const ok = await onAddSubcategory('subcategory', name, parentCat);
    if (ok) {
      setNewSubName('');
      setAddingSubFor(null);
    }
  };

  const handleRename = async (type, oldName, newName, parentCat) => {
    if (!newName || newName === oldName) return;
    await onRename(type, oldName, newName, parentCat);
    setRenaming(null);
  };

  const handleDelete = async (type, name, parentCat) => {
    const result = await onDelete(type, name, parentCat);
    if (result && result.needsReassign) {
      setReassign({ type, name, parentCat, affectedPosts: result.affectedPosts, siblings: result.siblings });
    }
  };

  const handleReassignDelete = async (targetSub) => {
    if (!reassign || !targetSub) return;
    await onDelete(reassign.type, reassign.name, reassign.parentCat, targetSub);
    setReassign(null);
  };

  const handleCancelRename = () => setRenaming(null);
  const handleCancelAddSub = () => { setAddingSubFor(null); setNewSubName(''); };

  const categories = Object.keys(categoryTree || {}).sort();

  return (
    <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Manage Categories" onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
      <div ref={panelRef} className="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style={{ maxHeight: '85vh' }}>
        <div className="flex items-center justify-between p-6 border-b border-slate-100 shrink-0">
          <div>
            <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">Manage Categories</h3>
            <p className="text-[10px] text-slate-400 font-bold mt-1">{categories.length} categories</p>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-700 transition-colors" aria-label="Close"><X size={20} /></button>
        </div>

        <div className="p-5 border-b border-slate-100 shrink-0">
          <div className="flex gap-2">
            <input type="text" value={newCategoryName} onChange={e => setNewCategoryName(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && handleAddCategory()}
              placeholder="New category name..."
              className="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors" />
            <button onClick={handleAddCategory}
              className="bg-red-600 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-red-700 transition-colors shrink-0 flex items-center gap-1.5">
              <Plus size={14} /> Add Category
            </button>
          </div>
        </div>

        <div className="overflow-y-auto flex-1 px-5 pb-5 pt-3 space-y-1">
          {categories.length === 0 && (
            <p className="text-center text-slate-300 font-black text-xs uppercase tracking-widest py-10">No categories yet. Add one above.</p>
          )}
          {categories.map(cat => {
            const subs = Object.keys(categoryTree[cat] || {}).sort();
            return (
              <div key={cat}>
                <div className="flex items-center justify-between px-4 py-3 bg-slate-50 rounded-xl group hover:bg-red-50 transition-colors">
                  <span className="font-black text-sm text-slate-900">{cat}</span>
                  <div className="flex items-center gap-1.5">
                    <button onClick={() => setAddingSubFor(addingSubFor === cat ? null : cat)}
                      className="text-slate-400 hover:text-green-600 transition-colors opacity-0 group-hover:opacity-100 p-1" aria-label={`Add subcategory under ${cat}`} title="Add subcategory">
                      <Plus size={14} />
                    </button>
                    <button onClick={() => setRenaming({ type: 'category', name: cat, parentCat: null })}
                      className="text-slate-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100 p-1" aria-label={`Rename ${cat}`} title="Rename">
                      <Edit2 size={12} />
                    </button>
                    <button onClick={() => handleDelete('category', cat, null)}
                      className="text-red-300 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100 p-1" aria-label={`Delete ${cat}`} title="Delete">
                      <X size={14} />
                    </button>
                  </div>
                </div>

                {addingSubFor === cat && (
                  <div className="ml-8 mt-1 mb-2 flex gap-2">
                    <input type="text" value={newSubName} onChange={e => setNewSubName(e.target.value)}
                      onKeyDown={e => e.key === 'Enter' && handleAddSubcategory(cat)}
                      placeholder={`Subcategory under ${cat}...`}
                      className="flex-1 border-2 border-slate-200 rounded-xl px-3 py-2 text-sm font-bold focus:outline-none focus:border-green-500 transition-colors" autoFocus />
                    <button onClick={() => handleAddSubcategory(cat)}
                      className="bg-green-600 text-white px-3 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-green-700 transition-colors shrink-0">
                      Add
                    </button>
                    <button onClick={handleCancelAddSub}
                      className="text-slate-400 hover:text-slate-700 p-1">
                      <X size={16} />
                    </button>
                  </div>
                )}

                {/* Rename inline for category */}
                {renaming && renaming.type === 'category' && renaming.name === cat && (
                  <div className="ml-8 mt-1 mb-2 flex gap-2">
                    <input type="text" defaultValue={cat} autoFocus
                      onKeyDown={e => {
                        if (e.key === 'Enter') handleRename('category', cat, e.target.value, null);
                        if (e.key === 'Escape') handleCancelRename();
                      }}
                      onBlur={e => handleRename('category', cat, e.target.value, null)}
                      className="flex-1 border-2 border-blue-400 rounded-xl px-3 py-2 text-sm font-bold focus:outline-none transition-colors" />
                    <button onClick={handleCancelRename} className="text-slate-400 hover:text-slate-700 p-1"><X size={16} /></button>
                  </div>
                )}

                {/* Subcategories */}
                {subs.map(sub => (
                  <div key={`${cat}-${sub}`}>
                    <div className="flex items-center justify-between px-4 py-2.5 ml-6 mt-0.5 bg-white rounded-xl group hover:bg-amber-50 transition-colors border border-slate-100">
                      <div className="flex items-center gap-2">
                        <ChevronRight size={12} className="text-slate-300 rotate-90 shrink-0" />
                        <span className="font-bold text-sm text-slate-700">{sub}</span>
                      </div>
                      <div className="flex items-center gap-1.5">
                        <button onClick={() => setRenaming({ type: 'subcategory', name: sub, parentCat: cat })}
                          className="text-slate-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100 p-1" aria-label={`Rename ${sub}`} title="Rename">
                          <Edit2 size={12} />
                        </button>
                        <button onClick={() => handleDelete('subcategory', sub, cat)}
                          className="text-red-300 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100 p-1" aria-label={`Delete ${sub}`} title="Delete">
                          <X size={14} />
                        </button>
                      </div>
                    </div>

                    {/* Rename inline for subcategory */}
                    {renaming && renaming.type === 'subcategory' && renaming.name === sub && renaming.parentCat === cat && (
                      <div className="ml-14 mt-1 mb-2 flex gap-2">
                        <input type="text" defaultValue={sub} autoFocus
                          onKeyDown={e => {
                            if (e.key === 'Enter') handleRename('subcategory', sub, e.target.value, cat);
                            if (e.key === 'Escape') handleCancelRename();
                          }}
                          onBlur={e => handleRename('subcategory', sub, e.target.value, cat)}
                          className="flex-1 border-2 border-blue-400 rounded-xl px-3 py-2 text-sm font-bold focus:outline-none transition-colors" />
                        <button onClick={handleCancelRename} className="text-slate-400 hover:text-slate-700 p-1"><X size={16} /></button>
                      </div>
                    )}

                    {/* Reassign on delete */}
                    {reassign && reassign.name === sub && reassign.parentCat === cat && (
                      <div className="ml-14 mt-1 mb-2 p-3 bg-amber-50 border border-amber-200 rounded-xl space-y-2">
                        <p className="text-[10px] font-black text-amber-800 uppercase tracking-wider">
                          Move {reassign.affectedPosts} unit(s) to:
                        </p>
                        <div className="flex gap-2">
                          <select
                            defaultValue=""
                            onChange={e => handleReassignDelete(e.target.value)}
                            className="flex-1 border-2 border-amber-300 rounded-xl px-3 py-2 text-sm font-bold focus:outline-none focus:border-amber-500 bg-white"
                          >
                            <option value="" disabled>-- Select subcategory --</option>
                            {reassign.siblings.map(s => <option key={s} value={s}>{s}</option>)}
                          </select>
                          <button onClick={() => setReassign(null)}
                            className="text-slate-400 hover:text-slate-700 p-1"><X size={16} /></button>
                        </div>
                        <p className="text-[9px] text-amber-600 font-bold">Select a subcategory to move units into, then the delete will complete.</p>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};
