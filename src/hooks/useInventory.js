import { useState, useEffect, useCallback, useRef } from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { apiFetch, uploadFile } from '../utils/api';
import { apiToLocal, apiToListItem } from '../utils/helpers';
import { DEFAULT_EMPTY_UNIT, getCategoryLabel } from '../constants/inventoryConstants';

export function useInventory(showToast, setActiveTab) {
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isUploadingImages, setIsUploadingImages] = useState(false);

  const [inventoryList, setInventoryList] = useState([]);
  const [deletedHistory, setDeletedHistory] = useState([]);
  const [unitData, setUnitData] = useState(DEFAULT_EMPTY_UNIT);
  const [brands, setBrands] = useState([]);
  const [years, setYears] = useState([]);
  const [categories, setCategories] = useState([]);
  const [subcategories, setSubcategories] = useState([]);
  const [categoryTree, setCategoryTree] = useState({});
  const [showBrandsModal, setShowBrandsModal] = useState(false);
  const [showYearsModal, setShowYearsModal] = useState(false);
  const [newBrandInput, setNewBrandInput] = useState('');
  const [newYearInput, setNewYearInput] = useState('');
  const [newCategoryInput, setNewCategoryInput] = useState('');
  const [newSubcategoryInput, setNewSubcategoryInput] = useState('');
  const [showCategoryManager, setShowCategoryManager] = useState(false);

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

  const categoryMutating = useRef(false);
  const prevCategoryManagerOpen = useRef(false);

  const updateCategoryStateFromTree = (tree) => {
    setCategoryTree(tree);
    setCategories(Object.keys(tree).sort());
    const subs = [...new Set(Object.values(tree).flatMap(o => Object.keys(o)))].sort();
    setSubcategories(subs);
  };

  const handleCategorySelectChange = (val) => {
    setUnitData(prev => ({
      ...prev,
      category: val,
      subcategory: '',
    }));
  };

  const handleSubcategorySelectChange = (val) => {
    setUnitData(prev => ({
      ...prev,
      subcategory: val,
    }));
  };

  const loadInventory = useCallback(async () => {
    setIsLoading(true);
    try {
      const perPage = 100;
      let page = 1;
      let allItems = [];
      let total = 0;

      // Fetch page 1 to learn total
      const first = await apiFetch(`/inventory?per_page=${perPage}&page=${page}`);
      const firstItems = Array.isArray(first) ? first : (first?.items ?? []);
      total = Array.isArray(first) ? firstItems.length : (first?.total ?? firstItems.length);
      allItems = firstItems;

      // Fetch remaining pages sequentially if needed
      const totalPages = Math.ceil(total / perPage);
      while (page < totalPages) {
        page += 1;
        const next = await apiFetch(`/inventory?per_page=${perPage}&page=${page}`);
        const nextItems = Array.isArray(next) ? next : (next?.items ?? []);
        allItems = allItems.concat(nextItems);
      }

      setInventoryList(allItems.map(apiToListItem));

      try {
        const deletedRaw = await apiFetch('/inventory/deleted?per_page=200');
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

  const loadBrands = useCallback(() => {
    apiFetch('/brands').then(setBrands).catch(e => console.error('Varner OS: Failed to load brands:', e));
  }, []);

  const loadYears = useCallback(() => {
    apiFetch('/years').then(setYears).catch(e => console.error('Varner OS: Failed to load years:', e));
  }, []);

  const loadCategories = useCallback(() => {
    apiFetch('/categories').then(setCategories).catch(e => console.error('Varner OS: Failed to load categories:', e));
    apiFetch('/subcategories').then(setSubcategories).catch(e => console.error('Varner OS: Failed to load subcategories:', e));
    apiFetch('/category-tree').then(setCategoryTree).catch(e => console.error('Varner OS: Failed to load category tree:', e));
  }, []);

  useEffect(() => {
    const isVarnerOSPage = window.location.search.includes('page=varner-os');
    const isEquipmentEdit = window.location.pathname.includes('post.php') || window.location.pathname.includes('post-new.php');
    const postId = window.varnerData?.post_id;

    if (!isVarnerOSPage && (isEquipmentEdit || (postId && postId > 0))) {
      handleFullEdit(postId);
    }

    // ── Staggered loading to avoid Cloudflare 429 rate limits ──
    // Phase 1: Load inventory immediately (critical path — user sees the list)
    loadInventory();

    // Phase 2: Load auxiliary data after a delay (brands, categories, user profile)
    const auxTimer = setTimeout(() => {
      apiFetch('/me').then(setCurrentUser).catch(e => console.error('Varner OS: Failed to load user:', e));
      loadBrands();
      loadYears();
      setTimeout(() => loadCategories(), 400);
    }, 600);

    return () => clearTimeout(auxTimer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [loadInventory]);

  // Re-fetch taxonomy lists whenever a Manage modal opens
  useEffect(() => {
    if (showBrandsModal) loadBrands();
  }, [showBrandsModal, loadBrands]);

  useEffect(() => {
    if (showYearsModal) loadYears();
  }, [showYearsModal, loadYears]);

  useEffect(() => {
    const isOpen = showCategoryManager;
    if (isOpen && !prevCategoryManagerOpen.current) {
      loadCategories();
    }
    prevCategoryManagerOpen.current = isOpen;
  }, [showCategoryManager, loadCategories]);

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
  const handleAddYear = () => handleListAdd('years', years, newYearInput, setYears, setNewYearInput);
  const handleDeleteYear = (n) => handleListDelete('years', years, n, setYears, 'year');
  const handleAddCategoryNode = async (type, name, parentCat = null) => {
    const term = name.trim();
    if (!term) return false;

    categoryMutating.current = true;
    try {
      const body = { type, name: term };
      if (parentCat) body.parent_category = parentCat;

      const response = await apiFetch('/category-tree/node', {
        method: 'POST',
        body: JSON.stringify(body),
      });

      if (response && response.category_tree) {
        updateCategoryStateFromTree(response.category_tree);
      }

      if (type === 'category') setNewCategoryInput('');
      else if (type === 'subcategory') setNewSubcategoryInput('');
      return true;
    } catch (e) {
      showToast('Failed to add category item: ' + e.message, 'error');
      return false;
    } finally {
      categoryMutating.current = false;
    }
  };

  const handleDeleteCategoryNode = async (type, name, parentCat = null, reassignTo = null) => {
    const target = name.trim();
    if (!target) return { success: false };

    const msg = type === 'category'
      ? `Delete "${name}" and all its subcategories?`
      : `Delete "${name}"?`;
    if (!window.confirm(msg)) return { success: false, cancelled: true };

    categoryMutating.current = true;
    try {
      const body = { type, name: target };
      if (parentCat) body.parent_category = parentCat;
      if (reassignTo) body.reassign_to = reassignTo;

      const response = await apiFetch('/category-tree/node', {
        method: 'DELETE',
        body: JSON.stringify(body),
      });

      if (response && response.category_tree) {
        updateCategoryStateFromTree(response.category_tree);
      }

      if (type === 'category' && unitData.category === target) {
        setUnitData(prev => ({ ...prev, category: '', subcategory: '' }));
      } else if (type === 'subcategory' && unitData.subcategory === target) {
        setUnitData(prev => ({ ...prev, subcategory: '' }));
      }

      showToast(`Deleted "${name}" successfully.`);
      return { success: true };
    } catch (e) {
      if (e.code === 'has_units') {
        const count = e.data?.affected_posts || 'some';
        if (type === 'category') {
          showToast(`Cannot delete "${name}": ${count} unit(s) are assigned. Reassign units first.`, 'error');
          return { success: false, code: 'has_units', affectedPosts: count };
        }
        if (!reassignTo) {
          const siblings = parentCat && categoryTree[parentCat]
            ? Object.keys(categoryTree[parentCat]).filter(s => s !== target)
            : [];
          return { success: false, code: 'has_units', affectedPosts: count, needsReassign: true, parentCat, siblings };
        }
        showToast('Failed to delete: ' + (e.message || 'unknown error'), 'error');
        return { success: false, code: 'has_units' };
      }
      if (e.code === 'category_not_empty') {
        showToast(`Cannot delete "${name}": remove subcategories first.`, 'error');
        return { success: false, code: 'category_not_empty' };
      }
      showToast('Failed to delete category item: ' + e.message, 'error');
      return { success: false };
    } finally {
      categoryMutating.current = false;
    }
  };

  const handleRenameCategoryNode = async (type, oldName, newName, parentCat = null) => {
    categoryMutating.current = true;
    try {
      const response = await apiFetch('/category-tree/rename', {
        method: 'POST',
        body: JSON.stringify({
          type,
          old_name: oldName,
          new_name: newName,
          parent_category: parentCat,
        })
      });
      if (response && response.success) {
        if (response.category_tree) {
          updateCategoryStateFromTree(response.category_tree);
        }
        if (response.brands) {
          setBrands(response.brands);
        }
        showToast(`Successfully renamed and migrated ${response.affected_posts} units.`);
        
        // Update loaded unitData locally
        if (type === 'category' && unitData.category === oldName) {
          setUnitData(prev => ({ ...prev, category: newName }));
        } else if (type === 'subcategory' && unitData.subcategory === oldName) {
          setUnitData(prev => ({ ...prev, subcategory: newName }));
        } else if ((type === 'make' || type === 'brand') && unitData.make === oldName) {
          setUnitData(prev => ({ ...prev, make: newName }));
        }

        loadInventory();
      }
    } catch (e) {
      showToast('Failed to rename category or brand item: ' + e.message, 'error');
    } finally {
      categoryMutating.current = false;
    }
  };


  const handleInputChange = (field, value) => {
    setUnitData(prev => {
      const next = { ...prev, [field]: value };
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
    } catch (err) {
      showToast('Image upload failed: ' + (err.message || 'unknown error'), 'error');
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
        facebook_sync: unitData.facebookSync ?? false,
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
      showToast(`${wpIds.length} unit${wpIds.length !== 1 ? 's' : ''} restored`);
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
      showToast(`${wpIds.length} unit${wpIds.length !== 1 ? 's' : ''} permanently deleted`);
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
        const units = await apiFetch('/inventory?per_page=100');
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

  const applyUnitUpdate = (updated) => {
    if (!updated?.id) return;
    setInventoryList(prev => prev.map(u => u.wpId === updated.id ? { ...u, ...updated } : u));
    setUnitData(prev => prev?.id === updated.id ? { ...prev, ...updated } : prev);
  };

  return {
    isSaving, isLoading, isUploadingImages,
    inventoryList, deletedHistory, unitData, setUnitData,
    brands, years, categories, subcategories,
    categoryTree, setCategoryTree,
    applyUnitUpdate,
    setCategories, setSubcategories,
    showBrandsModal, setShowBrandsModal,
    showYearsModal, setShowYearsModal,
    showCategoryManager, setShowCategoryManager,
    newBrandInput, setNewBrandInput,
    newYearInput, setNewYearInput,
    newCategoryInput, setNewCategoryInput,
    newSubcategoryInput, setNewSubcategoryInput,
    fieldErrors, setFieldErrors,
    currentUser, sessionList, isSessionsLoading,
    activityList, isActivityLoading,
    isPublicMode,
    loadInventory, loadSessions, loadActivity,
    handleCategorySelectChange, handleSubcategorySelectChange,
    handleAddBrand, handleDeleteBrand,
    handleAddYear, handleDeleteYear,
    handleAddCategoryNode, handleDeleteCategoryNode, handleRenameCategoryNode,
    handleInputChange,
    handleAddImages, handleRemoveImage, handleReorderImages,
    handleAddImplement, handleUpdateImplement, handleRemoveImplement, handleImplementImageUpload,
    handleSave, handleDeleteUnit, handleRestoreUnit, handleBulkRestore,
    handlePermanentDelete, handleBulkPermanentDelete,
    handleToggleBoolean, handleToggleDraft,
    handleFullEdit, handleClone,
  };
}
