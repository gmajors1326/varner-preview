import React, { useState } from 'react';
import { AlertCircle, RotateCcw, Trash2 } from 'lucide-react';

export const HistoryTab = ({ deletedItems, onRestore, onPermanentDelete, onBulkRestore, onBulkPermanentDelete }) => {
  const [selectedIds, setSelectedIds] = useState([]);

  const handleSelectAll = (e) => {
    if (e.target.checked) {
      setSelectedIds(deletedItems.map(item => item.id));
    } else {
      setSelectedIds([]);
    }
  };

  const handleSelectItem = (id) => {
    setSelectedIds(prev => 
      prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]
    );
  };

  const isAllSelected = deletedItems.length > 0 && selectedIds.length === deletedItems.length;

  const handleBulkRestoreClick = () => {
    if (onBulkRestore && selectedIds.length > 0) {
      const selectedWpIds = deletedItems
        .filter(item => selectedIds.includes(item.id))
        .map(item => item.wpId);
      onBulkRestore(selectedWpIds).then(() => setSelectedIds([]));
    }
  };

  const handleBulkPermanentDeleteClick = () => {
    if (onBulkPermanentDelete && selectedIds.length > 0) {
      const selectedWpIds = deletedItems
        .filter(item => selectedIds.includes(item.id))
        .map(item => item.wpId);
      onBulkPermanentDelete(selectedWpIds).then(() => setSelectedIds([]));
    }
  };

  return (
    <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-500">
      <div className="p-5 sm:p-8 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 className="text-lg sm:text-xl font-black uppercase tracking-tight leading-none">Recycle Bin</h3>
          <p className="text-slate-400 font-black uppercase text-[9px] tracking-[0.3em] mt-2 italic hidden sm:block">Items stay here until permanently deleted</p>
        </div>
        
        <div className="flex items-center gap-3 flex-wrap">
          {selectedIds.length > 0 && (
            <div className="flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-2xl p-1.5 pr-3 shadow-sm animate-in fade-in zoom-in-95 duration-200">
              <span className="text-[10px] font-black uppercase tracking-wider text-slate-500 px-2 py-1 bg-white rounded-xl border border-slate-200">
                {selectedIds.length} Selected
              </span>
              <button 
                onClick={handleBulkRestoreClick}
                className="bg-green-50 text-green-600 px-3.5 py-1.5 rounded-xl font-black text-[9px] uppercase tracking-widest flex items-center gap-1.5 hover:bg-green-100 transition-all border border-green-200/50 active:scale-95 cursor-pointer"
              >
                <RotateCcw size={12}/> Restore Selected
              </button>
              <button 
                onClick={handleBulkPermanentDeleteClick}
                className="bg-red-50 text-red-600 px-3.5 py-1.5 rounded-xl font-black text-[9px] uppercase tracking-widest flex items-center gap-1.5 hover:bg-red-100 transition-all border border-red-200/50 active:scale-95 cursor-pointer"
              >
                <Trash2 size={12}/> Delete Selected
              </button>
              <button 
                onClick={() => setSelectedIds([])}
                className="text-[9px] font-black uppercase tracking-wider text-slate-400 hover:text-slate-600 px-2 py-1 cursor-pointer"
              >
                Clear
              </button>
            </div>
          )}
          
          <div className="bg-amber-100 text-amber-700 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 h-fit">
            <AlertCircle size={14}/> {deletedItems.length} Items
          </div>
        </div>
      </div>
      
      <div className="overflow-x-auto p-2 no-scrollbar">
        <table className="w-full text-left border-collapse min-w-[800px]">
          <thead>
            <tr className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
              {deletedItems.length > 0 && (
                <th className="px-6 py-5 w-12 text-center">
                  <input 
                    type="checkbox" 
                    checked={isAllSelected} 
                    onChange={handleSelectAll}
                    className="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer accent-red-600"
                  />
                </th>
              )}
              <th className="px-6 py-5 w-32">STOCK #</th>
              <th className="px-6 py-5">EQUIPMENT</th>
              <th className="px-6 py-5">DELETED ON</th>
              <th className="px-6 py-5 text-right w-44">ACTIONS</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-50">
            {deletedItems.length === 0 ? (
              <tr>
                <td colSpan={5} className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">
                  Recycle bin is empty
                </td>
              </tr>
            ) : (
              deletedItems.map(item => {
                const isSelected = selectedIds.includes(item.id);
                return (
                  <tr 
                    key={item.id} 
                    className={`transition-all group cursor-pointer ${isSelected ? 'bg-red-50/20 hover:bg-red-50/30' : 'hover:bg-slate-50'}`}
                    onClick={() => handleSelectItem(item.id)}
                  >
                    <td className="px-6 py-5 text-center" onClick={e => e.stopPropagation()}>
                      <input 
                        type="checkbox" 
                        checked={isSelected}
                        onChange={() => handleSelectItem(item.id)}
                        className="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer accent-red-600"
                      />
                    </td>
                    <td className="px-6 py-5 font-mono font-bold text-sm text-slate-500">{item.stock}</td>
                    <td className="px-6 py-5">
                      <p className={`font-black text-base uppercase leading-tight ${isSelected ? 'text-slate-800' : 'text-slate-400'}`}>{item.year} {item.make} {item.model}</p>
                      <p className="text-[9px] font-black uppercase tracking-widest mt-1 text-slate-300">{item.category}</p>
                    </td>
                    <td className="px-6 py-5">
                      <p className="text-[11px] font-black text-slate-400 uppercase">{item.deletedAt ? new Date(item.deletedAt).toLocaleDateString() : '—'}</p>
                    </td>
                    <td className="px-6 py-5 text-right" onClick={e => e.stopPropagation()}>
                      <div className="flex items-center justify-end gap-3">
                        <button 
                          onClick={() => onRestore(item)} 
                          className="bg-green-50 text-green-600 px-4 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-green-100 transition-all active:scale-95 border border-green-100 cursor-pointer"
                        >
                          <RotateCcw size={14}/> Restore
                        </button>
                        <button 
                          onClick={() => onPermanentDelete(item)} 
                          className="bg-slate-100 text-slate-400 p-2.5 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all active:scale-95 border border-slate-200 cursor-pointer" 
                          title="Permanently Delete"
                        >
                          <Trash2 size={16}/>
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
