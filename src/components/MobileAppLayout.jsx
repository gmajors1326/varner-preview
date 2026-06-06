import React, { useState, useEffect, useRef } from 'react';
import {
  Smartphone, Zap, Loader2, AlertCircle, LogOut, Box, Clock, CheckCircle2,
  TrendingUp, Plus, List, Sparkles, Search, X, Image as ImageIcon, ChevronLeft,
  Trash2, ChevronDown, Save, Camera, LayoutDashboard, Settings
} from 'lucide-react';
import { apiFetch } from '../utils/api';
import { METER_TYPE_OPTIONS, CATEGORY_TREE } from '../constants/inventoryConstants';
import { ManageListModal } from './Common/Modals';

export const MobileAppLayout = ({
  toast,
  mobileToken,
  setMobileToken,
  mobileActiveTab,
  setMobileActiveTab,
  inventoryList,
  loadInventory,
  isLoading,
  unitData,
  setUnitData,
  defaultEmptyUnit,
  brands,
  categories,
  subcategories,
  subSubcategories,
  setCategories,
  setSubcategories,
  setSubSubcategories,
  handleSave,
  isSaving,
  fieldErrors,
  setFieldErrors,
  handleInputChange,
  handleAddImages,
  handleRemoveImage,
  handleReorderImages,
  handleAddImplement,
  handleUpdateImplement,
  handleRemoveImplement,
  handleImplementImageUpload,
  handleToggleBoolean,
  handleFullEdit,
  handleDeleteUnit,
  showToast
}) => {
  const [isStandalone, setIsStandalone] = useState(true);

  useEffect(() => {
    const isM = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    setIsStandalone(!!isM);
  }, []);

  const renderInstallBanner = () => {
    if (isStandalone) return null;
    const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    return (
      <div className="bg-gradient-to-r from-blue-900 to-indigo-950 border-b border-blue-800 px-4 py-3 flex items-start gap-3 shrink-0 shadow-lg text-white">
        <div className="bg-blue-600/30 p-2 rounded-xl text-blue-400 shrink-0">
          <Smartphone size={18} />
        </div>
        <div className="flex-1">
          <h4 className="font-black text-[11px] uppercase tracking-widest text-blue-300">Install as App</h4>
          {isiOS ? (
            <p className="text-[10px] font-bold text-slate-200 mt-0.5 leading-normal">
              Tap the share button (square with arrow up) at the bottom, then scroll and select <span className="text-blue-400 font-black">"Add to Home Screen"</span> to download this companion app.
            </p>
          ) : (
            <p className="text-[10px] font-bold text-slate-200 mt-0.5 leading-normal">
              Tap the browser menu <span className="font-black">(three dots)</span> and select <span className="text-blue-400 font-black">"Install App"</span> or <span className="text-blue-400 font-black">"Add to Home Screen"</span> to download this companion app.
            </p>
          )}
        </div>
      </div>
    );
  };

  const [tokenInput, setTokenInput] = useState('');
  const [authError, setAuthError] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [mobileSearch, setMobileSearch] = useState('');
  const [mobileCategoryFilter, setMobileCategoryFilter] = useState('');
  const [mobileConditionFilter, setMobileConditionFilter] = useState('');

  // Modal visibility states for list management on mobile
  const [showCategoriesModal, setShowCategoriesModal] = useState(false);
  const [showSubcategoriesModal, setShowSubcategoriesModal] = useState(false);
  const [showSubSubcategoriesModal, setShowSubSubcategoriesModal] = useState(false);
  const [newCategoryInput, setNewCategoryInput] = useState('');
  const [newSubcategoryInput, setNewSubcategoryInput] = useState('');
  const [newSubSubcategoryInput, setNewSubSubcategoryInput] = useState('');

  const handleListAdd = async (endpoint, current, newVal, setter, inputSetter) => {
    const name = newVal.trim();
    if (!name || current.includes(name)) return;
    const updated = [...current, name].sort((a, b) => a.localeCompare(b));
    await apiFetch(`/${endpoint}`, { method: 'POST', body: JSON.stringify({ [endpoint]: updated }) });
    setter(updated);
    inputSetter('');
  };

  const handleListDelete = async (endpoint, current, name, setter, clearField = null) => {
    const updated = current.filter(v => v !== name);
    await apiFetch(`/${endpoint}`, { method: 'POST', body: JSON.stringify({ [endpoint]: updated }) });
    setter(updated);
    if (clearField && unitData[clearField] === name) handleInputChange(clearField, '');
  };

  const handleAddCategory   = () => handleListAdd('categories', categories, newCategoryInput, setCategories, setNewCategoryInput);
  const handleDeleteCategory = (n) => handleListDelete('categories', categories, n, setCategories, 'category');
  const handleAddSubcategory   = () => handleListAdd('subcategories', subcategories, newSubcategoryInput, setSubcategories, setNewSubcategoryInput);
  const handleDeleteSubcategory = (n) => handleListDelete('subcategories', subcategories, n, setSubcategories, 'subcategory');
  const handleAddSubSubcategory   = () => handleListAdd('sub-subcategories', subSubcategories, newSubSubcategoryInput, setSubSubcategories, setNewSubSubcategoryInput);
  const handleDeleteSubSubcategory = (n) => handleListDelete('sub-subcategories', subSubcategories, n, setSubSubcategories, 'sub_subcategory');

  const handleCategorySelectChange = (val) => {
    setUnitData(prev => ({
      ...prev,
      category: val,
      subcategory: '',
      sub_subcategory: '',
    }));
  };

  const handleSubcategorySelectChange = (val) => {
    setUnitData(prev => ({
      ...prev,
      subcategory: val,
      sub_subcategory: '',
    }));
  };

  const handleSubSubcategorySelectChange = (val) => {
    setUnitData(prev => ({
      ...prev,
      sub_subcategory: val,
    }));
  };

  const cameraInputRef = useRef(null);
  const galleryInputRef = useRef(null);

  const verifyAndSaveToken = async (tokenToVerify) => {
    setIsVerifying(true);
    setAuthError('');
    try {
      localStorage.setItem('varner_mobile_token', tokenToVerify);
      await apiFetch('/inventory');
      setMobileToken(tokenToVerify);
      loadInventory();
      showToast('Welcome to Mobile Companion!');
    } catch (err) {
      localStorage.removeItem('varner_mobile_token');
      setAuthError('Authentication failed: Invalid or expired token.');
    } finally {
      setIsVerifying(false);
    }
  };

  useEffect(() => {
    if (mobileToken) {
      verifyAndSaveToken(mobileToken);
    }
  }, []);

  const handleLoginSubmit = (e) => {
    e.preventDefault();
    const cleaned = tokenInput.trim().toUpperCase();
    if (!cleaned) return;
    verifyAndSaveToken(cleaned);
  };

  const handleLogout = () => {
    if (window.confirm('Are you sure you want to sign out?')) {
      localStorage.removeItem('varner_mobile_token');
      setMobileToken('');
      setTokenInput('');
      setAuthError('');
    }
  };

  const handleEditClick = (wpId) => {
    handleFullEdit(wpId);
    setMobileActiveTab('edit');
  };

  const handleCreateNewClick = () => {
    setUnitData(defaultEmptyUnit);
    setFieldErrors({});
    setMobileActiveTab('edit');
  };

  const handleMobileSave = async () => {
    await handleSave();
    const required = ['title', 'year', 'make', 'model', 'category', 'stockStatus', 'condition'];
    if (!unitData.callForPrice) required.push('price');
    const hasErrors = required.some(key => !String(unitData[key] || '').trim());
    if (!hasErrors) {
      setMobileActiveTab('list');
    }
  };

  const filteredList = inventoryList.filter(item => {
    const query = mobileSearch.toLowerCase();
    const matchesSearch = !query || (
      item.stock?.toLowerCase().includes(query) ||
      item.make?.toLowerCase().includes(query) ||
      item.model?.toLowerCase().includes(query) ||
      item.year?.toLowerCase().includes(query) ||
      item.category?.toLowerCase().includes(query)
    );
    const matchesCategory = !mobileCategoryFilter || item.category === mobileCategoryFilter;
    const matchesCondition = !mobileConditionFilter || mobileConditionFilter === 'All' || item.condition === mobileConditionFilter;
    return matchesSearch && matchesCategory && matchesCondition;
  });

  if (!mobileToken) {
    return (
      <div className="flex flex-col min-h-screen bg-slate-900 text-white font-sans selection:bg-red-500/30">
        {toast && (
          <div className="fixed top-6 left-6 right-6 z-[9999] px-6 py-4 rounded-2xl font-black text-sm text-center shadow-2xl bg-green-600 text-white animate-in slide-in-from-top-4">
            {toast.msg}
          </div>
        )}
        {renderInstallBanner()}
        <div className="flex-1 flex flex-col justify-center items-center px-6 py-12">
          <div className="w-full max-w-sm space-y-8">
            <div className="text-center">
              <div className="inline-flex items-center gap-2 bg-red-500/10 text-red-500 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 border border-red-500/20">
                <Smartphone size={14}/> Mobile Companion
              </div>
              <h1 className="text-3xl font-black tracking-tighter uppercase">Varner OS</h1>
              <p className="text-slate-400 text-xs mt-2 font-medium">Field Inventory Management Console</p>
            </div>

            <form onSubmit={handleLoginSubmit} className="bg-slate-800/50 backdrop-blur-md rounded-3xl p-8 border border-slate-700/50 shadow-2xl space-y-6">
              <div>
                <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Secure Access Token</label>
                <input
                  type="text"
                  value={tokenInput}
                  onChange={e => setTokenInput(e.target.value)}
                  placeholder="E.G. A1B2C3D4E5F6"
                  maxLength={16}
                  className="w-full bg-slate-950 border border-slate-700 rounded-2xl py-4 px-4 text-center font-mono font-black text-lg tracking-widest uppercase focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all"
                />
              </div>

              {authError && (
                <div className="flex items-start gap-2 bg-red-950/40 border border-red-800/40 p-4 rounded-2xl text-red-400 text-xs">
                  <AlertCircle size={16} className="shrink-0 mt-0.5" />
                  <span className="font-bold">{authError}</span>
                </div>
              )}

              <button
                type="submit"
                disabled={isVerifying || !tokenInput.trim()}
                className="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 disabled:opacity-50 text-white font-black uppercase tracking-widest text-xs py-4 rounded-2xl shadow-xl active:scale-95 transition-all flex items-center justify-center gap-2"
              >
                {isVerifying ? <Loader2 className="animate-spin" size={16}/> : <Zap size={16}/>}
                Authenticate Mobile
              </button>
            </form>

            <div className="bg-slate-800/20 border border-slate-800/40 rounded-2xl p-5 text-center">
              <p className="text-[10px] font-bold text-slate-500 leading-relaxed uppercase tracking-wider">
                To connect, open Varner OS on your desktop, navigate to "Mobile Companion" in the sidebar, and scan the QR code.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const allCategories = Array.from(new Set([
    ...Object.keys(CATEGORY_TREE),
    ...categories,
    ...(unitData.category ? [unitData.category] : [])
  ])).sort();

  const subTree = CATEGORY_TREE[unitData.category] || {};
  const predefinedSubcategories = Object.keys(subTree);
  const allSubcategories = Array.from(new Set([
    ...predefinedSubcategories,
    ...subcategories,
    ...(unitData.subcategory ? [unitData.subcategory] : [])
  ])).sort();

  const predefinedSubSubcategories = (unitData.subcategory && subTree[unitData.subcategory]) || [];
  const allSubSubcategories = Array.from(new Set([
    ...predefinedSubSubcategories,
    ...subSubcategories,
    ...(unitData.sub_subcategory ? [unitData.sub_subcategory] : [])
  ])).sort();

  return (
    <div className="flex flex-col h-screen bg-slate-50 text-slate-900 font-sans overflow-hidden">
      {toast && (
        <div className={`fixed top-4 left-4 right-4 z-[9999] px-5 py-3 rounded-xl text-center font-black text-xs shadow-xl transition-all ${toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`}>
          {toast.msg}
        </div>
      )}
      {renderInstallBanner()}
      <header className="bg-slate-900 text-white px-4 py-3.5 flex items-center justify-between shadow-md shrink-0 safe-top">
        <div className="flex items-center gap-2">
          <div className="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
          <span className="font-black text-xs uppercase tracking-widest">Varner OS</span>
          <span className="bg-slate-800 text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded text-slate-400">Mobile</span>
        </div>
        <button onClick={handleLogout} className="p-2 text-slate-400 hover:text-white transition-colors">
          <LogOut size={18} />
        </button>
      </header>

      <div className="flex-1 overflow-y-auto no-scrollbar pb-24">
        {mobileActiveTab === 'dashboard' && (
          <div className="p-4 space-y-6 animate-in fade-in duration-300">
            <div>
              <h2 className="text-[10px] font-black uppercase tracking-widest text-slate-400">Operations Overview</h2>
              <h1 className="text-2xl font-black text-slate-950 uppercase tracking-tight">Yard Console</h1>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <Box size={18} className="text-blue-500" />
                <div className="mt-4">
                  <span className="text-2xl font-black text-slate-900">{inventoryList.filter(i => (i.status || '').toLowerCase() === 'in stock').length}</span>
                  <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Live Stock</p>
                </div>
              </div>
              <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <Clock size={18} className="text-red-500" />
                <div className="mt-4">
                  <span className="text-2xl font-black text-slate-900">{inventoryList.filter(i => ['sale pending','pending sale','pending'].includes((i.status || '').toLowerCase())).length}</span>
                  <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Pending</p>
                </div>
              </div>
              <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <CheckCircle2 size={18} className="text-green-500" />
                <div className="mt-4">
                  <span className="text-2xl font-black text-slate-900">{inventoryList.filter(i => (i.status || '').toLowerCase() === 'sold').length}</span>
                  <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Sold Units</p>
                </div>
              </div>
              <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <TrendingUp size={18} className="text-amber-500" />
                <div className="mt-4">
                  <span className="text-2xl font-black text-slate-900">{inventoryList.length}</span>
                  <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Total Ledger</p>
                </div>
              </div>
            </div>

            <div className="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
              <h3 className="text-xs font-black uppercase tracking-widest text-slate-900">Quick Actions</h3>
              <div className="grid grid-cols-1 gap-2">
                <button onClick={handleCreateNewClick} className="w-full bg-red-600 hover:bg-red-700 text-white font-black uppercase tracking-widest text-[10px] py-4 rounded-xl shadow-lg shadow-red-100 flex items-center justify-center gap-2 active:scale-95 transition-all">
                  <Plus size={14}/> Create New Listing
                </button>
                <button onClick={() => setMobileActiveTab('list')} className="w-full bg-slate-900 hover:bg-slate-800 text-white font-black uppercase tracking-widest text-[10px] py-4 rounded-xl flex items-center justify-center gap-2 active:scale-95 transition-all">
                  <List size={14}/> View Active Stock
                </button>
              </div>
            </div>

            <div className="bg-slate-100/80 rounded-2xl p-5 flex items-start gap-3">
              <Sparkles size={18} className="text-amber-500 shrink-0 mt-0.5" />
              <div>
                <h4 className="font-black text-[10px] uppercase tracking-wider text-slate-800">Camera Upload Tip</h4>
                <p className="text-[10px] font-medium text-slate-500 leading-relaxed mt-1">
                  When adding or editing units, tap "Take Photo" to launch your phone's camera instantly. Snap a photo and it will upload straight to the listing.
                </p>
              </div>
            </div>
          </div>
        )}

        {mobileActiveTab === 'list' && (
          <div className="p-4 space-y-4 animate-in fade-in duration-300">
            <div>
              <h2 className="text-[10px] font-black uppercase tracking-widest text-slate-400">Inventory Ledger</h2>
              <h1 className="text-2xl font-black text-slate-950 uppercase tracking-tight">Active Stock</h1>
            </div>

            <div className="relative">
              <Search className="absolute left-4 top-3.5 text-slate-400" size={18} />
              <input
                type="text"
                placeholder="Search Make, Model, Stock #..."
                value={mobileSearch}
                onChange={e => setMobileSearch(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded-xl py-3 pl-11 pr-4 text-xs focus:border-red-500 outline-none shadow-sm transition-all"
              />
              {mobileSearch && (
                <button onClick={() => setMobileSearch('')} className="absolute right-4 top-3.5 text-slate-400 hover:text-slate-900"><X size={16}/></button>
              )}
            </div>

            <div className="flex gap-2">
              {['All', 'New', 'Used'].map(cond => (
                <button
                  key={cond}
                  onClick={() => setMobileConditionFilter(cond === 'All' ? '' : cond)}
                  className={`flex-1 text-center py-2.5 rounded-lg border font-black uppercase text-[9px] tracking-widest transition-all ${
                    (cond === 'All' && !mobileConditionFilter) || mobileConditionFilter === cond
                      ? 'bg-slate-900 border-slate-900 text-white'
                      : 'bg-white border-slate-200 text-slate-500'
                  }`}
                >
                  {cond}
                </button>
              ))}
            </div>

            <div className="space-y-2">
              {isLoading ? (
                <div className="text-center py-12 text-xs font-black uppercase tracking-widest text-slate-300">Loading ledger…</div>
              ) : filteredList.length === 0 ? (
                <div className="text-center py-12 text-xs font-black uppercase tracking-widest text-slate-300">No matching units found</div>
              ) : (
                filteredList.map(item => (
                  <div
                    key={item.id}
                    onClick={() => handleEditClick(item.wpId)}
                    className="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3 active:bg-slate-50 transition-colors"
                  >
                    <div className="w-20 h-16 bg-slate-100 rounded-lg overflow-hidden border border-slate-100 shrink-0">
                      {item.image ? (
                        <img src={item.image} alt={item.model} className="w-full h-full object-cover" />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-slate-300"><ImageIcon size={16}/></div>
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-1.5 mb-1 flex-wrap">
                        <span className="font-mono text-[9px] font-bold text-slate-400">{item.stock || 'NO SKU'}</span>
                        <span className={`text-[7px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded ${
                          item.condition === 'New' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'
                        }`}>{item.condition}</span>
                        <span className={`text-[7px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded ${
                          (item.status || '').toLowerCase() === 'in stock' ? 'bg-green-50 text-green-600' :
                          ['sale pending','pending sale','pending'].includes((item.status || '').toLowerCase()) ? 'bg-yellow-50 text-yellow-600' :
                          (item.status || '').toLowerCase() === 'sold' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-500'
                        }`}>{item.status}</span>
                      </div>
                      <h4 className="font-black text-xs text-slate-900 truncate uppercase leading-tight">{item.year} {item.make} {item.model}</h4>
                      <p className="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-wide truncate">
                        {item.category}
                        {item.subcategory && ` \u203A ${item.subcategory}`}
                        {item.sub_subcategory && ` \u203A ${item.sub_subcategory}`}
                      </p>
                    </div>
                    <div className="text-right shrink-0">
                      <span className="font-black text-xs text-red-600">
                        {item.callForPrice ? 'Call' : item.price ? `$${parseFloat(item.price).toLocaleString()}` : '$0'}
                      </span>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        )}

        {mobileActiveTab === 'edit' && (
          <div className="p-4 space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4">
              <button onClick={() => setMobileActiveTab('list')} className="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-900 flex items-center gap-1">
                <ChevronLeft size={16}/> Back
              </button>
              <h2 className="text-sm font-black uppercase tracking-widest text-slate-900">
                {unitData.id ? 'Edit Unit' : 'New Unit'}
              </h2>
              {unitData.id ? (
                <button onClick={() => handleDeleteUnit(unitData.id, unitData.stockNumber)} className="text-red-500 p-2 hover:bg-red-50 rounded-lg">
                  <Trash2 size={16}/>
                </button>
              ) : <div className="w-6"></div>}
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Public Inventory Title *</label>
                <input
                  type="text"
                  value={unitData.title}
                  onChange={e => handleInputChange('title', e.target.value)}
                  className={`w-full bg-white border ${fieldErrors.title ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none`}
                  placeholder="e.g. 2026 Deutz Fahr 5080DF Keyline"
                />
                {fieldErrors.title && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.title}</p>}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Year *</label>
                  <input
                    type="text"
                    value={unitData.year}
                    onChange={e => handleInputChange('year', e.target.value)}
                    className={`w-full bg-white border ${fieldErrors.year ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3 px-4 text-xs font-mono font-bold focus:border-red-500 outline-none`}
                    placeholder="2026"
                  />
                  {fieldErrors.year && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.year}</p>}
                </div>
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Make / Brand *</label>
                  <div className="relative">
                    <select
                      value={unitData.make}
                      onChange={e => handleInputChange('make', e.target.value)}
                      className={`w-full bg-white border ${fieldErrors.make ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none`}
                    >
                      <option value="">Select Brand</option>
                      {brands.map(b => <option key={b} value={b}>{b}</option>)}
                      <option value="Other">Other</option>
                    </select>
                    <div className="absolute right-3.5 top-3.5 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                  {fieldErrors.make && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.make}</p>}
                </div>
              </div>

              <div>
                <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Model *</label>
                <input
                  type="text"
                  value={unitData.model}
                  onChange={e => handleInputChange('model', e.target.value)}
                  className={`w-full bg-white border ${fieldErrors.model ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none`}
                  placeholder="5080DF"
                />
                {fieldErrors.model && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.model}</p>}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Stock Number</label>
                  <input
                    type="text"
                    value={unitData.stockNumber}
                    onChange={e => handleInputChange('stockNumber', e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-mono font-bold focus:border-red-500 outline-none"
                    placeholder="12345"
                  />
                </div>
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">VIN / Serial</label>
                  <input
                    type="text"
                    value={unitData.vin}
                    onChange={e => handleInputChange('vin', e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-mono font-bold focus:border-red-500 outline-none"
                    placeholder="VIN"
                  />
                </div>
              </div>

              <div className="space-y-4 animate-in fade-in duration-300">
                <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Category *</label>
                
                {/* Category select */}
                <div className="space-y-1">
                  <div className="relative">
                    <select
                      value={unitData.category || ''}
                      onChange={e => handleCategorySelectChange(e.target.value)}
                      className={`w-full bg-white border ${fieldErrors.category ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3.5 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none`}
                    >
                      <option value="">Select Category</option>
                      {allCategories.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <div className="absolute right-3.5 top-4 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                  <button type="button" onClick={() => setShowCategoriesModal(true)}
                    className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-200 hover:border-red-200 text-red-600 rounded-xl py-4 px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest mt-1.5 min-h-[64px]">
                    <Settings size={14}/> Manage Categories
                  </button>
                </div>

                {/* Subcategory select */}
                <div className="space-y-1">
                  <label className="block text-[8px] font-black uppercase tracking-wider text-slate-400 pl-1">Subcategory</label>
                  <div className="relative">
                    <select
                      value={unitData.subcategory || ''}
                      onChange={e => handleSubcategorySelectChange(e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl py-3.5 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none"
                    >
                      <option value="">Select Subcategory</option>
                      {allSubcategories.map(sub => <option key={sub} value={sub}>{sub}</option>)}
                    </select>
                    <div className="absolute right-3.5 top-4 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                  <button type="button" onClick={() => setShowSubcategoriesModal(true)}
                    className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-200 hover:border-red-200 text-red-600 rounded-xl py-4 px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest mt-1.5 min-h-[64px]">
                    <Settings size={14}/> Manage Subcategories
                  </button>
                </div>

                {/* Sub-Subcategory select */}
                <div className="space-y-1">
                  <label className="block text-[8px] font-black uppercase tracking-wider text-slate-400 pl-1">Sub-Subcategory</label>
                  <div className="relative">
                    <select
                      value={unitData.sub_subcategory || ''}
                      onChange={e => handleSubSubcategorySelectChange(e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl py-3.5 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none"
                    >
                      <option value="">Select Sub-Subcategory</option>
                      {allSubSubcategories.map(ss => <option key={ss} value={ss}>{ss}</option>)}
                    </select>
                    <div className="absolute right-3.5 top-4 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                  <button type="button" onClick={() => setShowSubSubcategoriesModal(true)}
                    className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-200 hover:border-red-200 text-red-600 rounded-xl py-4 px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest mt-1.5 min-h-[64px]">
                    <Settings size={14}/> Manage Sub-Subcategories
                  </button>
                </div>

                {fieldErrors.category && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.category}</p>}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Meter Reading</label>
                  <input
                    type="text"
                    value={unitData.meter}
                    onChange={e => handleInputChange('meter', e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-mono font-bold focus:border-red-500 outline-none"
                    placeholder="e.g. 250"
                  />
                </div>
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Meter Type</label>
                  <div className="relative">
                    <select
                      value={unitData.meterType}
                      onChange={e => handleInputChange('meterType', e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none"
                    >
                      {METER_TYPE_OPTIONS.map(opt => <option key={opt} value={opt}>{opt}</option>)}
                    </select>
                    <div className="absolute right-3.5 top-3.5 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                </div>
              </div>

              <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                <div className="flex justify-between items-center">
                  <span className="text-[10px] font-black uppercase tracking-widest text-slate-900 font-black">Price Details</span>
                  <label className="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={unitData.callForPrice}
                      onChange={e => handleInputChange('callForPrice', e.target.checked)}
                      className="accent-red-600 w-4 h-4"
                    />
                    Call For Price
                  </label>
                </div>
                {!unitData.callForPrice && (
                  <div>
                    <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Retail Price (USD) *</label>
                    <input
                      type="number"
                      value={unitData.price}
                      onChange={e => handleInputChange('price', e.target.value)}
                      className={`w-full bg-white border ${fieldErrors.price ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3 px-4 text-xs font-mono font-bold focus:border-red-500 outline-none`}
                      placeholder="e.g. 45000"
                    />
                    {fieldErrors.price && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.price}</p>}
                  </div>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Condition *</label>
                  <div className="relative">
                    <select
                      value={unitData.condition}
                      onChange={e => handleInputChange('condition', e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none"
                    >
                      <option value="New">New</option>
                      <option value="Used">Used</option>
                    </select>
                    <div className="absolute right-3.5 top-3.5 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                </div>
                <div>
                  <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Stock Status *</label>
                  <div className="relative">
                    <select
                      value={unitData.stockStatus}
                      onChange={e => handleInputChange('stockStatus', e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none"
                    >
                      <option value="In Stock">In Stock</option>
                      <option value="Pending Sale">Pending Sale</option>
                      <option value="Sold">Sold</option>
                      <option value="Draft">Draft</option>
                    </select>
                    <div className="absolute right-3.5 top-3.5 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                  </div>
                </div>
              </div>

              <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                <div className="flex justify-between items-center">
                  <div>
                    <h4 className="text-[10px] font-black uppercase tracking-widest text-slate-900">Show on Website</h4>
                    <p className="text-[8px] font-bold text-slate-400 mt-0.5">Toggle visibility on all public pages</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleInputChange('showOnWebsite', !unitData.showOnWebsite)}
                    className={`w-12 h-6.5 rounded-full p-1 transition-colors ${unitData.showOnWebsite ? 'bg-red-600' : 'bg-slate-200'}`}
                  >
                    <div className={`w-4.5 h-4.5 rounded-full bg-white transition-transform ${unitData.showOnWebsite ? 'translate-x-5.5' : 'translate-x-0'}`}></div>
                  </button>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-50">
                  <div>
                    <h4 className="text-[10px] font-black uppercase tracking-widest text-slate-900 font-black">Featured Unit</h4>
                    <p className="text-[8px] font-bold text-slate-400 mt-0.5">Feature this unit on the homepage</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleInputChange('featured', !unitData.featured)}
                    className={`w-12 h-6.5 rounded-full p-1 transition-colors ${unitData.featured ? 'bg-red-600' : 'bg-slate-200'}`}
                  >
                    <div className={`w-4.5 h-4.5 rounded-full bg-white transition-transform ${unitData.featured ? 'translate-x-5.5' : 'translate-x-0'}`}></div>
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Description (Plain Text / HTML)</label>
                <textarea
                  value={unitData.description}
                  onChange={e => handleInputChange('description', e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs focus:border-red-500 outline-none"
                  rows={4}
                  placeholder="Details about the unit..."
                />
              </div>

              <div className="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-xs font-black uppercase tracking-widest text-slate-900 font-black">Image Gallery</h3>
                  <span className="text-[9px] font-black text-slate-400 uppercase tracking-widest">{unitData.images?.length || 0} Images</span>
                </div>

                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  ref={cameraInputRef}
                  className="hidden"
                  onChange={e => {
                    const files = Array.from(e.target.files || []);
                    if (files.length) handleAddImages(files);
                  }}
                />
                <input
                  type="file"
                  accept="image/*"
                  multiple
                  ref={galleryInputRef}
                  className="hidden"
                  onChange={e => {
                    const files = Array.from(e.target.files || []);
                    if (files.length) handleAddImages(files);
                  }}
                />

                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => cameraInputRef.current?.click()}
                    className="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-3.5 rounded-xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95 transition-all shadow"
                  >
                    <Camera size={14}/> Take Photo
                  </button>
                  <button
                    type="button"
                    onClick={() => galleryInputRef.current?.click()}
                    className="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-3.5 rounded-xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95 transition-all"
                  >
                    <ImageIcon size={14}/> Add Gallery
                  </button>
                </div>

                {unitData.images?.length > 0 && (
                  <div className="grid grid-cols-4 gap-2 pt-2">
                    {unitData.images.map((img, i) => (
                      <div key={i} className="relative aspect-square bg-slate-100 rounded-lg overflow-hidden border border-slate-100 group">
                        <img src={img} alt="Thumb" className="w-full h-full object-cover" />
                        <button
                          type="button"
                          onClick={() => handleRemoveImage(i)}
                          className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition-opacity"
                        >
                          <X size={14} />
                        </button>
                        <button
                          type="button"
                          onClick={() => handleRemoveImage(i)}
                          className="absolute top-1 right-1 bg-slate-900/80 text-white rounded-full p-1 lg:hidden"
                        >
                          <X size={10} />
                        </button>
                        {i > 0 && (
                          <button
                            type="button"
                            onClick={(e) => { e.stopPropagation(); handleReorderImages(i, i - 1); }}
                            className="absolute bottom-1 left-1 bg-slate-900/80 text-white rounded p-0.5 text-[8px]"
                          >
                            ◀
                          </button>
                        )}
                        {i < unitData.images.length - 1 && (
                          <button
                            type="button"
                            onClick={(e) => { e.stopPropagation(); handleReorderImages(i, i + 1); }}
                            className="absolute bottom-1 right-1 bg-slate-900/80 text-white rounded p-0.5 text-[8px]"
                          >
                            ▶
                          </button>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-xs font-black uppercase tracking-widest text-slate-900 font-black">Implements / Attachments</h3>
                  <button
                    type="button"
                    onClick={handleAddImplement}
                    className="text-[9px] font-black text-red-600 uppercase tracking-widest flex items-center gap-1"
                  >
                    <Plus size={12}/> Add Item
                  </button>
                </div>

                <div className="space-y-4">
                  {(unitData.attachments ?? []).map((imp, idx) => (
                    <div key={idx} className="bg-slate-50 p-4 rounded-2xl border border-slate-200/50 space-y-3 relative animate-in fade-in">
                      <button
                        type="button"
                        onClick={() => handleRemoveImplement(idx)}
                        className="absolute top-3 right-3 text-slate-400 hover:text-red-500"
                      >
                        <X size={16}/>
                      </button>

                      <div>
                        <label className="block text-[8px] font-black uppercase tracking-widest text-slate-400 mb-1">Implement Title</label>
                        <input
                          type="text"
                          value={imp.title}
                          onChange={e => handleUpdateImplement(idx, 'title', e.target.value)}
                          className="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs font-bold"
                          placeholder="e.g. Loader Bucket"
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-2">
                        <div>
                          <label className="block text-[8px] font-black uppercase tracking-widest text-slate-400 mb-1">Price (USD)</label>
                          <input
                            type="text"
                            value={imp.price}
                            onChange={e => handleUpdateImplement(idx, 'price', e.target.value)}
                            className="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs font-bold"
                            placeholder="e.g. 1200"
                          />
                        </div>
                        <div>
                          <label className="block text-[8px] font-black uppercase tracking-widest text-slate-400 mb-1">Attachment Image</label>
                          <div className="flex gap-2">
                            <input
                              type="file"
                              accept="image/*"
                              onChange={e => {
                                const file = e.target.files?.[0];
                                if (file) handleImplementImageUpload(idx, file);
                              }}
                              className="hidden"
                              id={`imp-img-${idx}`}
                            />
                            <label
                              htmlFor={`imp-img-${idx}`}
                              className="flex-1 bg-white border border-slate-200 rounded-lg py-2 px-3 text-center text-slate-500 text-[10px] font-black uppercase tracking-widest cursor-pointer hover:border-red-500 truncate"
                            >
                              {imp.image ? 'CHANGE IMAGE' : 'CHOOSE PHOTO'}
                            </label>
                            {imp.image && (
                              <div className="w-8 h-8 rounded overflow-hidden border border-slate-200 shrink-0">
                                <img src={imp.image} alt="Implement" className="w-full h-full object-cover" />
                              </div>
                            )}
                          </div>
                        </div>
                      </div>

                      <div>
                        <label className="block text-[8px] font-black uppercase tracking-widest text-slate-400 mb-1">Description</label>
                        <textarea
                          value={imp.description}
                          onChange={e => handleUpdateImplement(idx, 'description', e.target.value)}
                          className="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs"
                          rows={2}
                          placeholder="Brief description..."
                        />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <button
              onClick={handleMobileSave}
              disabled={isSaving}
              className="w-full bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white py-4.5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl flex items-center justify-center gap-2 active:scale-95 transition-all mt-4 border-b-2 border-red-800"
            >
              {isSaving ? <Loader2 className="animate-spin" size={16}/> : <Save size={16}/>}
              {isSaving ? 'SAVING CHANGES…' : (unitData.id ? 'SAVE UNIT CHANGES' : 'PUBLISH NEW UNIT')}
            </button>
          </div>
        )}
      </div>

      <nav className="fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-800 flex items-center justify-around py-3 px-2 shadow-2xl z-50 shrink-0 safe-bottom animate-in slide-in-from-bottom-8">
        <button
          onClick={() => setMobileActiveTab('dashboard')}
          className={`flex flex-col items-center gap-1 p-2 min-w-16 transition-colors ${
            mobileActiveTab === 'dashboard' ? 'text-red-500' : 'text-slate-400 hover:text-white'
          }`}
        >
          <LayoutDashboard size={20} />
          <span className="text-[9px] font-black uppercase tracking-wider">Overview</span>
        </button>
        
        <button
          onClick={() => { setMobileActiveTab('list'); loadInventory(); }}
          className={`flex flex-col items-center gap-1 p-2 min-w-16 transition-colors ${
            mobileActiveTab === 'list' ? 'text-red-500' : 'text-slate-400 hover:text-white'
          }`}
        >
          <List size={20} />
          <span className="text-[9px] font-black uppercase tracking-wider">Ledger</span>
        </button>

        <button
          onClick={handleCreateNewClick}
          className={`flex flex-col items-center gap-1 p-2 min-w-16 transition-colors ${
            mobileActiveTab === 'edit' && !unitData.id ? 'text-red-500' : 'text-slate-400 hover:text-white'
          }`}
        >
          <Plus size={20} />
          <span className="text-[9px] font-black uppercase tracking-wider">Add New</span>
        </button>
      </nav>

      {/* Categories Management Modal */}
      {showCategoriesModal && (
        <ManageListModal title="Manage Categories" items={categories} inputValue={newCategoryInput}
          onInputChange={setNewCategoryInput} onAdd={handleAddCategory} onDelete={handleDeleteCategory}
          onClose={() => setShowCategoriesModal(false)} placeholder="New category name..." />
      )}

      {/* Subcategories Management Modal */}
      {showSubcategoriesModal && (
        <ManageListModal title="Manage Custom Subcategories" items={subcategories} inputValue={newSubcategoryInput}
          onInputChange={setNewSubcategoryInput} onAdd={handleAddSubcategory} onDelete={handleDeleteSubcategory}
          onClose={() => setShowSubcategoriesModal(false)} placeholder="New subcategory name..." />
      )}

      {/* Sub-Subcategories Management Modal */}
      {showSubSubcategoriesModal && (
        <ManageListModal title="Manage Custom Sub-Subcategories" items={subSubcategories} inputValue={newSubSubcategoryInput}
          onInputChange={setNewSubSubcategoryInput} onAdd={handleAddSubSubcategory} onDelete={handleDeleteSubSubcategory}
          onClose={() => setShowSubSubcategoriesModal(false)} placeholder="New sub-subcategory name..." />
      )}
    </div>
  );
};
