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
  ChevronLeft, ChevronRight, Plus, Settings, Zap, Menu, Image as ImageIcon, Smartphone, Eye,
  ArrowUpRight, BarChart3, Users, Wrench, Clock, ShieldCheck, Camera, Loader2,
  ScanText, List, Search, Edit2, X, TrendingUp, Activity, DollarSign, History,
  Sparkles, Info, Trash2, RotateCcw, Star
} from 'lucide-react';

// ─── API helpers ─────────────────────────────────────────────────────────────

const API = window.varnerData?.rest_url
  ? window.varnerData.rest_url.replace(/\/$/, '') + '/varner/v1'
  : '/wp-json/varner/v1';

const NONCE = window.varnerData?.nonce ?? '';

async function apiFetch(path, options = {}) {
  const res = await fetch(`${API}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': NONCE,
      ...(options.headers ?? {}),
    },
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message ?? `Request failed: ${res.status}`);
  }
  return res.json();
}

async function uploadFile(file) {
  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API}/media`, {
    method: 'POST',
    headers: { 'X-WP-Nonce': NONCE },
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

// Map inventory list item shape for the table
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
    deleted_at: u.deleted_at ?? '',
  };
}

// ─── App ─────────────────────────────────────────────────────────────────────

const App = () => {
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
  const [activeFilters, setActiveFilters]     = useState({ status: [], categories: [], makes: [], models: [], yearMin: '', yearMax: '', priceMin: '', priceMax: '', conditions: [] });
  const [showFilterPanel, setShowFilterPanel] = useState(false);
  const [isPublicMode, setIsPublicMode]       = useState(false);

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

  useEffect(() => {
    loadInventory();
    apiFetch('/brands').then(setBrands).catch(() => {});
    apiFetch('/categories').then(setCategories).catch(() => {});
  }, [loadInventory]);

  const handleAddBrand = async () => {
    const name = newBrandInput.trim();
    if (!name || brands.includes(name)) return;
    const updated = [...brands, name].sort((a, b) => a.localeCompare(b));
    await apiFetch('/brands', { method: 'POST', body: JSON.stringify({ brands: updated }) });
    setBrands(updated);
    setNewBrandInput('');
  };

  const handleDeleteBrand = async (name) => {
    const updated = brands.filter(b => b !== name);
    await apiFetch('/brands', { method: 'POST', body: JSON.stringify({ brands: updated }) });
    setBrands(updated);
    if (unitData.make === name) handleInputChange('make', '');
  };

  const handleAddCategory = async () => {
    const name = newCategoryInput.trim();
    if (!name || categories.includes(name)) return;
    const updated = [...categories, name].sort((a, b) => a.localeCompare(b));
    await apiFetch('/categories', { method: 'POST', body: JSON.stringify({ categories: updated }) });
    setCategories(updated);
    setNewCategoryInput('');
  };

  const handleDeleteCategory = async (name) => {
    const updated = categories.filter(c => c !== name);
    await apiFetch('/categories', { method: 'POST', body: JSON.stringify({ categories: updated }) });
    setCategories(updated);
    if (unitData.category === name) handleInputChange('category', '');
  };

  const handleInputChange = (field, value) => {
    setUnitData(prev => ({ ...prev, [field]: value }));
    
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
      showToast('Unit saved successfully');
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

  const handleEditUnit = (item) => {
    setUnitData({
      id:          item.wpId,
      title:       `${item.year} ${item.make} ${item.model}`.trim(),
      year:        item.year,
      make:        item.make,
      model:       item.model,
      stockNumber: item.stock,
      condition:   item.condition,
      price:       item.price,
      vin:         '',
      stockStatus: item.status,
      category:    item.category,
      color:       '',
      meter:       '',
      meterType:   'Hours',
      intakeDate:  '',
      description: '',
      sellerInfo:  defaultEmptyUnit.sellerInfo,
      featured:    item.featured ?? false,
      showOnWebsite: item.showOnWebsite ?? true,
      images:      item.image ? [item.image] : [],
      image_ids:   [],
      attachments: item.attachments ?? [],
    });
    setActiveTab('inventory');
  };

  // Full edit — fetches complete data from API
  const handleFullEdit = async (wpId) => {
    try {
      const units = await apiFetch('/inventory');
      const unit  = units.find(u => u.id === wpId);
      if (unit) { setUnitData(apiToLocal(unit)); setActiveTab('inventory'); }
    } catch { /* fallback already applied */ }
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
  const handleClearFilters = () => { setActiveFilters({ status: [], categories: [], makes: [], models: [], yearMin: '', yearMax: '', priceMin: '', priceMax: '', conditions: [] }); setSearchQuery(''); };

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
    return true;
  });

  const getHeaderTitle = () => {
    switch (activeTab) {
      case 'dashboard':     return 'Operations Overview';
      case 'all-inventory': return 'Master Stock Ledger';
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
      case 'settings':      return 'System Configuration';
      default:              return 'Varner OS';
    }
  };

  return (
    <div className="flex bg-[#f8fafc] font-sans text-slate-900 selection:bg-red-100 min-h-screen">

      {/* Toast */}
      {toast && (
        <div className={`fixed top-6 right-6 z-[9999] px-6 py-4 rounded-2xl font-black text-sm shadow-2xl transition-all animate-in slide-in-from-top-4 ${toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`}>
          {toast.msg}
        </div>
      )}

      {/* Brands Management Modal */}
      {showBrandsModal && (
        <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4" onClick={e => { if (e.target === e.currentTarget) setShowBrandsModal(false); }}>
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col" style={{ maxHeight: '85vh' }}>
            <div className="flex items-center justify-between p-6 border-b border-slate-100 shrink-0">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">Manage Brands</h3>
                <p className="text-[10px] text-slate-400 font-bold mt-1">{brands.length} brands in list</p>
              </div>
              <button onClick={() => setShowBrandsModal(false)} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
            </div>
            <div className="p-5 shrink-0">
              <div className="flex gap-2">
                <input type="text" value={newBrandInput} onChange={e => setNewBrandInput(e.target.value)}
                  onKeyDown={e => e.key === 'Enter' && handleAddBrand()}
                  placeholder="New brand name..."
                  className="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors"/>
                <button onClick={handleAddBrand}
                  className="bg-red-600 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-red-700 transition-colors shrink-0">
                  Add
                </button>
              </div>
            </div>
            <div className="overflow-y-auto flex-1 px-5 pb-5">
              <div className="space-y-1.5">
                {brands.map(b => (
                  <div key={b} className="flex items-center justify-between px-4 py-2.5 bg-slate-50 rounded-xl group hover:bg-red-50 transition-colors">
                    <span className="font-bold text-sm text-slate-900">{b}</span>
                    <button onClick={() => handleDeleteBrand(b)}
                      className="text-slate-300 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100">
                      <X size={14}/>
                    </button>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Categories Management Modal */}
      {showCategoriesModal && (
        <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4" onClick={e => { if (e.target === e.currentTarget) setShowCategoriesModal(false); }}>
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col" style={{ maxHeight: '85vh' }}>
            <div className="flex items-center justify-between p-6 border-b border-slate-100 shrink-0">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">Manage Categories</h3>
                <p className="text-[10px] text-slate-400 font-bold mt-1">{categories.length} categories in list</p>
              </div>
              <button onClick={() => setShowCategoriesModal(false)} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
            </div>
            <div className="p-5 shrink-0">
              <div className="flex gap-2">
                <input type="text" value={newCategoryInput} onChange={e => setNewCategoryInput(e.target.value)}
                  onKeyDown={e => e.key === 'Enter' && handleAddCategory()}
                  placeholder="New category name..."
                  className="flex-1 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:border-red-500 transition-colors"/>
                <button onClick={handleAddCategory}
                  className="bg-red-600 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-red-700 transition-colors shrink-0">
                  Add
                </button>
              </div>
            </div>
            <div className="overflow-y-auto flex-1 px-5 pb-5">
              <div className="space-y-1.5">
                {categories.map(c => (
                  <div key={c} className="flex items-center justify-between px-4 py-2.5 bg-slate-50 rounded-xl group hover:bg-red-50 transition-colors">
                    <span className="font-bold text-sm text-slate-900">{c}</span>
                    <button onClick={() => handleDeleteCategory(c)}
                      className="text-slate-300 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100">
                      <X size={14}/>
                    </button>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MOBILE SIDEBAR OVERLAY */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" onClick={() => setIsMobileMenuOpen(false)}></div>
          <aside className="fixed inset-y-0 left-0 w-72 bg-slate-950 text-white p-6 shadow-2xl flex flex-col">
            <div className="flex items-center justify-between mb-8 border-b border-slate-800 pb-6">
              <div className="flex items-center gap-3">
                <div className="bg-red-600 p-2 rounded-xl"><Box size={22} /></div>
                <div>
                  <span className="font-black text-xl tracking-tighter block leading-none">VARNER</span>
                  <span className="text-red-500 text-[9px] font-black uppercase tracking-[0.3em] mt-0.5 block">Equipment</span>
                </div>
              </div>
              <button onClick={() => setIsMobileMenuOpen(false)} className="text-slate-400 hover:text-white p-2"><X size={24} /></button>
            </div>
            <nav className="space-y-2 flex-1">
              <NavItem icon={<LayoutDashboard size={20}/>} label="Dashboard" active={activeTab==='dashboard'} onClick={() => { setActiveTab('dashboard'); setIsMobileMenuOpen(false); }} />
              <NavItem icon={<List size={20}/>} label="Inventory List" active={activeTab==='all-inventory'} onClick={() => { setActiveTab('all-inventory'); setIsMobileMenuOpen(false); }} badge={inventoryList.length} />
              <NavItem icon={<Box size={20}/>} label="Add / Edit" active={activeTab==='inventory'} onClick={() => { setActiveTab('inventory'); setIsMobileMenuOpen(false); }} />
              <NavItem icon={<Facebook size={20}/>} label="Meta Sync" active={activeTab==='marketplace'} onClick={() => { setActiveTab('marketplace'); setIsMobileMenuOpen(false); }} badge="Live" />
              <NavItem icon={<History size={20}/>} label="History" active={activeTab==='history'} onClick={() => { setActiveTab('history'); setIsMobileMenuOpen(false); }} badge={deletedHistory.length > 0 ? deletedHistory.length : null} />
              <NavItem icon={<Smartphone size={20}/>} label="Mobile App" active={activeTab==='mobile'} onClick={() => { setActiveTab('mobile'); setIsMobileMenuOpen(false); }} />
            </nav>
            <div className="mt-auto pt-4 border-t border-slate-800">
              <NavItem icon={<Settings size={18}/>} label="Configuration" active={activeTab==='settings'} onClick={() => { setActiveTab('settings'); setIsMobileMenuOpen(false); }} />
            </div>
          </aside>
        </div>
      )}

      {/* SIDEBAR */}
      <aside className="hidden lg:flex flex-col w-72 bg-slate-950 text-white p-6 shadow-2xl border-r border-slate-800 shrink-0">
        <div className="flex items-center gap-3 mb-8 border-b border-slate-800 pb-6">
          <div className="bg-red-600 p-2 rounded-xl"><Box size={22} /></div>
          <div>
            <span className="font-black text-xl tracking-tighter block leading-none">VARNER</span>
            <span className="text-red-500 text-[9px] font-black uppercase tracking-[0.3em] mt-0.5 block">Equipment</span>
          </div>
        </div>
        <nav className="space-y-2 flex-1">
          <NavItem icon={<LayoutDashboard size={20}/>} label="Dashboard" active={activeTab==='dashboard'} onClick={() => setActiveTab('dashboard')} />
          <NavItem icon={<List size={20}/>} label="Inventory List" active={activeTab==='all-inventory'} onClick={() => setActiveTab('all-inventory')} badge={inventoryList.length} />
          <NavItem icon={<Box size={20}/>} label="Add / Edit" active={activeTab==='inventory'} onClick={() => setActiveTab('inventory')} />
          <NavItem icon={<Facebook size={20}/>} label="Meta Sync" active={activeTab==='marketplace'} onClick={() => setActiveTab('marketplace')} badge="Live" />
          <NavItem icon={<History size={20}/>} label="History" active={activeTab==='history'} onClick={() => setActiveTab('history')} badge={deletedHistory.length > 0 ? deletedHistory.length : null} />
          <NavItem icon={<Smartphone size={20}/>} label="Mobile App" active={activeTab==='mobile'} onClick={() => setActiveTab('mobile')} />
        </nav>
        <div className="mt-auto pt-4 border-t border-slate-800">
          <NavItem icon={<Settings size={18}/>} label="Configuration" active={activeTab==='settings'} onClick={() => setActiveTab('settings')} />
        </div>
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
            {(activeTab === 'inventory' || activeTab === 'all-inventory') && (
              <button
                onClick={activeTab === 'inventory' ? handleSave : handleAddNewUnit}
                className="bg-red-600 text-white p-3 sm:px-7 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 active:scale-95 transition-all border-b-2 border-red-800"
              >
                {isSaving ? <Zap className="animate-spin" size={16}/> : (activeTab === 'inventory' ? <Save size={16}/> : <Plus size={16}/>)}
                <span className="hidden sm:inline">{isSaving ? 'SAVING...' : (activeTab === 'inventory' ? 'SAVE TO LEDGER' : 'NEW UNIT')}</span>
                <span className="sm:hidden">{activeTab === 'inventory' ? 'SAVE' : 'NEW'}</span>
              </button>
            )}
          </div>
        </header>

        <div className={`flex-1 overflow-y-auto bg-slate-50/50 no-scrollbar ${activeTab === 'all-inventory' ? 'px-2 py-4 sm:px-3 sm:py-6' : 'p-4 sm:p-6 lg:p-8'}`}>
          <div className="max-w-7xl mx-auto pb-10">

            {/* DASHBOARD */}
            {activeTab === 'dashboard' && (
              <div className="space-y-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                  <MetricCard icon={<Box size={24}/>} label="Live Units" value={isLoading ? '…' : String(inventoryList.filter(i => i.status === 'In Stock').length)} subtext="In stock right now" color="blue" />
                  <MetricCard icon={<TrendingUp size={24}/>} label="Total Units" value={isLoading ? '…' : String(inventoryList.length)} subtext="All active listings" color="amber" />
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
                    {activeFilters.makes.map(v => <span key={v} className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('makes', activeFilters.makes.filter(x => x !== v))} className="font-black leading-none hover:text-red-200">×</button>{v.toUpperCase()}</span>)}
                    {activeFilters.status.map(v => <span key={v} className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('status', activeFilters.status.filter(x => x !== v))} className="font-black leading-none hover:text-red-200">×</button>{v.toUpperCase()}</span>)}
                    {activeFilters.categories.map(v => <span key={v} className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('categories', activeFilters.categories.filter(x => x !== v))} className="font-black leading-none hover:text-red-200">×</button>{v.toUpperCase()}</span>)}
                    {activeFilters.models.map(v => <span key={v} className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('models', activeFilters.models.filter(x => x !== v))} className="font-black leading-none hover:text-red-200">×</button>{v.toUpperCase()}</span>)}
                    {activeFilters.conditions.map(v => <span key={v} className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('conditions', activeFilters.conditions.filter(x => x !== v))} className="font-black leading-none hover:text-red-200">×</button>{v.toUpperCase()}</span>)}
                    {(activeFilters.yearMin || activeFilters.yearMax) && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => { handleFilterChange('yearMin',''); handleFilterChange('yearMax',''); }} className="font-black leading-none hover:text-red-200">×</button>YEAR: {activeFilters.yearMin||'?'}–{activeFilters.yearMax||'?'}</span>}
                    {(activeFilters.priceMin || activeFilters.priceMax) && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => { handleFilterChange('priceMin',''); handleFilterChange('priceMax',''); }} className="font-black leading-none hover:text-red-200">×</button>PRICE: ${activeFilters.priceMin||'0'}–${activeFilters.priceMax||'∞'}</span>}
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

                {/* Table / Master stock ledger card */}
                <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden" style={{ minWidth: 0 }}>
                  <div className="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between w-full">
                    <div className="flex items-center gap-3">
                      <button onClick={() => setShowFilterPanel(true)}
                        className="xl:hidden flex items-center gap-2 bg-white border-2 border-slate-200 px-4 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-sm hover:border-red-500 transition-colors">
                        <Search size={14}/> Filters
                      </button>
                      <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Master Stock Ledger</span>
                    </div>
                    <span className="bg-slate-900 text-white text-[11px] font-black px-4 py-2 rounded-full uppercase tracking-widest shrink-0">
                      {filteredInventory.length} Unit{filteredInventory.length !== 1 ? 's' : ''} Found
                    </span>
                  </div>
                  <div className="overflow-x-auto p-2 no-scrollbar">
                    {isLoading ? (
                      <div className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">Loading inventory…</div>
                    ) : (
                      <table className="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                          <tr className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
                            <th className="px-6 py-5 w-24">STOCK #</th>
                            <th className="px-6 py-5 w-28">PHOTO</th>
                            <th className="px-6 py-5">YEAR / MAKE / MODEL</th>
                            <th className="px-6 py-5">CATEGORY</th>
                            <th className="px-6 py-5 text-center w-32">CONDITION</th>
                            <th className="px-6 py-5 w-32">PRICE (USD)</th>
                             <th className="px-6 py-5 w-40">STATUS</th>
                             <th className="px-6 py-5 text-center w-28">WEBSITE</th>
                             <th className="px-6 py-5 text-center w-28">FEATURED</th>
                             <th className="px-6 py-5 text-right w-32">ACTIONS</th>
                           </tr>
                         </thead>
                         <tbody className="divide-y divide-slate-50">
                           {filteredInventory.length === 0 ? (
                             <tr><td colSpan="10" className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">No units found</td></tr>
                           ) : filteredInventory.map(item => (
                             <tr key={item.id} className="hover:bg-slate-50 transition-all cursor-pointer group" onClick={() => { handleEditUnit(item); handleFullEdit(item.wpId); }}>
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
                                  <button onClick={() => { handleEditUnit(item); handleFullEdit(item.wpId); }} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Edit"><Edit2 size={16}/></button>
                                  <button onClick={() => { handleEditUnit(item); handleFullEdit(item.wpId); setTimeout(handleClone, 200); }} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Clone"><Copy size={16}/></button>
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
                            <button type="button" onClick={() => setShowCategoriesModal(true)}
                              className="text-[9px] font-black uppercase tracking-widest text-red-600 hover:text-red-700 flex items-center gap-1">
                              <Settings size={10}/> Manage Categories
                            </button>
                          </div>
                          <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                            <select value={unitData.category} onChange={e => handleInputChange('category', e.target.value)}
                              className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none">
                              <option value="">— Select Category —</option>
                              {categories.map(c => <option key={c} value={c}>{c}</option>)}
                            </select>
                            <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90"/></div>
                          </div>
                        </div>
                      </div>
                      <div className="md:col-span-2">
                        <div className="space-y-3">
                          <div className="flex items-center justify-between pl-1">
                            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Brand / Manufacturer</label>
                            <button type="button" onClick={() => setShowBrandsModal(true)}
                              className="text-[9px] font-black uppercase tracking-widest text-red-600 hover:text-red-700 flex items-center gap-1">
                              <Settings size={10}/> Manage Brands
                            </button>
                          </div>
                          <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
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
                        </div>
                      </div>
                      <div className="md:col-span-2 border-y border-slate-50 py-6 my-2">
                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 block">VIN / SERIAL NUMBER</label>
                        <input type="text" value={unitData.vin} onChange={e => handleInputChange('vin', e.target.value)}
                          className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-mono font-black text-lg text-slate-900 outline-none shadow-inner focus:border-red-500 focus:bg-white transition-all tracking-widest uppercase"
                          placeholder="TYPE SERIAL..."/>
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
                        </div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1"><SelectField label="Stock Status" options={['In Stock','Pending Sale','Sold','Draft']} value={unitData.stockStatus} onChange={v => handleInputChange('stockStatus', v)}/></div>
                        <div className="flex-1"><SelectField label="Condition" options={['New','Used']} value={unitData.condition} onChange={v => handleInputChange('condition', v)}/></div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1"><InputField label="Color" value={unitData.color} onChange={v => handleInputChange('color', v)}/></div>
                        <div className="flex-1"><InputField label="Length (e.g. 20 ft)" value={unitData.length} onChange={v => handleInputChange('length', v)}/></div>
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
                    {unitData.title && (
                      <button onClick={handleClone} className="flex-1 bg-white text-slate-600 px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-slate-50 transition-all active:scale-95 border-2 border-slate-100 shadow-xl shadow-slate-200/50">
                        <Copy size={18}/> Clone Unit
                      </button>
                    )}
                    <button
                      onClick={handleSave}
                      disabled={isSaving}
                      className="flex-[2] bg-red-600 text-white px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-red-700 transition-all active:scale-95 shadow-2xl shadow-red-200 border-b-4 border-red-800 disabled:opacity-50"
                    >
                      {isSaving ? <Loader2 className="animate-spin" size={18}/> : <Save size={18}/>}
                      {isSaving ? 'SAVING TO LEDGER...' : 'PUBLISH TO MASTER LEDGER'}
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
            {activeTab === 'settings'    && <SettingsTab users={[]}/>}
            {activeTab === 'mobile'      && <MobileAccessTab/>}
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

const InputField = ({ label, value, onChange }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <input type="text" value={value} onChange={e => onChange(e.target.value)} className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-black text-slate-900 focus:border-slate-300 focus:bg-white outline-none transition-all shadow-sm text-lg leading-none"/>
  </div>
);

const TextAreaField = ({ label, value, onChange }) => (
  <div className="space-y-3 rich-text-field">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <div className="bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] overflow-hidden focus-within:border-red-500 transition-all shadow-sm">
      <ReactQuill theme="snow" value={value} onChange={onChange}
        modules={{ toolbar: [[{ header: [1,2,false] }],['bold','italic','underline','strike'],[{ list:'ordered'},{ list:'bullet'}],['clean']] }}
        className="bg-transparent"
      />
    </div>
    <style dangerouslySetInnerHTML={{ __html: `.rich-text-field .ql-toolbar.ql-snow{border:none;border-bottom:1px solid #f1f5f9;background:#fff;padding:12px 20px}.rich-text-field .ql-container.ql-snow{border:none;font-family:inherit;font-size:14px;min-height:150px}.rich-text-field .ql-editor{padding:20px;color:#1e293b;line-height:1.6}.rich-text-field .ql-editor.ql-blank::before{color:#94a3b8;font-style:normal;left:20px}` }}/>
  </div>
);

const SelectField = ({ label, options, value, onChange, placeholder }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
      <select value={value} onChange={e => onChange(e.target.value)} className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none">
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((o, i) => <option key={i} value={o}>{o}</option>)}
      </select>
      <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90"/></div>
    </div>
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

const MetricCard = ({ icon, label, value, subtext, color }) => {
  const styles = { blue:'bg-blue-50 text-blue-600', red:'bg-red-50 text-red-600', green:'bg-green-50 text-green-600', amber:'bg-amber-50 text-amber-600' };
  const bg     = { blue:'bg-blue-50', red:'bg-red-50', green:'bg-green-50', amber:'bg-amber-50' };
  return (
    <div className="rounded-[2rem] p-5 sm:p-8 border bg-white border-slate-200/60 shadow-xl relative overflow-hidden group transition-all">
      <div className="flex items-center gap-3 sm:gap-4 mb-5 sm:mb-8 relative z-10">
        <div className={`p-3 sm:p-4 rounded-xl ${styles[color]} shadow-md group-hover:scale-110 transition-transform`}>{icon}</div>
        <h4 className="font-black text-[10px] uppercase tracking-widest text-slate-400 leading-none">{label}</h4>
      </div>
      <p className="text-4xl sm:text-5xl font-black text-slate-950 mb-3 tracking-tighter relative z-10 leading-none">{value}</p>
      <p className={`text-[10px] font-black uppercase tracking-[0.1em] relative z-10 ${styles[color]}`}>{subtext}</p>
      <div className={`absolute -right-6 -bottom-6 w-32 h-32 rounded-full opacity-10 ${bg[color]} group-hover:scale-150 transition-transform duration-700`}></div>
    </div>
  );
};

const QuickActions = ({ onAdd }) => (
  <div className="bg-white rounded-[2rem] p-5 sm:p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2"><Zap size={14} className="text-red-600"/>Quick Operations</h4>
    <div className="grid grid-cols-1 gap-4">
      <button onClick={onAdd} className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform"><Plus size={20} className="text-red-600"/></div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Add Unit</span>
      </button>
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

  const generateToken = () => {
    setIsGenerating(true);
    setTimeout(() => {
      setToken(Math.random().toString(36).substring(2, 15).toUpperCase());
      setExpiry(new Date(Date.now() + 30 * 60000).toLocaleTimeString());
      setIsGenerating(false);
    }, 1200);
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
                {token ? <img src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=https://varnerequipment.com/mobile/access/${token}`} className="w-full h-full p-4" alt="QR"/> : <div className="text-center p-8"><ScanText size={48} className="text-slate-200 mx-auto mb-4"/><p className="text-[10px] font-black uppercase tracking-widest text-slate-300">Token Required</p></div>}
              </div>
              {token && <div className="mt-6 text-center"><p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Secure Token</p><p className="text-xl font-mono font-black text-slate-900 tracking-wider">{token}</p><div className="mt-4 flex items-center justify-center gap-2 text-amber-500"><Clock size={12}/><span className="text-[9px] font-black uppercase tracking-widest">Expires at {expiry}</span></div></div>}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

const SettingsTab = ({ users }) => (
  <div className="max-w-4xl mx-auto space-y-8 sm:space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-500 text-slate-900">
    <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-5 sm:p-10 border border-slate-200/60 shadow-xl">
      <h3 className="font-black text-[12px] uppercase tracking-widest text-slate-400 mb-10 flex items-center gap-3"><ShieldCheck size={20}/> Technical Permissions</h3>
      {users.length === 0 ? (
        <div className="p-12 text-center border-2 border-dashed border-slate-100 rounded-3xl"><Users size={40} className="mx-auto text-slate-200 mb-4"/><p className="text-slate-400 uppercase text-[10px] font-black tracking-widest">No active users detected</p></div>
      ) : users.map((u,i) => (
        <div key={i} className="p-6 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center shadow-sm hover:shadow-lg transition-all group">
          <div className="flex items-center gap-5"><div className="w-12 h-12 rounded-xl bg-slate-950 text-white flex items-center justify-center font-black text-xl">{u.name.charAt(0)}</div><div><p className="font-black text-lg leading-none mb-1.5">{u.name}</p><p className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{u.role} • {u.device}</p></div></div>
          <span className={`px-4 py-1.5 rounded-full font-black text-[10px] uppercase tracking-widest border ${u.status==='Inactive'?'bg-slate-100 text-slate-400 border-slate-200':'bg-green-100 text-green-700 border-green-200'}`}>{u.status}</span>
        </div>
      ))}
    </div>
  </div>
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
    model: true, year: true, price: true,
    condition: false, state: false, city: false, country: false,
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

        {/* State */}
        <SectionHeader label="State" sKey="state" />
        {sections.state && <div className="py-2"><p className="text-xs text-gray-400 italic px-1">No state data available</p></div>}

        {/* City */}
        <SectionHeader label="City" sKey="city" />
        {sections.city && <div className="py-2"><p className="text-xs text-gray-400 italic px-1">No city data available</p></div>}

        {/* Country */}
        <SectionHeader label="Country" sKey="country" />
        {sections.country && <div className="py-2"><p className="text-xs text-gray-400 italic px-1">No country data available</p></div>}
      </div>
    </div>
  );
};

export default App;
