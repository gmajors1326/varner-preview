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

import { apiFetch, uploadFile } from './utils/api';
import { apiToLocal, apiToListItem, getDaysInStock } from './utils/helpers';

import { SidebarLogo, SidebarContent, FilterTag, MappingRow } from './components/Common/Navigation';
import { ManageListModal } from './components/Common/Modals';
import { InputField, TextAreaField, SelectField, QUILL_STYLES } from './components/Common/FormFields';
import { MetricCard, QuickActions, RecentActivity } from './components/Common/DashboardCards';

import { FBPreviewModal } from './components/FBPreviewModal';
import { InventoryTable } from './components/InventoryTable';
import { UnitEditorPanel } from './components/UnitEditorPanel';

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

  // Priority: PHP-injected auto-token â†’ URL ?token= param â†’ localStorage
  const _varnerToken = window.varnerData?.mobile_token || '';
  if (_varnerToken) localStorage.setItem('varner_mobile_token', _varnerToken);

  const [mobileToken, setMobileToken] = useState(
    _varnerToken ||
    new URLSearchParams(window.location.search).get('token') ||
    localStorage.getItem('varner_mobile_token') || ''
  );
  const [mobileActiveTab, setMobileActiveTab] = useState('edit');

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
  const [isUploadingImages, setIsUploadingImages] = useState(false);

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
      // /inventory returns a flat array by default (no per_page param).
      // Guard against paginated shape { items, total } in case server behaviour changes.
      const activeRaw = await apiFetch('/inventory');
      const activeItems = Array.isArray(activeRaw) ? activeRaw : (activeRaw?.items ?? []);
      setInventoryList(activeItems.map(apiToListItem));

      // Fetch deleted history separately so a permissions/nonce failure here
      // doesn't block the main inventory list from loading.
      try {
        const deletedRaw = await apiFetch('/inventory/deleted');
        const deletedItems = Array.isArray(deletedRaw) ? deletedRaw : (deletedRaw?.items ?? []);
        setDeletedHistory(deletedItems.map(item => ({
          ...apiToListItem(item),
          deletedAt: item.deleted_at,
        })));
      } catch (delErr) {
        console.warn('Varner OS: Could not load deleted inventory:', delErr.message);
      }
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
    if (!window.confirm(`Delete "${name}" from ${endpoint}?`)) return;
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
    setUnitData(prev => {
      const next = { ...prev, [field]: value };
      if (['year', 'make', 'model'].includes(field)) {
        next.title = `${next.year || ''} ${next.make || ''} ${next.model || ''}`.trim();
      }
      return next;
    });

    // Clear field-level error when user edits the field
    if (fieldErrors[field]) {
      setFieldErrors(prev => {
        const next = { ...prev }; delete next[field]; return next;
      });
    }

    // Bidirectional sync: If toggling visibility, featured status, or Meta sync in the editor,
    // update the corresponding list item and save to the database immediately.
    if ((field === 'featured' || field === 'showOnWebsite' || field === 'facebookSync') && unitData.id) {
      setInventoryList(prev => prev.map(u => u.wpId === unitData.id ? { ...u, [field]: value } : u));

      const wpField = field === 'showOnWebsite' ? 'show_on_website' : (field === 'facebookSync' ? 'facebook_sync' : 'featured');
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

  // Image upload â€” sends file to WP Media Library, stores id + url
  const handleAddImages = async (files) => {
    if (!files || !files.length) return;
    setIsUploadingImages(true);
    showToast('Uploading images...', 'info');
    try {
      const results = await Promise.all(files.map(uploadFile));
      setUnitData(prev => ({
        ...prev,
        images: [...prev.images, ...results.map(r => r.url)],
        image_ids: [...prev.image_ids, ...results.map(r => r.id)],
      }));
      showToast('Images uploaded successfully!');
    } catch (err) {
      showToast('Image upload failed: ' + err.message, 'error');
    } finally {
      setIsUploadingImages(false);
    }
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
        facebook_sync: unitData.facebookSync ?? true,
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
    let fieldKey;
    if (field === 'show_on_website') {
      fieldKey = 'showOnWebsite';
    } else if (field === 'facebook_sync') {
      fieldKey = 'facebookSync';
    } else {
      fieldKey = 'featured';
    }
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

  const handleToggleDraft = async (item) => {
    const newStatus = item.status === 'Draft' ? 'In Stock' : 'Draft';

    // Optimistic update for list
    setInventoryList(prev => prev.map(u => u.wpId === item.wpId ? { ...u, status: newStatus } : u));

    // Update unitData if currently editing this unit (keeps editor toggle + dropdown in sync)
    if (unitData.id === item.wpId) {
      setUnitData(prev => ({ ...prev, stockStatus: newStatus }));
    }

    // New unit not yet created — nothing to persist; the create Save carries stock_status.
    if (!item.wpId) return;

    try {
      await apiFetch(`/inventory/${item.wpId}`, {
        method: 'PATCH',
        body: JSON.stringify({ stock_status: newStatus })
      });
    } catch (e) {
      showToast(`Failed to update draft status: ${e.message}`, 'error');
      loadInventory(); // Rollback list
      if (unitData.id === item.wpId) {
        setUnitData(prev => ({ ...prev, stockStatus: item.status }));
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

  const handleNav = (tab) => {
    if (tab === 'inventory' && activeTab !== 'inventory') {
      setUnitData(defaultEmptyUnit);
    }
    setActiveTab(tab);
    setIsMobileMenuOpen(false);
  };

  const handleAddNewUnit = () => { setUnitData(defaultEmptyUnit); setActiveTab('inventory'); };

  // Fetches complete unit data from API and opens editor
  const handleFullEdit = async (wpId) => {
    setActiveTab('inventory');
    if (!wpId) return null;
    try {
      const unit = await apiFetch(`/inventory/${wpId}`);
      if (unit) {
        setUnitData(apiToLocal(unit));
        return unit;
      }
    } catch {
      // Fall back to searching the list if individual endpoint fails
      try {
        const units = await apiFetch('/inventory');
        const list = Array.isArray(units) ? units : (units?.items ?? []);
        const found = list.find(u => u.id === wpId);
        if (found) {
          setUnitData(apiToLocal(found));
          return found;
        }
      } catch { /* tab already switched; user will see empty editor */ }
    }
    return null;
  };

  const handleClone = (source = null) => {
    // Tolerate any caller shape: React events (onClick) and null → clone the open unit;
    // an object → clone it (normalizing API shape); a bare id should never reach here now,
    // but if it does, fall back to the open unit rather than spreading a primitive.
    const isEvent  = source && typeof source === 'object' && (source.nativeEvent || source._reactName);
    const isObject = source && typeof source === 'object' && !isEvent;
    const base = isObject ? (source.id ? apiToLocal(source) : source) : unitData;

    if (!base || (!base.title && !base.stockNumber && !base.make)) {
      showToast?.('Open a unit before cloning', 'error');
      return;
    }

    setUnitData({
      ...base,
      id: null,
      stockNumber: (base.stockNumber || '') + '-COPY',
      title: (base.title || '') + ' (Copy)',
      vin: '',
    });
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
        isUploadingImages={isUploadingImages}
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
        deletedHistory={deletedHistory}
        handleRestoreUnit={handleRestoreUnit}
        handlePermanentDelete={handlePermanentDelete}
        handleBulkRestore={handleBulkRestore}
        handleBulkPermanentDelete={handleBulkPermanentDelete}
        handleClone={handleClone}
      />
    );
  }

  return (
    <div className="flex bg-[#f8fafc] font-sans text-slate-900 selection:bg-red-100 min-h-screen">

      {/* Quill editor styles â€” injected once at app root */}
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
              onNav={handleNav} />
          </aside>
        </div>
      )}

      {/* SIDEBAR */}
      <aside className="hidden lg:flex flex-col w-72 bg-slate-950 text-white p-6 shadow-2xl border-r border-slate-800 shrink-0">
        <SidebarContent activeTab={activeTab} inventoryList={inventoryList} deletedHistory={deletedHistory}
          onNav={handleNav} />
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
              <button onClick={() => handleClone()} className="bg-slate-100 text-slate-600 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95">
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
                <span className="hidden sm:inline">{isSaving ? 'PUBLISHINGâ€¦' : (activeTab === 'inventory' ? 'PUBLISH TO INVENTORY' : 'NEW UNIT')}</span>
                <span className="sm:hidden">{activeTab === 'inventory' ? (isSaving ? 'PUBâ€¦' : 'PUBLISH') : 'NEW'}</span>
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
                  <MetricCard icon={<Box size={24} />} label="Live Units" value={isLoading ? 'â€¦' : String(inventoryList.filter(i => (i.status || '').toLowerCase() === 'in stock').length)} subtext="In stock right now" color="blue" />
                  <MetricCard icon={<TrendingUp size={24} />} label="Total Units" value={isLoading ? 'â€¦' : String(inventoryList.length)} subtext="All active listings" color="amber" />
                  <MetricCard icon={<CheckCircle2 size={24} />} label="Sold Units" value={isLoading ? 'â€¦' : String(inventoryList.filter(i => (i.status || '').toLowerCase() === 'sold').length)} subtext="Marked as sold" color="green" />
                  <MetricCard icon={<Clock size={24} />} label="Pending Sales" value={isLoading ? 'â€¦' : String(inventoryList.filter(i => ['sale pending', 'pending sale', 'pending'].includes((i.status || '').toLowerCase())).length)} subtext="Awaiting close" color="red" />
                </div>
                <div className="space-y-8">
                  <QuickActions onAdd={handleAddNewUnit} />
                  <RecentActivity />
                </div>
              </div>
            )}

            {/* MASTER INVENTORY */}
            {activeTab === 'all-inventory' && (
              <InventoryTable
                filteredInventory={filteredInventory}
                inventoryList={inventoryList}
                isLoading={isLoading}
                activeFilters={activeFilters}
                searchQuery={searchQuery}
                onFilterChange={handleFilterChange}
                onSearch={setSearchQuery}
                onClearFilters={handleClearFilters}
                onEdit={handleFullEdit}
                onDelete={handleDeleteUnit}
                onClone={(wpId) => handleFullEdit(wpId).then(handleClone)}
                onToggle={handleToggleBoolean}
                onToggleDraft={handleToggleDraft}
              />
            )}

            {/* UNIT EDITOR */}
            {activeTab === 'inventory' && (
              <UnitEditorPanel
                unitData={unitData}
                handleInputChange={handleInputChange}
                onToggleDraft={handleToggleDraft}
                handleSave={handleSave}
                handleClone={handleClone}
                isSaving={isSaving}
                isUploadingImages={isUploadingImages}
                fieldErrors={fieldErrors}
                brands={brands}
                categories={categories}
                subcategories={subcategories}
                subSubcategories={subSubcategories}
                handleCategorySelectChange={handleCategorySelectChange}
                handleSubcategorySelectChange={handleSubcategorySelectChange}
                handleSubSubcategorySelectChange={handleSubSubcategorySelectChange}
                handleAddImages={handleAddImages}
                handleRemoveImage={handleRemoveImage}
                handleReorderImages={handleReorderImages}
                handleAddImplement={handleAddImplement}
                handleUpdateImplement={handleUpdateImplement}
                handleRemoveImplement={handleRemoveImplement}
                handleImplementImageUpload={handleImplementImageUpload}
                setShowBrandsModal={setShowBrandsModal}
                setShowCategoriesModal={setShowCategoriesModal}
                setShowSubcategoriesModal={setShowSubcategoriesModal}
                setShowSubSubcategoriesModal={setShowSubSubcategoriesModal}
                syncEnabled={syncEnabled}
                setSyncEnabled={setSyncEnabled}
                setShowFBPreview={setShowFBPreview}
              />
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
                onNav={handleNav}
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
