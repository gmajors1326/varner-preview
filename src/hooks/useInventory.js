import { useState, useEffect, useCallback } from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { apiFetch, uploadFile } from '../utils/api';
import { apiToLocal, apiToListItem } from '../utils/helpers';
import { DEFAULT_EMPTY_UNIT, getCategoryLabel } from '../constants/inventoryConstants';

export function useInventory(showToast, setActiveTab) {
  const [syncEnabled, setSyncEnabled] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isUploadingImages, setIsUploadingImages] = useState(false);
  const [showFBPreview, setShowFBPreview] = useState(false);

  const [inventoryList, setInventoryList] = useState([]);
  const [deletedHistory, setDeletedHistory] = useState([]);
  const [unitData, setUnitData] = useState(DEFAULT_EMPTY_UNIT);
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
  const [fieldErrors, setFieldErrors] = useState({});
  const [currentUser, setCurrentUser] = useState(null);
  const [sessionList, setSessionList] = useState([]);
  const [isSessionsLoading, setIsSessionsLoading] = useState(false);
  const [activityList, setActivityList] = useState([]);
  const [isActivityLoading, setIsActivityLoading] = useState(false);

  const [isPublicMode, setIsPublicMode] = useState(false);
  useEffect(() => {
    setIsPublicMode(!!document.querySelector('.varner-public-showroom'));
  }, []);

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

  const loadInventory = useCallback(async () => {
    setIsLoading(true);
    try {
      const activeRaw = await apiFetch('/inventory');
      const activeItems = Array.isArray(activeRaw) ? activeRaw : (activeRaw?.items ?? []);
      setInventoryList(activeItems.map(apiToListItem));

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
  }, [showToast]);

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
  }, [showToast]);

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
  }, [showToast]);

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
    // eslint-disable-next-line react-hooks/exhaustive-deps
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

    if (fieldErrors[field]) {
      setFieldErrors(prev => {
        const next = { ...prev }; delete next[field]; return next;
      });
    }

    if ((field === 'featured' || field === 'showOnWebsite' || field === 'facebookSync') && unitData.id) {
      setInventoryList(prev => prev.map(u => u.wpId === unitData.id ? { ...u, [field]: value } : u));

      const wpField = field === 'showOnWebsite' ? 'show_on_website' : (field === 'facebookSync' ? 'facebook_sync' : 'featured');
      apiFetch(`/inventory/${unitData.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ [wpField]: value })
      }).catch(e => {
        showToast(`Sync failed: ${e.message}`, 'error');
        setUnitData(prev => ({ ...prev, [field]: !value }));
        setInventoryList(prev => prev.map(u => u.wpId === unitData.id ? { ...u, [field]: !value } : u));
      });
    }
  };

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
      if (unitData.id === wpId) { setUnitData(DEFAULT_EMPTY_UNIT); setActiveTab('all-inventory'); }
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

    setInventoryList(prev => prev.map(u => u.wpId === item.wpId ? { ...u, [fieldKey]: newVal } : u));

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
      loadInventory();
      if (unitData.id === item.wpId) {
        setUnitData(prev => ({ ...prev, [fieldKey]: !newVal }));
      }
    }
  };

  const handleToggleDraft = async (item) => {
    const newStatus = item.status === 'Draft' ? 'In Stock' : 'Draft';

    setInventoryList(prev => prev.map(u => u.wpId === item.wpId ? { ...u, status: newStatus } : u));

    if (unitData.id === item.wpId) {
      setUnitData(prev => ({ ...prev, stockStatus: newStatus }));
    }

    if (!item.wpId) return;

    try {
      await apiFetch(`/inventory/${item.wpId}`, {
        method: 'PATCH',
        body: JSON.stringify({ stock_status: newStatus })
      });
    } catch (e) {
      showToast(`Failed to update draft status: ${e.message}`, 'error');
      loadInventory();
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
      try {
        const units = await apiFetch('/inventory');
        const list = Array.isArray(units) ? units : (units?.items ?? []);
        const found = list.find(u => u.id === wpId);
        if (found) {
          setUnitData(apiToLocal(found));
          return found;
        }
      } catch { /* */ }
    }
    return null;
  };

  const handleClone = (source = null) => {
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

  return {
    syncEnabled, setSyncEnabled,
    isSaving, isLoading, isUploadingImages,
    showFBPreview, setShowFBPreview,
    inventoryList, deletedHistory, unitData, setUnitData,
    brands, categories, subcategories, subSubcategories,
    setCategories, setSubcategories, setSubSubcategories,
    showBrandsModal, setShowBrandsModal,
    showCategoriesModal, setShowCategoriesModal,
    showSubcategoriesModal, setShowSubcategoriesModal,
    showSubSubcategoriesModal, setShowSubSubcategoriesModal,
    newBrandInput, setNewBrandInput,
    newCategoryInput, setNewCategoryInput,
    newSubcategoryInput, setNewSubcategoryInput,
    newSubSubcategoryInput, setNewSubSubcategoryInput,
    fieldErrors, setFieldErrors,
    currentUser, sessionList, isSessionsLoading,
    activityList, isActivityLoading,
    isPublicMode,
    loadInventory, loadSessions, loadActivity,
    handleCategorySelectChange, handleSubcategorySelectChange, handleSubSubcategorySelectChange,
    handleAddBrand, handleDeleteBrand,
    handleAddCategory, handleDeleteCategory,
    handleAddSubcategory, handleDeleteSubcategory,
    handleAddSubSubcategory, handleDeleteSubSubcategory,
    handleInputChange,
    handleAddImages, handleRemoveImage, handleReorderImages,
    handleAddImplement, handleUpdateImplement, handleRemoveImplement, handleImplementImageUpload,
    handleSave, handleDeleteUnit, handleRestoreUnit, handleBulkRestore,
    handlePermanentDelete, handleBulkPermanentDelete,
    handleToggleBoolean, handleToggleDraft,
    handleFullEdit, handleClone,
  };
}
