import React, { useState } from 'react';
import { Search, X, Edit2, Copy, Image as ImageIcon } from 'lucide-react';
import { FilterSidebar } from './FilterSidebar';
import { FilterTag } from './Common/Navigation';
import { getDaysInStock } from '../utils/helpers';

export const InventoryTable = ({
  filteredInventory,
  inventoryList,
  isLoading,
  activeFilters,
  searchQuery,
  onFilterChange,
  onSearch,
  onClearFilters,
  onEdit,
  onDelete,
  onClone,
  onToggle,
}) => {
  const [showFilterPanel, setShowFilterPanel] = useState(false);

  const hasFilters = searchQuery || Object.values(activeFilters).some(v =>
    Array.isArray(v) ? v.length > 0 : v !== ''
  );

  return (
    <div className="flex flex-col gap-4 animate-in fade-in slide-in-from-bottom-6 duration-500">

      {/* Horizontal filter bar — desktop */}
      <div className="hidden xl:block">
        <FilterSidebar horizontal inventoryList={inventoryList} filters={activeFilters}
          searchQuery={searchQuery} onFilterChange={onFilterChange}
          onKeywordSearch={onSearch} onClearAll={onClearFilters} />
      </div>

      {/* Active filters bar */}
      {hasFilters && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 flex flex-wrap items-center gap-2">
          <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0 mr-1">Active Filters:</span>
          {searchQuery && (
            <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
              <button onClick={() => onSearch('')} className="font-black leading-none hover:text-red-200">×</button>
              {searchQuery.toUpperCase()}
            </span>
          )}
          {['makes', 'status', 'categories', 'models', 'conditions'].flatMap(key =>
            activeFilters[key].map(v => (
              <FilterTag key={`${key}-${v}`} label={v.toUpperCase()}
                onRemove={() => onFilterChange(key, activeFilters[key].filter(x => x !== v))} />
            ))
          )}
          {(activeFilters.yearMin || activeFilters.yearMax) && (
            <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
              <button onClick={() => { onFilterChange('yearMin', ''); onFilterChange('yearMax', ''); }} className="font-black leading-none hover:text-red-200">×</button>
              YEAR: {activeFilters.yearMin || '?'}–{activeFilters.yearMax || '?'}
            </span>
          )}
          {(activeFilters.priceMin || activeFilters.priceMax) && (
            <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
              <button onClick={() => { onFilterChange('priceMin', ''); onFilterChange('priceMax', ''); }} className="font-black leading-none hover:text-red-200">×</button>
              PRICE: ${activeFilters.priceMin || '0'}–${activeFilters.priceMax || '∞'}
            </span>
          )}
          {activeFilters.stockSearch && (
            <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
              <button onClick={() => onFilterChange('stockSearch', '')} className="font-black leading-none hover:text-red-200">×</button>
              STOCK #: {activeFilters.stockSearch}
            </span>
          )}
          {activeFilters.vinSearch && (
            <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
              <button onClick={() => onFilterChange('vinSearch', '')} className="font-black leading-none hover:text-red-200">×</button>
              VIN: {activeFilters.vinSearch}
            </span>
          )}
          <button onClick={onClearFilters} className="ml-auto text-xs font-black text-slate-400 hover:text-red-600 uppercase tracking-widest transition-colors shrink-0">Clear All</button>
        </div>
      )}

      <div className="flex flex-col gap-3">
        {/* Mobile filter drawer */}
        {showFilterPanel && (
          <div className="fixed inset-0 z-[9997] xl:hidden">
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setShowFilterPanel(false)} />
            <div className="absolute inset-y-0 left-0 w-80 bg-white overflow-y-auto shadow-2xl">
              <div className="flex items-center justify-between p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                <h3 className="font-black text-sm uppercase tracking-widest">Filters</h3>
                <button onClick={() => setShowFilterPanel(false)} className="p-1 text-gray-400 hover:text-gray-700"><X size={20} /></button>
              </div>
              <FilterSidebar inventoryList={inventoryList} filters={activeFilters}
                searchQuery={searchQuery} onFilterChange={onFilterChange}
                onKeywordSearch={onSearch} onClearAll={onClearFilters} />
            </div>
          </div>
        )}

        {/* Table card */}
        <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden" style={{ minWidth: 0 }}>
          <div className="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between w-full">
            <div className="flex items-center gap-3">
              <button onClick={() => setShowFilterPanel(true)}
                className="xl:hidden flex items-center gap-2 bg-white border-2 border-slate-200 px-4 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-sm hover:border-red-500 transition-colors">
                <Search size={14} /> Filters
              </button>
              <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Master Inventory Ledger</span>
            </div>
            <span className="bg-slate-900 text-white text-[11px] font-black px-4 py-2 rounded-full uppercase tracking-widest shrink-0">
              {filteredInventory.length} Unit{filteredInventory.length !== 1 ? 's' : ''} Found
            </span>
          </div>

          <div className="overflow-x-auto p-2">
            {isLoading ? (
              <div className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">Loading inventory…</div>
            ) : (
              <table className="w-full text-left border-collapse min-w-[1400px]">
                <thead>
                  <tr className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
                    <th className="px-6 py-5 w-24">STOCK #</th>
                    <th className="px-6 py-5 w-28">PHOTO</th>
                    <th className="px-6 py-5">YEAR / MAKE / MODEL</th>
                    <th className="px-6 py-5">CATEGORY</th>
                    <th className="px-6 py-5 text-center w-32">CONDITION</th>
                    <th className="px-6 py-5 w-32">PRICE (USD)</th>
                    <th className="px-6 py-5 w-40">STATUS</th>
                    <th className="px-6 py-5 text-center w-36">DAYS IN STOCK</th>
                    <th className="px-6 py-5 text-center w-28">WEBSITE</th>
                    <th className="px-6 py-5 text-center w-28">FEATURED</th>
                    <th className="px-6 py-5 text-right w-32">ACTIONS</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                  {filteredInventory.length === 0 ? (
                    <tr><td colSpan="11" className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">No units found</td></tr>
                  ) : filteredInventory.map(item => (
                    <tr key={item.id} className="hover:bg-slate-50 transition-all cursor-pointer group" onClick={() => onEdit(item.wpId)}>
                      <td className="px-6 py-5 font-mono font-bold text-sm text-slate-500">{item.stock}</td>
                      <td className="px-4 py-3">
                        <div className="w-40 h-28 bg-slate-100 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                          {item.images?.[0]
                            ? <img src={item.images[0]} alt={`${item.year} ${item.make} ${item.model}`} className="w-full h-full object-cover"
                                onError={e => { e.target.onerror = null; e.target.src = 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }} />
                            : <div className="w-full h-full flex items-center justify-center"><ImageIcon size={16} className="text-slate-300" /></div>
                          }
                        </div>
                      </td>
                      <td className="px-6 py-5">
                        <p className="font-black text-base leading-tight uppercase tracking-tight">{item.year} {item.make}</p>
                        <p className="text-[10px] font-black uppercase tracking-widest mt-1 opacity-60">{item.model}</p>
                      </td>
                      <td className="px-6 py-5">
                        <div className="flex flex-col gap-0.5">
                          <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">{item.category}</span>
                          {item.subcategory && (
                            <span className="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                              &raquo; {item.subcategory}
                              {item.sub_subcategory && ` \u203A ${item.sub_subcategory}`}
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-5 text-center">
                        <span className="text-[9px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 px-3 py-1 rounded-lg border border-blue-100 shadow-sm">{item.condition}</span>
                      </td>
                      <td className="px-6 py-5 font-black text-base tracking-tighter">
                        {item.callForPrice
                          ? <span className="text-red-600 text-[11px] uppercase tracking-widest">Call for Price</span>
                          : <span className="text-slate-900">${parseInt(item.price || 0).toLocaleString()}</span>
                        }
                      </td>
                      <td className="px-6 py-5">
                        <span className={`inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border ${
                          item.status === 'In Stock' ? 'text-green-500 bg-green-50 border-green-100' :
                          item.status === 'Pending Sale' ? 'text-amber-500 bg-amber-50 border-amber-100' :
                          'text-slate-400 bg-slate-50 border-slate-200'
                        }`}>
                          <div className={`w-1.5 h-1.5 rounded-full ${
                            item.status === 'In Stock' ? 'bg-green-500 animate-pulse' :
                            item.status === 'Pending Sale' ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'
                          }`} />
                          {item.status}
                        </span>
                      </td>
                      <td className="px-6 py-5 text-center">
                        <span className="text-xs font-black uppercase tracking-wider text-slate-600 bg-slate-100/80 px-2.5 py-1 rounded-md border border-slate-200/50 shadow-sm">
                          {getDaysInStock(item)}
                        </span>
                      </td>
                      <td className="px-6 py-5 text-center" onClick={e => e.stopPropagation()}>
                        <div className="flex justify-center">
                          <button onClick={() => onToggle(item, 'show_on_website')}
                            className={`w-12 h-6 rounded-full relative transition-all duration-300 ${item.showOnWebsite ? 'bg-green-500' : 'bg-slate-200'}`}>
                            <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 ${item.showOnWebsite ? 'left-7' : 'left-1'}`} />
                          </button>
                        </div>
                      </td>
                      <td className="px-6 py-5 text-center" onClick={e => e.stopPropagation()}>
                        <div className="flex justify-center">
                          <button onClick={() => onToggle(item, 'featured')}
                            className={`w-12 h-6 rounded-full relative transition-all duration-300 ${item.featured ? 'bg-amber-500' : 'bg-slate-200'}`}>
                            <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 ${item.featured ? 'left-7' : 'left-1'}`} />
                          </button>
                        </div>
                      </td>
                      <td className="px-6 py-5 text-right">
                        <div className="flex items-center justify-end gap-2" onClick={e => e.stopPropagation()}>
                          <button onClick={() => onEdit(item.wpId)} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Edit"><Edit2 size={16} /></button>
                          <button onClick={() => onClone(item.wpId)} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Clone"><Copy size={16} /></button>
                          <button onClick={() => onDelete(item.wpId, item.stock)} className="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all active:scale-95" title="Delete"><X size={16} /></button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
