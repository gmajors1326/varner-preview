import React, { useState, useEffect, useCallback } from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import {
  DndContext, 
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
  rectSortingStrategy,
} from '@dnd-kit/sortable';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
  LayoutDashboard, Box, Truck, Facebook, Save, Copy, CheckCircle2, AlertCircle,
  ChevronLeft, ChevronRight, Plus, Settings, Sliders, Zap, Menu, Image as ImageIcon, Smartphone, Eye,
  ArrowUpRight, BarChart3, Users, Wrench, Clock, ShieldCheck, Camera, Loader2,
  ScanText, List, Search, Edit2, X, TrendingUp, Activity, DollarSign, History,
  Sparkles, Info, Trash2, RotateCcw, Star, Upload, Download, ChevronUp, ChevronDown, Mail, LogOut, Briefcase
} from 'lucide-react';

// ─── API helpers ─────────────────────────────────────────────────────────────

const API = window.varnerData?.rest_url
  ? window.varnerData.rest_url.replace(/\/$/, '') + '/varner/v1'
  : '/wp-json/varner/v1';

const NONCE = window.varnerData?.nonce ?? '';

const getMobileToken = () => localStorage.getItem('varner_mobile_token') || '';

async function apiFetch(path, options = {}) {
  const token = getMobileToken();
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { 'X-Varner-Mobile-Token': token } : { 'X-WP-Nonce': NONCE }),
    ...(options.headers ?? {}),
  };

  const res = await fetch(`${API}${path}`, {
    ...options,
    headers,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message ?? `Request failed: ${res.status}`);
  }
  return res.json();
}

async function uploadFile(file) {
  const token = getMobileToken();
  const headers = {
    ...(token ? { 'X-Varner-Mobile-Token': token } : { 'X-WP-Nonce': NONCE }),
  };

  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API}/media`, {
    method: 'POST',
    headers,
    body: form,
  });
  if (!res.ok) throw new Error('Upload failed');
  return res.json(); // { id, url }
}

// ─── Default empty unit ───────────────────────────────────────────────────────


const defaultEmptyUnit = {
  id: null,
  title: '', year: '', make: '', model: '', stockNumber: '', condition: 'New',
  price: '', callForPrice: false, vin: '', stockStatus: 'Draft', category: 'Compact Tractors',
  color: '', length: '', meter: '', meterType: 'Hours', intakeDate: '', description: '',
  featured: false, showOnWebsite: true, images: [], image_ids: [], attachments: [],
  sellerInfo: '<p>Call or stop by to see it in person</p><p>Varner Equipment</p><p>1375 Hwy 50</p><p>Delta, CO 81416</p><p>(970) 874-0612</p>',
  hasAttachments: false, attachmentDetails: '', engineHorsepower: '', drive: '',
};

// Map API unit → local unit shape used by the editor
function apiToLocal(u) {
  return {
    id: u.id,
    title: u.title,
    year: u.year,
    make: u.make,
    model: u.model,
    stockNumber: u.stock_number,
    condition: u.condition,
    price: u.price,
    callForPrice: u.call_for_price ?? false,
    vin: u.vin,
    stockStatus: u.stock_status,
    category: u.category,
    color: u.color,
    length: u.length,
    meter: u.meter,
    meterType: u.meter_type,
    intakeDate: u.intake_date,
    description: u.description,
    sellerInfo: u.seller_info,
    featured: u.featured ?? false,
    showOnWebsite: u.show_on_website ?? true,
    hasAttachments: u.has_attachments ?? false,
    attachmentDetails: u.attachment_details ?? '',
    engineHorsepower: u.engine_horsepower ?? '',
    drive: u.drive ?? '',
    images: u.images ?? [],
    image_ids: u.image_ids ?? [],
    attachments: (u.implements ?? []).map(imp => ({
      image: imp.image,
      image_id: imp.image_id,
      title: imp.title,
      price: imp.price,
      description: imp.description,
    })),
  };
}

// Friendly label for success messaging
function getCategoryLabel(cat) {
  if (!cat) return 'unit';
  const c = String(cat).toLowerCase();
  if (c.includes('tractor')) return 'tractor';
  if (c.includes('trailer')) return 'trailer';
  if (c.includes('implement') || c.includes('attachment')) return 'attachment';
  return 'unit';
}

// Map inventory list item shape for the table
function getDaysInStock(item) {
  const dateStr = item.intakeDate || item.createdAt;
  if (!dateStr) return '-';
  try {
    const datePart = dateStr.split(' ')[0];
    const parts = datePart.split('-');
    if (parts.length < 3) return '-';
    
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    
    const itemDate = new Date(year, month, day);
    const today = new Date();
    itemDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);
    
    const diffTime = today.getTime() - itemDate.getTime();
    const diffDays = Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
    return `${diffDays} Day${diffDays !== 1 ? 's' : ''}`;
  } catch (e) {
    return '-';
  }
}

function apiToListItem(u) {
  return {
    id: String(u.id),
    wpId: u.id,
    stock: u.stock_number,
    year: u.year,
    make: u.make,
    model: u.model,
    category: u.category,
    condition: u.condition,
    price: u.price,
    callForPrice: u.call_for_price ?? false,
    status: u.stock_status,
    image: u.images?.[0] ?? '',
    images: u.images ?? [],
    showOnWebsite: u.show_on_website ?? true,
    featured: u.featured ?? false,
    attachments: (u.implements ?? []).map(imp => ({
      image: imp.image,
      image_id: imp.image_id ?? 0,
      title: imp.title,
      price: imp.price,
      description: imp.description,
    })),
    hasAttachments: u.has_attachments ?? false,
    attachmentDetails: u.attachment_details ?? '',
    engineHorsepower: u.engine_horsepower ?? '',
    drive: u.drive ?? '',
    deleted_at: u.deleted_at ?? '',
    intakeDate: u.intake_date,
    createdAt: u.created_at,
  };
}

// ─── MobileAppLayout Component ────────────────────────────────────────────────

const MobileAppLayout = ({
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

  const cameraInputRef = React.useRef(null);
  const galleryInputRef = React.useRef(null);

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
                      <p className="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-wide truncate">{item.category}</p>
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

              <div>
                <label className="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Category *</label>
                <div className="relative">
                  <select
                    value={unitData.category}
                    onChange={e => handleInputChange('category', e.target.value)}
                    className={`w-full bg-white border ${fieldErrors.category ? 'border-red-500' : 'border-slate-200'} rounded-xl py-3 px-4 text-xs font-bold focus:border-red-500 outline-none appearance-none`}
                  >
                    <option value="">Select Category</option>
                    {categories.map(c => <option key={c} value={c}>{c}</option>)}
                    <option value="Other">Other</option>
                  </select>
                  <div className="absolute right-3.5 top-3.5 pointer-events-none text-slate-400"><ChevronDown size={14}/></div>
                </div>
                {fieldErrors.category && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.category}</p>}
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
    </div>
  );
};

// ─── App ─────────────────────────────────────────────────────────────────────

const App = () => {
  const isMobileApp = window.varnerData?.is_mobile_app || window.location.pathname.includes('/mobile-app/');
  const [mobileToken, setMobileToken] = useState(
    new URLSearchParams(window.location.search).get('token') || localStorage.getItem('varner_mobile_token') || ''
  );
  const [mobileActiveTab, setMobileActiveTab] = useState('dashboard');

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tokenFromUrl = urlParams.get('token');
    if (tokenFromUrl) {
      localStorage.setItem('varner_mobile_token', tokenFromUrl);
      setMobileToken(tokenFromUrl);
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }, []);

  const [syncEnabled, setSyncEnabled]         = useState(true);
  const [isSaving, setIsSaving]               = useState(false);
  const [isLoading, setIsLoading]             = useState(true);
  const [activeTab, setActiveTab]             = useState('dashboard');
  const [showFBPreview, setShowFBPreview]     = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [toast, setToast]                     = useState(null);

  const [inventoryList, setInventoryList]     = useState([]);
  const [deletedHistory, setDeletedHistory]   = useState([]);
  const [unitData, setUnitData]               = useState(defaultEmptyUnit);
  const [searchQuery, setSearchQuery]         = useState('');
  const [brands, setBrands]                   = useState([]);
  const [categories, setCategories]           = useState([]);
  const [showBrandsModal, setShowBrandsModal] = useState(false);
  const [showCategoriesModal, setShowCategoriesModal] = useState(false);
  const [newBrandInput, setNewBrandInput]     = useState('');
  const [newCategoryInput, setNewCategoryInput] = useState('');
  const [activeFilters, setActiveFilters]     = useState({ status: [], categories: [], makes: [], models: [], yearMin: '', yearMax: '', priceMin: '', priceMax: '', conditions: [], stockSearch: '', vinSearch: '' });
  const [showFilterPanel, setShowFilterPanel] = useState(false);
  const [isPublicMode, setIsPublicMode]       = useState(false);
  const [fieldErrors, setFieldErrors]         = useState({});
  const [currentUser, setCurrentUser]         = useState(null);
  const [sessionList, setSessionList]         = useState([]);
  const [isSessionsLoading, setIsSessionsLoading] = useState(false);
  const [activityList, setActivityList]       = useState([]);
  const [isActivityLoading, setIsActivityLoading] = useState(false);

  useEffect(() => {
    setIsPublicMode(!!document.querySelector('.varner-public-showroom'));
  }, []);

  const showToast = (msg, type = 'success') => {
    setToast({ msg, type });
    setTimeout(() => setToast(null), 3500);
  };

  // Load inventory on mount
  const loadInventory = useCallback(async () => {
    setIsLoading(true);
    try {
      const [active, deleted] = await Promise.all([
        apiFetch('/inventory'),
        apiFetch('/inventory/deleted'),
      ]);
      setInventoryList(active.map(apiToListItem));
      setDeletedHistory(deleted.map(item => ({
        ...apiToListItem(item),
        deletedAt: item.deleted_at,
      })));
    } catch (e) {
      showToast('Failed to load inventory: ' + e.message, 'error');
    } finally {
      setIsLoading(false);
    }
  }, []);

  const loadSessions = useCallback(async (activeOnly = true) => {
    setIsSessionsLoading(true);
    try {
      const url = `/sessions?per_page=50${activeOnly ? '&active_only=true' : ''}`;
      const data = await apiFetch(url);
      setSessionList(data.items || []);
    } catch (e) {
      showToast('Failed to load session audits: ' + e.message, 'error');
    } finally {
      setIsSessionsLoading(false);
    }
  }, []);

  const loadActivity = useCallback(async () => {
    setIsActivityLoading(true);
    try {
      const data = await apiFetch('/ledger?per_page=50');
      setActivityList(data.items || []);
    } catch (e) {
      showToast('Failed to load user activity log: ' + e.message, 'error');
    } finally {
      setIsActivityLoading(false);
    }
  }, []);

  useEffect(() => {
    if (activeTab === 'config') {
      loadSessions(true);
      loadActivity();
    }
  }, [activeTab, loadSessions, loadActivity]);

  useEffect(() => {
    const isVarnerOSPage = window.location.search.includes('page=varner-os');
    const isEquipmentEdit = window.location.pathname.includes('post.php') || window.location.pathname.includes('post-new.php');
    const postId = window.varnerData?.post_id;

    if (!isVarnerOSPage && (isEquipmentEdit || (postId && postId > 0))) {
      handleFullEdit(postId);
    }

    loadInventory();
    apiFetch('/me').then(setCurrentUser).catch(() => {});
    apiFetch('/brands').then(setBrands).catch(() => {});
    apiFetch('/categories').then(setCategories).catch(() => {});
  }, [loadInventory]);

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

  const handleAddBrand      = () => handleListAdd('brands',     brands,     newBrandInput,    setBrands,     setNewBrandInput);
  const handleDeleteBrand   = (n) => handleListDelete('brands',     brands,     n, setBrands,     'make');
  const handleAddCategory   = () => handleListAdd('categories', categories, newCategoryInput, setCategories, setNewCategoryInput);
  const handleDeleteCategory = (n) => handleListDelete('categories', categories, n, setCategories, 'category');

  const handleInputChange = (field, value) => {
    setUnitData(prev => ({ ...prev, [field]: value }));

    // Clear field-level error when user edits the field
    if (fieldErrors[field]) {
      setFieldErrors(prev => {
        const next = { ...prev }; delete next[field]; return next;
      });
    }
    
    // Bidirectional sync: If toggling visibility or featured status in the editor,
    // update the corresponding list item and save to the database immediately.
    if ((field === 'featured' || field === 'showOnWebsite') && unitData.id) {
      setInventoryList(prev => prev.map(u => u.wpId === unitData.id ? { ...u, [field]: value } : u));
      
      const wpField = field === 'showOnWebsite' ? 'show_on_website' : 'featured';
      apiFetch(`/inventory/${unitData.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ [wpField]: value })
      }).catch(e => {
        showToast(`Sync failed: ${e.message}`, 'error');
        // Rollback on failure
        setUnitData(prev => ({ ...prev, [field]: !value }));
        setInventoryList(prev => prev.map(u => u.wpId === unitData.id ? { ...u, [field]: !value } : u));
      });
    }
  };

  // Image upload — sends file to WP Media Library, stores id + url
  const handleAddImages = async (files) => {
    const results = await Promise.all(files.map(uploadFile));
    setUnitData(prev => ({
      ...prev,
      images:    [...prev.images,    ...results.map(r => r.url)],
      image_ids: [...prev.image_ids, ...results.map(r => r.id)],
    }));
  };

  const handleRemoveImage = (index) => {
    setUnitData(prev => ({
      ...prev,
      images:    prev.images.filter((_, i) => i !== index),
      image_ids: prev.image_ids.filter((_, i) => i !== index),
    }));
  };

  const handleReorderImages = (oldIndex, newIndex) => {
    setUnitData(prev => ({
      ...prev,
      images: arrayMove(prev.images, oldIndex, newIndex),
      image_ids: arrayMove(prev.image_ids, oldIndex, newIndex),
    }));
  };

  const handleAddImplement = () => {
    setUnitData(prev => ({
      ...prev,
      attachments: [...(prev.attachments ?? []), { image: '', image_id: 0, title: '', price: '', description: '' }],
    }));
  };

  const handleUpdateImplement = (index, field, value) => {
    setUnitData(prev => {
      const next = [...prev.attachments];
      next[index] = { ...next[index], [field]: value };
      return { ...prev, attachments: next };
    });
  };

  // Implement image upload
  const handleImplementImageUpload = async (index, file) => {
    try {
      const result = await uploadFile(file);
      handleUpdateImplement(index, 'image', result.url);
      handleUpdateImplement(index, 'image_id', result.id);
    } catch {
      showToast('Image upload failed', 'error');
    }
  };

  const handleRemoveImplement = (index) => {
    setUnitData(prev => ({
      ...prev,
      attachments: prev.attachments.filter((_, i) => i !== index),
    }));
  };

  // Save (create or update)
  const handleSave = async () => {
    const required = [
      ['title', 'Public Inventory Title'],
      ['year', 'Year'],
      ['make', 'Brand / Manufacturer'],
      ['model', 'Model'],
      ['category', 'Category'],
      ['stockStatus', 'Stock Status'],
      ['condition', 'Condition'],
    ];
    if (!unitData.callForPrice) {
      required.push(['price', 'Price']);
    }

    const errors = {};
    required.forEach(([key, label]) => {
      if (!String(unitData[key] || '').trim()) {
        errors[key] = `${label} is required`;
      }
    });

    if (Object.keys(errors).length) {
      setFieldErrors(errors);
      setActiveTab('inventory');
      return;
    }

    setIsSaving(true);
    try {
      const payload = {
        title:        unitData.title || 'Untitled Unit',
        year:         unitData.year,
        make:         unitData.make,
        model:        unitData.model,
        stock_number: unitData.stockNumber,
        vin:          unitData.vin,
        price:        unitData.price,
        call_for_price: unitData.callForPrice ?? false,
        condition:    unitData.condition,
        stock_status: unitData.stockStatus,
        category:     unitData.category,
        color:        unitData.color,
        length:       unitData.length,
        meter:        unitData.meter,
        meter_type:   unitData.meterType,
        intake_date:  unitData.intakeDate,
        description:  unitData.description,
        seller_info:  unitData.sellerInfo,
        featured:     unitData.featured ?? false,
        show_on_website: unitData.showOnWebsite ?? true,
        has_attachments: unitData.hasAttachments ?? false,
        attachment_details: unitData.attachmentDetails || '',
        engine_horsepower: unitData.engineHorsepower || '',
        drive: unitData.drive || '',
        image_ids:    unitData.image_ids ?? [],
        implements:   (unitData.attachments ?? []).map(a => ({
          title:       a.title,
          price:       a.price,
          description: a.description,
          image_id:    a.image_id ?? 0,
        })),
      };

      let saved;
      if (unitData.id) {
        saved = await apiFetch(`/inventory/${unitData.id}`, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      } else {
        saved = await apiFetch('/inventory', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
      }

      setUnitData(apiToLocal(saved));
      await loadInventory();
      const catLabel = getCategoryLabel(unitData.category);
      showToast(`Success! The ${catLabel} has been uploaded successfully!`);
      setActiveTab('all-inventory');
    } catch (e) {
      showToast('Save failed: ' + e.message, 'error');
    } finally {
      setIsSaving(false);
    }
  };

  const handleDeleteUnit = async (wpId, stockNumber) => {
    if (!window.confirm(`Delete unit #${stockNumber}?`)) return;
    try {
      await apiFetch(`/inventory/${wpId}`, { method: 'DELETE' });
      await loadInventory();
      if (unitData.id === wpId) { setUnitData(defaultEmptyUnit); setActiveTab('all-inventory'); }
      showToast('Unit moved to recycle bin');
    } catch (e) {
      showToast('Delete failed: ' + e.message, 'error');
    }
  };

  const handleRestoreUnit = async (wpId) => {
    try {
      await apiFetch(`/inventory/${wpId}/restore`, { method: 'POST' });
      await loadInventory();
      showToast('Unit restored');
    } catch (e) {
      showToast('Restore failed: ' + e.message, 'error');
    }
  };

  const handleToggleBoolean = async (item, field) => {
    const isShowOnWebsite = field === 'show_on_website';
    const fieldKey = isShowOnWebsite ? 'showOnWebsite' : 'featured';
    const newVal = !item[fieldKey];
    
    // Optimistic update for list
    setInventoryList(prev => prev.map(u => u.wpId === item.wpId ? { ...u, [fieldKey]: newVal } : u));
    
    // Update unitData if currently editing this unit
    if (unitData.id === item.wpId) {
      setUnitData(prev => ({ ...prev, [fieldKey]: newVal }));
    }
    
    try {
      await apiFetch(`/inventory/${item.wpId}`, {
        method: 'PATCH',
        body: JSON.stringify({ [field]: newVal })
      });
    } catch (e) {
      showToast(`Failed to update: ${e.message}`, 'error');
      loadInventory(); // Rollback list
      if (unitData.id === item.wpId) {
        setUnitData(prev => ({ ...prev, [fieldKey]: !newVal }));
      }
    }
  };

  const handlePermanentDelete = async (wpId) => {
    if (!window.confirm('PERMANENT DELETE: This cannot be undone. Proceed?')) return;
    try {
      await apiFetch(`/inventory/${wpId}/permanent`, { method: 'DELETE' });
      await loadInventory();
      showToast('Unit permanently deleted');
    } catch (e) {
      showToast('Delete failed: ' + e.message, 'error');
    }
  };

  const handleAddNewUnit = () => { setUnitData(defaultEmptyUnit); setActiveTab('inventory'); };

  // Fetches complete unit data from API and opens editor
  const handleFullEdit = async (wpId) => {
    setActiveTab('inventory');
    try {
      const units = await apiFetch('/inventory');
      const unit  = units.find(u => u.id === wpId);
      if (unit) setUnitData(apiToLocal(unit));
    } catch { /* tab already switched; user will see empty editor */ }
  };

  const handleClone = () => {
    setUnitData(prev => ({
      ...prev,
      id: null,
      stockNumber: prev.stockNumber + '-COPY',
      title: prev.title + ' (Copy)',
      vin: '',
    }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleFilterChange = (key, value) => setActiveFilters(prev => ({ ...prev, [key]: value }));
  const handleClearFilters = () => { setActiveFilters({ status: [], categories: [], makes: [], models: [], yearMin: '', yearMax: '', priceMin: '', priceMax: '', conditions: [], stockSearch: '', vinSearch: '' }); setSearchQuery(''); };

  const filteredInventory = inventoryList.filter(item => {
    const hit = (q) => !q || (
      item.stock?.toLowerCase().includes(q) || item.make?.toLowerCase().includes(q) ||
      item.model?.toLowerCase().includes(q) || item.year?.toLowerCase().includes(q) ||
      item.category?.toLowerCase().includes(q) || item.condition?.toLowerCase().includes(q)
    );
    if (isPublicMode && item.showOnWebsite === false) return false;
    if (!hit(searchQuery?.toLowerCase())) return false;
    if (activeFilters.status.length     && !activeFilters.status.includes(item.status))       return false;
    if (activeFilters.categories.length && !activeFilters.categories.includes(item.category)) return false;
    if (activeFilters.makes.length      && !activeFilters.makes.includes(item.make))           return false;
    if (activeFilters.models.length     && !activeFilters.models.includes(item.model))         return false;
    if (activeFilters.yearMin  && parseInt(item.year)     < parseInt(activeFilters.yearMin))   return false;
    if (activeFilters.yearMax  && parseInt(item.year)     > parseInt(activeFilters.yearMax))   return false;
    if (activeFilters.priceMin && parseInt(item.price||0) < parseInt(activeFilters.priceMin))  return false;
    if (activeFilters.priceMax && parseInt(item.price||0) > parseInt(activeFilters.priceMax))  return false;
    if (activeFilters.conditions.length && !activeFilters.conditions.includes(item.condition)) return false;
    if (activeFilters.stockSearch && !item.stock?.toLowerCase().includes(activeFilters.stockSearch.toLowerCase())) return false;
    if (activeFilters.vinSearch   && !item.vin?.toLowerCase().includes(activeFilters.vinSearch.toLowerCase()))   return false;
    return true;
  });

  const getHeaderTitle = () => {
    switch (activeTab) {
      case 'dashboard':     return 'Operations Overview';
      case 'all-inventory': return 'Master Inventory Ledger';
      case 'inventory':     return (
        <span className="flex items-center gap-2">
          {unitData.title || 'Inventory Editor'}
          <span className="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded uppercase tracking-tighter font-black">
            SKU: {unitData.stockNumber || 'PENDING'}
          </span>
        </span>
      );
      case 'marketplace':   return 'Meta Commerce Sync';
      case 'history':       return 'Deletion History / Recycle Bin';
      case 'mobile':        return 'Mobile Companion Access';
      case 'settings':      return 'Page Editor';
      case 'videos':        return 'Videos Manager';
      case 'config':        return 'System Settings & Audit';
      default:              return 'Varner OS';
    }
  };

  if (isMobileApp) {
    return (
      <MobileAppLayout
        toast={toast}
        mobileToken={mobileToken}
        setMobileToken={setMobileToken}
        mobileActiveTab={mobileActiveTab}
        setMobileActiveTab={setMobileActiveTab}
        inventoryList={inventoryList}
        loadInventory={loadInventory}
        isLoading={isLoading}
        unitData={unitData}
        setUnitData={setUnitData}
        defaultEmptyUnit={defaultEmptyUnit}
        brands={brands}
        categories={categories}
        handleSave={handleSave}
        isSaving={isSaving}
        fieldErrors={fieldErrors}
        setFieldErrors={setFieldErrors}
        handleInputChange={handleInputChange}
        handleAddImages={handleAddImages}
        handleRemoveImage={handleRemoveImage}
        handleReorderImages={handleReorderImages}
        handleAddImplement={handleAddImplement}
        handleUpdateImplement={handleUpdateImplement}
        handleRemoveImplement={handleRemoveImplement}
        handleImplementImageUpload={handleImplementImageUpload}
        handleToggleBoolean={handleToggleBoolean}
        handleFullEdit={handleFullEdit}
        handleDeleteUnit={handleDeleteUnit}
        showToast={showToast}
      />
    );
  }

  return (
    <div className="flex bg-[#f8fafc] font-sans text-slate-900 selection:bg-red-100 min-h-screen">

      {/* Quill editor styles — injected once at app root */}
      <style dangerouslySetInnerHTML={{ __html: QUILL_STYLES }}/>

      {/* Toast */}
      {toast && (
        <div className={`fixed top-6 right-6 z-[9999] px-6 py-4 rounded-2xl font-black text-sm shadow-2xl transition-all animate-in slide-in-from-top-4 ${toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`}>
          {toast.msg}
        </div>
      )}

      {/* Brands Management Modal */}
      {showBrandsModal && (
        <ManageListModal title="Manage Brands" items={brands} inputValue={newBrandInput}
          onInputChange={setNewBrandInput} onAdd={handleAddBrand} onDelete={handleDeleteBrand}
          onClose={() => setShowBrandsModal(false)} placeholder="New brand name..." />
      )}

      {/* Categories Management Modal */}
      {showCategoriesModal && (
        <ManageListModal title="Manage Categories" items={categories} inputValue={newCategoryInput}
          onInputChange={setNewCategoryInput} onAdd={handleAddCategory} onDelete={handleDeleteCategory}
          onClose={() => setShowCategoriesModal(false)} placeholder="New category name..." />
      )}

      {/* MOBILE SIDEBAR OVERLAY */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" onClick={() => setIsMobileMenuOpen(false)}></div>
          <aside className="fixed inset-y-0 left-0 w-72 bg-slate-950 text-white p-6 shadow-2xl flex flex-col">
            <div className="flex items-center justify-between mb-8 border-b border-slate-800 pb-6">
              <SidebarLogo />
              <button onClick={() => setIsMobileMenuOpen(false)} className="text-slate-400 hover:text-white p-2"><X size={24} /></button>
            </div>
            <SidebarContent activeTab={activeTab} inventoryList={inventoryList} deletedHistory={deletedHistory}
              onNav={tab => { setActiveTab(tab); setIsMobileMenuOpen(false); }} />
          </aside>
        </div>
      )}

      {/* SIDEBAR */}
      <aside className="hidden lg:flex flex-col w-72 bg-slate-950 text-white p-6 shadow-2xl border-r border-slate-800 shrink-0">
        <div className="flex items-center gap-3 mb-8 border-b border-slate-800 pb-6">
          <SidebarLogo />
        </div>
        <SidebarContent activeTab={activeTab} inventoryList={inventoryList} deletedHistory={deletedHistory}
          onNav={tab => setActiveTab(tab)} />
      </aside>

      {/* MAIN */}
      <main className="flex-1 flex flex-col text-slate-900 min-h-screen">
        <header className="bg-white border-b border-slate-200 px-4 sm:px-8 py-4 sm:py-5 flex items-center justify-between shadow-sm z-10">
          <div className="flex items-center gap-3 min-w-0">
            <button onClick={() => setIsMobileMenuOpen(true)} className="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-xl shrink-0"><Menu size={24}/></button>
            <div className="flex flex-col min-w-0">
              <h2 className="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1 hidden sm:block">System Modules</h2>
              <h3 className="text-base sm:text-xl font-black text-slate-950 tracking-tight leading-none uppercase truncate">{getHeaderTitle()}</h3>
            </div>
          </div>
          <div className="flex items-center gap-2 sm:gap-3">
            {activeTab === 'inventory' && unitData.title && (
              <button onClick={handleClone} className="bg-slate-100 text-slate-600 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95">
                <Copy size={16}/> <span className="hidden sm:inline">Clone Unit</span>
              </button>
            )}

            {activeTab === 'inventory' && (
              <button onClick={handleAddNewUnit} className="bg-red-600 text-white p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-red-700 transition-all border-b-2 border-red-800 shadow-xl shadow-red-100 active:scale-95">
                <Plus size={16}/> <span className="hidden sm:inline">New Unit</span>
              </button>
            )}

            {activeTab === 'all-inventory' && (
              <a
                href="/wp-admin/admin.php?page=pmxi-admin-import"
                className="bg-slate-100 text-slate-700 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95"
              >
                <Upload size={16}/> <span className="hidden sm:inline">Import Inventory</span><span className="sm:hidden">Import</span>
              </a>
            )}

            {activeTab === 'all-inventory' && (
              <a
                href="/wp-admin/admin.php?page=pmxe-admin-manage"
                className="bg-slate-100 text-slate-700 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95"
              >
                <Download size={16}/> <span className="hidden sm:inline">Export Inventory</span><span className="sm:hidden">Export</span>
              </a>
            )}

            {(activeTab === 'inventory' || activeTab === 'all-inventory') && (
              <button
                onClick={activeTab === 'inventory' ? handleSave : handleAddNewUnit}
                className="bg-red-600 text-white p-3 sm:px-7 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 active:scale-95 transition-all border-b-2 border-red-800"
              >
                {isSaving ? <Zap className="animate-spin" size={16}/> : (activeTab === 'inventory' ? <Save size={16}/> : <Plus size={16}/>)}
                <span className="hidden sm:inline">{isSaving ? 'PUBLISHING…' : (activeTab === 'inventory' ? 'PUBLISH TO INVENTORY' : 'NEW UNIT')}</span>
                <span className="sm:hidden">{activeTab === 'inventory' ? (isSaving ? 'PUB…' : 'PUBLISH') : 'NEW'}</span>
              </button>
            )}
          </div>
        </header>

        <div className={`flex-1 overflow-y-auto bg-slate-50/50 no-scrollbar ${activeTab === 'all-inventory' || activeTab === 'history' ? 'px-2 py-4 sm:px-3 sm:py-6' : 'p-4 sm:p-6 lg:p-8'}`}>
          <div className={`${activeTab === 'all-inventory' || activeTab === 'history' ? 'max-w-none px-4 sm:px-6 lg:px-8' : 'max-w-7xl'} mx-auto pb-10`}>

            {/* DASHBOARD */}
            {activeTab === 'dashboard' && (
              <div className="space-y-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                  <MetricCard icon={<Box size={24}/>} label="Live Units" value={isLoading ? '…' : String(inventoryList.filter(i => (i.status || '').toLowerCase() === 'in stock').length)} subtext="In stock right now" color="blue" />
                  <MetricCard icon={<TrendingUp size={24}/>} label="Total Units" value={isLoading ? '…' : String(inventoryList.length)} subtext="All active listings" color="amber" />
                  <MetricCard icon={<CheckCircle2 size={24}/>} label="Sold Units" value={isLoading ? '…' : String(inventoryList.filter(i => (i.status || '').toLowerCase() === 'sold').length)} subtext="Marked as sold" color="green" />
                  <MetricCard icon={<Clock size={24}/>} label="Pending Sales" value={isLoading ? '…' : String(inventoryList.filter(i => ['sale pending','pending sale','pending'].includes((i.status || '').toLowerCase())).length)} subtext="Awaiting close" color="red" />
                </div>
                <div className="space-y-8">
                  <QuickActions onAdd={handleAddNewUnit} />
                  <RecentActivity />
                </div>
              </div>
            )}

            {/* MASTER INVENTORY */}
            {activeTab === 'all-inventory' && (
              <div className="flex flex-col gap-4 animate-in fade-in slide-in-from-bottom-6 duration-500">

                {/* Application Filter — Horizontal on desktop, above the ledger card */}
                <div className="hidden xl:block">
                  <FilterSidebar horizontal inventoryList={inventoryList} filters={activeFilters} searchQuery={searchQuery}
                    onFilterChange={handleFilterChange} onKeywordSearch={setSearchQuery} onClearAll={handleClearFilters}/>
                </div>

                {/* Applied Filters Bar — full width across top */}
                {(searchQuery || Object.values(activeFilters).some(v => Array.isArray(v) ? v.length > 0 : v !== '')) && (
                  <div className="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 flex flex-wrap items-center gap-2">
                    <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0 mr-1">Active Filters:</span>
                    {searchQuery && (
                      <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
                        <button onClick={() => setSearchQuery('')} className="font-black leading-none hover:text-red-200">×</button>
                        {searchQuery.toUpperCase()}
                      </span>
                    )}
                    {['makes','status','categories','models','conditions'].flatMap(key =>
                      activeFilters[key].map(v => (
                        <FilterTag key={`${key}-${v}`} label={v.toUpperCase()}
                          onRemove={() => handleFilterChange(key, activeFilters[key].filter(x => x !== v))} />
                      ))
                    )}
                    {(activeFilters.yearMin || activeFilters.yearMax) && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => { handleFilterChange('yearMin',''); handleFilterChange('yearMax',''); }} className="font-black leading-none hover:text-red-200">×</button>YEAR: {activeFilters.yearMin||'?'}–{activeFilters.yearMax||'?'}</span>}
                    {(activeFilters.priceMin || activeFilters.priceMax) && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => { handleFilterChange('priceMin',''); handleFilterChange('priceMax',''); }} className="font-black leading-none hover:text-red-200">×</button>PRICE: ${activeFilters.priceMin||'0'}–${activeFilters.priceMax||'∞'}</span>}
                    {activeFilters.stockSearch && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('stockSearch','')} className="font-black leading-none hover:text-red-200">×</button>STOCK #: {activeFilters.stockSearch}</span>}
                    {activeFilters.vinSearch   && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('vinSearch','')} className="font-black leading-none hover:text-red-200">×</button>VIN: {activeFilters.vinSearch}</span>}
                    <button onClick={handleClearFilters} className="ml-auto text-xs font-black text-slate-400 hover:text-red-600 uppercase tracking-widest transition-colors shrink-0">Clear All</button>
                  </div>
                )}

                <div className="flex flex-col gap-3">
                {/* Mobile filter drawer button logic (FilterSidebar is now used in drawer for mobile) */}
                {showFilterPanel && (
                  <div className="fixed inset-0 z-[9997] xl:hidden">
                    <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setShowFilterPanel(false)}/>
                    <div className="absolute inset-y-0 left-0 w-80 bg-white overflow-y-auto shadow-2xl">
                      <div className="flex items-center justify-between p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                        <h3 className="font-black text-sm uppercase tracking-widest">Filters</h3>
                        <button onClick={() => setShowFilterPanel(false)} className="p-1 text-gray-400 hover:text-gray-700"><X size={20}/></button>
                      </div>
                      <FilterSidebar inventoryList={inventoryList} filters={activeFilters} searchQuery={searchQuery}
                        onFilterChange={handleFilterChange} onKeywordSearch={setSearchQuery} onClearAll={handleClearFilters}/>
                    </div>
                  </div>
                )}

                {/* Table / Master inventory ledger card */}
                <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden" style={{ minWidth: 0 }}>
                  <div className="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between w-full">
                    <div className="flex items-center gap-3">
                      <button onClick={() => setShowFilterPanel(true)}
                        className="xl:hidden flex items-center gap-2 bg-white border-2 border-slate-200 px-4 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-sm hover:border-red-500 transition-colors">
                        <Search size={14}/> Filters
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
                             <tr key={item.id} className="hover:bg-slate-50 transition-all cursor-pointer group" onClick={() => handleFullEdit(item.wpId)}>
                               <td className="px-6 py-5 font-mono font-bold text-sm text-slate-500">{item.stock}</td>
                               <td className="px-4 py-3">
                                 <div className="w-40 h-28 bg-slate-100 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                                   {item.images?.[0]
                                     ? <img src={item.images[0]} alt={`${item.year} ${item.make} ${item.model}`} className="w-full h-full object-cover" onError={e => { e.target.onerror=null; e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}/>
                                     : <div className="w-full h-full flex items-center justify-center"><ImageIcon size={16} className="text-slate-300"/></div>
                                   }
                                 </div>
                               </td>
                               <td className="px-6 py-5">
                                 <p className="font-black text-base leading-tight uppercase tracking-tight">{item.year} {item.make}</p>
                                 <p className="text-[10px] font-black uppercase tracking-widest mt-1 opacity-60">{item.model}</p>
                               </td>
                               <td className="px-6 py-5"><span className="text-[10px] font-black uppercase tracking-widest text-slate-400">{item.category}</span></td>
                               <td className="px-6 py-5 text-center"><span className="text-[9px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 px-3 py-1 rounded-lg border border-blue-100 shadow-sm">{item.condition}</span></td>
                               <td className="px-6 py-5 font-black text-base tracking-tighter">
                                 {item.callForPrice
                                   ? <span className="text-red-600 text-[11px] uppercase tracking-widest">Call for Price</span>
                                   : <span className="text-slate-900">${parseInt(item.price || 0).toLocaleString()}</span>
                                 }
                               </td>
                               <td className="px-6 py-5">
                                 <span className={`inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border ${item.status==='In Stock' ? 'text-green-500 bg-green-50 border-green-100' : item.status==='Pending Sale' ? 'text-amber-500 bg-amber-50 border-amber-100' : 'text-slate-400 bg-slate-50 border-slate-200'}`}>
                                   <div className={`w-1.5 h-1.5 rounded-full ${item.status==='In Stock' ? 'bg-green-500 animate-pulse' : item.status==='Pending Sale' ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'}`}></div>
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
                                   <button onClick={() => handleToggleBoolean(item, 'show_on_website')} 
                                     className={`w-12 h-6 rounded-full relative transition-all duration-300 ${item.showOnWebsite ? 'bg-green-500' : 'bg-slate-200'}`}>
                                     <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 ${item.showOnWebsite ? 'left-7' : 'left-1'}`}/>
                                   </button>
                                 </div>
                               </td>
                               <td className="px-6 py-5 text-center" onClick={e => e.stopPropagation()}>
                                 <div className="flex justify-center">
                                   <button onClick={() => handleToggleBoolean(item, 'featured')} 
                                     className={`w-12 h-6 rounded-full relative transition-all duration-300 ${item.featured ? 'bg-amber-500' : 'bg-slate-200'}`}>
                                     <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 ${item.featured ? 'left-7' : 'left-1'}`}/>
                                   </button>
                                 </div>
                               </td>
                               <td className="px-6 py-5 text-right">

                                <div className="flex items-center justify-end gap-2" onClick={e => e.stopPropagation()}>
                                   <button onClick={() => handleFullEdit(item.wpId)} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Edit"><Edit2 size={16}/></button>
                                   <button onClick={() => handleFullEdit(item.wpId).then(handleClone)} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Clone"><Copy size={16}/></button>
                                  <button onClick={() => handleDeleteUnit(item.wpId, item.stock)} className="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all active:scale-95" title="Delete"><X size={16}/></button>
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
            )}

            {/* UNIT EDITOR */}
            {activeTab === 'inventory' && (
              <div className="grid grid-cols-1 xl:grid-cols-3 gap-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="xl:col-span-2 space-y-8">
                  <div className="bg-white rounded-[2rem] p-4 sm:p-6 lg:p-8 shadow-xl border border-slate-200/60 relative overflow-hidden text-slate-900">
                    <div className="flex justify-between items-center mb-6 sm:mb-8">
                      <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2 leading-none"><Box size={14} className="text-red-600"/> Equipment Identity</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="md:col-span-2">
                        <div className="space-y-3">
                          <div className="flex items-center justify-between pl-1">
                            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Equipment Category</label>
                          </div>
                          <div className="flex flex-col sm:flex-row gap-3">
                            <div className="relative flex-1 flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                              <select value={unitData.category} onChange={e => handleInputChange('category', e.target.value)}
                                className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none">
                                <option value="">— Select Category —</option>
                                {categories.map(c => <option key={c} value={c}>{c}</option>)}
                              </select>
                              <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90"/></div>
                            </div>
                            <button type="button" onClick={() => setShowCategoriesModal(true)}
                              className="bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest whitespace-nowrap min-h-[64px]">
                              <Settings size={14}/> Manage Categories
                            </button>
                          </div>
                          {fieldErrors.category && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.category}</p>}
                        </div>
                      </div>
                      <div className="md:col-span-2">
                        <div className="space-y-3">
                          <div className="flex items-center justify-between pl-1">
                            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Brand / Manufacturer</label>
                          </div>
                          <div className="flex flex-col sm:flex-row gap-3">
                            <div className="relative flex-1 flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                              <select value={unitData.make} 
                                onChange={e => {
                                  const v = e.target.value;
                                  handleInputChange('make', v);
                                  const newTitle = `${unitData.year} ${v} ${unitData.model}`.trim();
                                  handleInputChange('title', newTitle);
                                }}
                                className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none">
                                <option value="">— Select Brand —</option>
                                {brands.map(b => <option key={b} value={b}>{b}</option>)}
                              </select>
                              <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90"/></div>
                            </div>
                            <button type="button" onClick={() => setShowBrandsModal(true)}
                              className="bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest whitespace-nowrap min-h-[64px]">
                              <Settings size={14}/> Manage Brands
                            </button>
                          </div>
                          {fieldErrors.make && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.make}</p>}
                        </div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3 md:col-span-2">
                        <div className="flex-1">
                          <div className="space-y-3">
                            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Year</label>
                          <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                            <select 
                              value={unitData.year} 
                              onChange={e => {
                                const v = e.target.value;
                                handleInputChange('year', v);
                                const newTitle = `${v} ${unitData.make} ${unitData.model}`.trim();
                                handleInputChange('title', newTitle);
                              }}
                              className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none"
                            >
                              <option value="">— Select Year —</option>
                              {Array.from({ length: 2027 - 1950 + 1 }, (_, i) => 2027 - i).map(year => (
                                <option key={year} value={year}>{year}</option>
                              ))}
                            </select>
                            <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400">
                              <ChevronRight size={24} className="rotate-90"/>
                            </div>
                          </div>
                          {fieldErrors.year && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.year}</p>}
                        </div>
                        </div>
                        <div className="flex-1">
                          <InputField 
                            label="Model" 
                            value={unitData.model} 
                            onChange={v => {
                              handleInputChange('model', v);
                              const newTitle = `${unitData.year} ${unitData.make} ${v}`.trim();
                              handleInputChange('title', newTitle);
                            }}
                            error={fieldErrors.model}
                          />
                        </div>
                      </div>
                      <div className="md:col-span-2">
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">
                            Public Inventory Title <span className="text-red-600">(Mandatory)</span>
                          </label>
                          <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wide -mt-1 ml-1 leading-relaxed opacity-80">
                            Main heading for website & marketplace. 
                            <span className="text-slate-500"> Include Year, Make, and Model for optimal SEO and visibility.</span>
                          </p>
                          <input 
                            type="text" 
                            value={unitData.title} 
                            onChange={e => handleInputChange('title', e.target.value)} 
                            className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-black text-slate-900 focus:border-slate-300 focus:bg-white outline-none transition-all shadow-sm text-lg leading-none"
                          />
                          {fieldErrors.title && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.title}</p>}
                        </div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1"><SelectField label="Stock Status" options={['In Stock','Pending Sale','Sold','Draft']} value={unitData.stockStatus} onChange={v => handleInputChange('stockStatus', v)} error={fieldErrors.stockStatus}/></div>
                        <div className="flex-1"><SelectField label="Condition" options={['New','Used']} value={unitData.condition} onChange={v => handleInputChange('condition', v)} error={fieldErrors.condition}/></div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1">
                          <SelectField 
                            label="Color" 
                            placeholder="Choose Color"
                            options={[
                              'Black', 'Red', 'Green', 'Green/Yellow', 'Brown', 
                              'Orange', 'Blue', 'Yellow', 'Gray', 'Silver', 'White', 
                              'Red/White', 'Blue/Black', 'Orange/Black', 
                              'Black/Gray', 'Gray/Black', 'Red/Black', 'Silver/Black', 'Yellow/Black'
                            ]} 
                            value={unitData.color} 
                            onChange={v => handleInputChange('color', v)} 
                            error={fieldErrors.color}
                          />
                        </div>
                        <div className="flex-1"><InputField label="Length (e.g. 20 ft)" value={unitData.length} onChange={v => handleInputChange('length', v)} error={fieldErrors.length}/></div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3 md:col-span-2">
                        <div className="flex-1"><InputField label="Hours" value={unitData.meter} onChange={v => handleInputChange('meter', v)} error={fieldErrors.meter} placeholder="e.g. 250"/></div>
                        <div className="flex-1"><InputField label="Drive" value={unitData.drive} onChange={v => handleInputChange('drive', v)} error={fieldErrors.drive} placeholder="e.g. 4WD / 2WD"/></div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3 md:col-span-2">
                        <div className="flex-1"><InputField label="Engine Horsepower" value={unitData.engineHorsepower} onChange={v => handleInputChange('engineHorsepower', v)} error={fieldErrors.engineHorsepower} placeholder="e.g. 25 HP"/></div>
                        <div className="flex-1"><SelectField label="Attachments" options={['No','Yes']} value={unitData.hasAttachments ? 'Yes' : 'No'} onChange={v => handleInputChange('hasAttachments', v === 'Yes')}/></div>
                      </div>
                      {unitData.hasAttachments && (
                        <div className="md:col-span-2">
                          <InputField label="Attachment Details" value={unitData.attachmentDetails} onChange={v => handleInputChange('attachmentDetails', v)} placeholder="Describe the included attachment(s)..."/>
                        </div>
                      )}
                      <div className="md:col-span-2 border-y border-slate-50 py-6 my-2 grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">VIN / SERIAL NUMBER</label>
                          <input type="text" value={unitData.vin} onChange={e => handleInputChange('vin', e.target.value)}
                            className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-mono font-black text-lg text-slate-900 outline-none shadow-inner focus:border-red-500 focus:bg-white transition-all tracking-widest uppercase"
                            placeholder="TYPE SERIAL..."/>
                        </div>
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Stock Number</label>
                          <input type="text" value={unitData.stockNumber} onChange={e => handleInputChange('stockNumber', e.target.value)}
                            className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-mono font-black text-lg text-slate-900 outline-none shadow-inner focus:border-red-500 focus:bg-white transition-all tracking-widest uppercase"
                            placeholder="STOCK #"/>
                        </div>
                      </div>
                      <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 px-1 items-end">
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-green-600 uppercase tracking-widest block pl-1">Retail Price (USD)</label>
                          <label className="flex items-center gap-3 cursor-pointer group w-fit ml-1">
                            <div className="relative flex items-center">
                              <input type="checkbox" checked={unitData.callForPrice} onChange={e => handleInputChange('callForPrice', e.target.checked)} className="sr-only peer"/>
                              <div className="w-10 h-6 bg-slate-200 rounded-full peer-checked:bg-red-600 transition-all after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4 shadow-inner"></div>
                            </div>
                            <span className="text-[9px] font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-900 transition-colors">Call For Price</span>
                          </label>
                          <div className={`flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px] ${unitData.callForPrice ? 'opacity-40 grayscale pointer-events-none' : ''}`}>
                            <div className="pl-5 pr-3 py-5 border-r border-slate-200 bg-slate-100/50 rounded-l-xl">
                              <span className="text-green-600 font-black text-xl select-none">$</span>
                            </div>
                            <input type="text" value={unitData.price ? Number(unitData.price).toLocaleString() : ''}
                              disabled={unitData.callForPrice}
                              onChange={e => handleInputChange('price', e.target.value.replace(/[^0-9]/g, ''))}
                              className="flex-1 bg-transparent p-4 font-black text-slate-900 outline-none text-xl leading-none" placeholder="0.00"/>
                          </div>
                          {fieldErrors.price && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.price}</p>}
                        </div>
                      </div>

                      <div className="md:col-span-2 space-y-4">
                        <div className="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-red-200 transition-all">
                          <div className="flex items-center gap-4">
                            <div className={`p-3 rounded-xl transition-all ${unitData.featured ? 'bg-amber-100 text-amber-600' : 'bg-white text-slate-300'}`}>
                              <Star size={20} fill={unitData.featured ? 'currentColor' : 'none'}/>
                            </div>
                            <div>
                              <p className="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">Featured Unit</p>
                              <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Display at the top of the homepage</p>
                            </div>
                          </div>
                          <button type="button" onClick={() => handleInputChange('featured', !unitData.featured)}
                            className={`w-14 h-7 rounded-full relative transition-all duration-300 ${unitData.featured ? 'bg-amber-500' : 'bg-slate-200'}`}>
                            <div className={`absolute top-1 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300 ${unitData.featured ? 'left-8' : 'left-1'}`}/>
                          </button>
                        </div>

                        <div className="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-red-200 transition-all">
                          <div className="flex items-center gap-4">
                            <div className={`p-3 rounded-xl transition-all ${unitData.showOnWebsite ? 'bg-green-100 text-green-600' : 'bg-white text-slate-300'}`}>
                              <Eye size={20}/>
                            </div>
                            <div>
                              <p className="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">Website Visibility</p>
                              <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Publicly visible on showroom pages</p>
                            </div>
                          </div>
                          <button type="button" onClick={() => handleInputChange('showOnWebsite', !unitData.showOnWebsite)}
                            className={`w-14 h-7 rounded-full relative transition-all duration-300 ${unitData.showOnWebsite ? 'bg-green-600' : 'bg-slate-200'}`}>
                            <div className={`absolute top-1 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300 ${unitData.showOnWebsite ? 'left-8' : 'left-1'}`}/>
                          </button>
                        </div>
                      </div>
                      <div className="md:col-span-2 space-y-6 pt-6 border-t border-slate-50">
                        <TextAreaField label="Public Description / Features" value={unitData.description} onChange={v => handleInputChange('description', v)}/>
                        <TextAreaField label="Seller Information Template" value={unitData.sellerInfo} onChange={v => handleInputChange('sellerInfo', v)}/>
                      </div>
                    </div>
                  </div>

                  <MediaSection title="High-Resolution Media" images={unitData.images} onAddFiles={handleAddImages} onRemove={handleRemoveImage} onReorder={handleReorderImages}/>
                  <AttachmentsSection attachments={unitData.attachments} onAdd={handleAddImplement} onChange={handleUpdateImplement} onRemove={handleRemoveImplement} onImageUpload={handleImplementImageUpload}/>

                  {/* BOTTOM ACTION BUTTONS */}
                  <div className="flex flex-col sm:flex-row gap-4 pt-6">
                    <button
                      onClick={handleClone}
                      disabled={!unitData.id}
                      className={`flex-1 px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 transition-all active:scale-95 border-2 shadow-xl shadow-slate-200/50 ${!unitData.id ? 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed' : 'bg-white text-slate-600 border-slate-100 hover:bg-slate-50'}`}
                    >
                      <Copy size={18}/>
                      Clone Unit
                    </button>

                    <button
                      onClick={handleSave}
                      disabled={isSaving}
                      className="flex-[2] bg-red-600 text-white px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-red-700 transition-all active:scale-95 shadow-2xl shadow-red-200 border-b-4 border-red-800 disabled:opacity-50"
                    >
                      {isSaving ? <Loader2 className="animate-spin" size={18}/> : <Save size={18}/>}
                      {isSaving ? 'PUBLISHING…' : 'PUBLISH TO INVENTORY'}
                    </button>
                  </div>
                </div>

                {/* RIGHT — MARKETPLACE WIDGET */}
                <div className="space-y-8">
                  <div className="bg-white rounded-[2rem] overflow-hidden shadow-2xl border border-slate-200/60 flex flex-col">
                    <div className="bg-slate-950 p-6 text-white flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <div className="bg-blue-600 p-2.5 rounded-xl"><Facebook size={20} fill="white"/></div>
                        <div>
                          <h4 className="font-black text-sm uppercase tracking-tight leading-none mb-1">Meta Marketplace</h4>
                          <p className="text-[8px] text-slate-500 uppercase font-black tracking-widest">Auto-Sync Active</p>
                        </div>
                      </div>
                      <button onClick={() => setSyncEnabled(!syncEnabled)} className={`w-14 h-7 rounded-full relative transition-all duration-300 ${syncEnabled ? 'bg-blue-600' : 'bg-slate-800'}`}>
                        <div className={`absolute top-1 w-5 h-5 bg-white rounded-full transition-all duration-300 ${syncEnabled ? 'left-8' : 'left-1'}`}/>
                      </button>
                    </div>
                    <div className="p-8 space-y-8 bg-white text-slate-900">
                      <div className="flex items-center gap-4 p-5 bg-blue-50/40 border-2 border-blue-100 rounded-[1.5rem]">
                        <div className="bg-white p-2 rounded-full border border-blue-200 shadow-md text-blue-600"><CheckCircle2 size={20}/></div>
                        <div>
                          <p className="text-[11px] font-black text-blue-950 uppercase leading-none mb-1">Facebook Catalog Synced</p>
                          <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest italic">Refreshed 2m ago</p>
                        </div>
                      </div>
                      <div className="space-y-4 px-1 font-black text-slate-900">
                        <h5 className="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mb-4">Catalog Mapping Logic</h5>
                        <MappingRow label="Vehicle Category" value="Agriculture / Tractor"/>
                        <MappingRow label="Location Tag" value="Delta, CO (150mi)"/>
                        <MappingRow label="Price Format" value="USD Fixed"/>
                      </div>
                      <button onClick={() => setShowFBPreview(true)} className="w-full bg-slate-950 text-white py-6 rounded-[1.5rem] font-black text-[13px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-black transition-all active:scale-95 shadow-2xl shadow-slate-300 mt-2 leading-none border-b-4 border-slate-800">
                        View Marketplace Preview <ArrowUpRight size={18} className="text-blue-400"/>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'marketplace' && <MarketplaceTab/>}
            {activeTab === 'settings'    && <SettingsTab showToast={showToast}/>}
            {activeTab === 'videos'      && <VideosTab showToast={showToast}/>}
            {activeTab === 'mobile'      && <MobileAccessTab/>}
            {activeTab === 'config'      && (
              <ConfigurationTab
                showToast={showToast}
                currentUser={currentUser}
                sessionList={sessionList}
                isLoading={isSessionsLoading}
                loadSessions={loadSessions}
                activityList={activityList}
                isActivityLoading={isActivityLoading}
                loadActivity={loadActivity}
                onNav={setActiveTab}
                handleFullEdit={handleFullEdit}
              />
            )}
            {activeTab === 'history'     && (
              <HistoryTab
                deletedItems={deletedHistory}
                onRestore={item => handleRestoreUnit(item.wpId)}
                onPermanentDelete={item => handlePermanentDelete(item.wpId)}
              />
            )}

          </div>
        </div>
      </main>

      {showFBPreview && <FBPreviewModal unitData={unitData} onClose={() => setShowFBPreview(false)}/>}
    </div>
  );
};

// ─── Sub-components ──────────────────────────────────────────────────────────

const SidebarLogo = () => (
  <div className="flex items-center gap-3">
    <div className="bg-red-600 p-2 rounded-xl"><Box size={22} /></div>
    <div>
      <span className="font-black text-xl tracking-tighter block leading-none">VARNER</span>
      <span className="text-red-500 text-[9px] font-black uppercase tracking-[0.3em] mt-0.5 block">Equipment</span>
    </div>
  </div>
);

const SidebarContent = ({ activeTab, inventoryList, deletedHistory, onNav }) => (
  <>
    <nav className="space-y-2 flex-1">
      <NavItem icon={<LayoutDashboard size={20}/>} label="Dashboard"      active={activeTab==='dashboard'}     onClick={() => onNav('dashboard')} />
      <NavItem icon={<List size={20}/>}            label="Inventory List" active={activeTab==='all-inventory'} onClick={() => onNav('all-inventory')} badge={inventoryList.length} />
      <NavItem icon={<Box size={20}/>}             label="Add / Edit"     active={activeTab==='inventory'}     onClick={() => onNav('inventory')} />
      <NavItem icon={<Facebook size={20}/>}        label="Meta Sync"      active={activeTab==='marketplace'}   onClick={() => onNav('marketplace')} badge="Live" />
      <NavItem icon={<History size={20}/>}         label="History"        active={activeTab==='history'}       onClick={() => onNav('history')} badge={deletedHistory.length > 0 ? deletedHistory.length : null} />
      <NavItem icon={<Sliders size={20}/>}         label="Page Editor"    active={activeTab==='settings'}      onClick={() => onNav('settings')} />
      <NavItem icon={<Camera size={20}/>}          label="Videos Manager" active={activeTab==='videos'}        onClick={() => onNav('videos')} />
      <NavItem icon={<Smartphone size={20}/>}      label="Mobile Companion" active={activeTab==='mobile'}        onClick={() => onNav('mobile')} />
    </nav>
    <div className="mt-auto pt-4 border-t border-slate-800">
      <NavItem icon={<Settings size={18}/>} label="Configuration" active={activeTab==='config'} onClick={() => onNav('config')} />
    </div>
  </>
);

const ManageListModal = ({ title, items, inputValue, onInputChange, onAdd, onDelete, onClose, placeholder }) => (
  <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4" onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
    <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col" style={{ maxHeight: '85vh' }}>
      <div className="flex items-center justify-between p-6 border-b border-slate-100 shrink-0">
        <div>
          <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">{title}</h3>
          <p className="text-[10px] text-slate-400 font-bold mt-1">{items.length} {title.toLowerCase().split(' ')[1]} in list</p>
        </div>
        <button onClick={onClose} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
      </div>
      <div className="p-5 shrink-0">
        <div className="flex gap-2">
          <input type="text" value={inputValue} onChange={e => onInputChange(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && onAdd()}
            placeholder={placeholder}
            className="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors"/>
          <button onClick={onAdd} className="bg-red-600 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-red-700 transition-colors shrink-0">Add</button>
        </div>
      </div>
      <div className="overflow-y-auto flex-1 px-5 pb-5">
        <div className="space-y-1.5">
          {items.map(item => (
            <div key={item} className="flex items-center justify-between px-4 py-2.5 bg-slate-50 rounded-xl group hover:bg-red-50 transition-colors">
              <span className="font-bold text-sm text-slate-900">{item}</span>
              <button onClick={() => onDelete(item)} className="text-slate-300 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100"><X size={14}/></button>
            </div>
          ))}
        </div>
      </div>
    </div>
  </div>
);

const FilterTag = ({ label, onRemove }) => (
  <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md">
    <button onClick={onRemove} className="font-black leading-none hover:text-red-200">×</button>
    {label}
  </span>
);

const NavItem = ({ icon, label, active = false, badge = null, onClick }) => (
  <div onClick={onClick} className={`flex items-center justify-between p-4 rounded-xl cursor-pointer transition-all duration-300 ${active ? 'bg-red-600 text-white shadow-xl shadow-red-900/50 border-b-2 border-red-700' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-100'}`}>
    <div className="flex items-center gap-4">{icon}<span className="font-black text-[13px] uppercase tracking-wider">{label}</span></div>
    {badge !== null && badge !== undefined && <span className={`px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest shadow-md ${active ? 'bg-white text-red-600' : 'bg-green-500 text-white'}`}>{badge}</span>}
  </div>
);

const MappingRow = ({ label, value }) => (
  <div className="flex justify-between items-center py-1.5 border-b border-slate-50 pb-4 last:border-0 last:pb-0">
    <span className="text-[11px] font-black text-slate-400 uppercase tracking-widest">{label}</span>
    <span className="text-[11px] font-black text-slate-950 uppercase tracking-tight flex items-center gap-3">
      <div className="w-1.5 h-1.5 rounded-full bg-blue-600"></div>{value}
    </span>
  </div>
);

const InputField = ({ label, value, onChange, error }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <input type="text" value={value} onChange={e => onChange(e.target.value)} className={`w-full bg-slate-50 border-2 rounded-xl p-4 font-black text-slate-900 outline-none transition-all shadow-sm text-lg leading-none ${error ? 'border-red-400 focus:border-red-500 bg-red-50/40' : 'border-slate-100 focus:border-slate-300 focus:bg-white'}`}/>
    {error && <p className="text-[10px] font-bold text-red-600 pl-1">{error}</p>}
  </div>
);

const QUILL_STYLES = `.rich-text-field .ql-toolbar.ql-snow{border:none;border-bottom:1px solid #f1f5f9;background:#fff;padding:12px 20px}.rich-text-field .ql-container.ql-snow{border:none;font-family:inherit;font-size:14px;min-height:150px}.rich-text-field .ql-editor{padding:20px;color:#1e293b;line-height:1.6}.rich-text-field .ql-editor.ql-blank::before{color:#94a3b8;font-style:normal;left:20px}`;

const TextAreaField = ({ label, value, onChange }) => (
  <div className="space-y-3 rich-text-field">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <div className="bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] overflow-hidden focus-within:border-red-500 transition-all shadow-sm">
      <ReactQuill theme="snow" value={value} onChange={onChange}
        modules={{ toolbar: [[{ header: [1,2,false] }],['bold','italic','underline','strike'],[{ list:'ordered'},{ list:'bullet'}],['clean']] }}
        className="bg-transparent"
      />
    </div>
  </div>
);

const SelectField = ({ label, options, value, onChange, placeholder, error }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <div className={`relative flex items-center bg-slate-50 border-2 rounded-xl transition-all shadow-sm min-h-[64px] ${error ? 'border-red-400 focus-within:border-red-500 bg-red-50/40' : 'border-slate-100 focus-within:border-slate-300 focus-within:bg-white'}`}>
      <select value={value} onChange={e => onChange(e.target.value)} className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none">
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((o, i) => <option key={i} value={o}>{o}</option>)}
      </select>
      <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90"/></div>
    </div>
    {error && <p className="text-[10px] font-bold text-red-600 pl-1">{error}</p>}
  </div>
);

const MiniCarousel = ({ images = [], alt = '' }) => {
  const [idx, setIdx] = useState(0);
  const count = images.length;
  const fallback = 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=100';

  useEffect(() => {
    if (count <= 1) return;
    const t = setInterval(() => setIdx(i => (i + 1) % count), 3000);
    return () => clearInterval(t);
  }, [count]);

  const go = (e, dir) => { e.stopPropagation(); setIdx(i => (i + dir + count) % count); };

  if (!count) return (
    <div className="w-full h-full flex items-center justify-center bg-slate-100">
      <ImageIcon size={16} className="text-slate-300"/>
    </div>
  );

  return (
    <div className="relative w-full h-full group/mc">
      {images.map((src, i) => (
        <img key={i} src={src} alt={alt}
             className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-500 ${i === idx ? 'opacity-100' : 'opacity-0'}`}
             onError={e => { e.target.onerror = null; e.target.src = fallback; }}/>
      ))}
      {count > 1 && <>
        <button onClick={e => go(e, -1)}
                className="absolute left-0.5 top-1/2 -translate-y-1/2 bg-black/60 text-white rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover/mc:opacity-100 transition-opacity z-10">
          <ChevronLeft size={11}/>
        </button>
        <button onClick={e => go(e, 1)}
                className="absolute right-0.5 top-1/2 -translate-y-1/2 bg-black/60 text-white rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover/mc:opacity-100 transition-opacity z-10">
          <ChevronRight size={11}/>
        </button>
        <div className="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-0.5 z-10">
          {images.map((_, i) => (
            <div key={i} className={`rounded-full transition-all duration-300 ${i === idx ? 'w-2 h-1.5 bg-white' : 'w-1.5 h-1.5 bg-white/50'}`}/>
          ))}
        </div>
      </>}
    </div>
  );
};

const SortableImage = ({ img, i, onRemove }) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging
  } = useSortable({ id: img });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: isDragging ? 100 : 'auto',
    opacity: isDragging ? 0.3 : 1,
  };

  return (
    <div 
      ref={setNodeRef} 
      style={style} 
      className="aspect-[4/3] bg-slate-50 rounded-[1.5rem] overflow-hidden relative shadow-md group border-2 border-transparent hover:border-red-500 transition-all cursor-grab active:cursor-grabbing"
    >
      <div {...attributes} {...listeners} className="absolute inset-0 z-10" />
      <button 
        onClick={e => { e.stopPropagation(); onRemove(i); }} 
        className="absolute top-2 right-2 bg-red-600 text-white p-1.5 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-20 hover:bg-red-700"
      >
        <X size={14}/>
      </button>
      <img 
        src={img} 
        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000" 
        alt={`Image ${i+1}`} 
        onError={e => { e.target.onerror=null; e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}
      />
      {i === 0 && (
        <div className="absolute bottom-3 left-3 bg-red-600 text-white text-[8px] font-black px-3 py-1.5 rounded uppercase tracking-widest shadow-xl z-20">
          MASTER PHOTO
        </div>
      )}
    </div>
  );
};

const MediaSection = ({ title, images, onAddFiles, onRemove, onReorder }) => {
  const ref = React.useRef(null);
  
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const handleDragEnd = (event) => {
    const { active, over } = event;
    if (over && active.id !== over.id) {
      const oldIndex = images.indexOf(active.id);
      const newIndex = images.indexOf(over.id);
      onReorder(oldIndex, newIndex);
    }
  };

  return (
    <div className="bg-white rounded-[2rem] p-4 sm:p-6 lg:p-10 shadow-xl border border-slate-200/60">
      <div className="flex justify-between items-center mb-6 sm:mb-10">
        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2">
          <ImageIcon size={14} className="text-red-600"/>{title}
        </h3>
        <span className="hidden sm:block bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-4 py-2 rounded-full border border-slate-100 tracking-widest shadow-sm">
          Drag to Reorder
        </span>
      </div>
      
      <input 
        type="file" 
        multiple 
        accept="image/*" 
        className="hidden" 
        ref={ref} 
        onChange={e => { if (e.target.files?.length) onAddFiles(Array.from(e.target.files)); e.target.value = null; }}
      />

      <DndContext 
        sensors={sensors}
        collisionDetection={closestCenter}
        onDragEnd={handleDragEnd}
      >
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
          <SortableContext 
            items={images || []}
            strategy={rectSortingStrategy}
          >
            {images && images.map((img, i) => (
              <SortableImage key={img} img={img} i={i} onRemove={onRemove} />
            ))}
          </SortableContext>
          
          <div 
            onClick={() => ref.current?.click()} 
            className="aspect-[4/3] border-2 border-dashed border-slate-200 rounded-[1.5rem] flex flex-col items-center justify-center text-slate-300 hover:text-red-600 hover:bg-red-50/20 transition-all cursor-pointer bg-white group"
          >
            <div className="bg-white p-3 rounded-full shadow-lg mb-2 border border-slate-50 group-hover:scale-110 transition-transform">
              <Plus size={28}/>
            </div>
            <span className="text-[9px] font-black uppercase tracking-[0.2em]">Add Images</span>
          </div>
        </div>
      </DndContext>
    </div>
  );
};

const AttachmentsSection = ({ attachments = [], onAdd, onChange, onRemove, onImageUpload }) => {
  const ref = React.useRef(null);
  const [editingIndex, setEditingIndex] = React.useState(null);

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
              <img src={imp.image} className="w-full h-full object-cover" onError={e => { e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}/>
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

const METRIC_COLORS = {
  blue:  { text: 'bg-blue-50 text-blue-600',  bg: 'bg-blue-50'  },
  red:   { text: 'bg-red-50 text-red-600',    bg: 'bg-red-50'   },
  green: { text: 'bg-green-50 text-green-600',bg: 'bg-green-50' },
  amber: { text: 'bg-amber-50 text-amber-600',bg: 'bg-amber-50' },
};

const MetricCard = ({ icon, label, value, subtext, color }) => {
  const c = METRIC_COLORS[color];
  return (
    <div className="rounded-[2rem] p-5 sm:p-8 border bg-white border-slate-200/60 shadow-xl relative overflow-hidden group transition-all">
      <div className="flex items-center gap-3 sm:gap-4 mb-5 sm:mb-8 relative z-10">
        <div className={`p-3 sm:p-4 rounded-xl ${c.text} shadow-md group-hover:scale-110 transition-transform`}>{icon}</div>
        <h4 className="font-black text-[10px] uppercase tracking-widest text-slate-400 leading-none">{label}</h4>
      </div>
      <p className="text-4xl sm:text-5xl font-black text-slate-950 mb-3 tracking-tighter relative z-10 leading-none">{value}</p>
      <p className={`text-[10px] font-black uppercase tracking-[0.1em] relative z-10 ${c.text}`}>{subtext}</p>
      <div className={`absolute -right-6 -bottom-6 w-32 h-32 rounded-full opacity-10 ${c.bg} group-hover:scale-150 transition-transform duration-700`}></div>
    </div>
  );
};

const QuickActions = ({ onAdd }) => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2"><Zap size={14} className="text-red-600"/>Quick Operations</h4>
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button onClick={onAdd} className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Plus size={20} className="text-red-600"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Add Unit</span>
      </button>
      <a href="/wp-admin/admin.php?page=pmxi-admin-import" className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Upload size={20} className="text-slate-700"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Import Inventory</span>
      </a>
      <a href="/wp-admin/admin.php?page=pmxe-admin-manage" className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Download size={20} className="text-slate-700"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Export Inventory</span>
      </a>
    </div>
  </div>
);

const RecentActivity = () => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2"><History size={14} className="text-blue-600"/>Activity Stream</h4>
    <div className="space-y-6">
      <ActivityItem icon={<CheckCircle2 size={14}/>} title="Database Connected" desc="Inventory loading from WordPress" time="Live" color="green"/>
    </div>
  </div>
);

const ActivityItem = ({ icon, title, desc, time, color }) => {
  const colors = { green:'text-green-600 bg-green-50', blue:'text-blue-600 bg-blue-50', red:'text-red-600 bg-red-50' };
  return (
    <div className="flex gap-4">
      <div className={`mt-1 p-2 rounded-lg ${colors[color]} h-fit`}>{icon}</div>
      <div className="flex-1 border-b border-slate-50 pb-4 last:border-0">
        <div className="flex justify-between items-start mb-1">
          <h5 className="text-[11px] font-black uppercase tracking-tight text-slate-900">{title}</h5>
          <span className="text-[9px] font-bold text-slate-400 uppercase">{time}</span>
        </div>
        <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wide">{desc}</p>
      </div>
    </div>
  );
};

const MarketplaceTab = () => (
  <div className="space-y-8 animate-in fade-in duration-500 text-slate-950 font-black">
    <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-12 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
      <div className="relative z-10"><h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">Meta Commerce Engine</h3><p className="text-white font-bold opacity-90 uppercase tracking-[0.3em] text-[10px]">API Health: Connected</p></div>
      <Facebook size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
    </div>
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">
      <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-5 sm:p-10 shadow-2xl border border-slate-200/60">
        <div className="flex items-center gap-4 mb-10 border-b border-slate-50 pb-6"><List size={22} className="text-blue-600"/><h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Sync Activity Logs</h4></div>
        <div className="space-y-2">
          {['Price Sync: Mahindra 2638 HST','New Media: Big Tex 14LP Dump','Inventory Update checked','Lead Captured: Marketplace Messenger','Batch Update: Compact Tractors','API Handshake: Success'].map((msg, i) => (
            <div key={i} className="flex justify-between items-center p-6 bg-slate-50/50 rounded-2xl border-2 border-white mb-4 hover:bg-white transition-all shadow-sm group">
              <div className="flex items-center gap-6"><div className="p-2.5 bg-green-100 rounded-xl"><CheckCircle2 size={20} className="text-green-600"/></div><span className="text-base font-black tracking-tight leading-none">{msg}</span></div>
              <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{['2m','14m','1h','3h','5h','12h'][i]} ago</span>
            </div>
          ))}
        </div>
      </div>
      <div className="space-y-8">
        <div className="bg-white rounded-[2.5rem] p-10 shadow-2xl border border-slate-200/60">
          <div className="flex items-center gap-4 mb-8"><BarChart3 size={22} className="text-blue-600"/><h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Distribution Health</h4></div>
          <div className="space-y-6">
            {[['Catalog Match Rate','98%','blue'],['Image Optimization','100%','green'],['Sync Latency','1.2s','blue']].map(([l,v,c]) => (
              <div key={l} className="space-y-2">
                <div className="flex justify-between"><span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{l}</span><span className="text-[10px] font-black text-slate-900 uppercase">{v}</span></div>
                <div className="h-1.5 w-full bg-slate-50 rounded-full overflow-hidden border border-slate-100"><div className={`h-full ${c==='blue'?'bg-blue-600':'bg-green-600'} rounded-full`} style={{ width: v.includes('%') ? v : '100%' }}></div></div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  </div>
);

const MobileAccessTab = () => {
  const [token, setToken]           = useState(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [expiry, setExpiry]         = useState(null);
  const [qrUrl, setQrUrl]           = useState('');

  const generateToken = async () => {
    setIsGenerating(true);
    try {
      const data = await apiFetch('/mobile/token', { method: 'POST' });
      setToken(data.token);
      setQrUrl(data.url);
      setExpiry(new Date(Date.now() + data.expires_in * 1000).toLocaleTimeString());
    } catch (e) {
      alert('Failed to generate secure token: ' + e.message);
    } finally {
      setIsGenerating(false);
    }
  };

  return (
    <div className="space-y-8 animate-in fade-in slide-in-from-bottom-6 duration-700">
      <div className="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-12 text-white shadow-2xl relative overflow-hidden border border-slate-700">
        <div className="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 items-center">
          <div>
            <div className="inline-flex items-center gap-2 bg-blue-500/20 text-blue-400 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 border border-blue-500/30"><Smartphone size={14}/> Mobile Companion v2.0</div>
            <h2 className="text-3xl sm:text-5xl font-black tracking-tighter leading-[0.9] mb-4 sm:mb-6 uppercase text-white">Varner <span className="text-blue-500">Mobile</span> Companion</h2>
            <p className="text-slate-400 text-lg leading-relaxed mb-8 max-w-md font-medium">Provision mobile devices for field inventory audits, photo uploads, and real-time stock scanning.</p>
            {!token ? (
              <button onClick={generateToken} disabled={isGenerating} className="mt-4 bg-blue-600 hover:bg-blue-500 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-2xl flex items-center gap-3 active:scale-95 disabled:opacity-50">
                {isGenerating ? <Loader2 className="animate-spin"/> : <Zap size={18}/>} Generate Secure Access
              </button>
            ) : (
              <button onClick={() => setToken(null)} className="mt-4 text-slate-500 hover:text-white text-[10px] font-black uppercase tracking-widest">Revoke Token</button>
            )}
          </div>
          <div className="flex justify-center lg:justify-end">
            <div className={`bg-white p-8 rounded-[2.5rem] shadow-2xl transition-all duration-700 ${token ? 'scale-100 opacity-100' : 'scale-90 opacity-20 blur-sm'}`}>
              <div className="w-64 h-64 bg-slate-50 rounded-2xl flex items-center justify-center border-2 border-slate-100">
                {token ? <img src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrUrl)}`} className="w-full h-full p-4" alt="QR"/> : <div className="text-center p-8"><ScanText size={48} className="text-slate-200 mx-auto mb-4"/><p className="text-[10px] font-black uppercase tracking-widest text-slate-300">Token Required</p></div>}
              </div>
              {token && <div className="mt-6 text-center"><p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Secure Token</p><p className="text-xl font-mono font-black text-slate-900 tracking-wider">{token}</p><div className="mt-4 flex items-center justify-center gap-2 text-amber-500"><Clock size={12}/><span className="text-[9px] font-black uppercase tracking-widest">Expires at {expiry}</span></div></div>}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

const SettingsTab = ({ showToast }) => {
  const [settings, setSettings] = useState({
    hero_title: '',
    hero_subtitle: '',
    hero_button1_text: '',
    hero_button1_link: '',
    hero_button2_text: '',
    hero_button2_link: '',
    hero_video_url: '',
    support_hub_service_link: '',
    support_hub_parts_link: '',
    support_hub_finance_link: '',
    youtube_tagline: '',
    youtube_title: '',
    youtube_paragraph: '',
    youtube_channel_url: '',
    youtube_video_id: '',
    youtube_custom_thumbnail: '',
    cta_title: '',
    cta_text: '',
    cta_button_text: '',
    cta_button_link: '',
    about_why_choose_us_title: '',
    about_why_choose_us_bullets: [],
    contact_email: '',
    contact_phone: '',
    contact_phone_raw: '',
    contact_address_line1: '',
    contact_address_line2: '',
    contact_map_link: '',
    contact_map_embed_url: '',
    hours_mon_fri: '',
    hours_sat: '',
    hours_sun: '',
    social_facebook: '',
    social_youtube: '',
    social_custom_links: [],
    employment_tagline: '',
    employment_headline: '',
    employment_intro: '',
    employment_jobs: [],
  });

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isUploadingThumbnail, setIsUploadingThumbnail] = useState(false);
  const thumbnailInputRef = React.useRef(null);

  const [openSections, setOpenSections] = useState({
    hero: false,
    support: false,
    youtube: false,
    contact: false,
    hours: false,
    about: false,
    careers: false,
  });

  const toggleSection = (key) => {
    setOpenSections(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };

  const [previewUrl, setPreviewUrl] = useState('');

  useEffect(() => {
    const fetchSettings = async () => {
      setIsLoading(true);
      try {
        const data = await apiFetch('/settings');
        setSettings(data);
      } catch (err) {
        showToast('Failed to load settings: ' + err.message, 'error');
      } finally {
        setIsLoading(false);
      }
    };
    fetchSettings();
  }, [showToast]);

  useEffect(() => {
    if (!isLoading) {
      const baseUrl = window.varnerData?.site_url || '/';
      setPreviewUrl(`${baseUrl}?varner_preview=1`);
    }
  }, [isLoading]);

  useEffect(() => {
    if (isLoading) return;
    const timer = setTimeout(async () => {
      try {
        await apiFetch('/settings/preview', {
          method: 'POST',
          body: JSON.stringify(settings),
        });
        const baseUrl = window.varnerData?.site_url || '/';
        setPreviewUrl(`${baseUrl}?varner_preview=1&t=${Date.now()}`);
      } catch (err) {
        console.error('Failed to sync preview: ' + err.message);
      }
    }, 600);
    return () => clearTimeout(timer);
  }, [settings, isLoading]);

  const handleFieldChange = (key, value) => {
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handleThumbnailUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setIsUploadingThumbnail(true);
    try {
      const result = await uploadFile(file);
      handleFieldChange('youtube_custom_thumbnail', result.url);
      showToast('Custom thumbnail uploaded successfully!');
    } catch (err) {
      showToast('Thumbnail upload failed: ' + err.message, 'error');
    } finally {
      setIsUploadingThumbnail(false);
      if (thumbnailInputRef.current) thumbnailInputRef.current.value = null;
    }
  };

  const handleRemoveThumbnail = () => {
    handleFieldChange('youtube_custom_thumbnail', '');
    showToast('Custom thumbnail removed.');
  };

  const handleAddBullet = () => {
    handleFieldChange('about_why_choose_us_bullets', [
      ...settings.about_why_choose_us_bullets,
      ''
    ]);
  };

  const handleUpdateBullet = (index, value) => {
    const updated = [...settings.about_why_choose_us_bullets];
    updated[index] = value;
    handleFieldChange('about_why_choose_us_bullets', updated);
  };

  const handleRemoveBullet = (index) => {
    const updated = settings.about_why_choose_us_bullets.filter((_, i) => i !== index);
    handleFieldChange('about_why_choose_us_bullets', updated);
  };

  const handleMoveBulletUp = (index) => {
    if (index === 0) return;
    const updated = [...settings.about_why_choose_us_bullets];
    const temp = updated[index];
    updated[index] = updated[index - 1];
    updated[index - 1] = temp;
    handleFieldChange('about_why_choose_us_bullets', updated);
  };

  const handleMoveBulletDown = (index) => {
    if (index === settings.about_why_choose_us_bullets.length - 1) return;
    const updated = [...settings.about_why_choose_us_bullets];
    const temp = updated[index];
    updated[index] = updated[index + 1];
    updated[index + 1] = temp;
    handleFieldChange('about_why_choose_us_bullets', updated);
  };

  const handleAddSocialLink = () => {
    handleFieldChange('social_custom_links', [
      ...(settings.social_custom_links || []),
      { platform: 'facebook', url: '', label: '' }
    ]);
  };

  const handleUpdateSocialLink = (index, field, value) => {
    const updated = [...(settings.social_custom_links || [])];
    updated[index] = { ...updated[index], [field]: value };
    handleFieldChange('social_custom_links', updated);
  };

  const handleRemoveSocialLink = (index) => {
    const updated = (settings.social_custom_links || []).filter((_, i) => i !== index);
    handleFieldChange('social_custom_links', updated);
  };

  const handleSaveSettings = async () => {
    setIsSaving(true);
    try {
      const result = await apiFetch('/settings', {
        method: 'POST',
        body: JSON.stringify(settings),
      });
      if (result.success) {
        setSettings(result.settings);
        showToast('Settings saved successfully!');
      }
    } catch (err) {
      showToast('Failed to save settings: ' + err.message, 'error');
    } finally {
      setIsSaving(false);
    }
  };

  const scrollToEditorSection = (key) => {
    setOpenSections(prev => ({
      ...prev,
      [key]: true
    }));
    setTimeout(() => {
      const container = document.getElementById('settings-editor-container');
      const target = document.getElementById(`editor-section-${key}`);
      if (container && target) {
        const offsetPosition = target.offsetTop - container.offsetTop;
        container.scrollTo({
          top: offsetPosition - 10,
          behavior: 'smooth'
        });
      }
    }, 100);
  };

  if (isLoading) {
    return (
      <div className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">
        Loading configuration settings…
      </div>
    );
  }

  return (
    <div className="flex flex-col lg:flex-row gap-8 w-full max-w-[1600px] mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500 text-slate-900 pb-16">
      
      {/* LEFT COLUMN: Controls Panel */}
      <div 
        id="settings-editor-container" 
        className="w-full lg:w-[420px] xl:w-[480px] shrink-0 space-y-6 sm:space-y-8 lg:max-h-[calc(100vh-12rem)] lg:overflow-y-auto pr-2 no-scrollbar pb-24"
      >
        
        {/* 1. HERO SECTION */}
        <div id="editor-section-hero">
          <CollapsiblePanel
            title="Hero Section"
            icon={<Sparkles size={20} />}
            isOpen={openSections.hero}
            onToggle={() => toggleSection('hero')}
          >
            <div className="space-y-4">
              <TextAreaField
                label="Hero Title"
                value={settings.hero_title}
                onChange={v => handleFieldChange('hero_title', v)}
              />

              <TextAreaField
                label="Hero Subtitle"
                value={settings.hero_subtitle}
                onChange={v => handleFieldChange('hero_subtitle', v)}
              />

              <InputField
                label="Custom Hero Video URL (Optional)"
                value={settings.hero_video_url}
                onChange={v => handleFieldChange('hero_video_url', v)}
                placeholder="e.g. /wp-content/uploads/... or leave blank for default"
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Primary Button Text"
                  value={settings.hero_button1_text}
                  onChange={v => handleFieldChange('hero_button1_text', v)}
                />
                <InputField
                  label="Primary Button Link"
                  value={settings.hero_button1_link}
                  onChange={v => handleFieldChange('hero_button1_link', v)}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Secondary Button Text"
                  value={settings.hero_button2_text}
                  onChange={v => handleFieldChange('hero_button2_text', v)}
                />
                <InputField
                  label="Secondary Button Link"
                  value={settings.hero_button2_link}
                  onChange={v => handleFieldChange('hero_button2_link', v)}
                />
              </div>
            </div>
          </CollapsiblePanel>
        </div>



        {/* 3. YOUTUBE MEDIA SECTION */}
        <div id="editor-section-youtube">
          <CollapsiblePanel
            title="YouTube Media Section"
            icon={<ImageIcon size={20} />}
            isOpen={openSections.youtube}
            onToggle={() => toggleSection('youtube')}
          >
            <div className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="YouTube Tagline"
                  value={settings.youtube_tagline}
                  onChange={v => handleFieldChange('youtube_tagline', v)}
                />
                <InputField
                  label="YouTube Channel URL"
                  value={settings.youtube_channel_url}
                  onChange={v => handleFieldChange('youtube_channel_url', v)}
                />
              </div>

              <TextAreaField
                label="Section Title"
                value={settings.youtube_title}
                onChange={v => handleFieldChange('youtube_title', v)}
              />

              <TextAreaField
                label="Description Paragraph"
                value={settings.youtube_paragraph}
                onChange={v => handleFieldChange('youtube_paragraph', v)}
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                <InputField
                  label="YouTube Video ID (e.g. goF_3TspZ6k)"
                  value={settings.youtube_video_id}
                  onChange={v => handleFieldChange('youtube_video_id', v)}
                />

                <div>
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1 mb-2">YouTube Custom Thumbnail</label>
                  <input
                    type="file"
                    accept="image/*"
                    className="hidden"
                    ref={thumbnailInputRef}
                    onChange={handleThumbnailUpload}
                  />
                  <div className="flex gap-3">
                    <button
                      type="button"
                      onClick={() => thumbnailInputRef.current?.click()}
                      disabled={isUploadingThumbnail}
                      className="bg-slate-950 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all flex items-center gap-2"
                    >
                      {isUploadingThumbnail ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
                      Upload Custom Image
                    </button>
                    {settings.youtube_custom_thumbnail && (
                      <button
                        type="button"
                        onClick={handleRemoveThumbnail}
                        className="bg-red-50 text-red-600 border border-red-100 px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-red-100 transition-all flex items-center gap-2"
                      >
                        <Trash2 size={16} />
                        Revert To Default
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* YouTube Thumbnail Preview */}
              <div className="mt-4">
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 pl-1">Thumbnail Preview</p>
                <div className="relative aspect-video max-w-md bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-lg">
                  <img
                    src={settings.youtube_custom_thumbnail || `https://img.youtube.com/vi/${settings.youtube_video_id || 'goF_3TspZ6k'}/maxresdefault.jpg`}
                    alt="YouTube Thumbnail"
                    className="w-full h-full object-cover"
                    onError={e => { e.target.onerror = null; e.target.src = 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}
                  />
                  <div className="absolute inset-0 flex items-center justify-center bg-black/30 pointer-events-none">
                    <div className="w-16 h-16 bg-red-600 text-white rounded-full flex items-center justify-center shadow-2xl">
                      <PlayIcon className="ml-1 w-6 h-6 fill-current text-white" />
                    </div>
                  </div>
                  <div className="absolute bottom-3 left-3 bg-slate-900/90 text-white text-[8px] font-black px-2 py-1 rounded uppercase tracking-widest">
                    {settings.youtube_custom_thumbnail ? 'CUSTOM THUMBNAIL' : 'YOUTUBE DEFAULT'}
                  </div>
                </div>
              </div>
            </div>
          </CollapsiblePanel>
        </div>

        {/* 4. BUSINESS DETAILS & CONTACTS */}
        <div id="editor-section-contact">
          <CollapsiblePanel
            title="Business Details & Contacts"
            icon={<Mail size={20} />}
            isOpen={openSections.contact}
            onToggle={() => toggleSection('contact')}
          >
            <div className="space-y-4">
              <div>
                <InputField
                  label="Primary Notification Email Address"
                  value={settings.contact_email}
                  onChange={v => handleFieldChange('contact_email', v)}
                />
                <p className="text-[10px] font-bold text-slate-400 pl-1 mt-1.5 uppercase tracking-wide">
                  * Critical: This email receives all submissions from the frontend Chatbox, Contact Form, Parts Request, and Service Request forms.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Display Phone Number"
                  value={settings.contact_phone}
                  onChange={v => handleFieldChange('contact_phone', v)}
                  placeholder="e.g. (970) 874-0612"
                />
                <InputField
                  label="Raw Phone Digits (for calling links)"
                  value={settings.contact_phone_raw}
                  onChange={v => handleFieldChange('contact_phone_raw', v)}
                  placeholder="e.g. 9708740612"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Address Line 1"
                  value={settings.contact_address_line1}
                  onChange={v => handleFieldChange('contact_address_line1', v)}
                />
                <InputField
                  label="Address Line 2"
                  value={settings.contact_address_line2}
                  onChange={v => handleFieldChange('contact_address_line2', v)}
                />
              </div>

              <InputField
                label="Google Maps Navigation Directions URL"
                value={settings.contact_map_link}
                onChange={v => handleFieldChange('contact_map_link', v)}
              />

              <InputField
                label="Google Maps Embed Iframe URL"
                value={settings.contact_map_embed_url}
                onChange={v => handleFieldChange('contact_map_embed_url', v)}
              />
            </div>
          </CollapsiblePanel>
        </div>

        {/* 5. DEALER HOURS & SOCIALS */}
        <div id="editor-section-hours">
          <CollapsiblePanel
            title="Dealer Hours & Socials"
            icon={<Clock size={20} />}
            isOpen={openSections.hours}
            onToggle={() => toggleSection('hours')}
          >
            <div className="space-y-6">
              <div>
                <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Dealership Business Hours</h5>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <InputField
                    label="Monday - Friday Hours"
                    value={settings.hours_mon_fri}
                    onChange={v => handleFieldChange('hours_mon_fri', v)}
                  />
                  <InputField
                    label="Saturday Hours"
                    value={settings.hours_sat}
                    onChange={v => handleFieldChange('hours_sat', v)}
                  />
                  <InputField
                    label="Sunday Hours"
                    value={settings.hours_sun}
                    onChange={v => handleFieldChange('hours_sun', v)}
                  />
                </div>
              </div>

              <div>
                <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Social Media Profile Links</h5>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <InputField
                    label="Facebook Page URL"
                    value={settings.social_facebook}
                    onChange={v => handleFieldChange('social_facebook', v)}
                  />
                  <InputField
                    label="YouTube Channel URL"
                    value={settings.social_youtube}
                    onChange={v => handleFieldChange('social_youtube', v)}
                  />
                </div>
              </div>

              <div className="mt-6 pt-6 border-t border-slate-100">
                <div className="flex items-center justify-between mb-4">
                  <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Additional Social Links</h5>
                  <button
                    type="button"
                    onClick={handleAddSocialLink}
                    className="bg-slate-900 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-black transition-all flex items-center gap-1.5 active:scale-95 shadow-sm"
                  >
                    <Plus size={12} />
                    Add Link
                  </button>
                </div>
                
                <div className="space-y-4">
                  {settings.social_custom_links && settings.social_custom_links.map((link, idx) => (
                    <div key={idx} className="flex flex-col sm:flex-row gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 items-end relative">
                      <div className="flex-1 w-full grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="space-y-2">
                          <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">Platform</label>
                          <select
                            value={link.platform || 'custom'}
                            onChange={e => handleUpdateSocialLink(idx, 'platform', e.target.value)}
                            className="w-full bg-white border border-slate-200 rounded-xl p-3 font-bold text-slate-900 outline-none focus:border-red-500 transition-all text-xs"
                          >
                            <option value="facebook">Facebook</option>
                            <option value="youtube">YouTube</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">X / Twitter</option>
                            <option value="tiktok">TikTok</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="pinterest">Pinterest</option>
                            <option value="custom">Custom / Other</option>
                          </select>
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                          <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">URL Target</label>
                          <input
                            type="text"
                            value={link.url || ''}
                            onChange={e => handleUpdateSocialLink(idx, 'url', e.target.value)}
                            placeholder="e.g. https://instagram.com/mydealership"
                            className="w-full bg-white border border-slate-200 rounded-xl p-3 font-bold text-slate-900 outline-none focus:border-red-500 transition-all text-xs"
                          />
                        </div>
                      </div>
                      <button
                        type="button"
                        onClick={() => handleRemoveSocialLink(idx)}
                        className="bg-red-50 text-red-600 border border-red-100 p-3 rounded-xl hover:bg-red-100 transition-all active:scale-95 flex items-center justify-center shrink-0 w-full sm:w-auto h-[46px]"
                        title="Remove Link"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  ))}

                  {(!settings.social_custom_links || settings.social_custom_links.length === 0) && (
                    <p className="text-[10px] text-slate-400 font-bold uppercase tracking-widest text-center py-4 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200/60">
                      No additional social links configured
                    </p>
                  )}
                </div>
              </div>
            </div>
          </CollapsiblePanel>
        </div>



        {/* 7. EMPLOYMENT & CAREERS */}
        <div id="editor-section-careers">
          <CollapsiblePanel
            title="Employment & Careers"
            icon={<Briefcase size={20} />}
            isOpen={openSections.careers}
            onToggle={() => toggleSection('careers')}
          >
            <div className="space-y-6">
              <InputField
                label="Careers Page Tagline"
                value={settings.employment_tagline}
                onChange={v => handleFieldChange('employment_tagline', v)}
              />

              <InputField
                label="Careers Page Headline"
                value={settings.employment_headline}
                onChange={v => handleFieldChange('employment_headline', v)}
              />

              <TextAreaField
                label="Careers Page Introduction Text"
                value={settings.employment_intro}
                onChange={v => handleFieldChange('employment_intro', v)}
              />

              <div className="border-t border-slate-100 pt-6">
                <div className="flex justify-between items-center mb-4">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Current Job Openings</label>
                  <span className="bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-3 py-1.5 rounded-full border border-slate-100 tracking-widest shadow-sm">
                    {settings.employment_jobs ? settings.employment_jobs.length : 0} Openings
                  </span>
                </div>

                <div className="space-y-4">
                  {settings.employment_jobs && settings.employment_jobs.map((job, idx) => (
                    <div key={idx} className="bg-slate-50 rounded-[1.5rem] p-6 border-2 border-slate-100 space-y-4 relative group">
                      <div className="absolute top-4 right-4 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                          type="button"
                          onClick={() => {
                            if (idx === 0) return;
                            const updated = [...settings.employment_jobs];
                            const temp = updated[idx];
                            updated[idx] = updated[idx - 1];
                            updated[idx - 1] = temp;
                            handleFieldChange('employment_jobs', updated);
                          }}
                          disabled={idx === 0}
                          className="bg-white border border-slate-200 text-slate-400 p-2 rounded-xl hover:text-slate-900 disabled:opacity-30"
                          title="Move Up"
                        >
                          <ChevronUp size={14} />
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            if (idx === settings.employment_jobs.length - 1) return;
                            const updated = [...settings.employment_jobs];
                            const temp = updated[idx];
                            updated[idx] = updated[idx + 1];
                            updated[idx + 1] = temp;
                            handleFieldChange('employment_jobs', updated);
                          }}
                          disabled={idx === settings.employment_jobs.length - 1}
                          className="bg-white border border-slate-200 text-slate-400 p-2 rounded-xl hover:text-slate-900 disabled:opacity-30"
                          title="Move Down"
                        >
                          <ChevronDown size={14} />
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            const updated = settings.employment_jobs.filter((_, i) => i !== idx);
                            handleFieldChange('employment_jobs', updated);
                          }}
                          className="bg-white border border-slate-200 text-red-600 p-2 rounded-xl hover:bg-red-50 hover:border-red-100"
                          title="Delete Job Opening"
                        >
                          <Trash2 size={14} />
                        </button>
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div className="sm:col-span-2">
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Job Title</label>
                          <input
                            type="text"
                            placeholder="e.g. Heavy Equipment Mechanic"
                            value={job.job_title || ''}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_title: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                          />
                        </div>

                        <div>
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Job Type</label>
                          <select
                            value={job.job_type || 'Full-Time'}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_type: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm appearance-none cursor-pointer"
                          >
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                          </select>
                        </div>

                        <div>
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Location</label>
                          <input
                            type="text"
                            placeholder="e.g. Delta, CO"
                            value={job.job_location || ''}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_location: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                          />
                        </div>

                        <div className="sm:col-span-2">
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Job Description</label>
                          <textarea
                            placeholder="Detail job requirements, responsibilities, and benefits..."
                            value={job.job_description || ''}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_description: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm h-24 resize-none"
                          />
                        </div>

                        <div className="sm:col-span-2 flex items-center gap-4 bg-white p-4 rounded-xl border border-slate-150">
                          <label className="flex items-center gap-2 cursor-pointer select-none">
                            <input
                              type="checkbox"
                              checked={!!job.job_show_badge}
                              onChange={e => {
                                const updated = [...settings.employment_jobs];
                                updated[idx] = { ...updated[idx], job_show_badge: e.target.checked };
                                handleFieldChange('employment_jobs', updated);
                              }}
                              className="w-4 h-4 accent-red-600 cursor-pointer"
                            />
                            <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Show Status Badge</span>
                          </label>
                          
                          {job.job_show_badge && (
                            <input
                              type="text"
                              placeholder="e.g. Urgently Hiring"
                              value={job.job_badge_text || ''}
                              onChange={e => {
                                const updated = [...settings.employment_jobs];
                                updated[idx] = { ...updated[idx], job_badge_text: e.target.value };
                                handleFieldChange('employment_jobs', updated);
                              }}
                              className="flex-1 bg-slate-50 border-2 border-slate-100 rounded-lg p-2 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-xs"
                            />
                          )}
                        </div>
                      </div>
                    </div>
                  ))}

                  {(!settings.employment_jobs || settings.employment_jobs.length === 0) && (
                    <div className="p-12 text-center border-2 border-dashed border-slate-100 rounded-3xl text-slate-400 uppercase text-[10px] font-black tracking-widest">
                      No job openings listed.
                    </div>
                  )}
                </div>

                <button
                  type="button"
                  onClick={() => {
                    const jobs = settings.employment_jobs || [];
                    handleFieldChange('employment_jobs', [
                      ...jobs,
                      {
                        job_title: '',
                        job_type: 'Full-Time',
                        job_location: 'Delta, CO',
                        job_description: '',
                        job_show_badge: false,
                        job_badge_text: 'Urgently Hiring'
                      }
                    ]);
                  }}
                  className="mt-4 w-full py-4 border-2 border-dashed border-slate-200 rounded-[1.5rem] text-slate-400 font-black uppercase tracking-widest text-[10px] hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center gap-2"
                >
                  <Plus size={16} /> Add Job Opening
                </button>
              </div>
            </div>
          </CollapsiblePanel>
        </div>

        {/* STATIONARY SAVE CONFIGURATION BAR */}
        <div className="bg-white rounded-[2rem] p-6 border border-slate-200/60 flex items-center justify-between mt-8 shadow-xl">
          <div className="hidden sm:block">
            <h4 className="font-black text-xs uppercase tracking-tight text-slate-900">Save Configuration</h4>
            <p className="text-[9px] text-slate-400 font-bold uppercase mt-0.5">Saves all theme settings live.</p>
          </div>
          <button
            onClick={handleSaveSettings}
            disabled={isSaving}
            className="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-10 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.25em] flex items-center justify-center gap-3 active:scale-95 transition-all border-b-4 border-red-800 disabled:opacity-50 shadow-xl shadow-red-200"
          >
            {isSaving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
            {isSaving ? 'SAVING CHANGES…' : 'SAVE CONFIGURATION'}
          </button>
        </div>

      </div>

      {/* RIGHT COLUMN: Live Visual Preview Simulator */}
      <div className="flex-1 hidden lg:flex flex-col bg-slate-900 border border-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden h-[calc(100vh-12rem)] sticky top-4">
        {/* Browser Header Bar */}
        <div className="flex gap-1.5 px-6 py-4 bg-slate-950 items-center border-b border-slate-900 shrink-0">
          <div className="flex gap-1.5">
            <span className="w-3 h-3 rounded-full bg-[#ff5f56]" />
            <span className="w-3 h-3 rounded-full bg-[#ffbd2e]" />
            <span className="w-3 h-3 rounded-full bg-[#27c93f]" />
          </div>
          <div className="flex-1 flex justify-center max-w-md mx-auto">
            <div className="bg-slate-900 border border-slate-800 text-slate-400 px-4 py-1.5 rounded-xl text-[10px] font-mono select-none flex items-center gap-2 w-full justify-center">
              <svg className="w-3 h-3 text-green-500 fill-current" viewBox="0 0 24 24">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
              </svg>
              varnerequipment.com/preview
            </div>
          </div>
          <div className="w-16"></div>
        </div>
        {/* Live Website Preview Frame (Iframe) */}
        <div className="flex-1 bg-slate-950 relative overflow-hidden">
          {previewUrl ? (
            <iframe 
              src={previewUrl} 
              className="w-full h-full border-none bg-white"
              title="Varner Equipment Site Preview"
            />
          ) : (
            <div className="w-full h-full flex flex-col items-center justify-center text-slate-500 gap-3">
              <Loader2 className="animate-spin text-red-600" size={24} />
              <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">Loading site preview...</span>
            </div>
          )}
        </div>
      </div>

    </div>
  );
};

const CollapsiblePanel = ({ title, icon, isOpen, onToggle, children }) => (
  <div className="bg-white rounded-[2rem] shadow-xl border border-slate-200/60 overflow-hidden transition-all duration-300">
    <button
      onClick={onToggle}
      className="w-full flex items-center justify-between p-6 sm:p-8 text-left hover:bg-slate-50/50 transition-colors"
    >
      <div className="flex items-center gap-4">
        <div className="bg-red-50 text-red-600 p-3 rounded-2xl">
          {icon}
        </div>
        <div>
          <h4 className="font-black text-base uppercase tracking-tight text-slate-900">{title}</h4>
        </div>
      </div>
      <span className="text-slate-400 font-black text-lg select-none mr-2">
        {isOpen ? '−' : '+'}
      </span>
    </button>
    {isOpen && (
      <div className="p-6 sm:p-8 border-t border-slate-100 bg-white space-y-6">
        {children}
      </div>
    )}
  </div>
);

const PlayIcon = ({ className }) => (
  <svg className={className} viewBox="0 0 24 24">
    <path d="M8 5v14l11-7z" fill="currentColor" />
  </svg>
);

const FBPreviewModal = ({ unitData, onClose }) => (
  <div className="fixed inset-0 bg-slate-950/90 backdrop-blur-xl z-50 flex items-center justify-center p-8">
    <div className="bg-white w-full max-w-[420px] rounded-[3.5rem] overflow-hidden shadow-2xl border-[12px] border-slate-950 relative h-[85vh] flex flex-col animate-in zoom-in duration-300">
      <div className="p-8 bg-white flex justify-between border-b items-center pt-10">
        <span className="font-black text-[11px] uppercase text-blue-600 flex items-center gap-3 tracking-[0.2em]"><Facebook size={20} fill="currentColor"/> Meta Marketplace Preview</span>
        <button onClick={onClose} className="p-2 hover:bg-slate-100 rounded-full"><X size={24} className="text-slate-400"/></button>
      </div>
      <div className="flex-1 overflow-y-auto no-scrollbar pb-12">
        <div className="aspect-[4/3] bg-slate-100 relative overflow-hidden">
          {unitData.images?.length > 0 ? <img src={unitData.images[0]} className="w-full h-full object-cover" onError={e => { e.target.onerror=null; e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}/> : <div className="w-full h-full flex items-center justify-center text-slate-200"><ImageIcon size={64}/></div>}
        </div>
        <div className="p-8 space-y-8">
          <div className="text-slate-900"><h2 className="text-4xl font-black leading-none mb-2 tracking-tighter">${parseInt(unitData.price||0).toLocaleString()}</h2><h3 className="text-2xl font-bold text-slate-800 leading-tight mb-2 tracking-tight">{unitData.year} {unitData.title}</h3><p className="text-slate-400 text-sm font-black uppercase tracking-widest">Delta, CO · Posted now</p></div>
          <div className="flex gap-3"><button className="flex-1 bg-[#0866FF] text-white py-4 rounded-[1.25rem] font-black text-sm shadow-xl">Message</button><button className="p-4 bg-slate-100 rounded-[1.25rem] text-slate-600"><Plus size={24}/></button></div>
          <div className="pt-8 border-t border-slate-100 text-slate-900">
            <h4 className="font-black text-[12px] uppercase text-slate-400 mb-5 tracking-[0.3em]">Description</h4>
            <div className="text-[16px] text-slate-800 font-medium leading-relaxed rich-text-content" dangerouslySetInnerHTML={{ __html: unitData.description }}/>
          </div>
        </div>
      </div>
      <div className="p-8 bg-slate-50 border-t border-slate-200 shadow-inner"><button onClick={onClose} className="w-full py-5 bg-slate-950 text-white font-black uppercase tracking-[0.4em] text-[11px] rounded-3xl hover:bg-black transition-all">Close Simulator</button></div>
    </div>
  </div>
);

const HistoryTab = ({ deletedItems, onRestore, onPermanentDelete }) => (
  <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-500">
    <div className="p-5 sm:p-8 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4">
      <div><h3 className="text-lg sm:text-xl font-black uppercase tracking-tight leading-none">Recycle Bin</h3><p className="text-slate-400 font-black uppercase text-[9px] tracking-[0.3em] mt-2 italic hidden sm:block">Items stay here until permanently deleted</p></div>
      <div className="bg-amber-100 text-amber-700 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2"><AlertCircle size={14}/> {deletedItems.length} Items</div>
    </div>
    <div className="overflow-x-auto p-2 no-scrollbar">
      <table className="w-full text-left border-collapse min-w-[700px]">
        <thead><tr className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50"><th className="px-6 py-5">STOCK #</th><th className="px-6 py-5">EQUIPMENT</th><th className="px-6 py-5">DELETED ON</th><th className="px-6 py-5 text-right">ACTIONS</th></tr></thead>
        <tbody className="divide-y divide-slate-50">
          {deletedItems.length === 0 ? (
            <tr><td colSpan="4" className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">Recycle bin is empty</td></tr>
          ) : deletedItems.map(item => (
            <tr key={item.id} className="hover:bg-slate-50 transition-all group">
              <td className="px-6 py-5 font-mono font-bold text-sm text-slate-400">{item.stock}</td>
              <td className="px-6 py-5"><p className="font-black text-slate-400 text-base uppercase leading-tight">{item.year} {item.make} {item.model}</p><p className="text-[9px] font-black uppercase tracking-widest mt-1 text-slate-300">{item.category}</p></td>
              <td className="px-6 py-5"><p className="text-[11px] font-black text-slate-400 uppercase">{item.deletedAt ? new Date(item.deletedAt).toLocaleDateString() : '—'}</p></td>
              <td className="px-6 py-5 text-right">
                <div className="flex items-center justify-end gap-3">
                  <button onClick={() => onRestore(item)} className="bg-green-50 text-green-600 px-4 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-green-100 transition-all active:scale-95 border border-green-100"><RotateCcw size={14}/> Restore</button>
                  <button onClick={() => onPermanentDelete(item)} className="bg-slate-100 text-slate-400 p-2.5 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all active:scale-95 border border-slate-200" title="Permanently Delete"><Trash2 size={16}/></button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  </div>
);

const FilterSidebar = ({ inventoryList, filters, searchQuery, onFilterChange, onKeywordSearch, onClearAll, horizontal = false }) => {
  const [sections, setSections] = React.useState({
    listingType: true, category: true, manufacturer: true,
    model: true, year: true, price: true, condition: false,
  });
  const [showAllMakes, setShowAllMakes] = React.useState(false);
  const [showAllModels, setShowAllModels] = React.useState(false);
  const [yearInput, setYearInput] = React.useState({ min: filters.yearMin || '', max: filters.yearMax || '' });
  const [priceInput, setPriceInput] = React.useState({ min: filters.priceMin || '', max: filters.priceMax || '' });
  const [kwInput, setKwInput] = React.useState(searchQuery || '');

  React.useEffect(() => { setKwInput(searchQuery || ''); }, [searchQuery]);
  React.useEffect(() => { setYearInput({ min: filters.yearMin || '', max: filters.yearMax || '' }); }, [filters.yearMin, filters.yearMax]);
  React.useEffect(() => { setPriceInput({ min: filters.priceMin || '', max: filters.priceMax || '' }); }, [filters.priceMin, filters.priceMax]);

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

  const appliedTags = [
    ...(searchQuery ? [{ label: searchQuery, onRemove: () => { onKeywordSearch(''); setKwInput(''); } }] : []),
    ...filters.makes.map(v => ({ label: v, onRemove: () => toggleArr('makes', v) })),
    ...filters.status.map(v => ({ label: v, onRemove: () => toggleArr('status', v) })),
    ...filters.categories.map(v => ({ label: v, onRemove: () => toggleArr('categories', v) })),
    ...filters.models.map(v => ({ label: v, onRemove: () => toggleArr('models', v) })),
    ...filters.conditions.map(v => ({ label: v, onRemove: () => toggleArr('conditions', v) })),
    ...(filters.yearMin || filters.yearMax ? [{ label: `Year: ${filters.yearMin || '?'}-${filters.yearMax || '?'}`, onRemove: () => { onFilterChange('yearMin', ''); onFilterChange('yearMax', ''); } }] : []),
    ...(filters.priceMin || filters.priceMax ? [{ label: `Price: $${filters.priceMin || '0'}-$${filters.priceMax || '∞'}`, onRemove: () => { onFilterChange('priceMin', ''); onFilterChange('priceMax', ''); } }] : []),
    ...(filters.stockSearch ? [{ label: `Stock #: ${filters.stockSearch}`, onRemove: () => onFilterChange('stockSearch', '') }] : []),
    ...(filters.vinSearch   ? [{ label: `VIN: ${filters.vinSearch}`,       onRemove: () => onFilterChange('vinSearch', '')   }] : []),
  ];

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
            <select 
              value={filters.status[0] || ""} 
              onChange={e => onFilterChange('status', e.target.value ? [e.target.value] : [])}
              className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all appearance-none cursor-pointer"
            >
              <option value="">All Statuses</option>
              {allStatuses.map(s => <option key={s} value={s}>{s} ({countOf('status', s)})</option>)}
            </select>
          </div>

          {/* Category Dropdown */}
          <div className="w-56">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Category</p>
            <select 
              value={filters.categories[0] || ""} 
              onChange={e => onFilterChange('categories', e.target.value ? [e.target.value] : [])}
              className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all appearance-none cursor-pointer"
            >
              <option value="">All Categories</option>
              {allCategories.map(cat => <option key={cat} value={cat}>{cat} ({countOf('category', cat)})</option>)}
            </select>
          </div>

          {/* Manufacturer Dropdown */}
          <div className="w-56">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Manufacturer</p>
            <select 
              value={filters.makes[0] || ""} 
              onChange={e => onFilterChange('makes', e.target.value ? [e.target.value] : [])}
              className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm font-bold focus:bg-white focus:border-red-500 outline-none transition-all appearance-none cursor-pointer"
            >
              <option value="">All Brands</option>
              {sortedMakes.map(make => <option key={make} value={make}>{make} ({makeCounts[make]})</option>)}
            </select>
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

const ConfigurationTab = ({ 
  showToast, 
  currentUser, 
  sessionList, 
  isLoading, 
  loadSessions, 
  activityList, 
  isActivityLoading, 
  loadActivity,
  onNav,
  handleFullEdit 
}) => {
  const [activeSubTab, setActiveSubTab] = useState('active-sessions');
  const [expandedActivityId, setExpandedActivityId] = useState(null);

  return (
    <div className="space-y-6 sm:space-y-8 animate-in fade-in duration-500 text-slate-900 pb-16">
      
      {/* HEADER WELCOME BANNER */}
      <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-10 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10">
          <h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">System Settings & Audit</h3>
          <p className="text-indigo-400 font-bold uppercase tracking-[0.3em] text-[10px]">
            Current User ID: {currentUser ? `#${currentUser.id}` : 'Loading...'}
          </p>
        </div>
        <Sliders size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* TWO-COLUMN GRID */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        
        {/* LEFT COLUMN: USER PROFILE DETAILS */}
        <div className="bg-white rounded-[2rem] p-6 sm:p-8 shadow-2xl border border-slate-200/60 flex flex-col justify-between">
          <div>
            <div className="flex items-center gap-4 mb-8 border-b border-slate-50 pb-6">
              <Users size={22} className="text-indigo-600"/>
              <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">User Profile</h4>
            </div>
            
            <div className="flex flex-col items-center text-center space-y-4 mb-8">
              <div className="w-20 h-20 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-black shadow-lg">
                {currentUser?.initials || '?'}
              </div>
              <div>
                <h2 className="text-lg font-black text-slate-900 leading-tight">{currentUser?.display_name || 'Loading...'}</h2>
                <p className="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">Logged In User</p>
              </div>
            </div>

            <div className="space-y-4 border-t border-slate-100 pt-6">
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">User ID</span>
                <span className="font-black text-slate-800">{currentUser?.id || '—'}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Role</span>
                <div className="flex gap-1">
                  {currentUser?.roles ? (
                    currentUser.roles.map(role => (
                      <span key={role} className="bg-indigo-50 text-indigo-700 text-[9px] font-black uppercase px-2 py-0.5 rounded tracking-wider border border-indigo-100">
                        {role}
                      </span>
                    ))
                  ) : (
                    <span className="text-slate-400 font-black text-xs">—</span>
                  )}
                </div>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">First Name</span>
                <span className="font-black text-slate-800">{currentUser?.first_name || '—'}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Last Name</span>
                <span className="font-black text-slate-800">{currentUser?.last_name || '—'}</span>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT COLUMN: SECURITY & AUDIT LOG */}
        <div className="lg:col-span-2 bg-white rounded-[2rem] p-6 sm:p-8 shadow-2xl border border-slate-200/60 flex flex-col">
          <div className="flex items-center justify-between mb-6 border-b border-slate-50 pb-6 gap-4">
            <div className="flex items-center gap-4 min-w-0">
              <ShieldCheck size={22} className="text-indigo-600 shrink-0"/>
              <div className="min-w-0">
                <h4 className="font-black text-xs uppercase tracking-widest text-slate-900 truncate">
                  {activeSubTab === 'active-sessions' ? 'Active Logged In Users' : activeSubTab === 'all-sessions' ? 'Security & Session Audits' : 'User Activity Feed'}
                </h4>
                <p className="text-[9px] text-slate-400 font-bold mt-0.5 truncate">
                  {activeSubTab === 'active-sessions' ? 'Users currently connected to the Varner OS console' : activeSubTab === 'all-sessions' ? 'Recent system logins and event logs' : 'Live updates of inventory edits, additions, and deletions'}
                </p>
              </div>
            </div>
            <button
              onClick={() => {
                if (activeSubTab === 'activity') {
                  loadActivity();
                } else {
                  loadSessions(activeSubTab === 'active-sessions');
                }
              }}
              className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all font-black text-[10px] uppercase tracking-wider flex items-center gap-1.5 border border-indigo-100 shrink-0"
              disabled={isLoading || isActivityLoading}
            >
              {isLoading || isActivityLoading ? <Loader2 size={12} className="animate-spin" /> : <RotateCcw size={12} />}
              Refresh
            </button>
          </div>

          {/* TOGGLE BUTTONS */}
          <div className="flex gap-2 mb-6 border-b border-slate-100 pb-5 flex-wrap">
            <button 
              onClick={() => { setActiveSubTab('active-sessions'); loadSessions(true); }}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${activeSubTab === 'active-sessions' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'}`}
            >
              Logged In Users
            </button>
            <button 
              onClick={() => { setActiveSubTab('all-sessions'); loadSessions(false); }}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${activeSubTab === 'all-sessions' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'}`}
            >
              Session History
            </button>
            <button 
              onClick={() => { setActiveSubTab('activity'); loadActivity(); }}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${activeSubTab === 'activity' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'}`}
            >
              Live Activity Log
            </button>
          </div>

          {/* TAB CONTENTS */}
          {activeSubTab === 'activity' ? (
            isActivityLoading && activityList.length === 0 ? (
              <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
                <Loader2 className="animate-spin text-indigo-600" size={24} />
                Loading activity log...
              </div>
            ) : activityList.length === 0 ? (
              <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest">
                No recent user activity recorded.
              </div>
            ) : (
              <div className="space-y-4 max-h-[480px] overflow-y-auto pr-1 no-scrollbar">
                {activityList.map((act) => {
                  let timeStr = 'Unknown';
                  try {
                    if (act.created_at) {
                      const d = new Date(act.created_at.replace(/-/g, '/'));
                      timeStr = d.toLocaleString();
                    }
                  } catch (err) {}

                  let actionBadge = { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200', label: 'ACTION' };
                  if (act.action === 'create') actionBadge = { bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-150', label: 'CREATE' };
                  else if (act.action === 'update') actionBadge = { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-150', label: 'EDIT' };
                  else if (act.action === 'delete') actionBadge = { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-150', label: 'DELETE' };
                  else if (act.action === 'restore') actionBadge = { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-150', label: 'RESTORE' };
                  else if (act.action === 'permanent_delete') actionBadge = { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-150', label: 'PURGE' };

                  const hasDiff = act.action === 'update' && act.details && act.details.diff && Object.keys(act.details.diff).length > 0;
                  const isExpanded = expandedActivityId === act.id;

                  return (
                    <div key={act.id} className="flex flex-col p-5 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm gap-3">
                      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div className="flex items-center gap-4">
                          <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs shrink-0">
                            {act.initials || '??'}
                          </div>
                          <div>
                            <div className="flex items-center gap-2 flex-wrap">
                              <span className="text-sm font-black text-slate-800">{act.display_name || 'System'}</span>
                              <span className="text-[10px] text-slate-400 font-bold">({timeStr})</span>
                            </div>
                            <div className="text-[10px] font-bold text-slate-500 mt-0.5">
                              {act.action !== 'permanent_delete' ? (
                                <button 
                                  onClick={() => {
                                    handleFullEdit(act.post_id);
                                    onNav('inventory');
                                  }}
                                  className="text-indigo-600 hover:text-indigo-800 hover:underline text-left font-black"
                                >
                                  {act.post_title || `Unit ID #${act.post_id}`}
                                </button>
                              ) : (
                                <span className="text-slate-500 font-bold">{act.post_title || `Unit ID #${act.post_id}`}</span>
                              )}
                            </div>
                          </div>
                        </div>
                        <div className="shrink-0 flex items-center gap-2">
                          <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border ${actionBadge.bg} ${actionBadge.text} ${actionBadge.border}`}>
                            {actionBadge.label}
                          </span>
                        </div>
                      </div>
                      
                      <div className="text-xs font-medium text-slate-600 bg-white/40 p-3 rounded-xl border border-slate-100">
                        {act.summary}
                      </div>

                      {hasDiff && (
                        <div>
                          <button
                            onClick={() => setExpandedActivityId(isExpanded ? null : act.id)}
                            className="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-all"
                          >
                            {isExpanded ? <ChevronUp size={12}/> : <ChevronDown size={12}/>}
                            {isExpanded ? 'Hide Changed Fields' : 'View Changed Fields'}
                          </button>

                          {isExpanded && (
                            <div className="mt-3 p-4 bg-white rounded-xl border border-slate-200/80 space-y-2 animate-in slide-in-from-top-2 duration-300">
                              {Object.entries(act.details.diff).map(([field, val]) => {
                                const cleanField = field.replace(/_/g, ' ').toUpperCase();
                                return (
                                  <div key={field} className="flex justify-between items-start gap-4 py-2 border-b border-slate-100 last:border-b-0 text-[10px]">
                                    <span className="font-black text-slate-400 shrink-0">{cleanField}</span>
                                    <span className="font-bold text-slate-800 break-all text-right flex-1 flex justify-end gap-2 flex-wrap items-center">
                                      <span className="text-red-500 line-through bg-red-50 px-1.5 py-0.5 rounded font-mono">{String(val.from || '—')}</span>
                                      <span className="text-slate-300">➔</span>
                                      <span className="text-green-600 font-black bg-green-50 px-1.5 py-0.5 rounded font-mono">{String(val.to || '—')}</span>
                                    </span>
                                  </div>
                                );
                              })}
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )
          ) : (
            isLoading && sessionList.length === 0 ? (
              <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
                <Loader2 className="animate-spin text-indigo-600" size={24} />
                Loading sessions...
              </div>
            ) : sessionList.length === 0 ? (
              <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest">
                {activeSubTab === 'active-sessions' ? 'No active users logged in.' : 'No session logs found.'}
              </div>
            ) : (
              <div className="space-y-4 max-h-[480px] overflow-y-auto pr-1 no-scrollbar">
                {sessionList.map((session) => {
                  const isActive = !session.logout_at;
                  let loginTimeStr = 'Unknown';
                  try {
                    if (session.login_at) {
                      const d = new Date(session.login_at.replace(/-/g, '/'));
                      loginTimeStr = d.toLocaleString();
                    }
                  } catch (err) {}

                  let lastActiveTimeStr = '';
                  try {
                    if (session.last_activity_at) {
                      const d = new Date(session.last_activity_at.replace(/-/g, '/'));
                      lastActiveTimeStr = d.toLocaleString();
                    }
                  } catch (err) {}

                  let endedStr = '';
                  if (!isActive && session.logout_at) {
                    try {
                      const d = new Date(session.logout_at.replace(/-/g, '/'));
                      endedStr = d.toLocaleString();
                    } catch (err) {}
                  }

                  let device = 'Desktop / Browser';
                  const ua = session.user_agent || '';
                  if (/mobile/i.test(ua)) {
                    device = 'Mobile Device';
                    if (/iphone/i.test(ua)) device = 'Apple iPhone';
                    else if (/android/i.test(ua)) device = 'Android Device';
                  } else if (/ipad/i.test(ua)) {
                    device = 'iPad Tablet';
                  }

                  return (
                    <div key={session.id} className="flex flex-col sm:flex-row justify-between items-start sm:items-center p-5 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm group">
                      <div className="flex items-center gap-4 w-full sm:w-auto">
                        <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs shrink-0">
                          {session.initials || '??'}
                        </div>
                        <div className="min-w-0">
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-black text-slate-800 truncate">{session.display_name || 'System User'}</span>
                            <span className="text-[10px] text-slate-400 font-bold truncate">({session.ip || 'Unknown IP'})</span>
                          </div>
                          <div className="text-[10px] text-slate-400 font-bold mt-0.5 truncate flex items-center gap-1.5 flex-wrap">
                            <span>{device}</span>
                            <span className="text-slate-300">•</span>
                            <span>Logged in: {loginTimeStr}</span>
                            {isActive && lastActiveTimeStr && (
                              <>
                                <span className="text-slate-300">•</span>
                                <span className="text-indigo-600 font-black">Active: {lastActiveTimeStr}</span>
                              </>
                            )}
                          </div>
                          {!isActive && endedStr && (
                            <div className="text-[9px] text-slate-400 mt-0.5 font-bold">
                              Logged out: {endedStr} {session.ended_reason ? `(${session.ended_reason})` : ''}
                            </div>
                          )}
                        </div>
                      </div>
                      <div className="mt-3 sm:mt-0 self-end sm:self-center shrink-0">
                        {isActive ? (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-[9px] font-black uppercase tracking-wider border border-green-150">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            Active Session
                          </span>
                        ) : (
                          <span className="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-black uppercase tracking-wider border border-slate-200">
                            Ended
                          </span>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )
          )}
        </div>

      </div>
    </div>
  );
};

const VideosTab = ({ showToast }) => {
  const [videos, setVideos] = useState([]);
  const [categories, setCategories] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  const [selectedCategoryFilter, setSelectedCategoryFilter] = useState('all');
  
  // Video Modal State
  const [isVideoModalOpen, setIsVideoModalOpen] = useState(false);
  const [editingVideo, setEditingVideo] = useState(null);
  const [videoForm, setVideoForm] = useState({ title: '', youtube_link: '', category_id: '' });
  
  // Category Modal State
  const [isCatModalOpen, setIsCatModalOpen] = useState(false);
  const [newCatForm, setNewCatForm] = useState({ name: '', description: '' });

  const loadData = async () => {
    setIsLoading(true);
    try {
      const catsData = await apiFetch('/video-categories');
      const vidsData = await apiFetch('/videos');
      setCategories(catsData);
      setVideos(vidsData);
    } catch (e) {
      showToast('Failed to load videos data: ' + e.message, 'error');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleOpenVideoModal = (video = null) => {
    if (video) {
      setEditingVideo(video);
      setVideoForm({
        title: video.title,
        youtube_link: video.youtube_link,
        category_id: String(video.category_id || '')
      });
    } else {
      setEditingVideo(null);
      setVideoForm({
        title: '',
        youtube_link: '',
        category_id: categories[0] ? String(categories[0].id) : ''
      });
    }
    setIsVideoModalOpen(true);
  };

  const handleSaveVideo = async (e) => {
    e.preventDefault();
    if (!videoForm.title || !videoForm.youtube_link || !videoForm.category_id) {
      showToast('Please fill out all fields.', 'error');
      return;
    }

    try {
      if (editingVideo) {
        await apiFetch(`/videos/${editingVideo.id}`, {
          method: 'PATCH',
          body: JSON.stringify(videoForm)
        });
        showToast('Video updated successfully!');
      } else {
        await apiFetch('/videos', {
          method: 'POST',
          body: JSON.stringify(videoForm)
        });
        showToast('Video added successfully!');
      }
      setIsVideoModalOpen(false);
      loadData();
    } catch (err) {
      showToast('Failed to save video: ' + err.message, 'error');
    }
  };

  const handleDeleteVideo = async (id) => {
    if (!window.confirm('Are you sure you want to delete this video?')) return;
    try {
      await apiFetch(`/videos/${id}`, { method: 'DELETE' });
      showToast('Video deleted successfully!');
      loadData();
    } catch (err) {
      showToast('Failed to delete video: ' + err.message, 'error');
    }
  };

  const handleSaveCategory = async (e) => {
    e.preventDefault();
    if (!newCatForm.name) {
      showToast('Category name is required.', 'error');
      return;
    }
    try {
      await apiFetch('/video-categories', {
        method: 'POST',
        body: JSON.stringify(newCatForm)
      });
      showToast('Category added successfully!');
      setNewCatForm({ name: '', description: '' });
      loadData();
    } catch (err) {
      showToast('Failed to add category: ' + err.message, 'error');
    }
  };

  const handleDeleteCategory = async (id) => {
    if (!window.confirm('Are you sure you want to delete this category? All videos in this category will become Uncategorized.')) return;
    try {
      await apiFetch(`/video-categories/${id}`, { method: 'DELETE' });
      showToast('Category deleted successfully!');
      loadData();
    } catch (err) {
      showToast('Failed to delete category: ' + err.message, 'error');
    }
  };

  const getYouTubeId = (url) => {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
  };

  const filteredVideos = selectedCategoryFilter === 'all' 
    ? videos 
    : videos.filter(v => String(v.category_id) === String(selectedCategoryFilter));

  if (isLoading) {
    return (
      <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
        <Loader2 className="animate-spin text-red-600" size={24} />
        Loading Videos Console...
      </div>
    );
  }

  return (
    <div className="space-y-6 sm:space-y-8 animate-in fade-in duration-500 text-slate-900 pb-16">
      
      {/* HEADER WELCOME BANNER */}
      <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-10 text-white shadow-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between relative overflow-hidden gap-6">
        <div className="relative z-10">
          <h2 className="text-2xl sm:text-4xl font-black tracking-tighter mb-2 uppercase leading-none text-white">Videos Manager</h2>
          <p className="text-indigo-400 font-bold uppercase tracking-[0.3em] text-[10px]">
            Manage all video walkthroughs and showcase sections.
          </p>
        </div>
        <div className="relative z-10 flex gap-3 flex-wrap">
          <button
            onClick={() => setIsCatModalOpen(true)}
            className="bg-slate-800 border border-slate-700 hover:bg-slate-750 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 active:scale-95 transition-all"
          >
            <List size={16}/>
            Categories
          </button>
          <button
            onClick={() => handleOpenVideoModal()}
            className="bg-red-600 hover:bg-red-700 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 active:scale-95 transition-all shadow-xl shadow-red-950/20"
          >
            <Plus size={16}/>
            Add Video
          </button>
        </div>
        <Camera size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* CATEGORY BAR */}
      <div className="flex gap-2 border-b border-slate-200 pb-4 overflow-x-auto no-scrollbar">
        <button 
          onClick={() => setSelectedCategoryFilter('all')}
          className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${selectedCategoryFilter === 'all' ? 'bg-red-600 text-white border-red-600 shadow-md' : 'bg-white text-slate-500 border-slate-200/60 hover:bg-slate-50'}`}
        >
          All Categories ({videos.length})
        </button>
        {categories.map(cat => {
          const count = videos.filter(v => String(v.category_id) === String(cat.id)).length;
          return (
            <button 
              key={cat.id}
              onClick={() => setSelectedCategoryFilter(cat.id)}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${String(selectedCategoryFilter) === String(cat.id) ? 'bg-red-600 text-white border-red-600 shadow-md' : 'bg-white text-slate-500 border-slate-200/60 hover:bg-slate-50'}`}
            >
              {cat.name} ({count})
            </button>
          );
        })}
      </div>

      {/* VIDEOS GRID */}
      {filteredVideos.length === 0 ? (
        <div className="p-20 text-center border-2 border-dashed border-slate-200 rounded-[2rem] text-slate-400 font-black uppercase text-xs tracking-widest bg-white">
          No videos listed in this category
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredVideos.map(vid => {
            const ytId = getYouTubeId(vid.youtube_link);
            const thumbUrl = ytId 
              ? `https://img.youtube.com/vi/${ytId}/mqdefault.jpg`
              : 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400';
            
            return (
              <div key={vid.id} className="bg-white rounded-[2rem] overflow-hidden shadow-xl border border-slate-100 hover:shadow-2xl hover:-translate-y-0.5 transition-all group flex flex-col">
                <div className="aspect-video w-full bg-slate-900 relative overflow-hidden shrink-0">
                  <img src={thumbUrl} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt={vid.title}/>
                  <div className="absolute inset-0 bg-black/20 flex items-center justify-center pointer-events-none">
                    <div className="w-12 h-12 bg-red-600/90 text-white rounded-full flex items-center justify-center shadow-lg group-hover:bg-red-600 group-hover:scale-110 transition-all duration-300">
                      <PlayIcon className="ml-1 w-5 h-5 fill-current text-white" />
                    </div>
                  </div>
                  <span className="absolute bottom-3 left-3 bg-slate-900/95 text-white text-[8px] font-black px-2 py-1 rounded uppercase tracking-widest border border-slate-800">
                    {vid.category_name}
                  </span>
                </div>
                <div className="p-6 flex-1 flex flex-col justify-between gap-4">
                  <h3 className="text-base font-black text-slate-900 leading-snug truncate-2-lines">{vid.title}</h3>
                  <div className="flex gap-2 border-t border-slate-50 pt-4 mt-auto">
                    <button
                      onClick={() => handleOpenVideoModal(vid)}
                      className="flex-1 bg-slate-50 border border-slate-200 text-slate-600 py-3 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-slate-100 hover:text-slate-900 transition-all flex items-center justify-center gap-1.5 active:scale-95"
                    >
                      <Edit2 size={12}/> Edit
                    </button>
                    <button
                      onClick={() => handleDeleteVideo(vid.id)}
                      className="bg-red-50 border border-red-100 text-red-600 p-3 rounded-xl hover:bg-red-100 transition-all active:scale-95 flex items-center justify-center"
                      title="Delete Video"
                    >
                      <Trash2 size={12}/>
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* VIDEO DIALOG */}
      {isVideoModalOpen && (
        <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4 animate-in fade-in duration-200" onClick={e => { if (e.target === e.currentTarget) setIsVideoModalOpen(false); }}>
          <div className="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg flex flex-col overflow-hidden animate-in zoom-in duration-300">
            <div className="flex items-center justify-between p-6 sm:p-8 border-b border-slate-100 shrink-0">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">{editingVideo ? 'Edit Video' : 'Add New Video'}</h3>
                <p className="text-[9px] text-slate-400 font-bold uppercase mt-1.5">Showcase a new walkthrough or product highlight</p>
              </div>
              <button onClick={() => setIsVideoModalOpen(false)} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
            </div>
            <form onSubmit={handleSaveVideo} className="p-6 sm:p-8 space-y-5">
              <InputField
                label="Video Title"
                value={videoForm.title}
                onChange={v => setVideoForm(f => ({ ...f, title: v }))}
                placeholder="e.g. Mahindra 2638 Loader Work"
              />
              <InputField
                label="YouTube Video Link"
                value={videoForm.youtube_link}
                onChange={v => setVideoForm(f => ({ ...f, youtube_link: v }))}
                placeholder="e.g. https://www.youtube.com/watch?v=goF_3TspZ6k"
              />
              <div className="space-y-3">
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Video Category</label>
                <select
                  value={videoForm.category_id}
                  onChange={e => setVideoForm(f => ({ ...f, category_id: e.target.value }))}
                  className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm appearance-none cursor-pointer"
                >
                  <option value="" disabled>Select a Category...</option>
                  {categories.map(cat => <option key={cat.id} value={cat.id}>{cat.name}</option>)}
                </select>
              </div>
              <button type="submit" className="w-full bg-red-600 hover:bg-red-700 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-red-950/20 active:scale-95 transition-all mt-4 border-b-4 border-red-800">
                {editingVideo ? 'SAVE VIDEO CHANGES' : 'PUBLISH VIDEO'}
              </button>
            </form>
          </div>
        </div>
      )}

      {/* CATEGORIES DIALOG */}
      {isCatModalOpen && (
        <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4 animate-in fade-in duration-200" onClick={e => { if (e.target === e.currentTarget) setIsCatModalOpen(false); }}>
          <div className="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md flex flex-col overflow-hidden max-h-[85vh] animate-in zoom-in duration-300">
            <div className="flex items-center justify-between p-6 sm:p-8 border-b border-slate-100 shrink-0">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">Manage Categories</h3>
                <p className="text-[9px] text-slate-400 font-bold uppercase mt-1.5">{categories.length} Categories configured</p>
              </div>
              <button onClick={() => setIsCatModalOpen(false)} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
            </div>
            
            {/* Create Category form */}
            <form onSubmit={handleSaveCategory} className="p-6 border-b border-slate-100 bg-slate-50/50 space-y-4 shrink-0">
              <div className="flex gap-2 items-end">
                <div className="flex-1">
                  <InputField
                    label="Add Category Name"
                    value={newCatForm.name}
                    onChange={v => setNewCatForm(f => ({ ...f, name: v }))}
                    placeholder="e.g. Parts Counter"
                  />
                </div>
                <button type="submit" className="bg-slate-950 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all active:scale-95 mb-0.5 h-[52px] leading-none">
                  Create
                </button>
              </div>
            </form>

            {/* List Categories */}
            <div className="overflow-y-auto flex-1 p-6 space-y-3 no-scrollbar">
              {categories.map(cat => (
                <div key={cat.id} className="flex items-center justify-between px-5 py-4 bg-slate-50 rounded-2xl group hover:bg-red-50 hover:border-red-100 border border-slate-100/50 transition-all">
                  <div>
                    <span className="font-black text-sm text-slate-900 uppercase tracking-tight">{cat.name}</span>
                  </div>
                  <button 
                    onClick={() => handleDeleteCategory(cat.id)} 
                    className="text-slate-300 hover:text-red-600 transition-colors p-1"
                    title="Delete Category"
                  >
                    <X size={16}/>
                  </button>
                </div>
              ))}
              {categories.length === 0 && (
                <p className="text-slate-400 text-center font-black uppercase text-[10px] tracking-widest py-8">No Categories Registered</p>
              )}
            </div>
          </div>
        </div>
      )}

    </div>
  );
};

export default App;
