import React from 'react';
import {
  Box, Facebook, Settings, ChevronRight, ChevronDown, Star, Eye, Save, Copy,
  Loader2, CheckCircle2, ArrowUpRight
} from 'lucide-react';
import {
  COLOR_OPTIONS, STATUS_OPTIONS, CONDITION_OPTIONS,
  METER_TYPE_OPTIONS, CATEGORY_TREE
} from '../constants/inventoryConstants';
import { InputField, TextAreaField, SelectField } from './Common/FormFields';
import { MediaSection } from './MediaSection';
import { AttachmentsSection } from './AttachmentsSection';

export const UnitEditorPanel = ({
  unitData,
  handleInputChange,
  onToggleDraft,
  handleSave,
  handleClone,
  isSaving,
  isUploadingImages,
  fieldErrors,
  brands,
  categories,
  subcategories,
  subSubcategories,
  handleCategorySelectChange,
  handleSubcategorySelectChange,
  handleSubSubcategorySelectChange,
  handleAddImages,
  handleRemoveImage,
  handleReorderImages,
  handleAddImplement,
  handleUpdateImplement,
  handleRemoveImplement,
  handleImplementImageUpload,
  setShowBrandsModal,
  setShowCategoriesModal,
  setShowSubcategoriesModal,
  setShowSubSubcategoriesModal,
  syncEnabled,
  setSyncEnabled,
  setShowFBPreview,
}) => {
  // Build category option lists
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
    <div className="grid grid-cols-1 xl:grid-cols-3 gap-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
      <div className="xl:col-span-2 space-y-8">
        <div className="bg-white rounded-[2rem] p-4 sm:p-6 lg:p-8 shadow-xl border border-slate-200/60 relative overflow-hidden text-slate-900">
          <div className="flex justify-between items-center mb-6 sm:mb-8">
            <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2 leading-none"><Box size={14} className="text-red-600" /> Equipment Identity</h3>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Category Hierarchy */}
            <div className="md:col-span-2 animate-in fade-in duration-300">
              <div className="space-y-4">
                <div className="flex items-center justify-between pl-1">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Equipment Category Hierarchy</label>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* Category */}
                  <div className="space-y-2">
                    <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1">Category *</label>
                    <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                      <select value={unitData.category || ''} onChange={e => handleCategorySelectChange(e.target.value)}
                        className="w-full bg-transparent p-4 pr-12 font-bold text-slate-900 outline-none appearance-none cursor-pointer text-sm leading-none"
                        style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                                <option value="">-- Select Category --</option>
                        {allCategories.map(c => <option key={c} value={c}>{c}</option>)}
                      </select>
                      <div className="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><ChevronRight size={18} className="rotate-90" /></div>
                    </div>
                    <button type="button" onClick={() => setShowCategoriesModal(true)}
                      className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest min-h-[64px] mt-2">
                      <Settings size={14} /> Manage Categories
                    </button>
                  </div>

                  {/* Subcategory */}
                  <div className="space-y-2">
                    <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1">Subcategory</label>
                    <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                      <select value={unitData.subcategory || ''} onChange={e => handleSubcategorySelectChange(e.target.value)}
                        className="w-full bg-transparent p-4 pr-12 font-bold text-slate-900 outline-none appearance-none cursor-pointer text-sm leading-none"
                        style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                                <option value="">-- Select Subcategory --</option>
                        {allSubcategories.map(sub => <option key={sub} value={sub}>{sub}</option>)}
                      </select>
                      <div className="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400"><ChevronRight size={18} className="rotate-90" /></div>
                    </div>
                    <button type="button" onClick={() => setShowSubcategoriesModal(true)}
                      className="w-full bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest min-h-[64px] mt-2">
                      <Settings size={14} /> Manage Subcategories
                    </button>
                  </div>

                  {/* Sub-Subcategory */}
                  <div className="space-y-2">
                    <label className="text-[9px] font-black text-slate-400 uppercase tracking-wider pl-1">Sub-Subcategory</label>
                    <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                      <select value={unitData.sub_subcategory || ''} onChange={e => handleSubSubcategorySelectChange(e.target.value)}
                        className="w-full bg-transparent p-4 pr-12 font-bold text-slate-900 outline-none appearance-none cursor-pointer text-sm leading-none"
                        style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                                <option value="">-- Select Sub-Subcategory --</option>
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

            {/* Brand */}
            <div className="md:col-span-2">
              <div className="space-y-3">
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Brand / Manufacturer</label>
                <div className="flex flex-col sm:flex-row gap-3">
                  <div className="relative flex-1 flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                    <select key={`brand-select-${brands.length}`} value={unitData.make}
                      onChange={e => {
                        const v = e.target.value;
                        handleInputChange('make', v);
                        handleInputChange('title', `${unitData.year} ${v} ${unitData.model}`.trim());
                      }}
                      className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none"
                      style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                            <option value="">-- Select Brand --</option>
                      {brands.map(b => <option key={b} value={b}>{b}</option>)}
                    </select>
                    <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronDown size={24} /></div>
                  </div>
                  <button type="button" onClick={() => setShowBrandsModal(true)}
                    className="bg-slate-50 hover:bg-red-50 border-2 border-slate-100 hover:border-red-200 text-red-600 rounded-xl px-6 flex items-center justify-center gap-2 shadow-sm transition-all font-black text-xs uppercase tracking-widest whitespace-nowrap min-h-[64px]">
                    <Settings size={14} /> Manage Brands
                  </button>
                </div>
                {fieldErrors.make && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.make}</p>}
              </div>
            </div>

            {/* Year + Model */}
            <div className="flex flex-col sm:flex-row gap-3 md:col-span-2">
              <div className="flex-1">
                <div className="space-y-3">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Year</label>
                  <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                    <select value={unitData.year}
                      onChange={e => {
                        const v = e.target.value;
                        handleInputChange('year', v);
                        handleInputChange('title', `${v} ${unitData.make} ${unitData.model}`.trim());
                      }}
                      className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none"
                      style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}>
                                            <option value="">-- Select Year --</option>
                      {Array.from({ length: new Date().getFullYear() + 1 - 1950 }, (_, i) => new Date().getFullYear() - i).map(year => (
                        <option key={year} value={year}>{year}</option>
                      ))}
                    </select>
                    <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={24} className="rotate-90" /></div>
                  </div>
                  {fieldErrors.year && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.year}</p>}
                </div>
              </div>
              <div className="flex-1">
                <InputField label="Model" value={unitData.model}
                  onChange={v => { handleInputChange('model', v); handleInputChange('title', `${unitData.year} ${unitData.make} ${v}`.trim()); }}
                  error={fieldErrors.model} />
              </div>
            </div>

            {/* Title */}
            <div className="md:col-span-2">
              <div className="space-y-3">
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">
                  Public Inventory Title <span className="text-red-600">(Mandatory)</span>
                </label>
                <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wide -mt-1 ml-1 leading-relaxed opacity-80">
                  Main heading for website &amp; marketplace. <span className="text-slate-500">Include Year, Make, and Model for optimal SEO and visibility.</span>
                </p>
                <input type="text" value={unitData.title} onChange={e => handleInputChange('title', e.target.value)}
                  className={`w-full bg-slate-50 border-2 rounded-xl p-4 font-black text-slate-900 outline-none transition-all shadow-sm text-xl leading-none min-h-[64px] ${fieldErrors.title ? 'border-red-400 focus:border-red-500 bg-red-50/40' : 'border-slate-100 focus:border-slate-300 focus:bg-white'}`} />
                {fieldErrors.title && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.title}</p>}
              </div>
            </div>

            {/* Status + Condition */}
            <div className="flex flex-col sm:flex-row gap-3">
              <div className="flex-1"><SelectField label="Stock Status" options={STATUS_OPTIONS} value={unitData.stockStatus} onChange={v => handleInputChange('stockStatus', v)} error={fieldErrors.stockStatus} /></div>
              <div className="flex-1"><SelectField label="Condition" options={CONDITION_OPTIONS} value={unitData.condition} onChange={v => handleInputChange('condition', v)} error={fieldErrors.condition} /></div>
            </div>

            {/* Color + Trailer Length (shown only for trailer categories) */}
            <div className="flex flex-col sm:flex-row gap-3">
              <div className="flex-1"><SelectField label="Color" placeholder="Choose Color" options={COLOR_OPTIONS} value={unitData.color} onChange={v => handleInputChange('color', v)} error={fieldErrors.color} /></div>
              {unitData.category && unitData.category.toLowerCase().includes('trailer') && (
                <div className="flex-1 space-y-3">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Trailer Length</label>
                  <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px]">
                    <select
                      value={unitData.length}
                      onChange={e => handleInputChange('length', e.target.value)}
                      className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none"
                      style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}
                    >
                      <option value="">-- Select Length --</option>
                      {Array.from({ length: 46 }, (_, i) => i + 8).map(ft => (
                        <option key={ft} value={`${ft} ft`}>{ft} ft</option>
                      ))}
                    </select>
                    <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronDown size={24} /></div>
                  </div>
                  {fieldErrors.length && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.length}</p>}
                </div>
              )}
            </div>

            {/* Meter + Drive */}
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

            {/* VIN + Stock # */}
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

            {/* Price */}
            <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 px-1 items-end">
              <div className="space-y-3">
                <label className="text-[10px] font-black text-green-600 uppercase tracking-widest block pl-1">Retail Price (USD)</label>
                <label className="flex items-center gap-3 cursor-pointer group w-fit ml-1">
                  <div className="relative flex items-center">
                    <input type="checkbox" checked={unitData.callForPrice} onChange={e => handleInputChange('callForPrice', e.target.checked)} className="sr-only peer" />
                    <div className="w-10 h-6 bg-slate-200 rounded-full peer-checked:bg-red-600 transition-all after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4 shadow-inner" />
                  </div>
                  <span className="text-[9px] font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-900 transition-colors">Call For Price</span>
                </label>
                <div className={`flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl focus-within:border-slate-300 focus-within:bg-white transition-all shadow-sm min-h-[64px] ${unitData.callForPrice ? 'opacity-40 grayscale pointer-events-none' : ''}`}>
                  <div className="pl-5 pr-3 py-5 border-r border-slate-200 bg-slate-100/50 rounded-l-xl"><span className="text-green-600 font-black text-xl select-none">$</span></div>
                  <input type="text" value={unitData.price ? Number(unitData.price).toLocaleString() : ''} disabled={unitData.callForPrice}
                    onChange={e => handleInputChange('price', e.target.value.replace(/[^0-9]/g, ''))}
                    className="flex-1 bg-transparent p-4 font-black text-slate-900 outline-none text-xl leading-none" placeholder="0.00" />
                </div>
                {fieldErrors.price && <p className="text-[10px] font-bold text-red-600 pl-1">{fieldErrors.price}</p>}
              </div>
            </div>

            {/* Featured + Visibility toggles */}
            <div className="md:col-span-2 space-y-4">
              {[
                { field: 'featured', icon: <Star size={20} fill={unitData.featured ? 'currentColor' : 'none'} />, iconBg: unitData.featured ? 'bg-amber-100 text-amber-600' : 'bg-white text-slate-300', label: 'Featured Unit', sub: 'Display at the top of the homepage', color: unitData.featured ? 'bg-amber-500' : 'bg-slate-200' },
                { field: 'showOnWebsite', icon: <Eye size={20} />, iconBg: unitData.showOnWebsite ? 'bg-green-100 text-green-600' : 'bg-white text-slate-300', label: 'Website Visibility', sub: 'Publicly visible on showroom pages', color: unitData.showOnWebsite ? 'bg-green-600' : 'bg-slate-200' },
                { field: 'facebookSync', icon: <Facebook size={20} fill={unitData.facebookSync ? 'currentColor' : 'none'} />, iconBg: unitData.facebookSync ? 'bg-blue-100 text-blue-600' : 'bg-white text-slate-300', label: 'Sync to Meta/Facebook', sub: 'Include this unit in the Facebook Catalog CSV feed', color: unitData.facebookSync ? 'bg-blue-600' : 'bg-slate-200' },
              ].map(({ field, icon, iconBg, label, sub, color }) => (
                <div key={field} className="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-red-200 transition-all">
                  <div className="flex items-center gap-4">
                    <div className={`p-3 rounded-xl transition-all ${iconBg}`}>{icon}</div>
                    <div>
                      <p className="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">{label}</p>
                      <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wide">{sub}</p>
                    </div>
                  </div>
                  <button type="button" onClick={() => handleInputChange(field, !unitData[field])}
                    className={`w-14 h-7 rounded-full relative transition-all duration-300 ${color}`}>
                    <div className={`absolute top-1 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300 ${unitData[field] ? 'left-8' : 'left-1'}`} />
                  </button>
                </div>
              ))}
              {/* Draft toggle — separate row since it mirrors the enum, not a boolean */}
              <div className="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-red-200 transition-all">
                <div className="flex items-center gap-4">
                  <div className={`p-3 rounded-xl transition-all ${unitData.stockStatus === 'Draft' ? 'bg-amber-100 text-amber-600' : 'bg-white text-slate-300'}`}>
                    <Eye size={20} />
                  </div>
                  <div>
                    <p className="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">Draft (Hidden)</p>
                    <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Unit is a draft — hidden from the public site &amp; Facebook feed</p>
                  </div>
                </div>
                <button type="button" onClick={() => onToggleDraft({ wpId: unitData.id, status: unitData.stockStatus })}
                  className={`w-14 h-7 rounded-full relative transition-all duration-300 ${unitData.stockStatus === 'Draft' ? 'bg-amber-500' : 'bg-slate-200'}`}>
                  <div className={`absolute top-1 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300 ${unitData.stockStatus === 'Draft' ? 'left-8' : 'left-1'}`} />
                </button>
              </div>
            </div>

            {/* Description + Seller Info */}
            <div className="md:col-span-2 space-y-6 pt-6 border-t border-slate-50">
              <TextAreaField label="Public Description / Features" value={unitData.description} onChange={v => handleInputChange('description', v)} />
              <TextAreaField label="Seller Information Template" value={unitData.sellerInfo} onChange={v => handleInputChange('sellerInfo', v)} />
            </div>
          </div>
        </div>

        <MediaSection title="High-Resolution Media" images={unitData.images} onAddFiles={handleAddImages} onRemove={handleRemoveImage} onReorder={handleReorderImages} isUploading={isUploadingImages} />
        <AttachmentsSection attachments={unitData.attachments} onAdd={handleAddImplement} onChange={handleUpdateImplement} onRemove={handleRemoveImplement} onImageUpload={handleImplementImageUpload} />

        {/* Bottom action buttons */}
        <div className="flex flex-col sm:flex-row gap-4 pt-6">
          <button onClick={handleClone} disabled={!unitData.id}
            className={`flex-1 px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 transition-all active:scale-95 border-2 shadow-xl shadow-slate-200/50 ${!unitData.id ? 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed' : 'bg-white text-slate-600 border-slate-100 hover:bg-slate-50'}`}>
            <Copy size={18} /> Clone Unit
          </button>
          <button onClick={handleSave} disabled={isSaving}
            className="flex-[2] bg-red-600 text-white px-8 py-6 rounded-[2rem] font-black text-[11px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-red-700 transition-all active:scale-95 shadow-2xl shadow-red-200 border-b-4 border-red-800 disabled:opacity-50">
            {isSaving ? <Loader2 className="animate-spin" size={18} /> : <Save size={18} />}
            {isSaving ? 'PUBLISHING…' : 'PUBLISH TO INVENTORY'}
          </button>
        </div>
      </div>

      {/* Right — Marketplace widget */}
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
            <button onClick={() => setShowFBPreview(true)} className="w-full bg-slate-950 text-white py-6 rounded-[1.5rem] font-black text-[13px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-black transition-all active:scale-95 shadow-2xl shadow-slate-300 mt-2 leading-none border-b-4 border-slate-800">
              View Marketplace Preview <ArrowUpRight size={18} className="text-blue-400" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
