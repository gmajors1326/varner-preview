import React, { useState, useEffect } from 'react';
import { Search, ChevronDown } from 'lucide-react';

export const FilterSidebar = ({ inventoryList, filters, searchQuery, onFilterChange, onKeywordSearch, onClearAll, horizontal = false }) => {
  const [sections, setSections] = useState({
    listingType: true, category: true, manufacturer: true,
    model: true, year: true, price: true, condition: false,
  });
  const [showAllMakes, setShowAllMakes] = useState(false);
  const [showAllModels, setShowAllModels] = useState(false);
  const [yearInput, setYearInput] = useState({ min: filters.yearMin || '', max: filters.yearMax || '' });
  const [priceInput, setPriceInput] = useState({ min: filters.priceMin || '', max: filters.priceMax || '' });
  const [kwInput, setKwInput] = useState(searchQuery || '');

  useEffect(() => { setKwInput(searchQuery || ''); }, [searchQuery]);
  useEffect(() => { setYearInput({ min: filters.yearMin || '', max: filters.yearMax || '' }); }, [filters.yearMin, filters.yearMax]);
  useEffect(() => { setPriceInput({ min: filters.priceMin || '', max: filters.priceMax || '' }); }, [filters.priceMin, filters.priceMax]);

  const toggleSection = key => setSections(p => ({ ...p, [key]: !p[key] }));
  const toggleArr = (key, val) => {
    const arr = filters[key];
    onFilterChange(key, arr.includes(val) ? arr.filter(v => v !== val) : [...arr, val]);
  };

  const countOf = (field, val) => inventoryList.filter(i => i[field] === val).length;
  const allStatuses   = [...new Set(inventoryList.map(i => i.status).filter(Boolean))].sort();
  const allCategories = [...new Set(inventoryList.map(i => i.category).filter(Boolean))].sort();
  const allConditions = [...new Set(inventoryList.map(i => i.condition).filter(Boolean))].sort();

  const makeCounts = {};
  inventoryList.forEach(i => { if (i.make) makeCounts[i.make] = (makeCounts[i.make] || 0) + 1; });
  const sortedMakes = Object.keys(makeCounts).sort((a, b) => makeCounts[b] - makeCounts[a]);
  const displayMakes = showAllMakes ? sortedMakes : sortedMakes.slice(0, 5);

  const modelsByMake = {};
  inventoryList.forEach(i => {
    if (!i.make || !i.model) return;
    if (!modelsByMake[i.make]) modelsByMake[i.make] = {};
    modelsByMake[i.make][i.model] = (modelsByMake[i.make][i.model] || 0) + 1;
  });
  const makesForModels = filters.makes.length > 0
    ? filters.makes
    : Object.keys(modelsByMake).sort((a, b) => (makeCounts[b] || 0) - (makeCounts[a] || 0));
  const displayMakeGroups = showAllModels ? makesForModels : makesForModels.slice(0, 3);

  const SectionHeader = ({ label, sKey, applied }) => (
    <button onClick={() => toggleSection(sKey)}
      className="w-full flex items-center justify-between py-3.5 border-b border-gray-200 text-left hover:bg-gray-50 transition-colors px-2">
      <div className="flex items-center gap-2">
        <span className="font-bold text-sm text-gray-900 leading-none">{label}</span>
        {applied && <span className="text-[10px] font-bold text-red-600 leading-none">- Applied</span>}
      </div>
      <span className="text-gray-500 font-bold text-sm select-none w-4 text-center">{sections[sKey] ? '−' : '>'}</span>
    </button>
  );

  const CheckRow = ({ label, count, checked, onChange }) => (
    <label className="flex items-center justify-between py-1.5 cursor-pointer group hover:bg-gray-50 rounded-md px-2">
      <div className="flex items-center gap-2.5">
        <input type="checkbox" checked={checked} onChange={onChange} className="w-3.5 h-3.5 accent-red-600 cursor-pointer flex-shrink-0"/>
        <span className="text-sm text-gray-700 group-hover:text-gray-900">{label}</span>
      </div>
      {count !== undefined && <span className="text-xs text-gray-400 ml-2">({count})</span>}
    </label>
  );

  const ShowAllBtn = ({ show, onToggle }) => (
    <button onClick={onToggle} className="mt-3 w-full bg-slate-800 text-white text-[11px] font-bold py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
      {show ? '− Show Less' : '+ Show All'}
    </button>
  );

  if (horizontal) {
    return (
      <div className="w-full bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 mb-2">
        <div className="flex flex-wrap items-end gap-x-8 gap-y-6">
          {/* Quick Search */}
          <div className="flex-1 min-w-[300px]">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Keyword Search</p>
            <div className="flex gap-2">
              <input type="text" value={kwInput} onChange={e => setKwInput(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && onKeywordSearch(kwInput)}
                placeholder="Enter Keyword(s)..."
                className="flex-1 bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all"/>
              <button onClick={() => onKeywordSearch(kwInput)}
                className="bg-slate-950 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all">
                Search
              </button>
            </div>
          </div>

          {/* Status Dropdown */}
          <div className="w-48">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Stock Status</p>
            <div className="relative">
              <select 
                value={filters.status[0] || ""} 
                onChange={e => onFilterChange('status', e.target.value ? [e.target.value] : [])}
                className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 pr-10 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all appearance-none cursor-pointer"
              >
                <option value="">All Statuses</option>
                {allStatuses.map(s => <option key={s} value={s}>{s} ({countOf('status', s)})</option>)}
              </select>
              <div className="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                <ChevronDown size={16} />
              </div>
            </div>
          </div>

          {/* Category Dropdown */}
          <div className="w-56">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Category</p>
            <div className="relative">
              <select 
                value={filters.categories[0] || ""} 
                onChange={e => onFilterChange('categories', e.target.value ? [e.target.value] : [])}
                className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 pr-10 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all appearance-none cursor-pointer"
              >
                <option value="">All Categories</option>
                {allCategories.map(cat => <option key={cat} value={cat}>{cat} ({countOf('category', cat)})</option>)}
              </select>
              <div className="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                <ChevronDown size={16} />
              </div>
            </div>
          </div>

          {/* Manufacturer Dropdown */}
          <div className="w-56">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Manufacturer</p>
            <div className="relative">
              <select 
                value={filters.makes[0] || ""} 
                onChange={e => onFilterChange('makes', e.target.value ? [e.target.value] : [])}
                className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 pr-10 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all appearance-none cursor-pointer"
              >
                <option value="">All Brands</option>
                {sortedMakes.map(make => <option key={make} value={make}>{make} ({makeCounts[make]})</option>)}
              </select>
              <div className="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                <ChevronDown size={16} />
              </div>
            </div>
          </div>

          {/* Year Range */}
          <div className="space-y-3">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Year Range</p>
            <div className="flex items-center gap-2">
              <input type="number" placeholder="Min" value={yearInput.min} onChange={e => setYearInput(p => ({ ...p, min: e.target.value }))} className="w-20 bg-slate-50 border-2 border-slate-100 rounded-lg p-2 text-xs font-bold text-center focus:border-red-500 outline-none [appearance:textfield]"/>
              <span className="text-slate-300">-</span>
              <input type="number" placeholder="Max" value={yearInput.max} onChange={e => setYearInput(p => ({ ...p, max: e.target.value }))} className="w-20 bg-slate-50 border-2 border-slate-100 rounded-lg p-2 text-xs font-bold text-center focus:border-red-500 outline-none [appearance:textfield]"/>
              <button onClick={() => { onFilterChange('yearMin', yearInput.min); onFilterChange('yearMax', yearInput.max); }} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all">
                <Search size={14}/>
              </button>
            </div>
          </div>

          {/* Price Range */}
          <div className="space-y-3">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Price Range</p>
            <div className="flex items-center gap-2">
              <input type="number" placeholder="$Min" value={priceInput.min} onChange={e => setPriceInput(p => ({ ...p, min: e.target.value }))} className="w-24 bg-slate-50 border-2 border-slate-100 rounded-lg p-2 text-xs font-bold text-center focus:border-red-500 outline-none [appearance:textfield]"/>
              <span className="text-slate-300">-</span>
              <input type="number" placeholder="$Max" value={priceInput.max} onChange={e => setPriceInput(p => ({ ...p, max: e.target.value }))} className="w-24 bg-slate-50 border-2 border-slate-100 rounded-lg p-2 text-xs font-bold text-center focus:border-red-500 outline-none [appearance:textfield]"/>
              <button onClick={() => { onFilterChange('priceMin', priceInput.min); onFilterChange('priceMax', priceInput.max); }} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all">
                <Search size={14}/>
              </button>
            </div>
          </div>

          {/* Stock Number */}
          <div className="space-y-3">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Stock #</p>
            <div className="flex items-center gap-2">
              <input type="text" placeholder="e.g. VE-1042" value={filters.stockSearch || ''}
                onChange={e => onFilterChange('stockSearch', e.target.value)}
                className="w-32 bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all"/>
            </div>
          </div>

          {/* VIN / Serial */}
          <div className="space-y-3">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">VIN / Serial</p>
            <div className="flex items-center gap-2">
              <input type="text" placeholder="Full or partial VIN" value={filters.vinSearch || ''}
                onChange={e => onFilterChange('vinSearch', e.target.value)}
                className="w-40 bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all"/>
            </div>
          </div>

          <button onClick={onClearAll} className="h-[48px] px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-red-600 transition-colors">
            Clear All
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="w-64 shrink-0 bg-white rounded-2xl border border-slate-200 shadow-sm self-start sticky top-4 max-h-[calc(100vh-6rem)] overflow-y-auto no-scrollbar">
      <div className="p-5">
        {/* Quick Search */}
        <div className="mb-2">
          <p className="font-bold text-sm text-gray-900 mb-2">Quick Search</p>
          <div className="flex flex-col gap-2">
            <input type="text" value={kwInput} onChange={e => setKwInput(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && onKeywordSearch(kwInput)}
              placeholder="Enter Keyword(s)"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 transition-colors"/>
            <button onClick={() => onKeywordSearch(kwInput)}
              className="w-full bg-slate-700 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-slate-800 transition-colors">
              Search
            </button>
          </div>
        </div>
      </div>

      <div className="px-5 pb-5">
        {/* Stock Number */}
        <div className="mb-4">
          <p className="font-bold text-sm text-gray-900 mb-2">Stock #</p>
          <input type="text" placeholder="e.g. VE-1042" value={filters.stockSearch || ''}
            onChange={e => onFilterChange('stockSearch', e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 transition-colors"/>
        </div>

        {/* VIN / Serial */}
        <div className="mb-4">
          <p className="font-bold text-sm text-gray-900 mb-2">VIN / Serial</p>
          <input type="text" placeholder="Full or partial VIN" value={filters.vinSearch || ''}
            onChange={e => onFilterChange('vinSearch', e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 transition-colors"/>
        </div>

        {/* Stock Status */}
        <SectionHeader label="Stock Status" sKey="listingType" applied={filters.status.length > 0} />
        {sections.listingType && (
          <div className="py-2">
            {allStatuses.map(s => (
              <CheckRow key={s} label={s} count={countOf('status', s)}
                checked={filters.status.includes(s)} onChange={() => toggleArr('status', s)} />
            ))}
          </div>
        )}

        {/* Category */}
        <SectionHeader label="Category" sKey="category" applied={filters.categories.length > 0} />
        {sections.category && (
          <div className="py-2">
            {allCategories.map(cat => (
              <CheckRow key={cat} label={cat} count={countOf('category', cat)}
                checked={filters.categories.includes(cat)} onChange={() => toggleArr('categories', cat)} />
            ))}
          </div>
        )}

        {/* Manufacturer */}
        <SectionHeader label="Manufacturer" sKey="manufacturer" applied={filters.makes.length > 0} />
        {sections.manufacturer && (
          <div className="py-2">
            {sortedMakes.length > 5 && <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 px-1">Popular</p>}
            {displayMakes.map(make => (
              <CheckRow key={make} label={make} count={makeCounts[make]}
                checked={filters.makes.includes(make)} onChange={() => toggleArr('makes', make)} />
            ))}
            {sortedMakes.length > 5 && <ShowAllBtn show={showAllMakes} onToggle={() => setShowAllMakes(!showAllMakes)} />}
          </div>
        )}

        {/* Model */}
        <SectionHeader label="Model" sKey="model" applied={filters.models.length > 0} />
        {sections.model && (
          <div className="py-2">
            {displayMakeGroups.map(make => {
              const models = Object.entries(modelsByMake[make] || {}).sort(([a], [b]) => a.localeCompare(b));
              if (!models.length) return null;
              const total = models.reduce((s, [, c]) => s + c, 0);
              return (
                <div key={make}>
                  <div className="flex items-center justify-between py-1 px-1">
                    <span className="text-sm font-bold text-gray-800">{make}'s</span>
                    <span className="text-xs text-gray-400">({total})</span>
                  </div>
                  <div className="pl-4">
                    {models.map(([model, count]) => (
                      <CheckRow key={model} label={model} count={count}
                        checked={filters.models.includes(model)} onChange={() => toggleArr('models', model)} />
                    ))}
                  </div>
                </div>
              );
            })}
            {makesForModels.length > 3 && <ShowAllBtn show={showAllModels} onToggle={() => setShowAllModels(!showAllModels)} />}
          </div>
        )}

        {/* Year */}
        <SectionHeader label="Year" sKey="year" applied={!!(filters.yearMin || filters.yearMax)} />
        {sections.year && (
          <div className="py-2">
            <div className="flex items-center gap-1.5">
              <input type="number" placeholder="Min" value={yearInput.min}
                onChange={e => setYearInput(p => ({ ...p, min: e.target.value }))}
                className="w-full border border-gray-300 rounded px-2 py-2 text-sm focus:outline-none focus:border-red-500 text-center [appearance:textfield]"/>
              <span className="text-gray-400 shrink-0">-</span>
              <input type="number" placeholder="Max" value={yearInput.max}
                onChange={e => setYearInput(p => ({ ...p, max: e.target.value }))}
                className="w-full border border-gray-300 rounded px-2 py-2 text-sm focus:outline-none focus:border-red-500 text-center [appearance:textfield]"/>
              <button onClick={() => { onFilterChange('yearMin', yearInput.min); onFilterChange('yearMax', yearInput.max); }}
                className="bg-slate-700 text-white px-3 py-2 rounded text-xs font-bold hover:bg-slate-800 transition-colors shrink-0">
                Search
              </button>
            </div>
          </div>
        )}

        {/* Price */}
        <SectionHeader label="Price" sKey="price" applied={!!(filters.priceMin || filters.priceMax)} />
        {sections.price && (
          <div className="py-2">
            <div className="flex items-center gap-1.5">
              <input type="number" placeholder="$Min" value={priceInput.min}
                onChange={e => setPriceInput(p => ({ ...p, min: e.target.value }))}
                className="w-full border border-gray-300 rounded px-2 py-2 text-sm focus:outline-none focus:border-red-500 text-center [appearance:textfield]"/>
              <span className="text-gray-400 shrink-0">-</span>
              <input type="number" placeholder="$Max" value={priceInput.max}
                onChange={e => setPriceInput(p => ({ ...p, max: e.target.value }))}
                className="w-full border border-gray-300 rounded px-2 py-2 text-sm focus:outline-none focus:border-red-500 text-center [appearance:textfield]"/>
              <button onClick={() => { onFilterChange('priceMin', priceInput.min); onFilterChange('priceMax', priceInput.max); }}
                className="bg-slate-700 text-white px-3 py-2 rounded text-xs font-bold hover:bg-slate-800 transition-colors shrink-0">
                Search
              </button>
            </div>
          </div>
        )}

        {/* Condition */}
        <SectionHeader label="Condition" sKey="condition" applied={filters.conditions.length > 0} />
        {sections.condition && (
          <div className="py-2">
            {allConditions.map(c => (
              <CheckRow key={c} label={c} count={countOf('condition', c)}
                checked={filters.conditions.includes(c)} onChange={() => toggleArr('conditions', c)} />
            ))}
          </div>
        )}


      </div>
    </div>
  );
};
