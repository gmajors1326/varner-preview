import React, { useState, useEffect, useCallback } from 'react';
import {
  Box, LayoutDashboard, List, Facebook, History, Sliders, Camera, Smartphone, Settings,
  X, Menu, Copy, Plus, Upload, Download, Save, Zap, TrendingUp, CheckCircle2, Clock,
  ChevronRight, Star, Eye, ArrowUpRight, Search, Edit2, Image as ImageIcon, Loader2
} from 'lucide-react';

import { DEFAULT_EMPTY_UNIT } from './constants/inventoryConstants';

import { useInventory } from './hooks/useInventory';
import { useFilters } from './hooks/useFilters';
import { useMobileAuth } from './hooks/useMobileAuth';

import { SidebarLogo, SidebarContent, FilterTag, MappingRow } from './components/Common/Navigation';
import { ManageListModal } from './components/Common/Modals';
import { InputField, TextAreaField, SelectField, QUILL_STYLES } from './components/Common/FormFields';
import { MetricCard, QuickActions, RecentActivity } from './components/Common/DashboardCards';
import { InventoryTable } from './components/InventoryTable';
import { UnitEditorPanel } from './components/UnitEditorPanel';
import { MarketplaceTab } from './components/Tabs/MarketplaceTab';
import { SettingsTab } from './components/Tabs/SettingsTab';
import { VideosTab } from './components/Tabs/VideosTab';
import { MobileAccessTab } from './components/Tabs/MobileAccessTab';
import { ConfigurationTab } from './components/Tabs/ConfigurationTab';
import { HistoryTab } from './components/Tabs/HistoryTab';
import { PwaLoginGate } from './components/PwaLoginGate';
import { ErrorBoundary } from './components/ErrorBoundary';

const defaultEmptyUnit = DEFAULT_EMPTY_UNIT;

const App = () => {
  const { isMobileApp, mobileToken, setMobileToken } = useMobileAuth();

  const [activeTab, setActiveTab] = useState('dashboard');
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [toast, setToast] = useState(null);

  const showToast = useCallback((msg, type = 'success') => {
    setToast({ msg, type });
    setTimeout(() => setToast(null), 3500);
  }, []);

  const inv = useInventory(showToast, setActiveTab);
  const { searchQuery, setSearchQuery, activeFilters, showFilterPanel, setShowFilterPanel,
    handleFilterChange, handleClearFilters, filteredInventory } = useFilters(inv.inventoryList, inv.isPublicMode);

  useEffect(() => {
    if (activeTab === 'config') {
      inv.loadSessions(true);
      inv.loadActivity();
    }
  }, [activeTab, inv.loadSessions, inv.loadActivity]);

  useEffect(() => {
    if (isMobileApp) {
      setActiveTab('all-inventory');
    }
  }, []);

  useEffect(() => {
    const handler = () => { if (!document.hidden) inv.loadInventory(); };
    document.addEventListener('visibilitychange', handler);
    const pollId = setInterval(() => inv.loadInventory(), 30000);
    return () => {
      document.removeEventListener('visibilitychange', handler);
      clearInterval(pollId);
    };
  }, [inv.loadInventory]);

  const handlePwaAuthenticated = () => {
    const params = new URLSearchParams(window.location.search);
    const action = params.get('action');
    if (action === 'new') {
      inv.setUnitData(defaultEmptyUnit);
      setActiveTab('inventory');
    } else {
      setActiveTab('all-inventory');
    }
  };

  const handleNav = (tab) => {
    if (tab === 'inventory' && activeTab !== 'inventory') inv.setUnitData(defaultEmptyUnit);
    setActiveTab(tab);
    setIsMobileMenuOpen(false);
  };

  const handleAddNewUnit = () => { inv.setUnitData(defaultEmptyUnit); setActiveTab('inventory'); };

  const getHeaderTitle = () => {
    switch (activeTab) {
      case 'dashboard': return 'Operations Overview';
      case 'all-inventory': return 'Master Inventory Ledger';
      case 'inventory': return (
          <span className="flex items-center gap-2 min-w-0">
            <span className="truncate">{inv.unitData.title || 'Inventory Editor'}</span>
            <span className="hidden sm:inline bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded uppercase tracking-tighter font-black shrink-0">SKU: {inv.unitData.stockNumber || 'PENDING'}</span>
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

  if (isMobileApp && !mobileToken) {
    return (
      <PwaLoginGate
        mobileToken={mobileToken}
        setMobileToken={setMobileToken}
        toast={toast}
        onAuthenticated={handlePwaAuthenticated}
      />
    );
  }

  return (
    <div className={`flex bg-[#f8fafc] font-sans text-slate-900 selection:bg-red-100 ${isMobileApp ? 'h-dvh' : 'min-h-screen'}`}>
      <style dangerouslySetInnerHTML={{ __html: QUILL_STYLES }} />
      <style>{`body{padding:env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left)}`}</style>

      {toast && (
        <div role="alert" aria-live="assertive" className={`fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[9999] px-6 py-4 rounded-2xl font-black text-sm shadow-2xl transition-all animate-in fade-in whitespace-nowrap max-w-[90vw] text-center ${toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`}>
          {toast.msg}
        </div>
      )}

      {inv.showBrandsModal && (
        <ManageListModal title="Manage Brands" items={inv.brands} inputValue={inv.newBrandInput}
          onInputChange={inv.setNewBrandInput} onAdd={inv.handleAddBrand} onDelete={inv.handleDeleteBrand}
          onClose={() => inv.setShowBrandsModal(false)} placeholder="New brand name..." />
      )}
      {inv.showYearsModal && (
        <ManageListModal title="Manage Years" items={inv.years} inputValue={inv.newYearInput}
          onInputChange={inv.setNewYearInput} onAdd={inv.handleAddYear} onDelete={inv.handleDeleteYear}
          onClose={() => inv.setShowYearsModal(false)} placeholder="New year (e.g. 2028)..." />
      )}
      {inv.showCategoriesModal && (
        <ManageListModal title="Manage Categories"
          items={inv.categories}
          inputValue={inv.newCategoryInput}
          onInputChange={inv.setNewCategoryInput}
          onAdd={inv.handleAddCategory}
          onDelete={inv.handleDeleteCategory}
          onClose={() => inv.setShowCategoriesModal(false)}
          placeholder="New category name..." />
      )}
      {inv.showSubcategoriesModal && (
        <ManageListModal title="Manage Subcategories"
          items={inv.subcategories}
          inputValue={inv.newSubcategoryInput}
          onInputChange={inv.setNewSubcategoryInput}
          onAdd={inv.handleAddSubcategory}
          onDelete={inv.handleDeleteSubcategory}
          onClose={() => inv.setShowSubcategoriesModal(false)}
          placeholder="New subcategory name..." />
      )}
      {inv.showSubSubcategoriesModal && (
        <ManageListModal title="Manage Sub-Subcategories"
          items={inv.subSubcategories}
          inputValue={inv.newSubSubcategoryInput}
          onInputChange={inv.setNewSubSubcategoryInput}
          onAdd={inv.handleAddSubSubcategory}
          onDelete={inv.handleDeleteSubSubcategory}
          onClose={() => inv.setShowSubSubcategoriesModal(false)}
          placeholder="New sub-subcategory name..." />
      )}

      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" onClick={() => setIsMobileMenuOpen(false)}></div>
          <aside className="fixed inset-y-0 left-0 w-72 bg-slate-950 text-white p-6 shadow-2xl flex flex-col">
            <div className="flex items-center justify-between mb-8 border-b border-slate-800 pb-6">
              <SidebarLogo />
              <button onClick={() => setIsMobileMenuOpen(false)} className="text-slate-400 hover:text-white p-2"><X size={24} /></button>
            </div>
            <SidebarContent activeTab={activeTab} inventoryList={inv.inventoryList} deletedHistory={inv.deletedHistory}
              onNav={handleNav} isMobileApp={isMobileApp} />
          </aside>
        </div>
      )}

      <aside className="hidden lg:flex flex-col w-72 bg-slate-950 text-white p-6 shadow-2xl border-r border-slate-800 shrink-0">
        <SidebarContent activeTab={activeTab} inventoryList={inv.inventoryList} deletedHistory={inv.deletedHistory}
          onNav={handleNav} isMobileApp={isMobileApp} />
      </aside>

      <main className="flex-1 min-w-0 flex flex-col text-slate-900 min-h-0">
        <header className="bg-white border-b border-slate-200 px-4 sm:px-8 py-4 sm:py-5 flex items-center justify-between shadow-sm z-10">
          <div className="flex items-center gap-3 min-w-0">
            <button onClick={() => setIsMobileMenuOpen(true)} className="lg:hidden p-2 text-white bg-red-600 hover:bg-red-700 rounded-xl shrink-0"><Menu size={24} /></button>
            <div className="flex flex-col min-w-0">
              <h2 className="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1 hidden sm:block">System Modules</h2>
              <h3 className="text-base sm:text-xl font-black text-slate-950 tracking-tight leading-none uppercase truncate">{getHeaderTitle()}</h3>
            </div>
          </div>
           <div className="flex items-center gap-2 sm:gap-3 shrink-0">
            {activeTab === 'inventory' && inv.unitData.title && (
              <button onClick={() => inv.handleClone()} className="bg-slate-100 text-slate-600 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95">
                <Copy size={16} /> <span className="hidden sm:inline">Clone Unit</span>
              </button>
            )}
            {activeTab === 'inventory' && (
              <button onClick={handleAddNewUnit} className="bg-red-600 text-white p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-red-700 transition-all border-b-2 border-red-800 shadow-xl shadow-red-100 active:scale-95">
                <Plus size={16} /> <span className="hidden sm:inline">New Unit</span>
              </button>
            )}
            {!isMobileApp && activeTab === 'all-inventory' && (
              <a href="/wp-admin/admin.php?page=pmxi-admin-import" className="bg-slate-100 text-slate-700 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95">
                <Upload size={16} /> <span className="hidden sm:inline">Import Inventory</span><span className="sm:hidden">Import</span>
              </a>
            )}
            {!isMobileApp && activeTab === 'all-inventory' && (
              <a href="/wp-admin/admin.php?page=pmxe-admin-manage" className="bg-slate-100 text-slate-700 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95">
                <Download size={16} /> <span className="hidden sm:inline">Export Inventory</span><span className="sm:hidden">Export</span>
              </a>
            )}
            {(activeTab === 'inventory' || activeTab === 'all-inventory') && (
              <button onClick={activeTab === 'inventory' ? inv.handleSave : handleAddNewUnit}
                className="bg-red-600 text-white p-3 sm:px-7 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 active:scale-95 transition-all border-b-2 border-red-800">
                {inv.isSaving ? <Zap className="animate-spin" size={16} /> : (activeTab === 'inventory' ? <Save size={16} /> : <Plus size={16} />)}
                <span className="hidden sm:inline">{inv.isSaving ? 'PUBLISHING\u2026' : (activeTab === 'inventory' ? 'PUBLISH TO INVENTORY' : 'NEW UNIT')}</span>
                <span className="sm:hidden">{activeTab === 'inventory' ? (inv.isSaving ? 'PUB\u2026' : 'PUBLISH') : 'NEW'}</span>
              </button>
            )}
          </div>
        </header>

        <div className={`flex-1 min-h-0 overflow-y-auto bg-slate-50/50 no-scrollbar pb-[max(2rem,env(safe-area-inset-bottom))] ${activeTab === 'all-inventory' || activeTab === 'history' ? 'px-2 py-4 sm:px-3 sm:py-6' : 'p-4 sm:p-6 lg:p-8'}`} style={{ WebkitOverflowScrolling: 'touch' }}>
          <div className={`${activeTab === 'all-inventory' || activeTab === 'history' ? 'max-w-none px-4 sm:px-6 lg:px-8' : 'max-w-7xl'} mx-auto pb-10`}>

            {activeTab === 'dashboard' && (
              <div className="space-y-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                  <MetricCard icon={<Box size={24} />} label="Live Units" value={inv.isLoading ? '\u2026' : String(inv.inventoryList.filter(i => (i.status || '').toLowerCase() === 'in stock').length)} subtext="In stock right now" color="blue" />
                  <MetricCard icon={<TrendingUp size={24} />} label="Total Units" value={inv.isLoading ? '\u2026' : String(inv.inventoryList.length)} subtext="All active listings" color="amber" />
                  <MetricCard icon={<CheckCircle2 size={24} />} label="Sold Units" value={inv.isLoading ? '\u2026' : String(inv.inventoryList.filter(i => (i.status || '').toLowerCase() === 'sold').length)} subtext="Marked as sold" color="green" />
                  <MetricCard icon={<Clock size={24} />} label="Pending Sales" value={inv.isLoading ? '\u2026' : String(inv.inventoryList.filter(i => ['sale pending', 'pending sale', 'pending'].includes((i.status || '').toLowerCase())).length)} subtext="Awaiting close" color="red" />
                </div>
                <div className="space-y-8">
                  <QuickActions onAdd={handleAddNewUnit} />
                  <RecentActivity />
                </div>
              </div>
            )}

            {activeTab === 'all-inventory' && (
              <ErrorBoundary name="Inventory Table">
                <InventoryTable
                  filteredInventory={filteredInventory}
                  inventoryList={inv.inventoryList}
                  isLoading={inv.isLoading}
                  activeFilters={activeFilters}
                  searchQuery={searchQuery}
                  onFilterChange={handleFilterChange}
                  onSearch={setSearchQuery}
                  onClearFilters={handleClearFilters}
                  onEdit={inv.handleFullEdit}
                  onDelete={inv.handleDeleteUnit}
                  onClone={(wpId) => inv.handleFullEdit(wpId).then(inv.handleClone)}
                  onToggle={inv.handleToggleBoolean}
                  onToggleDraft={inv.handleToggleDraft}
                />
              </ErrorBoundary>
            )}

            {activeTab === 'inventory' && (
              <ErrorBoundary name="Unit Editor">
                <UnitEditorPanel
                  unitData={inv.unitData}
                  handleInputChange={inv.handleInputChange}
                  onToggleDraft={inv.handleToggleDraft}
                  handleSave={inv.handleSave}
                  handleClone={inv.handleClone}
                  isSaving={inv.isSaving}
                  isUploadingImages={inv.isUploadingImages}
                  fieldErrors={inv.fieldErrors}
                  brands={inv.brands}
                  years={inv.years}
                  categories={inv.categories}
                  subcategories={inv.subcategories}
                  subSubcategories={inv.subSubcategories}
                  handleCategorySelectChange={inv.handleCategorySelectChange}
                  handleSubcategorySelectChange={inv.handleSubcategorySelectChange}
                  handleSubSubcategorySelectChange={inv.handleSubSubcategorySelectChange}
                  handleAddImages={inv.handleAddImages}
                  handleRemoveImage={inv.handleRemoveImage}
                  handleReorderImages={inv.handleReorderImages}
                  handleAddImplement={inv.handleAddImplement}
                  handleUpdateImplement={inv.handleUpdateImplement}
                  handleRemoveImplement={inv.handleRemoveImplement}
                  handleImplementImageUpload={inv.handleImplementImageUpload}
                  setShowBrandsModal={inv.setShowBrandsModal}
                  setShowYearsModal={inv.setShowYearsModal}
                  setShowCategoriesModal={inv.setShowCategoriesModal}
                  setShowSubcategoriesModal={inv.setShowSubcategoriesModal}
                  setShowSubSubcategoriesModal={inv.setShowSubSubcategoriesModal}
                  onUnitUpdated={inv.applyUnitUpdate}
                />
              </ErrorBoundary>
            )}

            {activeTab === 'marketplace' && (
              <ErrorBoundary name="Marketplace">
                <MarketplaceTab />
              </ErrorBoundary>
            )}
            {activeTab === 'settings' && (
              <ErrorBoundary name="Settings">
                <SettingsTab showToast={showToast} />
              </ErrorBoundary>
            )}
            {activeTab === 'videos' && (
              <ErrorBoundary name="Videos">
                <VideosTab showToast={showToast} />
              </ErrorBoundary>
            )}
            {activeTab === 'mobile' && (
              <ErrorBoundary name="Mobile Access">
                <MobileAccessTab />
              </ErrorBoundary>
            )}
            {activeTab === 'config' && (
              <ErrorBoundary name="Configuration">
                <ConfigurationTab
                  showToast={showToast}
                  currentUser={inv.currentUser}
                  sessionList={inv.sessionList}
                  isLoading={inv.isSessionsLoading}
                  loadSessions={inv.loadSessions}
                  activityList={inv.activityList}
                  isActivityLoading={inv.isActivityLoading}
                  loadActivity={inv.loadActivity}
                  onNav={handleNav}
                  handleFullEdit={inv.handleFullEdit}
                />
              </ErrorBoundary>
            )}
            {activeTab === 'history' && (
              <ErrorBoundary name="History">
                <HistoryTab
                  deletedItems={inv.deletedHistory}
                  onRestore={item => inv.handleRestoreUnit(item.wpId)}
                  onPermanentDelete={item => inv.handlePermanentDelete(item.wpId)}
                  onBulkRestore={inv.handleBulkRestore}
                  onBulkPermanentDelete={inv.handleBulkPermanentDelete}
                />
              </ErrorBoundary>
            )}

          </div>
        </div>
      </main>

    </div>
  );
};

export default App;
