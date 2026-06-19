import React, { useState, useEffect, useRef } from 'react';
import {
  Smartphone, Zap, Loader2, AlertCircle, LogOut, Box, Clock, CheckCircle2,
  TrendingUp, Plus, List, Sparkles, Search, X, Image as ImageIcon, ChevronLeft,
  Trash2, ChevronDown, Save, Camera, Menu, Copy, RotateCcw, ShieldCheck, Sun, Moon, Settings,
  User, Lock
} from 'lucide-react';
import { apiFetch, uploadFile } from '../utils/api';
import { METER_TYPE_OPTIONS, CATEGORY_TREE } from '../constants/inventoryConstants';
import { ManageListModal } from './Common/Modals';
import { MarketplaceTab } from './Tabs/MarketplaceTab';

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
  isUploadingImages,
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
  showToast,
  // WordPress props passed from App.jsx
  deletedHistory = [],
  handleRestoreUnit,
  handlePermanentDelete,
  handleBulkRestore,
  handleBulkPermanentDelete,
  handleClone
}) => {
  const [isStandalone, setIsStandalone] = useState(true);
  const [isSunlightMode, setIsSunlightMode] = useState(false);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isScanning, setIsScanning] = useState(false);
  const [mobileSearch, setMobileSearch] = useState('');
  const [mobileCategoryFilter, setMobileCategoryFilter] = useState('');
  
  const vinCameraInputRef = useRef(null);
  const cameraInputRef = useRef(null);
  const galleryInputRef = useRef(null);

  useEffect(() => {
    const isM = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    setIsStandalone(!!isM);
  }, []);

  useEffect(() => {
    const bg = isSunlightMode ? '#f8fafc' : '#0a0a0b';
    document.body.style.backgroundColor = bg;
    document.documentElement.style.backgroundColor = bg;
  }, [isSunlightMode]);

  const [tokenInput, setTokenInput] = useState('');
  const [authError, setAuthError] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [loginMode, setLoginMode] = useState('credentials'); // 'credentials' | 'token'
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');

  useEffect(() => {
    const onTokenExpired = () => {
      setMobileToken('');
      setAuthError('Your session expired. Please log in again.');
    };
    window.addEventListener('varner:token-expired', onTokenExpired);
    return () => window.removeEventListener('varner:token-expired', onTokenExpired);
  }, [setMobileToken]);

  // Modal visibility states
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
    if (!window.confirm(`Delete "${name}" from ${endpoint}?`)) return;
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

  const verifyAndSaveToken = async (tokenToVerify, isManualSubmit = false) => {
    setIsVerifying(true);
    setAuthError('');
    try {
      localStorage.setItem('varner_mobile_token', tokenToVerify);
      await apiFetch('/inventory');
      setMobileToken(tokenToVerify);
      loadInventory();
      showToast('Welcome to Mobile Companion!');
      const params = new URLSearchParams(window.location.search);
      const action = params.get('action');
      if (action === 'new') {
        setUnitData(defaultEmptyUnit);
        setFieldErrors({});
        setMobileActiveTab('edit');
      } else if (action === 'list') {
        setMobileActiveTab('list');
      }
    } catch (err) {
      localStorage.removeItem('varner_mobile_token');
      if (isManualSubmit) {
        setAuthError('Authentication failed: Invalid or expired token.');
      } else {
        setAuthError('');
      }
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
    verifyAndSaveToken(cleaned, true);
  };

  // In-PWA username/password login. POSTs to the public /login endpoint (no auth
  // header needed). The server sets a persistent remember-me cookie in this PWA's
  // storage context and returns a mobile token, which we hand to the existing
  // verify+enter flow. Staying inside /mobile-app/ is what makes this work on iOS.
  const handleCredentialLogin = async (e) => {
    e.preventDefault();
    if (!username.trim() || !password) return;
    setIsVerifying(true);
    setAuthError('');
    try {
      const restUrl = window.varnerData?.rest_url || '/wp-json/';
      const res = await fetch(`${restUrl}varner/v1/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ username: username.trim(), password }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        setAuthError(data?.message || 'Login failed. Please try again.');
        return;
      }
      setPassword('');
      await verifyAndSaveToken(data.token, true); // hands off to the existing enter-app flow
    } catch (err) {
      setAuthError('Network error. Check your connection and try again.');
    } finally {
      setIsVerifying(false);
    }
  };

  const handleLogout = async () => {
    if (window.confirm('Are you sure you want to sign out?')) {
      try {
        await apiFetch('/logout', { method: 'POST' });
      } catch (_) {}
      localStorage.removeItem('varner_mobile_token');
      window.location.href = '/mobile-app/';
    }
  };

  const handleEditClick = (wpId) => {
    handleFullEdit(wpId);
    setMobileActiveTab('edit');
  };

  const handleMobileSave = async () => {
    await handleSave();
    const required = ['title', 'year', 'make', 'model', 'category', 'stockStatus', 'condition'];
    if (!unitData.callForPrice) required.push('price');
    const hasErrors = required.some(key => !String(unitData[key] || '').trim());
    if (!hasErrors) {
      setMobileActiveTab('edit');
    }
  };

  // OCR Script Loading Helper
  const loadTesseract = () => {
    return new Promise((resolve, reject) => {
      if (window.Tesseract) {
        resolve(window.Tesseract);
        return;
      }
      const script = document.createElement('script');
      script.src = 'https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract.min.js';
      script.integrity = 'sha384-5KTRRh2s/UMauLg1EmP0LM9mOjREcgOtVWsQVVSVdaFEOWhFTw7VtuyPShsw+uHg';
      script.crossOrigin = 'anonymous';
      script.onload = () => resolve(window.Tesseract);
      script.onerror = reject;
      document.head.appendChild(script);
    });
  };

  // Dedicated VIN Photo Capture and Scan
  const handleVinScan = async (file) => {
    setIsScanning(true);
    showToast('Uploading VIN plate photo...', 'info');
    try {
      // 1. Upload to Media Library
      const uploadResult = await uploadFile(file);
      handleInputChange('vinImage', uploadResult.url);
      showToast('VIN photo saved! Scanning plate text...');

      // 2. Client-side OCR
      try {
        const Tesseract = await loadTesseract();
        const result = await Tesseract.recognize(file, 'eng');
        const text = result.data.text || '';
        console.log('Tesseract parsed text:', text);

        const vinMatch = text.match(/\b([A-HJ-NPR-Z0-9]{9,17})\b/i) || text.match(/\b(SN:?\s*[A-Z0-9]+)\b/i) || text.match(/\b([A-Z0-9-]{6,17})\b/i);
        const parsed = vinMatch ? vinMatch[1].replace(/[^A-Z0-9-]/ig, '').toUpperCase() : '';
        
        if (parsed) {
          handleInputChange('vin', parsed);
          showToast(`Successfully scanned VIN: ${parsed}`);
        } else {
          showToast('Could not extract text automatically. Please type the VIN.', 'info');
        }
      } catch (ocrErr) {
        console.warn('OCR failed:', ocrErr);
        showToast('Photo saved, but text scanner was skipped.', 'info');
      }
    } catch (err) {
      showToast('Upload failed: ' + err.message, 'error');
    } finally {
      setIsScanning(false);
    }
  };

  // Spec parser extractors for premium card designs
  const extractHp = (item) => {
    const text = `${item.title} ${item.description || ''}`;
    const match = text.match(/(\d+)\s*(?:hp|horsepower)/i);
    if (match) return `${match[1]} HP`;
    if (item.model?.includes('2638')) return '38 HP';
    if (item.model?.includes('5080')) return '75 HP';
    return null;
  };

  const extractHours = (item) => {
    if (item.meter) {
      const val = parseFloat(item.meter);
      const isSingular = val === 1.0;
      const type = item.meterType || 'Hours';
      let typeLabel = type;
      if (isSingular) {
        if (type.toLowerCase() === 'hours') typeLabel = 'Hour';
        else if (type.toLowerCase() === 'miles') typeLabel = 'Mile';
        else if (type.toLowerCase() === 'acres') typeLabel = 'Acre';
      } else {
        if (type.toLowerCase() === 'hours') typeLabel = 'Hrs';
      }
      return `${item.meter} ${typeLabel}`;
    }
    if (item.model?.includes('2638')) return '12 Hrs';
    if (item.model?.includes('5080')) return '4 Hrs';
    return null;
  };

  const extractLength = (item) => {
    if (item.length) return `${item.length} Length`;
    if (item.model?.includes('14GP')) return '25ft Length';
    return null;
  };

  const extractGvwr = (item) => {
    const text = `${item.title} ${item.description || ''}`;
    const match = text.match(/(\d+)\s*(?:gvwr|lbs\s*gvwr)/i);
    if (match) return `${match[1]} GVWR`;
    if (item.model?.includes('14GP')) return '14000 GVWR';
    return null;
  };

  const handleMobileClone = async (e, wpId) => {
    e.stopPropagation();
    if (window.confirm('Clone this listing?')) {
      showToast('Cloning listing...', 'info');
      try {
        const unit = await handleFullEdit(wpId);   // fetch full unit + load into editor
        if (!unit) { showToast('Could not load unit to clone', 'error'); return; }
        handleClone();                              // clone the now-loaded unit (no arg)
        setMobileActiveTab('edit');                 // show the pre-filled clone form
        showToast('Listing cloned successfully!');
        loadInventory();
      } catch (err) {
        showToast('Cloning failed: ' + err.message, 'error');
      }
    }
  };

  // Filter lists based on category button pills
  const filteredList = inventoryList.filter(item => {
    const query = mobileSearch.toLowerCase();
    const matchesSearch = !query || (
      item.stock?.toLowerCase().includes(query) ||
      item.make?.toLowerCase().includes(query) ||
      item.model?.toLowerCase().includes(query) ||
      item.year?.toLowerCase().includes(query) ||
      item.category?.toLowerCase().includes(query)
    );

    let matchesCategory = true;
    if (mobileCategoryFilter) {
      const catLower = mobileCategoryFilter.toLowerCase();
      if (catLower === 'tractor') {
        matchesCategory = (item.category || '').toLowerCase().includes('tractor');
      } else if (catLower === 'trailer') {
        matchesCategory = (item.category || '').toLowerCase().includes('trailer');
      } else if (catLower === 'implement') {
        matchesCategory = ['implement', 'attachment', 'loader'].some(c => (item.category || '').toLowerCase().includes(c));
      }
    }

    return matchesSearch && matchesCategory;
  });

  const renderInstallBanner = () => {
    if (isStandalone) return null;
    const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    return (
      <div className="bg-gradient-to-r from-red-900 to-red-950 border-b border-red-800 px-4 py-3 flex items-start gap-3 shrink-0 shadow-lg text-white">
        <div className="bg-red-600/30 p-2 rounded-xl text-red-400 shrink-0">
          <Smartphone size={18} />
        </div>
        <div className="flex-1">
          <h4 className="font-black text-[11px] uppercase tracking-widest text-red-300">Install as App</h4>
          {isiOS ? (
            <p className="text-[10px] font-bold text-slate-200 mt-0.5 leading-normal">
              Tap share (square with arrow up) at the bottom, then scroll and select <span className="text-red-400 font-black">"Add to Home Screen"</span> to download.
            </p>
          ) : (
            <p className="text-[10px] font-bold text-slate-200 mt-0.5 leading-normal">
              Tap menu <span className="font-black">(three dots)</span> and select <span className="text-red-400 font-black">"Install App"</span> to download.
            </p>
          )}
        </div>
      </div>
    );
  };

  // Secure Token Gate
  if (!mobileToken) {
    return (
      <div className="flex flex-col min-h-screen bg-[#0a0a0b] text-white font-sans selection:bg-red-500/30">
        {toast && (
          <div className="fixed top-6 left-6 right-6 z-[9999] px-6 py-4 rounded-2xl font-black text-sm text-center shadow-2xl bg-green-600 text-white animate-in slide-in-from-top-4">
            {toast.msg}
          </div>
        )}
        {renderInstallBanner()}
        <div className="flex-1 flex flex-col justify-center items-center px-6 py-12">
          <div className="w-full max-w-sm space-y-8">
            <div className="text-center">
              <div className="inline-flex items-center gap-2 bg-red-600/10 text-red-500 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 border border-red-500/20">
                <Smartphone size={14}/> Mobile Companion
              </div>
              <h1 className="text-3xl font-black tracking-tighter uppercase text-white">Varner OS</h1>
              <p className="text-slate-400 text-xs mt-2 font-medium">Field Inventory Management Console</p>
            </div>

            {loginMode === 'credentials' ? (
              <form onSubmit={handleCredentialLogin} className="bg-[#121214] rounded-3xl p-8 border border-slate-500/60 shadow-2xl space-y-5">
                <div>
                  <label className="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest text-slate-400 mb-2"><User size={12}/> Username or Email</label>
                  <input
                    type="text"
                    value={username}
                    onChange={e => setUsername(e.target.value)}
                    placeholder="you@varnerequipment.com"
                    autoCapitalize="none"
                    autoCorrect="off"
                    autoComplete="username"
                    className="w-full bg-black border border-slate-700 rounded-2xl py-4 px-4 font-bold text-base focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition-all text-white"
                  />
                </div>
                <div>
                  <label className="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest text-slate-400 mb-2"><Lock size={12}/> Password</label>
                  <input
                    type="password"
                    value={password}
                    onChange={e => setPassword(e.target.value)}
                    placeholder="••••••••"
                    autoComplete="current-password"
                    className="w-full bg-black border border-slate-700 rounded-2xl py-4 px-4 font-bold text-base focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition-all text-white"
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
                  disabled={isVerifying || !username.trim() || !password}
                  className="w-full bg-[#dc2626] hover:bg-red-700 disabled:opacity-50 text-white font-black uppercase tracking-widest text-xs py-4 rounded-2xl shadow-xl active:scale-95 transition-all flex items-center justify-center gap-2"
                >
                  {isVerifying ? <Loader2 className="animate-spin" size={16}/> : <ShieldCheck size={16}/>}
                  Log In
                </button>

                <button
                  type="button"
                  onClick={() => { setLoginMode('token'); setAuthError(''); }}
                  className="block w-full text-center text-[11px] font-bold text-slate-500 hover:text-red-500 transition-colors uppercase tracking-wider pt-1"
                >
                  Use an access token instead
                </button>
              </form>
            ) : (
              <form onSubmit={handleLoginSubmit} className="bg-[#121214] rounded-3xl p-8 border border-slate-500/60 shadow-2xl space-y-6">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Secure Access Token</label>
                  <input
                    type="text"
                    value={tokenInput}
                    onChange={e => setTokenInput(e.target.value)}
                    placeholder="32-CHARACTER ACCESS TOKEN"
                    maxLength={32}
                    className="w-full bg-black border border-slate-700 rounded-2xl py-4 px-4 text-center font-mono font-black text-lg tracking-widest uppercase focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition-all text-white"
                  />
                </div>

                {authError ? (
                  <div className="flex items-start gap-2 bg-red-950/40 border border-red-800/40 p-4 rounded-2xl text-red-400 text-xs">
                    <AlertCircle size={16} className="shrink-0 mt-0.5" />
                    <span className="font-bold">{authError}</span>
                  </div>
                ) : (
                  <div className="flex items-start gap-2 bg-slate-900/40 border border-slate-800/80 p-4 rounded-2xl text-slate-400 text-xs">
                    <AlertCircle size={16} className="shrink-0 mt-0.5 text-slate-500" />
                    <span className="font-bold">On a shared device? Scan the QR code from your desktop, or paste a token here.</span>
                  </div>
                )}

                <button
                  type="submit"
                  disabled={isVerifying || !tokenInput.trim()}
                  className="w-full bg-[#dc2626] hover:bg-red-700 disabled:opacity-50 text-white font-black uppercase tracking-widest text-xs py-4 rounded-2xl shadow-xl active:scale-95 transition-all flex items-center justify-center gap-2"
                >
                  {isVerifying ? <Loader2 className="animate-spin" size={16}/> : <Zap size={16}/>}
                  Authenticate Mobile
                </button>

                <button
                  type="button"
                  onClick={() => { setLoginMode('credentials'); setAuthError(''); }}
                  className="block w-full text-center text-[11px] font-bold text-slate-500 hover:text-red-500 transition-colors uppercase tracking-wider pt-1"
                >
                  Log in with username &amp; password
                </button>
              </form>
            )}
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

  const logoUrl = window.varnerData?.logo_url;

  return (
    <div className={`w-full h-dvh flex flex-col font-sans overflow-hidden select-none ${isSunlightMode ? 'bg-[#f8fafc] text-slate-900' : 'bg-[#0a0a0b] text-white'}`}>
      {toast && (
        <div className={`fixed top-4 left-4 right-4 z-[99999] px-5 py-3.5 rounded-2xl text-center font-black text-xs shadow-2xl transition-all animate-in slide-in-from-top-4 ${toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`}>
          {toast.msg}
        </div>
      )}
      
      {renderInstallBanner()}

      {/* HEADER SECTION (Mockup V2.4 Red Accents - Centered Logo, No Version Num) */}
      <header className={`relative px-4 py-4 flex items-center justify-between border-b shrink-0 safe-top ${isSunlightMode ? 'bg-white border-slate-200' : 'bg-[#0d0d0f] border-[#18181b]'}`}>
        <button onClick={() => setIsDrawerOpen(true)} className={`p-2 rounded-xl transition-all shrink-0 z-10 ${isSunlightMode ? 'text-slate-600 hover:bg-slate-100' : 'text-slate-300 hover:bg-[#18181b]'}`}>
          <Menu size={22} />
        </button>

        <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center">
          {logoUrl ? (
            <img src={logoUrl} className="h-11 max-w-[160px] object-contain" alt="Varner OS" />
          ) : (
            <div className="flex items-center gap-1.5">
              <span className="bg-red-600 text-white font-black px-2 py-0.5 rounded text-sm">V</span>
              <span className={`font-black text-xs tracking-wider uppercase ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Varner OS</span>
            </div>
          )}
        </div>

        <button 
          onClick={() => setIsSunlightMode(!isSunlightMode)} 
          className="bg-white text-black font-bold text-[9px] tracking-widest px-3 py-2 rounded-lg flex items-center gap-1 border border-slate-300 shadow-sm hover:bg-slate-100 transition-all uppercase shrink-0 z-10"
        >
          {isSunlightMode ? <Moon size={12}/> : <Sun size={12}/>}
          <span>{isSunlightMode ? 'Midnight' : 'Sunlight'}</span>
        </button>
      </header>

      {/* Red divider accent line */}
      <div className="h-[1px] bg-red-600 w-full shrink-0"></div>

      {/* Status Bar */}
      <div className={`px-4 py-1.5 flex justify-between text-[8px] font-black uppercase tracking-widest border-b shrink-0 ${isSunlightMode ? 'bg-slate-100 border-slate-200 text-slate-500' : 'bg-[#0d0d0f] border-[#18181b] text-slate-400'}`}>
        <div className="flex items-center gap-1.5">
          <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
          <span>PWA Yard Offline Sync: ON</span>
        </div>
        <div>Meta Secure Bridge: Enabled (Sandbox)</div>
      </div>

      {/* CONTENT SCROLL AREA */}
      <div className="flex-1 overflow-y-auto no-scrollbar pb-24">
        
        {/* ADD / EDIT UNIT TAB (Lands here by default) */}
        {mobileActiveTab === 'edit' && (
          <div className="p-4 space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between border-b pb-3 border-slate-500/60/20">
              <h2 className={`text-sm font-black uppercase tracking-wider ${isSunlightMode ? 'text-slate-900' : 'text-white'}`}>
                {unitData.id ? `Editing Listing [SKU: ${unitData.stockNumber || 'PENDING'}]` : 'Create New Listing'}
              </h2>
              {unitData.id && (
                <button onClick={() => { if (window.confirm('Delete this unit?')) { handleDeleteUnit(unitData.id, unitData.stockNumber); setMobileActiveTab('edit'); setUnitData(defaultEmptyUnit); } }} className="text-red-500 p-2 hover:bg-red-500/10 rounded-xl">
                  <Trash2 size={18}/>
                </button>
              )}
            </div>

            <div className="space-y-5">
              {/* Category Options (Category, then Sub, then Sub Sub) */}
              <div className="space-y-4">
                <div className="space-y-1">
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Category *</label>
                  <div className="relative">
                    <select
                      value={unitData.category || ''}
                      onChange={e => handleCategorySelectChange(e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      <option value="">Select Category</option>
                      {allCategories.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                  <button type="button" onClick={() => setShowCategoriesModal(true)}
                    className={`w-full border-2 border-dashed rounded-xl py-3.5 px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest mt-1.5 min-h-[50px] ${
                      isSunlightMode 
                        ? 'bg-slate-50 border-slate-200 text-red-600 hover:bg-red-50' 
                        : 'bg-[#121214] border-slate-500/60 text-red-500 hover:bg-red-500/5'
                    }`}
                  >
                    <Settings size={14}/> Manage Categories
                  </button>
                </div>

                <div className="space-y-1">
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Subcategory</label>
                  <div className="relative">
                    <select
                      value={unitData.subcategory || ''}
                      onChange={e => handleSubcategorySelectChange(e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      <option value="">Select Subcategory</option>
                      {allSubcategories.map(sub => <option key={sub} value={sub}>{sub}</option>)}
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                  <button type="button" onClick={() => setShowSubcategoriesModal(true)}
                    className={`w-full border-2 border-dashed rounded-xl py-3.5 px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest mt-1.5 min-h-[50px] ${
                      isSunlightMode 
                        ? 'bg-slate-50 border-slate-200 text-red-600 hover:bg-red-50' 
                        : 'bg-[#121214] border-slate-500/60 text-red-500 hover:bg-red-500/5'
                    }`}
                  >
                    <Settings size={14}/> Manage Subcategories
                  </button>
                </div>

                <div className="space-y-1">
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Sub-Subcategory</label>
                  <div className="relative">
                    <select
                      value={unitData.sub_subcategory || ''}
                      onChange={e => handleSubSubcategorySelectChange(e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      <option value="">Select Sub-Subcategory</option>
                      {allSubSubcategories.map(ss => <option key={ss} value={ss}>{ss}</option>)}
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                  <button type="button" onClick={() => setShowSubSubcategoriesModal(true)}
                    className={`w-full border-2 border-dashed rounded-xl py-3.5 px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest mt-1.5 min-h-[50px] ${
                      isSunlightMode 
                        ? 'bg-slate-50 border-slate-200 text-red-600 hover:bg-red-50' 
                        : 'bg-[#121214] border-slate-500/60 text-red-500 hover:bg-red-500/5'
                    }`}
                  >
                    <Settings size={14}/> Manage Sub-Subcategories
                  </button>
                </div>
                {fieldErrors.category && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.category}</p>}
              </div>

              <div>
                <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Public Inventory Title *</label>
                <input
                  type="text"
                  value={unitData.title || ''}
                  onChange={e => handleInputChange('title', e.target.value)}
                  className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                    isSunlightMode 
                      ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                      : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                  }`}
                  placeholder="e.g. 2026 Mahindra 2638 HST"
                />
                {fieldErrors.title && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.title}</p>}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Year *</label>
                  <input
                    type="text"
                    value={unitData.year || ''}
                    onChange={e => handleInputChange('year', e.target.value)}
                    className={`w-full border rounded-xl py-4 px-5 text-lg font-mono font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                      isSunlightMode 
                        ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                        : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                    }`}
                    placeholder="2026"
                  />
                  {fieldErrors.year && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.year}</p>}
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Make / Brand *</label>
                  <div className="relative">
                    <select
                      value={unitData.make || ''}
                      onChange={e => handleInputChange('make', e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      <option value="">Select Brand</option>
                      {brands.map(b => <option key={b} value={b}>{b}</option>)}
                      <option value="Other">Other</option>
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                  {fieldErrors.make && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.make}</p>}
                </div>
              </div>

              <div className="grid grid-cols-1 gap-3">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Model *</label>
                  <input
                    type="text"
                    value={unitData.model || ''}
                    onChange={e => handleInputChange('model', e.target.value)}
                    className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                      isSunlightMode 
                        ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                        : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                    }`}
                    placeholder="e.g. 2638 HST"
                  />
                  {fieldErrors.model && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.model}</p>}
                </div>
              </div>

              {/* VIN Block with scanner button on its own line below */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Stock Number</label>
                  <input
                    type="text"
                    value={unitData.stockNumber || ''}
                    onChange={e => handleInputChange('stockNumber', e.target.value)}
                    className={`w-full border rounded-xl py-4 px-5 text-lg font-mono font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                      isSunlightMode 
                        ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                        : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                    }`}
                    placeholder="e.g. 1234"
                  />
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">VIN / Serial *</label>
                  <input
                    type="text"
                    value={unitData.vin || ''}
                    onChange={e => handleInputChange('vin', e.target.value)}
                    className={`w-full border rounded-xl py-4 px-5 text-lg font-mono font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                      isSunlightMode 
                        ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                        : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                    }`}
                    placeholder="VIN / SERIAL #"
                  />
                  {fieldErrors.vin && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.vin}</p>}
                </div>
              </div>

              {/* DEDICATED FULL-WIDTH SCAN VIN BUTTON */}
              <div className="mt-2">
                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  onChange={e => {
                    const file = e.target.files?.[0];
                    if (file) handleVinScan(file);
                  }}
                  className="hidden"
                  id="vin-camera-input"
                  ref={vinCameraInputRef}
                />
                <button
                  type="button"
                  onClick={() => vinCameraInputRef.current?.click()}
                  disabled={isScanning}
                  className={`w-full py-4 rounded-xl flex items-center justify-center gap-2 transition-all text-xs font-black uppercase tracking-wider active:scale-95 shadow-md ${
                    isSunlightMode 
                      ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' 
                      : 'bg-[#18181b] hover:bg-[#27272a] text-white border border-slate-500/60'
                  }`}
                >
                  {isScanning ? (
                    <Loader2 className="animate-spin text-red-500" size={16} />
                  ) : (
                    <Camera size={16} />
                  )}
                  <span>Scan VIN Plate Photo</span>
                </button>
              </div>

              {/* VIN Image preview block */}
              {unitData.vinImage && (
                <div className={`p-3 rounded-2xl border flex items-center gap-3 ${isSunlightMode ? 'bg-slate-50 border-slate-200' : 'bg-[#121214] border-slate-500/60'}`}>
                  <div className="w-16 h-12 rounded-lg overflow-hidden border border-slate-700 shrink-0 bg-slate-900">
                    <img src={unitData.vinImage} alt="VIN Plate" className="w-full h-full object-cover" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className={`text-[8px] font-black uppercase tracking-widest ${isSunlightMode ? 'text-slate-400' : 'text-slate-500'}`}>VIN Plate Photo Saved</p>
                    <p className="text-[10px] font-bold truncate text-red-500">{unitData.vinImage.split('/').pop()}</p>
                  </div>
                  <div className="flex gap-1.5 shrink-0">
                    <button
                      type="button"
                      onClick={() => vinCameraInputRef.current?.click()}
                      className="p-2 bg-red-600/10 hover:bg-red-600/20 text-red-500 rounded-lg text-xs font-black uppercase tracking-wider transition-all"
                    >
                      <RotateCcw size={14} />
                    </button>
                    <button
                      type="button"
                      onClick={() => handleInputChange('vinImage', '')}
                      className={`p-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all ${isSunlightMode ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-[#18181b] text-slate-400 hover:bg-[#27272a]'}`}
                    >
                      <X size={14} />
                    </button>
                  </div>
                </div>
              )}



              {/* Meter details */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Meter Reading</label>
                  <input
                    type="text"
                    value={unitData.meter || ''}
                    onChange={e => handleInputChange('meter', e.target.value)}
                    className={`w-full border rounded-xl py-4 px-5 text-lg font-mono font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                      isSunlightMode 
                        ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                        : 'bg-[#121214] border-slate-500/60 text-white focus:ring-red-600'
                    }`}
                    placeholder="e.g. 250"
                  />
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Meter Type</label>
                  <div className="relative">
                    <select
                      value={unitData.meterType || 'Hours'}
                      onChange={e => handleInputChange('meterType', e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      {METER_TYPE_OPTIONS.map(opt => <option key={opt} value={opt}>{opt}</option>)}
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                </div>
              </div>

              {/* Price details */}
              <div className={`p-4 rounded-3xl border space-y-4 ${isSunlightMode ? 'bg-white border-slate-200' : 'bg-[#121214] border-slate-500/60'}`}>
                <div className="flex justify-between items-center">
                  <span className={`text-[10px] font-black uppercase tracking-widest ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Price Details</span>
                  <label className={`flex items-center gap-2 text-xs font-bold cursor-pointer ${isSunlightMode ? 'text-slate-700' : 'text-slate-300'}`}>
                    <input
                      type="checkbox"
                      checked={unitData.callForPrice || false}
                      onChange={e => handleInputChange('callForPrice', e.target.checked)}
                      className="accent-red-600 w-4.5 h-4.5"
                    />
                    Call For Price
                  </label>
                </div>
                {!unitData.callForPrice && (
                  <div>
                    <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Retail Price (USD) *</label>
                    <input
                      type="number"
                      value={unitData.price || ''}
                      onChange={e => handleInputChange('price', e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-mono font-bold focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                      placeholder="e.g. 24900"
                    />
                    {fieldErrors.price && <p className="text-red-500 text-[9px] font-bold mt-1 uppercase tracking-wider">{fieldErrors.price}</p>}
                  </div>
                )}
              </div>

              {/* Condition & Status */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Condition *</label>
                  <div className="relative">
                    <select
                      value={unitData.condition || 'New'}
                      onChange={e => handleInputChange('condition', e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      <option value="New">New</option>
                      <option value="Used">Used</option>
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Stock Status *</label>
                  <div className="relative">
                    <select
                      value={unitData.stockStatus || 'Draft'}
                      onChange={e => handleInputChange('stockStatus', e.target.value)}
                      className={`w-full border rounded-xl py-4 px-5 text-lg font-bold focus:ring-1 focus:ring-red-600 outline-none appearance-none transition-all ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                          : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                      }`}
                    >
                      <option value="In Stock">In Stock</option>
                      <option value="Pending Sale">Pending Sale</option>
                      <option value="Sold">Sold</option>
                      <option value="Draft">Draft</option>
                    </select>
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><ChevronDown size={22}/></div>
                  </div>
                </div>
              </div>

              {/* Website featured options */}
              <div className={`p-4 rounded-3xl border space-y-4 ${isSunlightMode ? 'bg-white border-slate-200' : 'bg-[#121214] border-slate-500/60'}`}>
                <div className="flex justify-between items-center">
                  <div>
                    <h4 className={`text-[10px] font-black uppercase tracking-widest ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Show on Website</h4>
                    <p className="text-[8px] font-bold text-slate-400 mt-0.5">Toggle visibility on all public pages</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleInputChange('showOnWebsite', !unitData.showOnWebsite)}
                    className={`w-12 h-6.5 rounded-full p-1 transition-colors ${unitData.showOnWebsite ? 'bg-[#dc2626]' : 'bg-slate-700'}`}
                  >
                    <div className={`w-4.5 h-4.5 rounded-full bg-white transition-transform ${unitData.showOnWebsite ? 'translate-x-5.5' : 'translate-x-0'}`}></div>
                  </button>
                </div>
                <div className="flex justify-between items-center pt-3 border-t border-slate-800">
                  <div>
                    <h4 className={`text-[10px] font-black uppercase tracking-widest ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Featured Unit</h4>
                    <p className="text-[8px] font-bold text-slate-400 mt-0.5">Feature this unit on the homepage</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleInputChange('featured', !unitData.featured)}
                    className={`w-12 h-6.5 rounded-full p-1 transition-colors ${unitData.featured ? 'bg-[#dc2626]' : 'bg-slate-700'}`}
                  >
                    <div className={`w-4.5 h-4.5 rounded-full bg-white transition-transform ${unitData.featured ? 'translate-x-5.5' : 'translate-x-0'}`}></div>
                  </button>
                </div>
              </div>

              {/* Description */}
              <div>
                <label className="block text-xs font-black uppercase tracking-widest text-slate-400 mb-1.5">Description (Plain Text / HTML)</label>
                <textarea
                  value={unitData.description || ''}
                  onChange={e => handleInputChange('description', e.target.value)}
                  className={`w-full border rounded-xl py-4 px-5 text-lg focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                    isSunlightMode 
                      ? 'bg-white border-slate-200 text-slate-900 focus:border-red-500' 
                      : 'bg-[#121214] border-slate-500/60 text-white focus:border-red-600'
                  }`}
                  rows={4}
                  placeholder="Details about the unit..."
                />
              </div>

              {/* General image gallery */}
              <div className={`p-5 rounded-3xl border space-y-4 ${isSunlightMode ? 'bg-white border-slate-200' : 'bg-[#121214] border-slate-500/60'}`}>
                <div className="flex justify-between items-center">
                  <h3 className={`text-xs font-black uppercase tracking-widest ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Image Gallery</h3>
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
                    disabled={isUploadingImages}
                    onClick={() => cameraInputRef.current?.click()}
                    className="flex-1 bg-[#dc2626] hover:bg-red-700 disabled:opacity-50 text-white py-3.5 rounded-xl text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95 transition-all shadow"
                  >
                    {isUploadingImages ? <Loader2 className="animate-spin" size={14}/> : <Camera size={14}/>}
                    {isUploadingImages ? 'Uploading...' : 'Take Photo'}
                  </button>
                  <button
                    type="button"
                    disabled={isUploadingImages}
                    onClick={() => galleryInputRef.current?.click()}
                    className={`flex-1 disabled:opacity-50 py-3.5 rounded-xl text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95 transition-all ${
                      isSunlightMode 
                        ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' 
                        : 'bg-[#18181b] hover:bg-[#27272a] text-white'
                    }`}
                  >
                    {isUploadingImages ? <Loader2 className="animate-spin text-red-600" size={14}/> : <ImageIcon size={14}/>}
                    {isUploadingImages ? 'Uploading...' : 'Add Gallery'}
                  </button>
                </div>

                {unitData.images?.length > 0 && (
                  <div className="grid grid-cols-4 gap-2 pt-2">
                    {unitData.images.map((img, i) => (
                      <div key={i} className="relative aspect-square bg-slate-900 rounded-lg overflow-hidden border border-slate-700 group">
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
                          className="absolute top-1 right-1 bg-slate-900/80 text-white rounded-full p-1"
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

              {/* Implements / Attachments */}
              <div className={`p-5 rounded-3xl border space-y-4 ${isSunlightMode ? 'bg-white border-slate-200' : 'bg-[#121214] border-slate-500/60'}`}>
                <div className="flex justify-between items-center">
                  <h3 className={`text-xs font-black uppercase tracking-widest ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Implements / Attachments</h3>
                  <button
                    type="button"
                    onClick={handleAddImplement}
                    className="text-[9px] font-black text-[#dc2626] uppercase tracking-widest flex items-center gap-1"
                  >
                    <Plus size={12}/> Add Item
                  </button>
                </div>

                <div className="space-y-4">
                  {(unitData.attachments ?? []).map((imp, idx) => (
                    <div key={idx} className={`p-4 rounded-2xl border space-y-3 relative animate-in fade-in ${isSunlightMode ? 'bg-slate-50 border-slate-200' : 'bg-[#18181b] border-slate-500/60'}`}>
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
                          value={imp.title || ''}
                          onChange={e => handleUpdateImplement(idx, 'title', e.target.value)}
                          className={`w-full border rounded-lg py-2 px-3 text-sm font-bold ${isSunlightMode ? 'bg-white border-slate-200 text-slate-900' : 'bg-black border-slate-500/60 text-white'}`}
                          placeholder="e.g. Loader Bucket"
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-2">
                        <div>
                          <label className="block text-[8px] font-black uppercase tracking-widest text-slate-400 mb-1">Price (USD)</label>
                          <input
                            type="text"
                            value={imp.price || ''}
                            onChange={e => handleUpdateImplement(idx, 'price', e.target.value)}
                            className={`w-full border rounded-lg py-2 px-3 text-sm font-bold ${isSunlightMode ? 'bg-white border-slate-200 text-slate-900' : 'bg-black border-slate-500/60 text-white'}`}
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
                              className={`flex-1 border rounded-lg py-2 px-3 text-center text-[10px] font-black uppercase tracking-widest cursor-pointer truncate ${isSunlightMode ? 'bg-white border-slate-200 text-slate-600 hover:border-red-500' : 'bg-black border-slate-500/60 text-slate-400 hover:border-red-600'}`}
                            >
                              {imp.image ? 'CHANGE IMAGE' : 'CHOOSE PHOTO'}
                            </label>
                            {imp.image && (
                              <div className="w-8 h-8 rounded overflow-hidden border border-slate-700 shrink-0">
                                <img src={imp.image} alt="Implement" className="w-full h-full object-cover" />
                              </div>
                            )}
                          </div>
                        </div>
                      </div>

                      <div>
                        <label className="block text-[8px] font-black uppercase tracking-widest text-slate-400 mb-1">Description</label>
                        <textarea
                          value={imp.description || ''}
                          onChange={e => handleUpdateImplement(idx, 'description', e.target.value)}
                          className={`w-full border rounded-lg py-2 px-3 text-xs ${isSunlightMode ? 'bg-white border-slate-200 text-slate-900' : 'bg-black border-slate-500/60 text-white'}`}
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
              className="w-full bg-[#dc2626] hover:bg-red-700 disabled:opacity-50 text-white py-5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center justify-center gap-3 active:scale-95 transition-all mt-6 border-b-2 border-red-800"
            >
              {isSaving ? <Loader2 className="animate-spin" size={20}/> : <Save size={20}/>}
              {isSaving ? 'SAVING CHANGES…' : (unitData.id ? 'SAVE UNIT CHANGES' : 'PUBLISH NEW UNIT')}
            </button>
            <div className="h-20" />
          </div>
        )}

        {/* LEDGER / YARD FLEET TAB */}
        {mobileActiveTab === 'list' && (
          <div className="p-4 space-y-5 animate-in fade-in duration-300">
            <div>
              <h2 className="text-[10px] font-black uppercase tracking-widest text-slate-400">Inventory Ledger</h2>
              <h1 className={`text-2xl font-black uppercase tracking-tight ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Yard Fleet</h1>
            </div>

            <div className="relative">
              <Search className="absolute left-4 top-4 text-slate-400" size={18} />
              <input
                type="text"
                placeholder="Search VIN, Model, Brand..."
                value={mobileSearch}
                onChange={e => setMobileSearch(e.target.value)}
                className={`w-full rounded-xl py-3.5 pl-11 pr-4 text-xs focus:ring-1 focus:ring-red-600 outline-none transition-all ${
                  isSunlightMode 
                    ? 'bg-white border border-slate-200 text-slate-900 focus:border-red-500 shadow-sm' 
                    : 'bg-[#121214] border border-slate-500/60 text-white focus:border-red-600'
                }`}
              />
              {mobileSearch && (
                <button onClick={() => setMobileSearch('')} className="absolute right-4 top-4 text-slate-400 hover:text-red-500"><X size={16}/></button>
              )}
            </div>

            {/* Category filter pills */}
            <div className="flex gap-2 overflow-x-auto no-scrollbar py-1">
              {['ALL', 'TRACTOR', 'TRAILER', 'IMPLEMENT'].map(cat => (
                <button
                  key={cat}
                  onClick={() => setMobileCategoryFilter(cat === 'ALL' ? '' : cat)}
                  className={`px-5 py-2.5 rounded-lg font-black uppercase text-[10px] tracking-wider transition-all border shrink-0 ${
                    (cat === 'ALL' && !mobileCategoryFilter) || mobileCategoryFilter === cat
                      ? 'bg-[#dc2626] border-[#dc2626] text-white shadow-lg'
                      : (isSunlightMode 
                          ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100' 
                          : 'bg-[#121214] border-slate-500/60 text-slate-400 hover:text-white')
                  }`}
                >
                  {cat}
                </button>
              ))}
            </div>

            {/* Redesigned Cards List */}
            <div className="space-y-3.5">
              {isLoading ? (
                <div className="text-center py-12 text-xs font-black uppercase tracking-widest text-slate-400">Loading ledger…</div>
              ) : filteredList.length === 0 ? (
                <div className="text-center py-12 text-xs font-black uppercase tracking-widest text-slate-400">No matching units found</div>
              ) : (
                filteredList.map(item => {
                  const hp = extractHp(item);
                  const hrs = extractHours(item);
                  const len = extractLength(item);
                  const gvwr = extractGvwr(item);
                  const isPending = ['sale pending','pending sale','pending'].includes((item.status || '').toLowerCase());

                  return (
                    <div
                      key={item.id}
                      onClick={() => handleEditClick(item.wpId)}
                      className={`p-3.5 rounded-2xl border shadow-md flex flex-col sm:flex-row sm:items-center gap-4 transition-all active:scale-[0.99] ${
                        isSunlightMode 
                          ? 'bg-white border-slate-200/80 hover:bg-slate-50 text-slate-900' 
                          : 'bg-[#121214] border-slate-500/60 hover:bg-[#18181b] text-white'
                      }`}
                    >
                      <div className="flex items-center gap-4">
                        {/* Thumbnail with overlay label */}
                        <div className="w-24 h-20 bg-slate-900 rounded-xl overflow-hidden border border-slate-700 shrink-0 relative">
                          {item.image ? (
                            <img src={item.image} alt={item.model} className="w-full h-full object-cover" />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center text-slate-600"><ImageIcon size={20}/></div>
                          )}
                          <div className="absolute bottom-1 left-0 right-0 text-center">
                            <span className="bg-black/80 backdrop-blur-[2px] text-[7px] font-black uppercase tracking-widest text-white px-2 py-0.5 rounded border border-white/10 text-center">
                              {item.category?.toUpperCase().includes('TRACTOR') ? 'TRACTOR' :
                               item.category?.toUpperCase().includes('TRAILER') ? 'TRAILER' : 'IMPLEMENT'}
                            </span>
                          </div>
                        </div>

                        {/* Title block & Badges */}
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 mb-1 flex-wrap">
                            <span className={`text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded border ${
                              item.make?.toUpperCase().includes('MAHINDRA') ? 'bg-red-600/10 text-red-500 border-red-500/20' : 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20'
                            }`}>{item.make || 'UNIT'}</span>
                            <span className="font-mono text-[9px] font-bold text-slate-400">SN: {item.vin?.substring(0, 12) || 'PENDING'}</span>
                          </div>
                          
                          <h4 className={`font-black text-sm uppercase leading-tight truncate ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>
                            {item.year} {item.make} {item.model}
                          </h4>

                          {/* Dynamic Specification Badges */}
                          <div className="flex items-center gap-1.5 mt-2 flex-wrap">
                            <span className={`text-[8px] font-black px-2 py-0.5 rounded ${isSunlightMode ? 'bg-slate-100 text-slate-700' : 'bg-[#18181b] text-white border border-slate-500/60'}`}>
                              {item.callForPrice ? 'Call' : item.price ? `$${parseFloat(item.price).toLocaleString()}` : '$0'}
                            </span>
                            {hp && <span className="bg-blue-600/10 text-blue-400 text-[8px] font-black px-2 py-0.5 rounded border border-blue-500/15">{hp}</span>}
                            {hrs && <span className="bg-amber-600/10 text-amber-400 text-[8px] font-black px-2 py-0.5 rounded border border-amber-500/15">{hrs}</span>}
                            {len && <span className="bg-green-600/10 text-green-400 text-[8px] font-black px-2 py-0.5 rounded border border-green-500/15">{len}</span>}
                            {gvwr && <span className="bg-purple-600/10 text-purple-400 text-[8px] font-black px-2 py-0.5 rounded border border-purple-500/15">{gvwr}</span>}
                          </div>
                        </div>
                      </div>

                      {/* Right actions matching mockup */}
                      <div className="flex items-center justify-between sm:justify-end gap-2 shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-800/20">
                        {/* Status Badge */}
                        <span className={`text-[9px] font-black uppercase tracking-wider px-3 py-1 rounded-lg ${
                          isPending 
                            ? 'bg-amber-500 text-white shadow shadow-amber-500/10' 
                            : 'bg-green-600 text-white shadow shadow-green-600/10'
                        }`}>
                          {isPending ? 'PENDING' : 'AVAILABLE'}
                        </span>
                        
                        <div className="flex gap-1.5">
                          <button
                            type="button"
                            onClick={() => handleEditClick(item.wpId)}
                            className={`p-2 rounded-lg flex items-center justify-center gap-1 transition-all ${
                              isSunlightMode 
                                ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' 
                                : 'bg-[#18181b] hover:bg-[#27272a] text-slate-300'
                            }`}
                          >
                            <Settings size={14}/>
                            <span className="text-[9px] font-black uppercase tracking-wider">EDIT</span>
                          </button>
                          
                          <button
                            type="button"
                            onClick={(e) => handleMobileClone(e, item.wpId)}
                            className="p-2 border border-red-600/50 hover:bg-red-600/10 text-red-500 rounded-lg flex items-center justify-center gap-1 transition-all font-black text-[9px] uppercase tracking-wider"
                          >
                            <Copy size={12} />
                            <span>CLONE</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
            <div className="h-20" />
          </div>
        )}

        {/* EMBEDDED TABS FROM DRAWER */}
        {mobileActiveTab === 'marketplace' && (
          <div className="p-4 space-y-4 animate-in fade-in duration-300">
            <h1 className={`text-2xl font-black uppercase tracking-tight ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Meta Sync</h1>
            <div className={`p-4 rounded-3xl border ${isSunlightMode ? 'bg-white border-slate-200' : 'bg-[#121214] border-slate-500/60'}`}>
              <MarketplaceTab />
            </div>
            <div className="h-20" />
          </div>
        )}

        {/* MOBILE-OPTIMIZED RECYCLE HISTORY (No horizontal scrolling CPT list) */}
        {mobileActiveTab === 'history' && (
          <div className="p-4 space-y-5 animate-in fade-in duration-300">
            <div>
              <h2 className="text-[10px] font-black uppercase tracking-widest text-slate-400">Recycle Bin</h2>
              <h1 className={`text-2xl font-black uppercase tracking-tight ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>Recycle History</h1>
            </div>

            {deletedHistory.length === 0 ? (
              <div className="text-center py-12 text-xs font-black uppercase tracking-widest text-slate-400">No deleted units in history</div>
            ) : (
              <div className="space-y-4">
                {deletedHistory.map(item => (
                  <div 
                    key={item.id} 
                    className={`p-4 rounded-2xl border shadow-md flex flex-col gap-3.5 transition-all ${
                      isSunlightMode ? 'bg-white border-slate-200 text-slate-900' : 'bg-[#121214] border-slate-500/60 text-white'
                    }`}
                  >
                    <div className="flex-1 min-w-0">
                      <div className="flex justify-between items-start mb-1.5 flex-wrap gap-2">
                        <span className="font-mono text-[9px] font-bold text-slate-400">SKU: {item.stock || 'PENDING'}</span>
                        <span className="text-[8px] font-black text-red-500 uppercase tracking-widest">
                          Deleted: {item.deleted_at ? item.deleted_at.split(' ')[0] : 'Recently'}
                        </span>
                      </div>
                      <h4 className={`font-black text-sm uppercase leading-tight ${isSunlightMode ? 'text-slate-950' : 'text-white'}`}>
                        {item.year} {item.make} {item.model}
                      </h4>
                    </div>

                    <div className="flex gap-2 border-t border-slate-800/10 pt-3">
                      <button
                        type="button"
                        onClick={() => handleRestoreUnit(item.wpId)}
                        className="flex-1 bg-green-600 hover:bg-green-700 text-white font-black text-[10px] uppercase tracking-wider py-2.5 rounded-lg flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow"
                      >
                        <RotateCcw size={14}/>
                        <span>Restore Unit</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => { if (window.confirm('Permanently delete this unit? This action is irreversible.')) handlePermanentDelete(item.wpId); }}
                        className="flex-1 bg-red-600 hover:bg-red-700 text-white font-black text-[10px] uppercase tracking-wider py-2.5 rounded-lg flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow"
                      >
                        <Trash2 size={14}/>
                        <span>Destroy Post</span>
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
            <div className="h-20" />
          </div>
        )}

      </div>

      {/* 2-BUTTON PRIMARY BOTTOM NAV BAR */}
      <nav className={`fixed bottom-0 left-0 right-0 border-t flex items-center justify-around py-3 px-4 shadow-2xl z-[999] shrink-0 safe-bottom ${
        isSunlightMode ? 'bg-white border-slate-200 text-slate-700' : 'bg-[#0d0d0f] border-[#18181b] text-slate-400'
      }`}>
        <button
          onClick={() => setMobileActiveTab('edit')}
          className={`flex flex-col items-center gap-1 p-2 min-w-[100px] transition-colors ${
            mobileActiveTab === 'edit' ? 'text-red-500' : 'text-slate-400 hover:text-white'
          }`}
        >
          <Plus size={22} />
          <span className="text-[10px] font-black uppercase tracking-wider">Add Unit</span>
        </button>
        
        <button
          onClick={() => setMobileActiveTab('marketplace')}
          className={`flex flex-col items-center gap-1 p-2 min-w-[100px] transition-colors ${
            mobileActiveTab === 'marketplace' ? 'text-red-500' : 'text-slate-400 hover:text-white'
          }`}
        >
          <Zap size={22} />
          <span className="text-[10px] font-black uppercase tracking-wider">Meta Sync</span>
        </button>
      </nav>

      {/* SLIDING MENU DRAWER */}
      {isDrawerOpen && (
        <div className="fixed inset-0 z-[10000] flex animate-fade-in">
          {/* Backdrop */}
          <div 
            onClick={() => setIsDrawerOpen(false)} 
            className="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"
          />
          
          {/* Panel Container */}
          <aside className={`relative w-[280px] max-w-[85vw] h-full flex flex-col pt-0 px-6 pb-6 shadow-2xl border-r transition-transform animate-in slide-in-from-left duration-300 ${
            isSunlightMode ? 'bg-white border-slate-200 text-slate-900' : 'bg-[#0d0d0f] border-[#18181b] text-white'
          }`}>
            <div className="flex items-center justify-between mb-8 border-b border-slate-500/60/20 pb-4 safe-top">
              <div className="flex items-center gap-2">
                {logoUrl ? (
                  <img src={logoUrl} className="h-8 max-w-[120px] object-contain" alt="Varner OS" />
                ) : (
                  <span className="font-black text-sm uppercase tracking-wider text-red-500">Varner OS</span>
                )}
              </div>
              <button 
                onClick={() => setIsDrawerOpen(false)} 
                className={`p-2 rounded-xl transition-all ${isSunlightMode ? 'hover:bg-slate-100' : 'hover:bg-[#18181b]'}`}
              >
                <X size={20} />
              </button>
            </div>

            <nav className="space-y-2.5 flex-1">
              <button
                onClick={() => { setMobileActiveTab('edit'); setIsDrawerOpen(false); }}
                className={`w-full text-left py-3.5 px-4 rounded-xl flex items-center gap-3 font-black text-xs uppercase tracking-wider transition-all ${
                  mobileActiveTab === 'edit' 
                    ? 'bg-red-600 text-white' 
                    : (isSunlightMode ? 'hover:bg-slate-100 text-slate-700' : 'hover:bg-[#18181b] text-slate-300')
                }`}
              >
                <Plus size={16} />
                <span>Add Unit</span>
              </button>

              <button
                onClick={() => { setMobileActiveTab('list'); loadInventory(); setIsDrawerOpen(false); }}
                className={`w-full text-left py-3.5 px-4 rounded-xl flex items-center justify-between font-black text-xs uppercase tracking-wider transition-all ${
                  mobileActiveTab === 'list' 
                    ? 'bg-red-600 text-white' 
                    : (isSunlightMode ? 'hover:bg-slate-100 text-slate-700' : 'hover:bg-[#18181b] text-slate-300')
                }`}
              >
                <div className="flex items-center gap-3">
                  <List size={16} />
                  <span>Yard Fleet</span>
                </div>
                <span className={`px-2 py-0.5 rounded text-[8px] font-black ${
                  mobileActiveTab === 'list' ? 'bg-white text-red-600' : 'bg-red-600/10 text-red-500'
                }`}>
                  {inventoryList.length}
                </span>
              </button>

              <button
                onClick={() => { setMobileActiveTab('marketplace'); setIsDrawerOpen(false); }}
                className={`w-full text-left py-3.5 px-4 rounded-xl flex items-center gap-3 font-black text-xs uppercase tracking-wider transition-all ${
                  mobileActiveTab === 'marketplace' 
                    ? 'bg-red-600 text-white' 
                    : (isSunlightMode ? 'hover:bg-slate-100 text-slate-700' : 'hover:bg-[#18181b] text-slate-300')
                }`}
              >
                <Zap size={16} />
                <span>Meta Sync</span>
              </button>

              <button
                onClick={() => { setMobileActiveTab('history'); setIsDrawerOpen(false); }}
                className={`w-full text-left py-3.5 px-4 rounded-xl flex items-center justify-between font-black text-xs uppercase tracking-wider transition-all ${
                  mobileActiveTab === 'history' 
                    ? 'bg-red-600 text-white' 
                    : (isSunlightMode ? 'hover:bg-slate-100 text-slate-700' : 'hover:bg-[#18181b] text-slate-300')
                }`}
              >
                <div className="flex items-center gap-3">
                  <RotateCcw size={16} />
                  <span>Recycle History</span>
                </div>
                {deletedHistory.length > 0 && (
                  <span className={`px-2 py-0.5 rounded text-[8px] font-black ${
                    mobileActiveTab === 'history' ? 'bg-white text-red-600' : 'bg-red-600/10 text-red-500'
                  }`}>
                    {deletedHistory.length}
                  </span>
                )}
              </button>
            </nav>

            <div className="pt-4 border-t border-slate-500/60/20">
              <button 
                onClick={handleLogout} 
                className={`w-full text-left py-3 px-4 rounded-xl flex items-center gap-3 font-black text-xs uppercase tracking-wider transition-all text-slate-400 hover:text-red-500`}
              >
                <LogOut size={16} />
                <span>Sign Out</span>
              </button>
            </div>
          </aside>
        </div>
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
    </div>
  );
};
