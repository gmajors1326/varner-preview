import React, { useState, useEffect } from 'react';
import { Upload, FileText, ArrowRight, CheckCircle, RefreshCw, Settings, Download, AlertCircle, HelpCircle } from 'lucide-react';
import { apiFetch } from '../../utils/api';

// Simple CSV Parser that handles double quotes and commas in cells
function parseCSV(text) {
  const lines = [];
  let row = [""];
  let inQuotes = false;
  
  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    const next = text[i+1];
    
    if (c === '"') {
      if (inQuotes && next === '"') {
        row[row.length - 1] += '"';
        i++;
      } else {
        inQuotes = !inQuotes;
      }
    } else if (c === ',') {
      if (inQuotes) {
        row[row.length - 1] += c;
      } else {
        row.push("");
      }
    } else if (c === '\r' || c === '\n') {
      if (inQuotes) {
        row[row.length - 1] += c;
      } else {
        if (c === '\r' && next === '\n') {
          i++;
        }
        lines.push(row);
        row = [""];
      }
    } else {
      row[row.length - 1] += c;
    }
  }
  if (row.length > 1 || row[0] !== "") {
    lines.push(row);
  }
  return lines.filter(r => r.some(cell => cell.trim() !== ""));
}

// Convert Row array back to CSV string
function stringifyCSVRow(cells) {
  return cells.map(cell => {
    const clean = String(cell ?? "").replace(/"/g, '""');
    if (clean.includes(',') || clean.includes('"') || clean.includes('\n') || clean.includes('\r')) {
      return `"${clean}"`;
    }
    return clean;
  }).join(',');
}

export const MigrationTab = ({ showToast }) => {
  const [step, setStep] = useState(1);
  const [fileName, setFileName] = useState('');
  const [csvHeaders, setCsvHeaders] = useState([]);
  const [csvRows, setCsvRows] = useState([]);
  
  // Available Varner OS Categories
  const [varnerCategories, setVarnerCategories] = useState([
    'Compact Tractors', 'Utility Tractors', 'Tractors', 'Commercial Trailers', 
    'Dump Trailers', 'Flatbed Trailers', 'Utility Trailers', 'Horse Trailers', 
    'Livestock Trailers', 'Trailers', 'Utility Vehicles', 'Golf Carts', 
    'Implements', 'Attachments', 'Loaders', 'Hay Equipment', 'Balers', 
    'Rakes', 'Tedders', 'Snow Removal', 'Misc', 'Other'
  ]);
  
  // Field Mappings (Varner Field -> CSV Header Index)
  const [mappings, setMappings] = useState({
    title: '',
    year: '',
    make: '',
    model: '',
    price: '',
    stock_number: '',
    vin: '',
    condition: '',
    category: '',
    subcategory: '',
    description: '',
    images: ''
  });
  
  // Fallbacks & Defaults
  const [defaults, setDefaults] = useState({
    defaultYear: new Date().getFullYear().toString(),
    defaultMake: 'Unlisted',
    defaultModel: 'Equipment',
    defaultCondition: 'Used'
  });
  
  // Unique Categories detected in legacy CSV
  const [legacyCategories, setLegacyCategories] = useState([]);
  // Mappings of legacy Category -> Varner Category
  const [categoryMappings, setCategoryMappings] = useState({});
  const [isProcessing, setIsProcessing] = useState(false);

  // Load backend categories
  useEffect(() => {
    apiFetch('/categories')
      .then(cats => {
        if (Array.isArray(cats) && cats.length > 0) {
          // Merge unique categories
          setVarnerCategories(prev => Array.from(new Set([...cats, ...prev])).sort());
        }
      })
      .catch(() => {});
  }, []);

  // Handle file upload
  const handleFileUpload = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    
    setFileName(file.name);
    const reader = new FileReader();
    reader.onload = (event) => {
      try {
        const text = event.target.result;
        const parsed = parseCSV(text);
        
        if (parsed.length < 2) {
          showToast('CSV must contain a header row and at least one data row', 'error');
          return;
        }
        
        const headers = parsed[0].map(h => h.trim());
        const rows = parsed.slice(1);
        
        setCsvHeaders(headers);
        setCsvRows(rows);
        
        // Auto-guess column mappings based on common column names
        const newMappings = { ...mappings };
        headers.forEach((h, index) => {
          const lower = h.toLowerCase();
          const idxStr = String(index);
          
          if (lower.includes('title') || lower === 'name') newMappings.title = idxStr;
          else if (lower.includes('year')) newMappings.year = idxStr;
          else if (lower === 'make' || lower.includes('manufacturer') || lower.includes('brand')) newMappings.make = idxStr;
          else if (lower.includes('model')) newMappings.model = idxStr;
          else if (lower.includes('price') || lower === 'retail') newMappings.price = idxStr;
          else if (lower.includes('stock') || lower.includes('sku') || lower === 'controlnumber') newMappings.stock_number = idxStr;
          else if (lower.includes('vin') || lower.includes('serial')) newMappings.vin = idxStr;
          else if (lower.includes('condition')) newMappings.condition = idxStr;
          else if (lower.includes('category') && !lower.includes('sub')) newMappings.category = idxStr;
          else if (lower.includes('subcategory')) newMappings.subcategory = idxStr;
          else if (lower.includes('desc')) newMappings.description = idxStr;
          else if (lower.includes('image') || lower.includes('photo') || lower.includes('picture') || lower.includes('url')) newMappings.images = idxStr;
        });
        
        setMappings(newMappings);
        showToast('CSV loaded successfully! Proposing column mappings...');
        setStep(2);
      } catch (err) {
        showToast('Failed to parse CSV file: ' + err.message, 'error');
      }
    };
    reader.readAsText(file);
  };

  // Extract unique categories from CSV when category mapping is selected
  const prepareCategoryMappingStep = () => {
    const categoryColIdx = parseInt(mappings.category);
    if (isNaN(categoryColIdx)) {
      // No category column mapped, skip category translation step
      setLegacyCategories([]);
      setStep(4);
      return;
    }
    
    // Find all unique values in mapped Category column
    const uniqueCats = new Set();
    csvRows.forEach(row => {
      const val = row[categoryColIdx]?.trim();
      if (val) uniqueCats.add(val);
    });
    
    const sortedCats = Array.from(uniqueCats).sort();
    setLegacyCategories(sortedCats);
    
    // Initialize mappings with default category guesses
    const initialMappings = {};
    sortedCats.forEach(lc => {
      // Try to guess match
      const matched = varnerCategories.find(vc => 
        lc.toLowerCase().includes(vc.toLowerCase()) || 
        vc.toLowerCase().includes(lc.toLowerCase())
      );
      initialMappings[lc] = matched || 'Other';
    });
    
    setCategoryMappings(initialMappings);
    setStep(3);
  };

  const handleMappingChange = (field, colIndex) => {
    setMappings(prev => ({ ...prev, [field]: colIndex }));
  };

  const handleCategoryMapChange = (legacyCat, varnerCat) => {
    setCategoryMappings(prev => ({ ...prev, [legacyCat]: varnerCat }));
  };

  // Run the conversion and trigger file download
  const handleProcessAndDownload = () => {
    setIsProcessing(true);
    setTimeout(() => {
      try {
        const finalHeaders = [
          'title', 'year', 'make', 'model', 'price', 'stock_number', 'vin', 
          'condition', 'category', 'subcategory', 'description', 'images'
        ];
        
        const outputRows = [finalHeaders];
        
        csvRows.forEach((row, rowIndex) => {
          // Helper to get value from column index
          const getVal = (field) => {
            const idx = parseInt(mappings[field]);
            return isNaN(idx) ? '' : (row[idx] ?? '').trim();
          };
          
          let year = getVal('year');
          let make = getVal('make');
          let model = getVal('model');
          let title = getVal('title');
          let priceStr = getVal('price');
          let stockNumber = getVal('stock_number');
          let vin = getVal('vin');
          let conditionStr = getVal('condition');
          let categoryStr = getVal('category');
          let subcategory = getVal('subcategory');
          let description = getVal('description');
          let imagesStr = getVal('images');
          
          // Apply Fallbacks
          if (!year) year = defaults.defaultYear;
          if (!make || make.toLowerCase() === 'other') make = defaults.defaultMake;
          if (!model) model = defaults.defaultModel;
          
          // Auto-generate title if missing
          if (!title) {
            title = `${year} ${make} ${model}`.trim();
          }
          
          // Clean Price (extract raw numeric values)
          let price = '';
          if (priceStr) {
            price = priceStr.replace(/[^0-9.]/g, '');
          }
          
          // Stock number fallback
          if (!stockNumber) {
            stockNumber = `IMPORT-${rowIndex + 1000}`;
          }
          
          // VIN fallback
          if (!vin) {
            vin = 'UNSPECIFIED';
          }
          
          // Condition Normalization
          let condition = defaults.defaultCondition;
          if (conditionStr) {
            const condLower = conditionStr.toLowerCase();
            if (condLower.includes('new')) condition = 'New';
            else if (condLower.includes('used')) condition = 'Used';
          }
          
          // Category Mapping Translation
          let category = 'Other';
          if (categoryStr) {
            category = categoryMappings[categoryStr] || 'Other';
          }
          
          // Description HTML cleanup (collapsing breaks & spacing)
          if (description) {
            description = description.replace(/<[^>]*>/g, ' '); // Strip HTML
            description = description.replace(/\s+/g, ' ').trim(); // Collapse spaces
          }
          
          // Clean Image URLs (Sandhills separates photos with spaces, semicolons, or commas)
          let images = '';
          if (imagesStr) {
            // Split by space, comma, semicolon, or vertical bar
            const urls = imagesStr.split(/[\s,;|]+/).map(url => url.trim()).filter(url => url.startsWith('http'));
            images = urls.join(',');
          }
          
          outputRows.push([
            title,
            year,
            make,
            model,
            price,
            stockNumber,
            vin,
            condition,
            category,
            subcategory,
            description,
            images
          ]);
        });
        
        // Convert array to CSV string
        const csvContent = outputRows.map(stringifyCSVRow).join('\n');
        
        // Trigger download
        const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `cleaned_import_${fileName || 'inventory'}`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Sanitized CSV generated and downloaded successfully!');
        setStep(4);
      } catch (err) {
        showToast('Processing failed: ' + err.message, 'error');
      } finally {
        setIsProcessing(false);
      }
    }, 1200);
  };

  const handleReset = () => {
    setStep(1);
    setFileName('');
    setCsvHeaders([]);
    setCsvRows([]);
    setLegacyCategories([]);
    setMappings({
      title: '', year: '', make: '', model: '', price: '', stock_number: '',
      vin: '', condition: '', category: '', subcategory: '', description: '', images: ''
    });
  };

  return (
    <div className="space-y-8 animate-in fade-in duration-500 text-slate-950 font-black pb-16 max-w-5xl mx-auto">
      
      {/* Dynamic Header */}
      <div className="bg-gradient-to-br from-red-700 to-slate-900 rounded-[2rem] sm:rounded-[3rem] p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden">
        <div className="relative z-10 max-w-xl">
          <h3 className="text-2xl sm:text-4xl font-black tracking-tighter mb-3 uppercase leading-none text-white">CSV Import Assistant</h3>
          <p className="text-red-200 font-bold uppercase tracking-[0.2em] text-[10px] mb-6">Inventory Data Sanitizer & Parser</p>
          <p className="text-slate-300 font-bold text-xs uppercase tracking-normal leading-relaxed">
            Easily map and clean equipment spreadsheets exported from legacy databases or portals. The assistant corrects missing details and formats data perfectly before importing.
          </p>
        </div>
        <FileText size={120} className="absolute -right-6 -bottom-6 opacity-10 rotate-12 w-[160px] h-[160px]"/>
      </div>

      {/* Steps Progress Indicator */}
      <div className="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-200/60 flex items-center justify-between gap-2 overflow-x-auto">
        {[
          { num: 1, label: 'Upload CSV' },
          { num: 2, label: 'Map Columns' },
          { num: 3, label: 'Translate Categories' },
          { num: 4, label: 'Sanitize & Save' }
        ].map((s) => (
          <div key={s.num} className="flex items-center gap-3 shrink-0">
            <div className={`w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs ${step === s.num ? 'bg-red-600 text-white shadow-lg' : step > s.num ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-400'}`}>
              {step > s.num ? '✓' : s.num}
            </div>
            <span className={`text-[10px] font-black uppercase tracking-wider ${step === s.num ? 'text-slate-900' : 'text-slate-400'}`}>
              {s.label}
            </span>
            {s.num < 4 && <ArrowRight size={14} className="text-slate-300 mx-2" />}
          </div>
        ))}
      </div>

      {/* Main Form Content Cards */}
      <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 shadow-2xl border border-slate-200/60">
        
        {/* STEP 1: UPLOAD */}
        {step === 1 && (
          <div className="space-y-8 text-center py-8">
            <div className="max-w-md mx-auto space-y-4">
              <div className="mx-auto w-16 h-16 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center border border-red-100 shadow-inner">
                <Upload size={24} />
              </div>
              <h4 className="font-black text-lg text-slate-900 uppercase">Select Legacy Inventory CSV</h4>
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wide leading-relaxed">
                Upload the export sheet (from Sandhills, Excel, or another database) to begin cleaning the headers and data.
              </p>
            </div>
            
            <div className="max-w-md mx-auto">
              <label className="border-2 border-dashed border-slate-200 hover:border-red-500 rounded-3xl p-10 flex flex-col items-center justify-center cursor-pointer bg-slate-50 hover:bg-red-50/20 transition-all shadow-sm group">
                <input type="file" accept=".csv" className="hidden" onChange={handleFileUpload} />
                <FileText size={32} className="text-slate-300 group-hover:text-red-500 transition-colors mb-3" />
                <span className="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-slate-800">Choose CSV File</span>
                <span className="text-[9px] text-slate-400 font-bold uppercase mt-1">Maximum size 15MB</span>
              </label>
            </div>

            <div className="max-w-lg mx-auto bg-slate-50 border border-slate-100 p-6 rounded-2xl flex items-start gap-4 text-left">
              <AlertCircle size={20} className="text-red-500 shrink-0 mt-0.5" />
              <div>
                <h5 className="text-[10px] font-black text-slate-800 uppercase tracking-wider mb-1">Import Guidelines & Instructions:</h5>
                <p className="text-[10px] font-bold text-slate-400 uppercase tracking-normal leading-relaxed normal-case">
                  This assistant cleans your CSV file in the browser. <strong>It will not make any edits to your live website inventory yet.</strong> Once downloaded, upload the generated file using the "Import Inventory" link on the sidebar or dashboard.
                </p>
              </div>
            </div>
          </div>
        )}

        {/* STEP 2: FIELD MAPPING */}
        {step === 2 && (
          <div className="space-y-8">
            <div className="border-b border-slate-100 pb-6">
              <h4 className="font-black text-sm uppercase tracking-widest text-slate-900 mb-2">Map CSV Columns to website Fields</h4>
              <p className="text-[10px] font-bold text-slate-400 uppercase leading-relaxed">
                Match each website field on the left with the correct column header detected in your uploaded CSV.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 sm:p-8 rounded-[1.5rem] border border-slate-100">
              {[
                { key: 'title', label: 'Inventory Title', desc: 'Used as the main title header (Year Make Model)', required: true },
                { key: 'year', label: 'Year', desc: '4-digit year (e.g. 2020)', required: true },
                { key: 'make', label: 'Manufacturer / Make', desc: 'Brand name (e.g. Mahindra, Big Tex)', required: true },
                { key: 'model', label: 'Model', desc: 'Equipment model descriptor', required: true },
                { key: 'price', label: 'Price', desc: 'Retail price in USD digits', required: false },
                { key: 'stock_number', label: 'Stock Number', desc: 'Dealer SKU or asset identifier', required: false },
                { key: 'vin', label: 'VIN / Serial Number', desc: 'Factory serial number', required: false },
                { key: 'condition', label: 'Condition', desc: 'Accepts New or Used', required: false },
                { key: 'category', label: 'Category', desc: 'Primary equipment category class', required: true },
                { key: 'subcategory', label: 'Subcategory', desc: 'Detailed category label', required: false },
                { key: 'description', label: 'Description', desc: 'Description and features notes', required: false },
                { key: 'images', label: 'Image URLs', desc: 'Photo URL links (comma/space separated)', required: false },
              ].map((field) => (
                <div key={field.key} className="space-y-2 bg-white p-4 rounded-2xl border border-slate-200/50 shadow-sm flex flex-col justify-between">
                  <div>
                    <label className="text-[10px] font-black text-slate-900 uppercase tracking-wider block">
                      {field.label} {field.required && <span className="text-red-500">*</span>}
                    </label>
                    <span className="text-[9px] text-slate-400 font-bold uppercase tracking-tight block mt-0.5 leading-snug">
                      {field.desc}
                    </span>
                  </div>
                  
                  <select
                    value={mappings[field.key]}
                    onChange={(e) => handleMappingChange(field.key, e.target.value)}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-xs text-slate-800 outline-none focus:border-red-500 focus:bg-white transition-all appearance-none cursor-pointer mt-3"
                  >
                    <option value="">— Skip / No Matching Column —</option>
                    {csvHeaders.map((header, idx) => (
                      <option key={idx} value={String(idx)}>{header}</option>
                    ))}
                  </select>
                </div>
              ))}
            </div>

            {/* Fallback Rules Box */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Sanitizer Fallback Settings</h5>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="space-y-1.5">
                  <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">Default Year</label>
                  <input
                    type="text"
                    value={defaults.defaultYear}
                    onChange={(e) => setDefaults(prev => ({ ...prev, defaultYear: e.target.value }))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-xs text-slate-900 outline-none focus:border-red-500 transition-all"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">Default Make</label>
                  <input
                    type="text"
                    value={defaults.defaultMake}
                    onChange={(e) => setDefaults(prev => ({ ...prev, defaultMake: e.target.value }))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-xs text-slate-900 outline-none focus:border-red-500 transition-all"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">Default Model</label>
                  <input
                    type="text"
                    value={defaults.defaultModel}
                    onChange={(e) => setDefaults(prev => ({ ...prev, defaultModel: e.target.value }))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-xs text-slate-900 outline-none focus:border-red-500 transition-all"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">Default Condition</label>
                  <select
                    value={defaults.defaultCondition}
                    onChange={(e) => setDefaults(prev => ({ ...prev, defaultCondition: e.target.value }))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-xs text-slate-900 outline-none focus:border-red-500 transition-all cursor-pointer"
                  >
                    <option value="New">New</option>
                    <option value="Used">Used</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="flex justify-between items-center pt-8 border-t border-slate-100">
              <button
                onClick={handleReset}
                className="px-6 py-4 rounded-xl border border-slate-200 font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 transition-colors"
              >
                Reset Upload
              </button>
              <button
                onClick={prepareCategoryMappingStep}
                className="bg-red-600 text-white px-8 py-4 rounded-xl font-black text-xs uppercase tracking-[0.15em] hover:bg-red-700 transition-all flex items-center gap-2 active:scale-95 disabled:opacity-50"
              >
                Next: Map Categories <ArrowRight size={14} />
              </button>
            </div>
          </div>
        )}

        {/* STEP 3: CATEGORY TRANSLATION */}
        {step === 3 && (
          <div className="space-y-8">
            <div className="border-b border-slate-100 pb-6 flex items-center justify-between gap-4">
              <div>
                <h4 className="font-black text-sm uppercase tracking-widest text-slate-900 mb-2">Translate CSV Categories</h4>
                <p className="text-[10px] font-bold text-slate-400 uppercase leading-relaxed">
                  Map each unique category detected in the uploaded CSV to one of the active categories on your website.
                </p>
              </div>
              <span className="bg-slate-900 text-white text-[9px] font-black uppercase px-3 py-1.5 rounded-full tracking-widest shadow-sm">
                {legacyCategories.length} Categories Detected
              </span>
            </div>

            <div className="space-y-4 max-h-[420px] overflow-y-auto pr-2 no-scrollbar bg-slate-50 p-6 rounded-[1.5rem] border border-slate-100">
              {legacyCategories.map((lc) => (
                <div key={lc} className="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 bg-white rounded-xl border border-slate-200/50 shadow-sm gap-3 group hover:border-red-200 transition-colors">
                  <div className="flex items-center gap-3">
                    <div className="w-2.5 h-2.5 rounded-full bg-red-600 group-hover:animate-pulse"></div>
                    <span className="text-xs font-black text-slate-900 truncate max-w-sm uppercase">{lc}</span>
                  </div>
                  
                  <div className="flex items-center gap-3 w-full sm:w-auto shrink-0">
                    <ArrowRight size={12} className="text-slate-400 hidden sm:block" />
                    <select
                      value={categoryMappings[lc] || 'Other'}
                      onChange={(e) => handleCategoryMapChange(lc, e.target.value)}
                      className="w-full sm:w-[220px] bg-slate-50 border border-slate-200 rounded-lg p-2.5 font-bold text-xs text-slate-800 outline-none focus:border-red-500 focus:bg-white transition-all cursor-pointer"
                    >
                      {varnerCategories.map(vc => (
                        <option key={vc} value={vc}>{vc}</option>
                      ))}
                    </select>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex justify-between items-center pt-8 border-t border-slate-100">
              <button
                onClick={() => setStep(2)}
                className="px-6 py-4 rounded-xl border border-slate-200 font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 transition-colors"
              >
                Back To Mappings
              </button>
              <button
                onClick={handleProcessAndDownload}
                disabled={isProcessing}
                className="bg-red-600 text-white px-8 py-4 rounded-xl font-black text-xs uppercase tracking-[0.15em] hover:bg-red-700 transition-all flex items-center gap-2 active:scale-95 disabled:opacity-50 border-b-2 border-red-800"
              >
                {isProcessing ? <RefreshCw size={14} className="animate-spin" /> : <Settings size={14} />}
                {isProcessing ? 'Processing...' : 'Run Sanitizer & Download'}
              </button>
            </div>
          </div>
        )}

        {/* STEP 4: DOWNLOAD & COMPLETE */}
        {step === 4 && (
          <div className="space-y-8 text-center py-8">
            <div className="max-w-md mx-auto space-y-4">
              <div className="mx-auto w-16 h-16 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center border border-green-150 shadow-inner">
                <CheckCircle size={24} />
              </div>
              <h4 className="font-black text-lg text-slate-900 uppercase">Sanitization Complete!</h4>
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wide leading-relaxed">
                Your legacy CSV has been repaired and download has completed. Below are the next steps to import the data into your live inventory.
              </p>
            </div>

            <div className="max-w-xl mx-auto bg-slate-50 border border-slate-100 rounded-3xl p-6 sm:p-8 text-left space-y-6">
              <h5 className="text-[10px] font-black text-slate-800 uppercase tracking-wider mb-2 border-b border-slate-200/60 pb-3 flex items-center gap-2"><Settings size={14} className="text-red-600"/> Next Steps to Import:</h5>
              
              <div className="space-y-4 text-xs font-bold text-slate-500 uppercase tracking-tight leading-relaxed normal-case">
                <div className="flex gap-4">
                  <div className="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center font-black text-[11px] shrink-0 mt-0.5 shadow">1</div>
                  <p className="text-[11px] text-slate-600 font-bold">
                    Locate the downloaded file on your computer (typically named <code>cleaned_import_[original_name].csv</code>).
                  </p>
                </div>
                
                <div className="flex gap-4">
                  <div className="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center font-black text-[11px] shrink-0 mt-0.5 shadow">2</div>
                  <p className="text-[11px] text-slate-600 font-bold">
                    Click the <strong>Import Inventory</strong> button on the dashboard or left sidebar to open **WP All Import Pro**.
                  </p>
                </div>
                
                <div className="flex gap-4">
                  <div className="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center font-black text-[11px] shrink-0 mt-0.5 shadow">3</div>
                  <p className="text-[11px] text-slate-600 font-bold">
                    Select "Upload a file" and choose your cleaned CSV. Create/Update listings under custom post type <strong>"Equipment"</strong>.
                  </p>
                </div>

                <div className="flex gap-4">
                  <div className="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center font-black text-[11px] shrink-0 mt-0.5 shadow">4</div>
                  <p className="text-[11px] text-slate-600 font-bold">
                    Map the columns in Step 3 of WP All Import directly to the ACF field groups (they are now standard 1-to-1 mappings with no programming needed).
                  </p>
                </div>
              </div>
            </div>

            <div className="flex justify-center gap-4 pt-6">
              <a
                href={`${window.varnerData?.site_url || '/'}wp-admin/admin.php?page=pmxi-admin-import`}
                className="bg-red-600 text-white px-8 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-red-700 transition-all shadow-md active:scale-95 border-b-2 border-red-800"
              >
                Go to WP All Import
              </a>
              <button
                onClick={handleReset}
                className="bg-slate-900 text-white px-8 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black transition-all shadow-md active:scale-95"
              >
                Clean Another CSV
              </button>
            </div>
          </div>
        )}

      </div>
    </div>
  );
};
