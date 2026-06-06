import React, { useState, useEffect, useCallback } from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import {
  Box, LayoutDashboard, List, Facebook, History, Sliders, Camera, Smartphone, Settings,
  X, Menu, Copy, Plus, Upload, Download, Save, Zap, TrendingUp, CheckCircle2, Clock,
  ChevronRight, Star, Eye, ArrowUpRight, Search, Edit2, Image as ImageIcon, Loader2
} from 'lucide-react';


import {
  DEFAULT_EMPTY_UNIT,
  CATEGORY_TREE,
  COLOR_OPTIONS,
  STATUS_OPTIONS,
  CONDITION_OPTIONS,
  METER_TYPE_OPTIONS,
  getCategoryLabel
} from './constants/inventoryConstants';

import { apiFetch } from './utils/api';
import { apiToLocal, apiToListItem, getDaysInStock } from './utils/helpers';

import { SidebarLogo, SidebarContent, FilterTag, MappingRow } from './components/Common/Navigation';
import { ManageListModal } from './components/Common/Modals';
import { InputField, TextAreaField, SelectField, QUILL_STYLES } from './components/Common/FormFields';
import { MetricCard, QuickActions, RecentActivity } from './components/Common/DashboardCards';

import { MediaSection } from './components/MediaSection';
import { AttachmentsSection } from './components/AttachmentsSection';
import { FilterSidebar } from './components/FilterSidebar';
import { FBPreviewModal } from './components/FBPreviewModal';

import { MarketplaceTab } from './components/Tabs/MarketplaceTab';
import { SettingsTab } from './components/Tabs/SettingsTab';
import { VideosTab } from './components/Tabs/VideosTab';
import { MobileAccessTab } from './components/Tabs/MobileAccessTab';
import { ConfigurationTab } from './components/Tabs/ConfigurationTab';
import { HistoryTab } from './components/Tabs/HistoryTab';

import { MobileAppLayout } from './components/MobileAppLayout';

const defaultEmptyUnit = DEFAULT_EMPTY_UNIT;

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

  const [syncEnabled, setSyncEnabled] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showFBPreview, setShowFBPreview] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [toast, setToast] = useState(null);

  const [inventoryList, setInventoryList] = useState([]);
  const [deletedHistory, setDeletedHistory] = useState([]);
  const [unitData, setUnitData] = useState(defaultEmptyUnit);
  const [searchQuery, setSearchQuery] = useState('');
  const [brands, setBrands] = useState([]);
  const [categories, setCategories] = useState([]);
  const [subcategories, setSubcategories] = useState([]);
  const [subSubcategories, setSubSubcategories] = useState([]);
  const [showBrandsModal, setShowBrandsModal] = useState(false);
  const [showCategoriesModal, setShowCategoriesModal] = useState(false);
  const [showSubcategoriesModal, setShowSubcategoriesModal] = useState(false);
  const [showSubSubcategoriesModal, setShowSubSubcategoriesModal] = useState(false);
  const [newBrandInput, setNewBrandInput] = useState('');
  const [newCategoryInput, setNewCategoryInput] = useState('');
  const [newSubcategoryInput, setNewSubcategoryInput] = useState('');
  const [newSubSubcategoryInput, setNewSubSubcategoryInput] = useState('');
  const [activeFilters, setActiveFilters] = useState({ status: [], categories: [], makes: [], models: [], yearMin: '', yearMax: '', priceMin: '', priceMax: '', conditions: [], stockSearch: '', vinSearch: '' });
  const [showFilterPanel, setShowFilterPanel] = useState(false);
  const [isPublicMode, setIsPublicMode] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});
  const [currentUser, setCurrentUser] = useState(null);
  const [sessionList, setSessionList] = useState([]);
  const [isSessionsLoading, setIsSessionsLoading] = useState(false);
  const [activityList, setActivityList] = useState([]);
  const [isActivityLoading, setIsActivityLoading] = useState(false);

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
    apiFetch('/me').then(setCurrentUser).catch(() => { });
    apiFetch('/brands').then(setBrands).catch(() => { });
    apiFetch('/categories').then(setCategories).catch(() => { });
    apiFetch('/subcategories').then(setSubcategories).catch(() => { });
    apiFetch('/sub-subcategories').then(setSubSubcategories).catch(() => { });
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

  const handleAddBrand = () => handleListAdd('brands', brands, newBrandInput, setBrands, setNewBrandInput);
  const handleDeleteBrand = (n) => handleListDelete('brands', brands, n, setBrands, 'make');
  const handleAddCategory = () => handleListAdd('categories', categories, newCategoryInput, setCategories, setNewCategoryInput);
  const handleDeleteCategory = (n) => handleListDelete('categories', categories, n, setCategories, 'category');
  const handleAddSubcategory = () => handleListAdd('subcategories', subcategories, newSubcategoryInput, setSubcategories, setNewSubcategoryInput);
  const handleDeleteSubcategory = (n) => handleListDelete('subcategories', subcategories, n, setSubcategories, 'subcategory');
  const handleAddSubSubcategory = () => handleListAdd('sub-subcategories', subSubcategories, newSubSubcategoryInput, setSubSubcategories, setNewSubSubcategoryInput);
  const handleDeleteSubSubcategory = (n) => handleListDelete('sub-subcategories', subSubcategories, n, setSubSubcategories, 'sub_subcategory');

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
      images: [...prev.images, ...results.map(r => r.url)],
      image_ids: [...prev.image_ids, ...results.map(r => r.id)],
    }));
  };

  const handleRemoveImage = (index) => {
    setUnitData(prev => ({
      ...prev,
      images: prev.images.filter((_, i) => i !== index),
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
        title: unitData.title || 'Untitled Unit',
        year: unitData.year,
        make: unitData.make,
        model: unitData.model,
        stock_number: unitData.stockNumber,
        vin: unitData.vin,
        price: unitData.price,
        call_for_price: unitData.callForPrice ?? false,
        condition: unitData.condition,
        stock_status: unitData.stockStatus,
        category: unitData.category,
        subcategory: unitData.subcategory,
        sub_subcategory: unitData.sub_subcategory,
        color: unitData.color,
        length: unitData.length,
        meter: unitData.meter,
        meter_type: unitData.meterType,
        intake_date: unitData.intakeDate,
        description: unitData.description,
        seller_info: unitData.sellerInfo,
        featured: unitData.featured ?? false,
        show_on_website: unitData.showOnWebsite ?? true,
        has_attachments: unitData.hasAttachments ?? false,
        attachment_details: unitData.attachmentDetails || '',
        drive: unitData.drive || '',
        image_ids: unitData.image_ids ?? [],
        implements: (unitData.attachments ?? []).map(a => ({
          title: a.title,
          price: a.price,
          description: a.description,
          image_id: a.image_id ?? 0,
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

  const handleBulkRestore = async (wpIds) => {
    try {
      await Promise.all(wpIds.map(wpId => apiFetch(`/inventory/${wpId}/restore`, { method: 'POST' })));
      await loadInventory();
      showToast(`${wpIds.length} units restored`);
    } catch (e) {
      showToast('Bulk restore failed: ' + e.message, 'error');
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

  const handleBulkPermanentDelete = async (wpIds) => {
    if (!window.confirm(`PERMANENT DELETE: This will permanently delete ${wpIds.length} selected units. This cannot be undone. Proceed?`)) return;
    try {
      await Promise.all(wpIds.map(wpId => apiFetch(`/inventory/${wpId}/permanent`, { method: 'DELETE' })));
      await loadInventory();
      showToast(`${wpIds.length} units permanently deleted`);
    } catch (e) {
      showToast('Bulk delete failed: ' + e.message, 'error');
    }
  };

  const handleAddNewUnit = () => { setUnitData(defaultEmptyUnit); setActiveTab('inventory'); };

  // Fetches complete unit data from API and opens editor
  const handleFullEdit = async (wpId) => {
    setActiveTab('inventory');
    try {
      const units = await apiFetch('/inventory');
      const unit = units.find(u => u.id === wpId);
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
    if (activeFilters.status.length && !activeFilters.status.includes(item.status)) return false;
    if (activeFilters.categories.length && !activeFilters.categories.includes(item.category)) return false;
    if (activeFilters.makes.length && !activeFilters.makes.includes(item.make)) return false;
    if (activeFilters.models.length && !activeFilters.models.includes(item.model)) return false;
    if (activeFilters.yearMin && parseInt(item.year) < parseInt(activeFilters.yearMin)) return false;
    if (activeFilters.yearMax && parseInt(item.year) > parseInt(activeFilters.yearMax)) return false;
    if (activeFilters.priceMin && parseInt(item.price || 0) < parseInt(activeFilters.priceMin)) return false;
    if (activeFilters.priceMax && parseInt(item.price || 0) > parseInt(activeFilters.priceMax)) return false;
    if (activeFilters.conditions.length && !activeFilters.conditions.includes(item.condition)) return false;
    if (activeFilters.stockSearch && !item.stock?.toLowerCase().includes(activeFilters.stockSearch.toLowerCase())) return false;
    if (activeFilters.vinSearch && !item.vin?.toLowerCase().includes(activeFilters.vinSearch.toLowerCase())) return false;
    return true;
  });

  const getHeaderTitle = () => {
    switch (activeTab) {
      case 'dashboard': return 'Operations Overview';
      case 'all-inventory': return 'Master Inventory Ledger';
      case 'inventory': return (
        <span className="flex items-center gap-2">
          {unitData.title || 'Inventory Editor'}
          <span className="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded uppercase tracking-tighter font-black">
            SKU: {unitData.stockNumber || 'PENDING'}
          </span>
        </span>
      );
      case 'marketplace': return 'Meta Commerce Sync';
      case 'history': return 'Deletion History / Recycle Bin';
      case 'mobile': return 'Mobile Companion Access';
      case 'settings': return 'Page Editor';
      case 'videos': return 'Videos Manager';
      case 'config': return 'System Settings & Audit';
      default: return 'Varner OS';
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
        subcategories={subcategories}
        subSubcategories={subSubcategories}
        setCategories={setCategories}
        setSubcategories={setSubcategories}
        setSubSubcategories={setSubSubcategories}
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
    <div className="flex bg-[#f8fafc] font-sans text-slate-900 selection:bg-red-100 min-h-screen">

      {/* Quill editor styles — injected once at app root */}
      <style dangerouslySetInnerHTML={{ __html: QUILL_STYLES }} />

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
            <button onClick={() => setIsMobileMenuOpen(true)} className="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-xl shrink-0"><Menu size={24} /></button>
            <div className="flex flex-col min-w-0">
              <h2 className="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1 hidden sm:block">System Modules</h2>
              <h3 className="text-base sm:text-xl font-black text-slate-950 tracking-tight leading-none uppercase truncate">{getHeaderTitle()}</h3>
            </div>
          </div>
          <div className="flex items-center gap-2 sm:gap-3">
            {activeTab === 'inventory' && unitData.title && (
              <button onClick={handleClone} className="bg-slate-100 text-slate-600 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95">
                <Copy size={16} /> <span className="hidden sm:inline">Clone Unit</span>
              </button>
            )}

            {activeTab === 'inventory' && (
              <button onClick={handleAddNewUnit} className="bg-red-600 text-white p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-red-700 transition-all border-b-2 border-red-800 shadow-xl shadow-red-100 active:scale-95">
                <Plus size={16} /> <span className="hidden sm:inline">New Unit</span>
              </button>
            )}

            {activeTab === 'all-inventory' && (
              <a
                href="/wp-admin/admin.php?page=pmxi-admin-import"
                className="bg-slate-100 text-slate-700 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95"
              >
                <Upload size={16} /> <span className="hidden sm:inline">Import Inventory</span><span className="sm:hidden">Import</span>
              </a>
            )}

            {activeTab === 'all-inventory' && (
              <a
                href="/wp-admin/admin.php?page=pmxe-admin-manage"
                className="bg-slate-100 text-slate-700 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95"
              >
                <Download size={16} /> <span className="hidden sm:inline">Export Inventory</span><span className="sm:hidden">Export</span>
              </a>
            )}

            {(activeTab === 'inventory' || activeTab === 'all-inventory') && (
              <button
                onClick={activeTab === 'inventory' ? handleSave : handleAddNewUnit}
                className="bg-red-600 text-white p-3 sm:px-7 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 active:scale-95 transition-all border-b-2 border-red-800"
              >
                {isSaving ? <Zap className="animate-spin" size={16} /> : (activeTab === 'inventory' ? <Save size={16} /> : <Plus size={16} />)}
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
                  <MetricCard icon={<Box size={24} />} label="Live Units" value={isLoading ? '…' : String(inventoryList.filter(i => (i.status || '').toLowerCase() === 'in stock').length)} subtext="In stock right now" color="blue" />
                  <MetricCard icon={<TrendingUp size={24} />} label="Total Units" value={isLoading ? '…' : String(inventoryList.length)} subtext="All active listings" color="amber" />
                  <MetricCard icon={<CheckCircle2 size={24} />} label="Sold Units" value={isLoading ? '…' : String(inventoryList.filter(i => (i.status || '').toLowerCase() === 'sold').length)} subtext="Marked as sold" color="green" />
                  <MetricCard icon={<Clock size={24} />} label="Pending Sales" value={isLoading ? '…' : String(inventoryList.filter(i => ['sale pending', 'pending sale', 'pending'].includes((i.status || '').toLowerCase())).length)} subtext="Awaiting close" color="red" />
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
                    onFilterChange={handleFilterChange} onKeywordSearch={setSearchQuery} onClearAll={handleClearFilters} />
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
                    {['makes', 'status', 'categories', 'models', 'conditions'].flatMap(key =>
                      activeFilters[key].map(v => (
                        <FilterTag key={`${key}-${v}`} label={v.toUpperCase()}
                          onRemove={() => handleFilterChange(key, activeFilters[key].filter(x => x !== v))} />
                      ))
                    )}
                    {(activeFilters.yearMin || activeFilters.yearMax) && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => { handleFilterChange('yearMin', ''); handleFilterChange('yearMax', ''); }} className="font-black leading-none hover:text-red-200">×</button>YEAR: {activeFilters.yearMin || '?'}–{activeFilters.yearMax || '?'}</span>}
                    {(activeFilters.priceMin || activeFilters.priceMax) && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => { handleFilterChange('priceMin', ''); handleFilterChange('priceMax', ''); }} className="font-black leading-none hover:text-red-200">×</button>PRICE: ${activeFilters.priceMin || '0'}–${activeFilters.priceMax || '∞'}</span>}
                    {activeFilters.stockSearch && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('stockSearch', '')} className="font-black leading-none hover:text-red-200">×</button>STOCK #: {activeFilters.stockSearch}</span>}
                    {activeFilters.vinSearch && <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-md"><button onClick={() => handleFilterChange('vinSearch', '')} className="font-black leading-none hover:text-red-200">×</button>VIN: {activeFilters.vinSearch}</span>}
                    <button onClick={handleClearFilters} className="ml-auto text-xs font-black text-slate-400 hover:text-red-600 uppercase tracking-widest transition-colors shrink-0">Clear All</button>
                  </div>
                )}

                <div className="flex flex-col gap-3">
                  {/* Mobile filter drawer button logic (FilterSidebar is now used in drawer for mobile) */}
                  {showFilterPanel && (
                    <div className="fixed inset-0 z-[9997] xl:hidden">
                      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setShowFilterPanel(false)} />
                      <div className="absolute inset-y-0 left-0 w-80 bg-white overflow-y-auto shadow-2xl">
                        <div className="flex items-center justify-between p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                          <h3 className="font-black text-sm uppercase tracking-widest">Filters</h3>
                          <button onClick={() => setShowFilterPanel(false)} className="p-1 text-gray-400 hover:text-gray-700"><X size={20} /></button>
                        </div>
                        <FilterSidebar inventoryList={inventoryList} filters={activeFilters} searchQuery={searchQuery}
                          onFilterChange={handleFilterChange} onKeywordSearch={setSearchQuery} onClearAll={handleClearFilters} />
                      </div>
                    </div>
                  )}

                  {/* Table / Master inventory ledger card */}
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
                              <tr key={item.id} className="hover:bg-slate-50 transition-all cursor-pointer group" onClick={() => handleFullEdit(item.wpId)}>
                                <td className="px-6 py-5 font-mono font-bold text-sm text-slate-500">{item.stock}</td>
                                <td className="px-4 py-3">
                                  <div className="w-40 h-28 bg-slate-100 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                                    {item.images?.[0]
                                      ? <img src={item.images[0]} alt={`${item.year} ${item.make} ${item.model}`} className="w-full h-full object-cover" onError={e => { e.target.onerror = null; e.target.src = 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }} />
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
                                <td className="px-6 py-5 text-center"><span className="text-[9px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 px-3 py-1 rounded-lg border border-blue-100 shadow-sm">{item.condition}</span></td>
                                <td className="px-6 py-5 font-black text-base tracking-tighter">
                                  {item.callForPrice
                                    ? <span className="text-red-600 text-[11px] uppercase tracking-widest">Call for Price</span>
                                    : <span className="text-slate-900">${parseInt(item.price || 0).toLocaleString()}</span>
                                  }
                                </td>
                                <td className="px-6 py-5">
                                  <span className={`inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border ${item.status === 'In Stock' ? 'text-green-500 bg-green-50 border-green-100' : item.status === 'Pending Sale' ? 'text-amber-500 bg-amber-50 border-amber-100' : 'text-slate-400 bg-slate-50 border-slate-200'}`}>
                                    <div className={`w-1.5 h-1.5 rounded-full ${item.status === 'In Stock' ? 'bg-green-500 animate-pulse' : item.status === 'Pending Sale' ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'}`}></div>
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
                                      <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 ${item.showOnWebsite ? 'left-7' : 'left-1'}`} />
                                    </button>
                                  </div>
                                </td>
                                <td className="px-6 py-5 text-center" onClick={e => e.stopPropagation()}>
                                  <div className="flex justify-center">
                                    <button onClick={() => handleToggleBoolean(item, 'featured')}
                                      className={`w-12 h-6 rounded-full relative transition-all duration-300 ${item.featured ? 'bg-amber-500' : 'bg-slate-200'}`}>
                                      <div className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 ${item.featured ? 'left-7' : 'left-1'}`} />
                                    </button>
                                  </div>
                                </td>
                                <td className="px-6 py-5 text-right">

                                  <div className="flex items-center justify-end gap-2" onClick={e => e.stopPropagation()}>
                                    <button onClick={() => handleFullEdit(item.wpId)} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Edit"><Edit2 size={16} /></button>
                                    <button onClick={() => handleFullEdit(item.wpId).then(handleClone)} className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95" title="Clone"><Copy size={16} /></button>
                                    <button onClick={() => handleDeleteUnit(item.wpId, item.stock)} className="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all active:scale-95" title="Delete"><X size={16} /></button>
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
                      <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2 leading-none"><Box size={14} className="text-red-600" /> Equipment Identity</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="md:col-span-2 animate-in fade-in duration-300">
                        <div className="space-y-4">
                          <div className="flex items-center justify-between pl-1">
                            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Equipment Category Hierarchy</label>
                          </div>

                          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* Category Dropdown */}
                            <div className="space-y-2">
                              <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1">Category *</label>
                              <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                                <select value={unitData.category || ''} onChange={e => handleCategorySelectChange(e.target.value)}
                                  className="w-full bg-transparent p-4 pr-12 font-bold text-slate-900 outline-none appearance-none cursor-pointer text-sm leading-none"
                                  style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                  <option value="">— Select Category —</option>
                                  {allCategories.map(c => <option key={c} value={c}>{c}</option>)}
                                </select>
                                <div className="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><ChevronRight size={18} className="rotate-90" /></div>
                              </div>
                              <button type="button" onClick={() => setShowCategoriesModal(true)}
                                className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest min-h-[64px] mt-2">
                                <Settings size={14} /> Manage Categories
                              </button>
                            </div>

                            {/* Subcategory Dropdown */}
                            <div className="space-y-2">
                              <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1">Subcategory</label>
                              <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                                <select value={unitData.subcategory || ''} onChange={e => handleSubcategorySelectChange(e.target.value)}
                                  className="w-full bg-transparent p-4 pr-12 font-bold text-slate-900 outline-none appearance-none cursor-pointer text-sm leading-none"
                                  style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                  <option value="">— Select Subcategory —</option>
                                  {allSubcategories.map(sub => <option key={sub} value={sub}>{sub}</option>)}
                                </select>
                                <div className="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><ChevronRight size={18} className="rotate-90" /></div>
                              </div>
                              <button type="button" onClick={() => setShowSubcategoriesModal(true)}
                                className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest min-h-[64px] mt-2">
                                <Settings size={14} /> Manage Subcategories
                              </button>
                            </div>

                            {/* Sub-Subcategory Dropdown */}
                            <div className="space-y-2">
                              <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1">Sub-Subcategory</label>
                              <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                                <select value={unitData.sub_subcategory || ''} onChange={e => handleSubSubcategorySelectChange(e.target.value)}
                                  className="w-full bg-transparent p-4 pr-12 font-bold text-slate-900 outline-none appearance-none cursor-pointer text-sm leading-none"
                                  style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                  <option value="">— Select Sub-Subcategory —</option>
                                  {allSubSubcategories.map(ss => <option key={ss} value={ss}>{ss}</option>)}
                                </select>
                                <div className="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><ChevronRight size={18} className="rotate-90" /></div>
                              </div>
                              <button type="button" onClick={() => setShowSubSubcategoriesModal(true)}
                                className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest min-h-[64px] mt-2">
                                <Settings size={14} /> Manage Sub-Subcategories
                              </button>
                            </div>
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
                                className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none"
                                style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                <option value="">— Select Brand —</option>
                                {brands.map(b => <option key={b} value={b}>{b}</option>)}
                              </select>
                              <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90" /></div>
                            </div>
                            <button type="button" onClick={() => setShowBrandsModal(true)}
                              className="bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest whitespace-nowrap min-h-[64px]">
                              <Settings size={14} /> Manage Brands
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
                                style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}
                              >
                                <option value="">— Select Year —</option>
                                {Array.from({ length: 2027 - 1950 + 1 }, (_, i) => 2027 - i).map(year => (
                                  <option key={year} value={year}>{year}</option>
                                ))}
                              </select>
                              <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400">
                                <ChevronRight size={24} className="rotate-90" />
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
                            className={`w-full bg-slate-50 border-2 rounded-xl p-4 font-black text-slate-900 outline-none transition-all shadow-sm text-xl leading-none min-h-[64px] ${fieldErrors.title ? 'border-red-400 focus:border-red-500 bg-red-50/40' : 'border-slate-100 focus:border-slate-300 focus:bg-white'}`}
                          />
                          {fieldErrors.title && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.title}</p>}
                        </div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1"><SelectField label="Stock Status" options={STATUS_OPTIONS} value={unitData.stockStatus} onChange={v => handleInputChange('stockStatus', v)} error={fieldErrors.stockStatus} /></div>
                        <div className="flex-1"><SelectField label="Condition" options={CONDITION_OPTIONS} value={unitData.condition} onChange={v => handleInputChange('condition', v)} error={fieldErrors.condition} /></div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1">
                          <SelectField
                            label="Color"
                            placeholder="Choose Color"
                            options={COLOR_OPTIONS}
                            value={unitData.color}
                            onChange={v => handleInputChange('color', v)}
                            error={fieldErrors.color}
                          />
                        </div>
                        <div className="flex-1"><InputField label="Length (e.g. 20 ft)" value={unitData.length} onChange={v => handleInputChange('length', v)} error={fieldErrors.length} /></div>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-3 md:col-span-2">
                        <div className="flex-1 space-y-3">
                          <InputField label="Meter Reading" value={unitData.meter} onChange={v => handleInputChange('meter', v)} error={fieldErrors.meter} placeholder="e.g. 250" />
                          <SelectField label="Meter Type" options={METER_TYPE_OPTIONS} value={unitData.meterType} onChange={v => handleInputChange('meterType', v)} />
                        </div>
                        <div className="flex-1 space-y-3">
                          <InputField label="Drive" value={unitData.drive} onChange={v => handleInputChange('drive', v)} error={fieldErrors.drive} placeholder="e.g. 4WD / 2WD" />
                          <SelectField label="Attachments" options={['No', 'Yes']} value={unitData.hasAttachments ? 'Yes' : 'No'} onChange={v => handleInputChange('hasAttachments', v === 'Yes')} />
                        </div>
                      </div>
                      {unitData.hasAttachments && (
                        <div className="md:col-span-2">
                          <InputField label="Attachment Details" value={unitData.attachmentDetails} onChange={v => handleInputChange('attachmentDetails', v)} placeholder="Describe the included attachment(s)..." />
                        </div>
                      )}
                      <div className="md:col-span-2 border-y border-slate-50 py-6 my-2 grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">VIN / SERIAL NUMBER</label>
                          <input type="text" value={unitData.vin} onChange={e => handleInputChange('vin', e.target.value)}
                            className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-mono font-black text-xl text-slate-900 outline-none shadow-inner focus:border-red-500 focus:bg-white transition-all tracking-widest uppercase min-h-[64px]"
                            placeholder="TYPE SERIAL..." />
                        </div>
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Stock Number</label>
                          <input type="text" value={unitData.stockNumber} onChange={e => handleInputChange('stockNumber', e.target.value)}
                            className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-mono font-black text-xl text-slate-900 outline-none shadow-inner focus:border-red-500 focus:bg-white transition-all tracking-widest uppercase min-h-[64px]"
                            placeholder="STOCK #" />
                        </div>
                      </div>
                      <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 px-1 items-end">
                        <div className="space-y-3">
                          <label className="text-[10px] font-black text-green-600 uppercase tracking-widest block pl-1">Retail Price (USD)</label>
                          <label className="flex items-center gap-3 cursor-pointer group w-fit ml-1">
                            <div className="relative flex items-center">
                              <input type="checkbox" checked={unitData.callForPrice} onChange={e => handleInputChange('callForPrice', e.target.checked)} className="sr-only peer" />
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
                              className="flex-1 bg-transparent p-4 font-black text-slate-900 outline-none text-xl leading-none" placeholder="0.00" />
                          </div>
                          {fieldErrors.price && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.price}</p>}
                        </div>
                      </div>

                      <div className="md:col-span-2 space-y-4">
                        <div className="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-red-200 transition-all">
                          <div className="flex items-center gap-4">
                            <div className={`p-3 rounded-xl transition-all ${unitData.featured ? 'bg-amber-100 text-amber-600' : 'bg-white text-slate-300'}`}>
                              <Star size={20} fill={unitData.featured ? 'currentColor' : 'none'} />
                            </div>
                            <div>
                              <p className="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">Featured Unit</p>
                              <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Display at the top of the homepage</p>
                            </div>
                          </div>
                          <button type="button" onClick={() => handleInputChange('featured', !unitData.featured)}
                            className={`w-14 h-7 rounded-full relative transition-all duration-300 ${unitData.featured ? 'bg-amber-500' : 'bg-slate-200'}`}>
                            <div className={`absolute top-1 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300 ${unitData.featured ? 'left-8' : 'left-1'}`} />
                          </button>
                        </div>

                        <div className="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-red-200 transition-all">
                          <div className="flex items-center gap-4">
                            <div className={`p-3 rounded-xl transition-all ${unitData.showOnWebsite ? 'bg-green-100 text-green-600' : 'bg-white text-slate-300'}`}>
                              <Eye size={20} />
                            </div>
                            <div>
                              <p className="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">Website Visibility</p>
                              <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Publicly visible on showroom pages</p>
                            </div>
                          </div>
                          <button type="button" onClick={() => handleInputChange('showOnWebsite', !unitData.showOnWebsite)}
                            className={`w-14 h-7 rounded-full relative transition-all duration-300 ${unitData.showOnWebsite ? 'bg-green-600' : 'bg-slate-200'}`}>
                            <div className={`absolute top-1 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300 ${unitData.showOnWebsite ? 'left-8' : 'left-1'}`} />
                          </button>
                        </div>
                      </div>
                      <div className="md:col-span-2 space-y-6 pt-6 border-t border-slate-50">
                        <TextAreaField label="Public Description / Features" value={unitData.description} onChange={v => handleInputChange('description', v)} />
                        <TextAreaField label="Seller Information Template" value={unitData.sellerInfo} onChange={v => handleInputChange('sellerInfo', v)} />
                      </div>
                    </div>
                  </div>

                  <MediaSection title="High-Resolution Media" images={unitData.images} onAddFiles={handleAddImages} onRemove={handleRemoveImage} onReorder={handleReorderImages} />
                  <AttachmentsSection attachments={unitData.attachments} onAdd={handleAddImplement} onChange={handleUpdateImplement} onRemove={handleRemoveImplement} onImageUpload={handleImplementImageUpload} />

                  {/* BOTTOM ACTION BUTTONS */}
                  <div className="flex flex-col sm:flex-row gap-4 pt-6">
                    <button
                      onClick={handleClone}
                      disabled={!unitData.id}
                      className={`flex-1 px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 transition-all active:scale-95 border-2 shadow-xl shadow-slate-200/50 ${!unitData.id ? 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed' : 'bg-white text-slate-600 border-slate-100 hover:bg-slate-50'}`}
                    >
                      <Copy size={18} />
                      Clone Unit
                    </button>

                    <button
                      onClick={handleSave}
                      disabled={isSaving}
                      className="flex-[2] bg-red-600 text-white px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-red-700 transition-all active:scale-95 shadow-2xl shadow-red-200 border-b-4 border-red-800 disabled:opacity-50"
                    >
                      {isSaving ? <Loader2 className="animate-spin" size={18} /> : <Save size={18} />}
                      {isSaving ? 'PUBLISHING…' : 'PUBLISH TO INVENTORY'}
                    </button>
                  </div>
                </div>

                {/* RIGHT — MARKETPLACE WIDGET */}
                <div className="space-y-8">
                  <div className="bg-white rounded-[2rem] overflow-hidden shadow-2xl border border-slate-200/60 flex flex-col">
                    <div className="bg-slate-950 p-6 text-white flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <div className="bg-blue-600 p-2.5 rounded-xl"><Facebook size={20} fill="white" /></div>
                        <div>
                          <h4 className="font-black text-sm uppercase tracking-tight leading-none mb-1">Meta Marketplace</h4>
                          <p className="text-[8px] text-slate-500 uppercase font-black tracking-widest">Auto-Sync Active</p>
                        </div>
                      </div>
                      <button onClick={() => setSyncEnabled(!syncEnabled)} className={`w-14 h-7 rounded-full relative transition-all duration-300 ${syncEnabled ? 'bg-blue-600' : 'bg-slate-800'}`}>
                        <div className={`absolute top-1 w-5 h-5 bg-white rounded-full transition-all duration-300 ${syncEnabled ? 'left-8' : 'left-1'}`} />
                      </button>
                    </div>
                    <div className="p-8 space-y-8 bg-white text-slate-900">
                      <div className="flex items-center gap-4 p-5 bg-blue-50/40 border-2 border-blue-100 rounded-[1.5rem]">
                        <div className="bg-white p-2 rounded-full border border-blue-200 shadow-md text-blue-600"><CheckCircle2 size={20} /></div>
                        <div>
                          <p className="text-[11px] font-black text-blue-950 uppercase leading-none mb-1">Facebook Catalog Synced</p>
                          <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest italic">Refreshed 2m ago</p>
                        </div>
                      </div>
                      <div className="space-y-4 px-1 font-black text-slate-900">
                        <h5 className="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mb-4">Catalog Mapping Logic</h5>
                        <MappingRow label="Vehicle Category" value="Agriculture / Tractor" />
                        <MappingRow label="Location Tag" value="Delta, CO (150mi)" />
                        <MappingRow label="Price Format" value="USD Fixed" />
                      </div>
                      <button onClick={() => setShowFBPreview(true)} className="w-full bg-slate-950 text-white py-6 rounded-[1.5rem] font-black text-[13px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-black transition-all active:scale-95 shadow-2xl shadow-slate-300 mt-2 leading-none border-b-4 border-slate-800">
                        View Marketplace Preview <ArrowUpRight size={18} className="text-blue-400" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'marketplace' && <MarketplaceTab />}
            {activeTab === 'settings' && <SettingsTab showToast={showToast} />}
            {activeTab === 'videos' && <VideosTab showToast={showToast} />}
            {activeTab === 'mobile' && <MobileAccessTab />}
            {activeTab === 'config' && (
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
            {activeTab === 'history' && (
              <HistoryTab
                deletedItems={deletedHistory}
                onRestore={item => handleRestoreUnit(item.wpId)}
                onPermanentDelete={item => handlePermanentDelete(item.wpId)}
                onBulkRestore={handleBulkRestore}
                onBulkPermanentDelete={handleBulkPermanentDelete}
              />
            )}

          </div>
        </div>
      </main>

      {showFBPreview && <FBPreviewModal unitData={unitData} onClose={() => setShowFBPreview(false)} />}
    </div>
  );
};

export default App;
