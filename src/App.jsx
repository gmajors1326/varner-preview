import React, { useState } from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { 
  LayoutDashboard, 
  Box, 
  Truck, 
  Facebook, 
  Save, 
  Copy, 
  CheckCircle2, 
  AlertCircle, 
  ChevronRight,
  Plus,
  Settings,
  Zap,
  Menu,
  Image as ImageIcon,
  Smartphone,
  Eye,
  ArrowUpRight,
  BarChart3,
  Users,
  Wrench,
  Clock,
  ShieldCheck,
  Camera,
  Loader2,
  ScanText,
  List,
  Search,
  Edit2,
  X,
  TrendingUp,
  Activity,
  DollarSign,
  History,
  Sparkles,
  Info,
  Trash2,
  RotateCcw
} from 'lucide-react';

const App = () => {
  const [syncEnabled, setSyncEnabled] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showFBPreview, setShowFBPreview] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [deletedHistory, setDeletedHistory] = useState([]);

  const defaultEmptyUnit = {
    title: "", year: "", make: "", model: "", stockNumber: "", condition: "New", price: "", vin: "",
    stockStatus: "Draft", category: "Compact Tractors", color: "", meter: "", meterType: "Hours",
    intakeDate: "", description: "", images: [], attachments: [],
    sellerInfo: "<p>Call or stop by to see it in person</p><p>Varner Equipment</p><p>1375 Hwy 50</p><p>Delta, CO 81416</p><p>(970) 874-0612</p>"
  };

  const [unitData, setUnitData] = useState({
    title: "Mahindra 2638 HST w/ Loader",
    year: "2024",
    make: "Mahindra",
    model: "2638 HST",
    stockNumber: "77492",
    condition: "New",
    price: "28950",
    vin: "M2638-X99284-CO",
    stockStatus: "In Stock",
    category: "Compact Tractors",
    color: "Red",
    meter: "2.5",
    meterType: "Hours",
    intakeDate: "2026-03-15",
    description: "2024 Mahindra 2638 HST equipped with front end loader and industrial tires. Excellent condition, ready for the yard.",
    sellerInfo: "<p>Call or stop by to see it in person</p><p>Varner Equipment</p><p>1375 Hwy 50</p><p>Delta, CO 81416</p><p>(970) 874-0612</p>",
    images: [
      '/left.jpg',
      '/mahindra.jpg',
      '/rear.jpg'
    ],
    attachments: [
      { image: '/imp1.jpg', title: 'Front End Loader', price: '4500', description: 'Heavy duty loader with quick attach bucket.' },
      { image: '/imp2.jpg', title: 'Backhoe Attachment', price: '7200', description: '9-foot digging depth, subframe mounted.' },
      { image: '/imp3.jpg', title: 'Mid-Mount Mower', price: '2800', description: '60-inch side discharge mower deck.' }
    ]
  });

  const [inventoryList, setInventoryList] = useState([
    { 
      id: '1', stock: '77492', year: '2024', make: 'Mahindra', model: '2638 HST', category: 'Compact Tractors', condition: 'New', price: '28950', status: 'In Stock', image: '/mahindra.jpg',
      attachments: [
        { image: '/imp1.jpg', title: 'Front End Loader', price: '4500', description: 'Heavy duty loader with quick attach bucket.' },
        { image: '/imp2.jpg', title: 'Backhoe Attachment', price: '7200', description: '9-foot digging depth, subframe mounted.' }
      ]
    },
    { id: '2', stock: '77493', year: '2024', make: 'Big Tex', model: '14LP 14ft Dump', category: 'Commercial Trailers', condition: 'New', price: '12500', status: 'In Stock', image: '/rear.jpg', attachments: [] },
    { id: '3', stock: '77420', year: '2019', make: 'Deutz-Fahr', model: 'Agrotron 6130', category: 'Compact Tractors', condition: 'Used', price: '45000', status: 'Pending Sale', image: '/left.jpg', attachments: [] },
    { id: '4', stock: '77415', year: '2021', make: 'Mahindra', model: '1626 Shuttle', category: 'Compact Tractors', condition: 'Used', price: '16500', status: 'In Stock', image: '/mahindra.jpg', attachments: [] },
    { id: '5', stock: '77300', year: '2023', make: 'Mahindra', model: 'eMax 20S', category: 'Compact Tractors', condition: 'Used', price: '14000', status: 'Sold', image: '/left.jpg', attachments: [] },
    { id: '6', stock: 'IMP-2201', year: '', make: 'Implement', model: 'Woods BB72.30 Brush Bull', category: 'Implements', condition: 'New', price: '4200', status: 'In Stock', image: '/imp1.jpg', attachments: [] },
    { id: '7', stock: 'IMP-2202', year: '', make: 'Implement', model: 'Land Pride RTA1258 Tiller', category: 'Implements', condition: 'New', price: '2950', status: 'In Stock', image: '/imp2.jpg', attachments: [] },
  ]);

  const usersList = [
    { name: 'Ashley Varner', role: 'Administrator', status: 'Online', lastActive: 'Currently Active', device: 'Desktop' },
    { name: 'Employee 8402', role: 'Editor', status: 'Online', lastActive: '5m ago', device: 'iPad Pro' },
    { name: 'Marcus (Sales)', role: 'Sales Manager', status: 'Offline', lastActive: '4:30 PM', device: 'iPhone' },
    { name: 'Yard Staff (Temp)', role: 'Viewer', status: 'Inactive', lastActive: '2d ago', device: 'Android Tablet' }
  ];

  const handleInputChange = (field, value) => {
    setUnitData(prev => ({ ...prev, [field]: value }));
  };

  const handleAddImplement = () => {
    setUnitData(prev => ({
      ...prev,
      attachments: [...(prev.attachments || []), { image: '/imp1.jpg', title: '', price: '', description: '' }]
    }));
  };

  const handleUpdateImplement = (index, field, value) => {
    setUnitData(prev => {
      const newAttachments = [...prev.attachments];
      newAttachments[index] = { ...newAttachments[index], [field]: value };
      return { ...prev, attachments: newAttachments };
    });
  };

  const handleRemoveImplement = (index) => {
    const removedItem = unitData.attachments[index];
    setUnitData(prev => ({
      ...prev,
      attachments: prev.attachments.filter((_, i) => i !== index)
    }));
    
    // Add removed implement to inventory list
    if (removedItem.title) {
        setInventoryList(prev => [{
          id: Math.random().toString(36).substr(2, 9),
          stock: "IMP-" + Math.floor(1000 + Math.random() * 9000),
          year: "",
          make: "Implement",
          model: removedItem.title,
          category: "Implements",
          condition: "Used",
          price: removedItem.price || "0",
          status: "In Stock",
          image: removedItem.image || '/imp1.jpg'
        }, ...prev]);
    }
  };

  const handleSave = () => {
    setIsSaving(true);
    setTimeout(() => {
      setInventoryList(prev => {
        const existingIndex = prev.findIndex(item => item.stock === unitData.stockNumber);
        const updatedItem = {
          id: unitData.id || Math.random().toString(36).substr(2, 9),
          stock: unitData.stockNumber,
          year: unitData.year,
          make: unitData.make,
          model: unitData.model,
          category: unitData.category,
          condition: unitData.condition,
          price: unitData.price,
          status: unitData.stockStatus,
          image: unitData.images?.[0] || '/mahindra.jpg',
          attachments: unitData.attachments || []
        };

        if (existingIndex > -1) {
          const newList = [...prev];
          newList[existingIndex] = updatedItem;
          return newList;
        } else {
          return [updatedItem, ...prev];
        }
      });
      setIsSaving(false);
      setActiveTab('all-inventory');
    }, 1500);
  };

  const handleDeleteUnit = (stockNumber) => {
    if (window.confirm(`Are you sure you want to delete unit #${stockNumber}?`)) {
      const unitToDelete = inventoryList.find(item => item.stock === stockNumber);
      if (unitToDelete) {
        setDeletedHistory(prev => [{ ...unitToDelete, deletedAt: new Date().toISOString() }, ...prev]);
        setInventoryList(prev => prev.filter(item => item.stock !== stockNumber));
        if (unitData.stockNumber === stockNumber) {
          setUnitData(defaultEmptyUnit);
          setActiveTab('all-inventory');
        }
      }
    }
  };

  const handleRestoreUnit = (stockNumber) => {
    const unitToRestore = deletedHistory.find(item => item.stock === stockNumber);
    if (unitToRestore) {
      const { deletedAt, ...restoredUnit } = unitToRestore;
      setInventoryList(prev => [restoredUnit, ...prev]);
      setDeletedHistory(prev => prev.filter(item => item.stock !== stockNumber));
    }
  };

  const handlePermanentDelete = (stockNumber) => {
    if (window.confirm(`Permanently delete unit #${stockNumber}? This cannot be undone.`)) {
      setDeletedHistory(prev => prev.filter(item => item.stock !== stockNumber));
    }
  };

  const handleAddNewUnit = () => {
    setUnitData(defaultEmptyUnit);
    setActiveTab('inventory');
  };

  const handleEditUnit = (item) => {
    setUnitData({
      ...defaultEmptyUnit,
      id: item.id,
      title: `${item.year} ${item.make} ${item.model}`,
      year: item.year, make: item.make, model: item.model, stockNumber: item.stock,
      condition: item.condition, price: item.price, vin: `VIN-${item.stock}-XX`, stockStatus: item.status,
      category: item.category || "Compact Tractors",
      description: item.description || `${item.year} ${item.make} ${item.model}. Ready for immediate delivery.`,
      images: item.images || [item.image || '/mahindra.jpg', '/left.jpg', '/rear.jpg'],
      attachments: item.attachments || []
    });
    setActiveTab('inventory');
  };

  const handleClone = () => {
    setIsSaving(true);
    setTimeout(() => {
        setUnitData(prev => ({
            ...prev,
            title: prev.title + " (COPY)",
            vin: "",
            stockNumber: "CL-AUTO"
        }));
        setIsSaving(false);
    }, 800);
  };


  const getHeaderTitle = () => {
    switch(activeTab) {
      case 'dashboard': return "Operations Overview";
      case 'all-inventory': return "Master Stock Ledger";
      case 'inventory': return <span className="flex items-center gap-2">{unitData.title || "Inventory Editor"} <span className="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded uppercase tracking-tighter font-black">SKU: {unitData.stockNumber || 'PENDING'}</span></span>;
      case 'marketplace': return "Meta Commerce Sync";
      case 'history': return "Deletion History / Recycle Bin";
      case 'mobile': return "Mobile Companion Access";
      case 'settings': return "System Configuration";
      default: return "Varner OS";
    }
  };

  return (
    <div className="flex min-h-screen bg-[#f8fafc] font-sans text-slate-900 overflow-hidden selection:bg-red-100">
      {/* MOBILE SIDEBAR OVERLAY */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" onClick={() => setIsMobileMenuOpen(false)}></div>
          <aside className="fixed inset-y-0 left-0 w-72 bg-slate-950 text-white p-6 shadow-2xl flex flex-col animate-in slide-in-from-left duration-300">
            <div className="flex items-center justify-between mb-8 border-b border-slate-800 pb-6">
              <div className="flex items-center gap-3">
                <div className="bg-red-600 p-2 rounded-xl shadow-lg border border-red-500/30 text-white">
                  <Box size={22} />
                </div>
                <div>
                  <span className="font-black text-xl tracking-tighter block leading-none">VARNER</span>
                  <span className="text-red-500 text-[9px] font-black uppercase tracking-[0.3em] mt-0.5 block">Equipment</span>
                </div>
              </div>
              <button onClick={() => setIsMobileMenuOpen(false)} className="text-slate-400 hover:text-white p-2">
                <X size={24} />
              </button>
            </div>

            <nav className="space-y-2 flex-1">
              <NavItem icon={<LayoutDashboard size={20}/>} label="Dashboard" active={activeTab === 'dashboard'} onClick={() => { setActiveTab('dashboard'); setIsMobileMenuOpen(false); }} />
              <NavItem icon={<List size={20}/>} label="Inventory List" active={activeTab === 'all-inventory'} onClick={() => { setActiveTab('all-inventory'); setIsMobileMenuOpen(false); }} badge={inventoryList.length} />
              <NavItem icon={<Box size={20}/>} label="Add / Edit" active={activeTab === 'inventory'} onClick={() => { setActiveTab('inventory'); setIsMobileMenuOpen(false); }} />
              <NavItem icon={<Facebook size={20}/>} label="Meta Sync" active={activeTab === 'marketplace'} onClick={() => { setActiveTab('marketplace'); setIsMobileMenuOpen(false); }} badge="Live" />
              <NavItem icon={<History size={20}/>} label="History" active={activeTab === 'history'} onClick={() => { setActiveTab('history'); setIsMobileMenuOpen(false); }} badge={deletedHistory.length > 0 ? deletedHistory.length : null} />
              <NavItem icon={<Smartphone size={20}/>} label="Mobile App" active={activeTab === 'mobile'} onClick={() => { setActiveTab('mobile'); setIsMobileMenuOpen(false); }} />
            </nav>

            <div className="mt-auto pt-4 border-t border-slate-800">
              <NavItem icon={<Settings size={18}/>} label="Configuration" active={activeTab === 'settings'} onClick={() => { setActiveTab('settings'); setIsMobileMenuOpen(false); }} />
            </div>
          </aside>
        </div>
      )}

      {/* SIDEBAR - RESTORED TO PERFECT SIZING */}
      <aside className="hidden lg:flex flex-col w-72 bg-slate-950 text-white p-6 shadow-2xl border-r border-slate-800 shrink-0">
        <div className="flex items-center gap-3 mb-8 border-b border-slate-800 pb-6">
          <div className="bg-red-600 p-2 rounded-xl shadow-lg border border-red-500/30 text-white">
            <Box size={22} />
          </div>
          <div>
            <span className="font-black text-xl tracking-tighter block leading-none">VARNER</span>
            <span className="text-red-500 text-[9px] font-black uppercase tracking-[0.3em] mt-0.5 block">Equipment</span>
          </div>
        </div>

        <nav className="space-y-2 flex-1">
          <NavItem icon={<LayoutDashboard size={20}/>} label="Dashboard" active={activeTab === 'dashboard'} onClick={() => setActiveTab('dashboard')} />
          <NavItem icon={<List size={20}/>} label="Inventory List" active={activeTab === 'all-inventory'} onClick={() => setActiveTab('all-inventory')} badge={inventoryList.length} />
          <NavItem icon={<Box size={20}/>} label="Add / Edit" active={activeTab === 'inventory'} onClick={() => setActiveTab('inventory')} />
          <NavItem icon={<Facebook size={20}/>} label="Meta Sync" active={activeTab === 'marketplace'} onClick={() => setActiveTab('marketplace')} badge="Live" />
          <NavItem icon={<History size={20}/>} label="History" active={activeTab === 'history'} onClick={() => setActiveTab('history')} badge={deletedHistory.length > 0 ? deletedHistory.length : null} />
          <NavItem icon={<Smartphone size={20}/>} label="Mobile App" active={activeTab === 'mobile'} onClick={() => setActiveTab('mobile')} />
        </nav>

        <div className="mt-auto pt-4 border-t border-slate-800">
          <NavItem icon={<Settings size={18}/>} label="Configuration" active={activeTab === 'settings'} onClick={() => setActiveTab('settings')} />
        </div>
      </aside>

      {/* Main Content Area */}
      <main className="flex-1 flex flex-col h-screen overflow-hidden text-slate-900">
        <header className="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between shadow-sm z-10">
          <div className="flex items-center gap-4">
            <button 
              onClick={() => setIsMobileMenuOpen(true)}
              className="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
            >
              <Menu size={24} />
            </button>
            <div className="flex flex-col">
              <h2 className="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">System Modules</h2>
              <h3 className="text-xl font-black text-slate-950 tracking-tight leading-none uppercase">{getHeaderTitle()}</h3>
            </div>
          </div>
          <div className="flex items-center gap-2 sm:gap-3">
            {activeTab === 'inventory' && unitData.title && (
              <button 
                onClick={handleClone}
                className="bg-slate-100 text-slate-600 p-3 sm:px-5 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 shadow-sm active:scale-95"
              >
                <Copy size={16} /> <span className="hidden sm:inline">Clone Unit</span>
              </button>
            )}

            {(activeTab === 'inventory' || activeTab === 'all-inventory') && (
              <button 
                onClick={activeTab === 'inventory' ? handleSave : handleAddNewUnit}
                className="bg-red-600 text-white p-3 sm:px-7 sm:py-3 rounded-xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 active:scale-95 transition-all border-b-2 border-red-800"
              >
                {isSaving ? <Zap className="animate-spin" size={16}/> : (activeTab === 'inventory' ? <Save size={16}/> : <Plus size={16}/>)}
                <span className="hidden sm:inline">{isSaving ? 'SYNCING...' : (activeTab === 'inventory' ? 'PUBLISH TO WEB' : 'NEW UNIT')}</span>
                {!isSaving && <span className="sm:hidden">{activeTab === 'inventory' ? 'PUBLISH' : 'NEW'}</span>}
              </button>
            )}
          </div>
        </header>

        <div className="flex-1 overflow-y-auto p-8 bg-slate-50/50 no-scrollbar">
          <div className="max-w-7xl mx-auto pb-10">
            
            {/* --- DASHBOARD TAB --- */}
            {activeTab === 'dashboard' && (
              <div className="space-y-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="grid grid-cols-1 gap-6">
                  <MetricCard icon={<Box size={24}/>} label="Live Units" value="142" subtext="+12 this week" color="blue" />
                </div>

                <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
                  <div className="xl:col-span-2">
                    <PerformanceChart />
                  </div>
                  <div className="space-y-8">
                    <QuickActions 
                      onAdd={() => handleAddNewUnit()} 
                    />
                    <RecentActivity />
                  </div>
                </div>
              </div>
            )}

            {/* --- MASTER INVENTORY TAB --- */}
            {activeTab === 'all-inventory' && (
              <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                  <div className="relative w-full max-w-md text-slate-900">
                    <Search size={20} className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input type="text" placeholder="Search Master Stock Ledger..." className="w-full pl-12 pr-6 py-3.5 bg-white border-2 border-slate-100 rounded-xl focus:border-red-500 outline-none font-bold text-sm shadow-sm" />
                  </div>
                </div>
                <div className="overflow-x-auto p-2 no-scrollbar">
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
                        <th className="px-6 py-5 text-right w-32">ACTIONS</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                      {inventoryList.map((item) => (
                        <tr key={item.id} className="hover:bg-slate-50 transition-all cursor-pointer group" onClick={() => handleEditUnit(item)}>
                          <td className="px-6 py-5 font-mono font-bold text-sm text-slate-500">{item.stock}</td>
                          <td className="px-6 py-5">
                             <div className="w-16 h-12 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 shadow-sm">
                               <img 
                                 src={item.image} 
                                 className="w-full h-full object-cover" 
                                 onError={(e) => {
                                   e.target.onerror = null;
                                   e.target.src = "https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=100";
                                 }}
                               />
                             </div>
                          </td>
                          <td className="px-6 py-5 text-slate-900">
                            <p className="font-black text-base leading-tight uppercase tracking-tight">{item.year} {item.make}</p>
                            <p className="text-[10px] font-black uppercase tracking-widest mt-1 opacity-60">{item.model}</p>
                          </td>
                          <td className="px-6 py-5">
                             <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">{item.category}</span>
                          </td>
                          <td className="px-6 py-5 text-center">
                             <span className="text-[9px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 px-3 py-1 rounded-lg border border-blue-100 shadow-sm">{item.condition}</span>
                          </td>
                          <td className="px-6 py-5 font-black text-base text-slate-900 tracking-tighter">${parseInt(item.price).toLocaleString()}</td>
                          <td className="px-6 py-5">
                             <span className={`inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border ${
                               item.status === 'In Stock' ? 'text-green-500 bg-green-50 border-green-100' :
                               item.status === 'Pending Sale' ? 'text-amber-500 bg-amber-50 border-amber-100' :
                               'text-slate-400 bg-slate-50 border-slate-200'
                             }`}>
                               <div className={`w-1.5 h-1.5 rounded-full shadow-sm ${
                                 item.status === 'In Stock' ? 'bg-green-500 animate-pulse' :
                                 item.status === 'Pending Sale' ? 'bg-amber-500 animate-pulse' :
                                 'bg-slate-400'
                               }`}></div>
                               {item.status}
                             </span>
                          </td>
                          <td className="px-6 py-5 text-right">
                             <div className="flex items-center justify-end gap-2" onClick={(e) => e.stopPropagation()}>
                               <button 
                                 onClick={() => handleEditUnit(item)}
                                 className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95"
                                 title="Edit Unit"
                               >
                                 <Edit2 size={16} />
                               </button>
                               <button 
                                 onClick={() => {
                                   handleEditUnit(item);
                                   setTimeout(handleClone, 100);
                                 }}
                                 className="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-all active:scale-95"
                                 title="Clone Unit"
                               >
                                 <Copy size={16} />
                               </button>
                               <button 
                                 onClick={() => handleDeleteUnit(item.stock)}
                                 className="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all active:scale-95"
                                 title="Delete Unit"
                               >
                                 <X size={16} />
                               </button>
                             </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* --- UNIT EDITOR TAB --- */}
            {activeTab === 'inventory' && (
              <div className="grid grid-cols-1 xl:grid-cols-3 gap-8 animate-in fade-in slide-in-from-bottom-6 duration-500">
                <div className="xl:col-span-2 space-y-8">
                  
                  <div className="bg-white rounded-[2rem] p-8 shadow-xl border border-slate-200/60 relative overflow-hidden text-slate-900">
                      <div className="flex justify-between items-center mb-8">
                        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2 leading-none font-black">
                          <Box size={14} className="text-red-600" /> Equipment Identity
                        </h3>
                      </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="md:col-span-2">
                        <InputField label="Public Inventory Title" value={unitData.title} onChange={(val) => handleInputChange('title', val)} />
                      </div>
                      <div className="flex gap-3 md:col-span-2 text-slate-900">
                        <div className="flex-1"><InputField label="Year" value={unitData.year} onChange={(val) => handleInputChange('year', val)} /></div>
                        <div className="flex-1"><InputField label="Make" value={unitData.make} onChange={(val) => handleInputChange('make', val)} /></div>
                        <div className="flex-1"><InputField label="Model" value={unitData.model} onChange={(val) => handleInputChange('model', val)} /></div>
                      </div>
                      
                      <div className="md:col-span-2 border-y border-slate-50 py-6 my-2">
                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 block flex justify-between items-center">
                          <span>VIN / SERIAL NUMBER</span>
                        </label>
                        <div className="flex flex-col sm:flex-row gap-4 sm:items-end max-w-2xl">
                          <div className="flex-1">
                            <input 
                              type="text" 
                              value={unitData.vin}
                              onChange={(e) => handleInputChange('vin', e.target.value)}
                              className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4.5 font-mono font-black text-lg text-slate-900 outline-none shadow-inner focus:border-red-500 focus:bg-white transition-all tracking-widest uppercase"
                              placeholder="SCAN OR TYPE SERIAL..."
                            />
                          </div>
                        </div>
                      </div>

                      {/* RESTORED FORMATTED PRICE FIELD */}
                      <div className="space-y-3">
                        <label className="text-[10px] font-black text-green-600 uppercase tracking-widest block pl-1 font-black">Retail Price (USD)</label>
                        <div className="relative">
                          <span className="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-black text-lg">$</span>
                          <input 
                            type="text" 
                            value={unitData.price ? Number(unitData.price).toLocaleString() : ''} 
                            onChange={(e) => {
                              const numericVal = e.target.value.replace(/[^0-9]/g, '');
                              handleInputChange('price', numericVal);
                            }}
                            className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 pl-8 font-black text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm text-lg leading-none" 
                          />
                        </div>
                      </div>

                       <SelectField label="Equipment Category" options={['Compact Tractors', 'Commercial Trailers', 'Utility Vehicles', 'Implements']} value={unitData.category} onChange={(val) => handleInputChange('category', val)} />
                       
                       <div className="flex gap-3 text-slate-900">
                         <div className="flex-1">
                           <SelectField label="Stock Status" options={['In Stock', 'Pending Sale', 'Sold', 'Draft']} value={unitData.stockStatus} onChange={(val) => handleInputChange('stockStatus', val)} />
                         </div>
                         <div className="flex-1">
                           <SelectField label="Condition" options={['New', 'Used']} value={unitData.condition} onChange={(val) => handleInputChange('condition', val)} />
                         </div>
                       </div>
                       
                       <div className="md:col-span-2 space-y-6 pt-6 border-t border-slate-50 text-slate-900">
                        <TextAreaField label="Public Description / Features" value={unitData.description} onChange={(val) => handleInputChange('description', val)} />
                        <TextAreaField label="Seller Information Template" value={unitData.sellerInfo} onChange={(val) => handleInputChange('sellerInfo', val)} />
                      </div>
                    </div>
                  </div>
                  
                  {/* MAIN EQUIPMENT MEDIA */}
                  <MediaSection 
                    title="High-Resolution Media" 
                    images={unitData.images} 
                    onAdd={() => handleInputChange('images', [...(unitData.images || []), '/mahindra.jpg'])} 
                  />

                  {/* IMPLEMENTS / ATTACHMENTS DETAILS */}
                  <AttachmentsSection 
                    attachments={unitData.attachments} 
                    onAdd={handleAddImplement}
                    onChange={handleUpdateImplement}
                    onRemove={handleRemoveImplement}
                  />
                </div>


                {/* RIGHT COLUMN - MARKETPLACE WIDGET */}
                <div className="space-y-8">
                  <div className="bg-white rounded-[2rem] overflow-hidden shadow-2xl border border-slate-200/60 flex flex-col">
                    <div className="bg-slate-950 p-6 text-white flex items-center justify-between">
                      <div className="flex items-center gap-4 text-white">
                        <div className="bg-blue-600 p-2.5 rounded-xl shadow-lg shadow-blue-500/20"><Facebook size={20} fill="white" /></div>
                        <div>
                          <h4 className="font-black text-sm uppercase tracking-tight leading-none mb-1">Meta Marketplace</h4>
                          <p className="text-[8px] text-slate-500 uppercase font-black tracking-widest leading-none">Auto-Sync Active</p>
                        </div>
                      </div>
                      <button onClick={() => setSyncEnabled(!syncEnabled)} className={`w-14 h-7 rounded-full relative transition-all duration-300 ${syncEnabled ? 'bg-blue-600 shadow-lg shadow-blue-500/50' : 'bg-slate-800'}`}>
                        <div className={`absolute top-1 w-5 h-5 bg-white rounded-full transition-all duration-300 ${syncEnabled ? 'left-8' : 'left-1'}`} />
                      </button>
                    </div>
                    
                    <div className="p-8 space-y-8 bg-white text-slate-900">
                      <div className="flex items-center gap-4 p-5 bg-blue-50/40 border-2 border-blue-100 rounded-[1.5rem] shadow-sm text-slate-900">
                         <div className="bg-white p-2 rounded-full border border-blue-200 shadow-md text-blue-600"><CheckCircle2 size={20} /></div>
                         <div>
                            <p className="text-[11px] font-black text-blue-950 uppercase leading-none mb-1">Facebook Catalog Synced</p>
                            <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest italic font-bold">Refreshed 2m ago</p>
                         </div>
                      </div>

                      <div className="space-y-4 px-1 font-black text-slate-900">
                        <h5 className="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mb-4">Catalog Mapping Logic</h5>
                        <MappingRow label="Vehicle Category" value="Agriculture / Tractor" />
                        <MappingRow label="Location Tag" value="Delta, CO (150mi)" />
                        <MappingRow label="Price Format" value="USD Fixed" />
                      </div>

                      {/* ENLARGED VIEW MARKETPLACE PREVIEW BUTTON */}
                        <button 
                          onClick={() => setShowFBPreview(true)} 
                          className="w-full bg-slate-950 text-white py-6 rounded-[1.5rem] font-black text-[13px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-black transition-all active:scale-95 shadow-2xl shadow-slate-300 mt-2 leading-none border-b-4 border-slate-800"
                        >
                          View Marketplace Preview <ArrowUpRight size={18} className="text-blue-400" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
            )}

            {activeTab === 'marketplace' && (
              <div className="space-y-8 animate-in fade-in duration-500 text-slate-950 font-black">
                <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[3rem] p-12 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
                  <div className="relative z-10"><h3 className="text-3xl font-black tracking-tighter mb-2 uppercase leading-none">Meta Commerce Engine</h3><p className="text-blue-100 font-bold opacity-80 uppercase tracking-[0.3em] text-[10px]">API Health: Connected • 142 SKUs Live</p></div>
                  <Facebook size={120} className="absolute -right-8 -bottom-8 opacity-10 rotate-12" />
                </div>
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-8">
                  <div className="bg-white rounded-[2.5rem] p-10 shadow-2xl border border-slate-200/60 font-black">
                     <div className="flex items-center gap-4 mb-10 border-b border-slate-50 pb-6"><List size={22} className="text-blue-600" /><h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Sync Activity Logs</h4></div>
                     <div className="space-y-2">
                        <LogEntry msg="Price Sync: Mahindra 2638 HST" time="2 mins ago" />
                        <LogEntry msg="New Media: Big Tex 14LP Dump" time="14 mins ago" />
                        <LogEntry msg="Inventory Update: 142 SKUs checked" time="1h ago" />
                        <LogEntry msg="Lead Captured: Marketplace Messenger" time="3h ago" />
                        <LogEntry msg="Batch Update: Compact Tractors" time="5h ago" />
                        <LogEntry msg="API Handshake: Success" time="12h ago" />
                     </div>
                  </div>
                  <div className="space-y-8">
                    <div className="bg-white rounded-[2.5rem] p-10 shadow-2xl border border-slate-200/60 font-black">
                      <div className="flex items-center gap-4 mb-8">
                        <BarChart3 size={22} className="text-blue-600" />
                        <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Distribution Health</h4>
                      </div>
                      <div className="space-y-6">
                        <HealthBar label="Catalog Match Rate" value="98%" color="blue" />
                        <HealthBar label="Image Optimization" value="100%" color="green" />
                        <HealthBar label="Sync Latency" value="1.2s" color="blue" />
                      </div>
                    </div>
                    <div className="bg-white rounded-[2.5rem] p-10 shadow-2xl border border-slate-200/60 font-black">
                       <h4 className="font-black text-[10px] uppercase tracking-widest text-slate-400 mb-6">Marketplace Reach</h4>
                       <div className="flex items-center justify-between px-2">
                         <div className="text-center">
                           <p className="text-3xl font-black text-blue-600">4.2k</p>
                           <p className="text-[9px] text-slate-400 uppercase mt-1">Weekly Views</p>
                         </div>
                         <div className="w-px h-10 bg-slate-100"></div>
                         <div className="text-center">
                           <p className="text-3xl font-black text-blue-600">28</p>
                           <p className="text-[9px] text-slate-400 uppercase mt-1">Conversions</p>
                         </div>
                         <div className="w-px h-10 bg-slate-100"></div>
                         <div className="text-center">
                           <p className="text-3xl font-black text-blue-600">142</p>
                           <p className="text-[9px] text-slate-400 uppercase mt-1">Live Ads</p>
                         </div>
                       </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'settings' && <SettingsTab users={usersList} />}
            {activeTab === 'mobile' && <MobileAccessTab />}
            {activeTab === 'history' && <HistoryTab deletedItems={deletedHistory} onRestore={handleRestoreUnit} onPermanentDelete={handlePermanentDelete} />}

          </div>
        </div>
      </main>

      {/* FACEBOOK PREVIEW MODAL */}
      {showFBPreview && <FBPreviewModal unitData={unitData} onClose={() => setShowFBPreview(false)} />}

      {/* AI VISION MODAL */}
    </div>
  );
};

// --- SUB-COMPONENTS ---

const NavItem = ({ icon, label, active = false, badge = null, onClick }) => (
  <div onClick={onClick} className={`flex items-center justify-between p-4 rounded-xl cursor-pointer transition-all duration-300 ${active ? 'bg-red-600 text-white shadow-xl shadow-red-900/50 border-b-2 border-red-700' : 'text-slate-500 hover:bg-slate-900 hover:text-slate-100'}`}>
    <div className="flex items-center gap-4">
      {icon}
      <span className="font-black text-[13px] uppercase tracking-wider">{label}</span>
    </div>
    {badge && <span className={`px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest shadow-md ${active ? 'bg-white text-red-600' : 'bg-green-500 text-white'}`}>{badge}</span>}
  </div>
);

const MappingRow = ({ label, value }) => (
  <div className="flex justify-between items-center group py-1.5 border-b border-slate-50 pb-4 last:border-0 last:pb-0">
    <span className="text-[11px] font-black text-slate-400 uppercase tracking-widest">{label}</span>
    <span className="text-[11px] font-black text-slate-950 uppercase tracking-tight flex items-center gap-3">
      <div className="w-1.5 h-1.5 rounded-full bg-blue-600 shadow-[0_0_10px_rgba(37,99,235,0.6)]"></div> {value}
    </span>
  </div>
);

const InputField = ({ label, value, onChange }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1 font-black">{label}</label>
    <input type="text" value={value} onChange={(e) => onChange(e.target.value)} className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-black text-slate-900 focus:border-red-500 focus:bg-white outline-none transition-all shadow-sm text-lg leading-none" />
  </div>
);

const TextAreaField = ({ label, value, onChange }) => (
  <div className="space-y-3 rich-text-field">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1 font-black">{label}</label>
    <div className="bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] overflow-hidden focus-within:border-red-500 transition-all shadow-sm">
      <ReactQuill 
        theme="snow" 
        value={value} 
        onChange={onChange}
        modules={{
          toolbar: [
            [{ 'header': [1, 2, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['clean']
          ],
        }}
        className="bg-transparent"
      />
    </div>
    <style dangerouslySetInnerHTML={{ __html: `
      .rich-text-field .ql-toolbar.ql-snow {
        border: none;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        padding: 12px 20px;
      }
      .rich-text-field .ql-container.ql-snow {
        border: none;
        font-family: inherit;
        font-size: 14px;
        min-height: 150px;
      }
      .rich-text-field .ql-editor {
        padding: 20px;
        color: #1e293b;
        line-height: 1.6;
      }
      .rich-text-field .ql-editor.ql-blank::before {
        color: #94a3b8;
        font-style: normal;
        left: 20px;
      }
      .rich-text-content h1, .rich-text-content h2 {
        font-weight: 900;
        text-transform: uppercase;
        margin-bottom: 0.5em;
      }
      .rich-text-content ul, .rich-text-content ol {
        padding-left: 1.5em;
        margin-bottom: 1em;
      }
      .rich-text-content ul { list-style-type: disc; }
      .rich-text-content ol { list-style-type: decimal; }
    `}} />
  </div>
);

const SelectField = ({ label, options, value, onChange }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1 font-black">{label}</label>
    <div className="relative">
      <select value={value} onChange={(e) => onChange(e.target.value)} className="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-4 font-black text-slate-900 outline-none appearance-none focus:border-red-500 focus:bg-white transition-all shadow-sm cursor-pointer text-lg">
        {options.map((o, i) => <option key={i} value={o}>{o}</option>)}
      </select>
      <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400"><ChevronRight size={20} className="rotate-90" /></div>
    </div>
  </div>
);

const MediaSection = ({ title, images, onAdd, badge = "Auto-Optimized" }) => (
  <div className="bg-white rounded-[2rem] p-10 shadow-xl border border-slate-200/60">
    <div className="flex justify-between items-center mb-10">
      <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2 leading-none font-black">
        <ImageIcon size={14} className="text-red-600" /> {title}
      </h3>
      <span className="bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-4 py-2 rounded-full border border-slate-100 tracking-widest shadow-sm">
        {badge}
      </span>
    </div>
    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
      {images && images.map((img, i) => (
        <div key={i} className="aspect-[4/3] bg-slate-50 rounded-[1.5rem] overflow-hidden relative shadow-md group cursor-pointer border-2 border-transparent hover:border-red-500 transition-all">
          <img 
            src={img} 
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000" 
            alt={`Image ${i+1}`} 
            onError={(e) => {
              e.target.onerror = null;
              e.target.src = "https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400";
            }}
          />
          {i === 0 && title.includes("High-Resolution") && (
            <div className="absolute bottom-3 left-3 bg-red-600 text-white text-[8px] font-black px-3 py-1.5 rounded uppercase tracking-widest shadow-xl font-black">
              MASTER PHOTO
            </div>
          )}
        </div>
      ))}
      <div onClick={onAdd} className="aspect-[4/3] border-2 border-dashed border-slate-200 rounded-[1.5rem] flex flex-col items-center justify-center text-slate-300 hover:text-red-600 hover:bg-red-50/20 transition-all cursor-pointer bg-white group">
        <div className="bg-white p-3 rounded-full shadow-lg mb-2 border border-slate-50 group-hover:scale-110 transition-transform">
          <Plus size={28} />
        </div>
        <span className="text-[9px] font-black uppercase tracking-[0.2em]">Add Images</span>
      </div>
    </div>
  </div>
);

const AttachmentsSection = ({ attachments = [], onAdd, onChange, onRemove }) => (
  <div className="bg-white rounded-[2rem] p-10 shadow-xl border border-slate-200/60">
    <div className="flex justify-between items-center mb-10">
      <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2 leading-none font-black">
        <ImageIcon size={14} className="text-red-600" /> Implements / Attachments
      </h3>
      <span className="bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-4 py-2 rounded-full border border-slate-100 tracking-widest shadow-sm">
        Add-on Products
      </span>
    </div>
    
    <div className="space-y-6">
      {attachments.map((imp, i) => (
        <div key={i} className="bg-slate-50 rounded-[1.5rem] p-6 border-2 border-slate-100 flex flex-col md:flex-row gap-6 relative group">
          <button 
            onClick={() => onRemove(i)}
            className="absolute -top-3 -right-3 bg-red-600 text-white p-2 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10"
          >
            <X size={16} />
          </button>
          
          <div className="w-full md:w-40 aspect-square bg-white rounded-xl overflow-hidden border-2 border-slate-200 shrink-0 relative">
             <img 
               src={imp.image} 
               className="w-full h-full object-cover" 
               onError={(e) => { e.target.src = "https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400"; }}
             />
             <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity cursor-pointer">
                <Camera size={20} className="text-white" />
             </div>
          </div>
          
          <div className="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
             <div className="sm:col-span-2">
               <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Implement Title</label>
               <input 
                 placeholder="e.g. Front End Loader"
                 value={imp.title}
                 onChange={(e) => onChange(i, 'title', e.target.value)}
                 className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
               />
             </div>
             <div>
               <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Price (USD)</label>
               <input 
                 placeholder="0.00"
                 value={imp.price}
                 onChange={(e) => onChange(i, 'price', e.target.value)}
                 className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
               />
             </div>
             <div className="sm:col-span-2">
               <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Short Description</label>
               <textarea 
                 placeholder="Brief specs or features..."
                 value={imp.description}
                 onChange={(e) => onChange(i, 'description', e.target.value)}
                 className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm h-20 resize-none"
               />
             </div>
          </div>
        </div>
      ))}
      
      <button 
        onClick={onAdd}
        className="w-full border-2 border-dashed border-slate-200 rounded-[1.5rem] p-8 flex flex-col items-center justify-center text-slate-300 hover:text-red-600 hover:bg-red-50/20 transition-all bg-white group"
      >
        <div className="bg-white p-3 rounded-full shadow-lg mb-2 border border-slate-50 group-hover:scale-110 transition-transform">
          <Plus size={24} />
        </div>
        <span className="text-[10px] font-black uppercase tracking-[0.2em]">Add Implement / Attachment</span>
      </button>
    </div>
  </div>
);

const MetricCard = ({ icon, label, value, subtext, color }) => {
  const styles = { 
    blue: "bg-blue-50 text-blue-600 shadow-blue-100", 
    red: "bg-red-50 text-red-600 shadow-red-100", 
    green: "bg-green-50 text-green-600 shadow-green-100",
    amber: "bg-amber-50 text-amber-600 shadow-amber-100"
  };
  return (
    <div className="rounded-[2rem] p-8 border bg-white border-slate-200/60 shadow-xl relative overflow-hidden group transition-all">
      <div className="flex items-center gap-4 mb-8 relative z-10">
        <div className={`p-4 rounded-xl ${styles[color]} shadow-md group-hover:scale-110 transition-transform`}>{icon}</div>
        <h4 className="font-black text-[10px] uppercase tracking-widest text-slate-400 leading-none">{label}</h4>
      </div>
      <p className="text-5xl font-black text-slate-950 mb-3 tracking-tighter relative z-10 leading-none">{value}</p>
      <p className={`text-[10px] font-black uppercase tracking-[0.1em] relative z-10 font-black ${styles[color].split(' ')[1]}`}>{subtext}</p>
      <div className={`absolute -right-6 -bottom-6 w-32 h-32 rounded-full opacity-10 ${styles[color].split(' ')[0]} group-hover:scale-150 transition-transform duration-700`}></div>
    </div>
  );
};

const QuickActions = ({ onAdd }) => (
  <div className="bg-white rounded-[2rem] p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
      <Zap size={14} className="text-red-600" /> Quick Operations
    </h4>
    <div className="grid grid-cols-1 gap-4">
      <button onClick={onAdd} className="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-red-500 hover:bg-white transition-all group">
        <div className="p-3 bg-white rounded-xl shadow-md mb-3 group-hover:scale-110 transition-transform">
          <Plus size={20} className="text-red-600" />
        </div>
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-600">Add Unit</span>
      </button>
    </div>
  </div>
);

const RecentActivity = () => (
  <div className="bg-white rounded-[2rem] p-8 border border-slate-200/60 shadow-xl">
    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
      <History size={14} className="text-blue-600" /> Activity Stream
    </h4>
    <div className="space-y-6">
      <ActivityItem icon={<CheckCircle2 size={14} />} title="Inventory Synced" desc="142 units updated on Meta" time="12m ago" color="green" />
      <ActivityItem icon={<Users size={14} />} title="New Lead" desc="Marcus R. • Mahindra 2638" time="3h ago" color="red" />
    </div>
  </div>
);

const ActivityItem = ({ icon, title, desc, time, color }) => {
  const colors = {
    green: "text-green-600 bg-green-50",
    blue: "text-blue-600 bg-blue-50",
    red: "text-red-600 bg-red-50"
  };
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

const PerformanceChart = () => {
  const chartData = [
    { day: 'MON', leads: 4, views: 120 }, { day: 'TUE', leads: 6, views: 165 }, { day: 'WED', leads: 5, views: 140 },
    { day: 'THU', leads: 12, views: 290 }, { day: 'FRI', leads: 8, views: 190 }, { day: 'SAT', leads: 18, views: 420 },
    { day: 'SUN', leads: 14, views: 350 },
  ];
  const maxViews = Math.max(...chartData.map(d => d.views));

  return (
    <div className="bg-white rounded-[2.5rem] p-6 sm:p-12 shadow-2xl border border-slate-100 min-h-[450px] flex flex-col text-slate-950 overflow-hidden relative">
      <div className="flex flex-col lg:flex-row justify-between items-start gap-6 mb-12">
        <div className="space-y-2">
          <h3 className="text-xl sm:text-2xl font-black tracking-tight leading-none uppercase">Weekly Engagement Ledger</h3>
          <p className="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mt-2 font-black">Sync Reach vs Inbound Lead Volume</p>
        </div>
        <div className="flex gap-4 sm:gap-6 bg-slate-50 p-3 sm:p-5 rounded-2xl border border-slate-100 shadow-inner w-full lg:w-auto justify-center">
          <div className="flex items-center gap-2 sm:gap-3 font-black text-[8px] sm:text-[10px] uppercase tracking-widest text-blue-600 font-black"><div className="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-600 shadow-lg shadow-blue-200"></div> VIEWS</div>
          <div className="flex items-center gap-2 sm:gap-3 font-black text-[8px] sm:text-[10px] uppercase tracking-widest text-red-600 font-black"><div className="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-red-600 shadow-lg shadow-red-200"></div> LEADS</div>
        </div>
      </div>
      
      <div className="flex-1 min-h-[250px] w-full overflow-x-auto no-scrollbar pb-2">
        <div className="flex items-end justify-between gap-4 sm:gap-8 border-b-4 border-slate-50 pb-8 min-w-[600px] h-[250px]">
          {chartData.map((data, i) => (
            <div key={i} className="flex flex-col items-center flex-1 gap-4 group h-full justify-end relative">
              <div className="w-full max-w-[45px] flex items-end justify-center gap-2 h-full relative">
                <div 
                  className="w-1/2 bg-blue-600 rounded-t-xl transition-all duration-1000 ease-out group-hover:bg-blue-400 relative shadow-xl shadow-blue-100" 
                  style={{ height: `${(data.views / maxViews) * 100}%` }}
                >
                   <div className="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-950 text-white text-[8px] font-black px-2 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all pointer-events-none shadow-2xl z-20 whitespace-nowrap">{data.views} Views</div>
                </div>
                <div 
                  className="w-1/2 bg-red-600 rounded-t-xl transition-all duration-1000 ease-out group-hover:bg-red-400 relative shadow-xl shadow-red-100" 
                  style={{ height: `${(data.leads / maxViews) * 100 * 3.5}%` }} 
                >
                   <div className="absolute -top-10 left-1/2 -translate-x-1/2 bg-red-600 text-white text-[8px] font-black px-2 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all pointer-events-none z-20 shadow-2xl whitespace-nowrap">{data.leads} Leads</div>
                </div>
              </div>
              <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{data.day}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

const HealthBar = ({ label, value, color }) => (
  <div className="space-y-2">
    <div className="flex justify-between items-center">
      <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{label}</span>
      <span className="text-[10px] font-black text-slate-900 uppercase">{value}</span>
    </div>
    <div className="h-1.5 w-full bg-slate-50 rounded-full overflow-hidden border border-slate-100">
      <div className={`h-full ${color === 'blue' ? 'bg-blue-600' : 'bg-green-600'} rounded-full`} style={{ width: value.includes('%') ? value : '100%' }}></div>
    </div>
  </div>
);

const LogEntry = ({ msg, time }) => (
  <div className="flex justify-between items-center p-6 bg-slate-50/50 rounded-2xl border-2 border-white mb-4 hover:bg-white transition-all shadow-sm group font-black text-slate-900">
    <div className="flex items-center gap-6">
      <div className="p-2.5 bg-green-100 rounded-xl group-hover:scale-110 transition-transform font-black"><CheckCircle2 size={20} className="text-green-600" /></div>
      <span className="text-base font-black tracking-tight leading-none">{msg}</span>
    </div>
    <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest font-black">{time}</span>
  </div>
);




const MobileAccessTab = () => (
  <div className="bg-white rounded-[3rem] p-14 border border-slate-200/60 shadow-2xl text-center max-w-4xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div className="bg-slate-50 w-24 h-24 rounded-[2rem] mx-auto flex items-center justify-center mb-8 border-2 border-slate-100 shadow-inner">
      <Smartphone size={40} className="text-slate-900" />
    </div>
    <h3 className="text-3xl font-black mb-4 tracking-tighter leading-none text-slate-900 uppercase font-black">Varner Mobile Companion</h3>
    <p className="text-slate-400 font-bold uppercase tracking-widest text-[11px] mb-12 max-w-lg mx-auto leading-relaxed">Download the PWA to your yard tablets for "Gloved-Hand" inventory uploads.</p>
    <div className="aspect-square w-64 bg-slate-950 rounded-[3rem] mx-auto mb-10 flex items-center justify-center text-white font-black uppercase tracking-[0.4em] border-8 border-slate-100 border-dashed text-xs shadow-3xl font-black">QR CODE</div>
    <button className="bg-red-600 text-white px-12 py-5 rounded-2xl font-black uppercase tracking-widest shadow-2xl shadow-red-200 hover:bg-red-700 transition active:scale-95 text-[11px] font-black">Generate Token</button>
  </div>
);

const SettingsTab = ({ users }) => (
  <div className="max-w-4xl mx-auto space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-500 text-slate-900">
    <div className="bg-white rounded-[2.5rem] p-10 border border-slate-200/60 shadow-xl">
      <h3 className="font-black text-[12px] uppercase tracking-widest text-slate-400 mb-10 flex items-center gap-3 font-black font-black"><ShieldCheck size={20}/> Technical Permissions</h3>
      <div className="space-y-6 text-slate-900 font-black">
        {users.map((u, i) => (
          <div key={i} className="p-6 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center shadow-sm hover:shadow-lg transition-all group font-black">
            <div className="flex items-center gap-5">
               <div className="w-12 h-12 rounded-xl bg-slate-950 text-white flex items-center justify-center font-black text-xl group-hover:scale-110 transition-transform">A</div>
               <div><p className="font-black text-lg leading-none mb-1.5 font-black">{u.name}</p><p className="text-[10px] font-black text-slate-400 uppercase tracking-widest font-black">{u.role} • {u.device}</p></div>
            </div>
            <span className={`px-4 py-1.5 rounded-full font-black text-[10px] uppercase tracking-widest border font-black ${u.status === 'Inactive' ? 'bg-slate-100 text-slate-400 border-slate-200' : 'bg-green-100 text-green-700 border-green-200'}`}>
              {u.status}
            </span>
          </div>
        ))}
      </div>
    </div>
  </div>
);

const FBPreviewModal = ({ unitData, onClose }) => (
  <div className="fixed inset-0 bg-slate-950/90 backdrop-blur-xl z-50 flex items-center justify-center p-8">
    <div className="bg-white w-full max-w-[420px] rounded-[3.5rem] overflow-hidden shadow-3xl border-[12px] border-slate-950 relative h-[85vh] flex flex-col animate-in zoom-in duration-300">
      <div className="p-8 bg-white flex justify-between border-b items-center relative z-20 pt-10">
        <span className="font-black text-[11px] uppercase text-blue-600 flex items-center gap-3 tracking-[0.2em] leading-none font-black font-black font-black"><Facebook size={20} fill="currentColor"/> Meta Marketplace Preview</span>
        <button onClick={onClose} className="p-2 hover:bg-slate-100 rounded-full transition-colors font-black"><X size={24} className="text-slate-400 font-black"/></button>
      </div>
      <div className="flex-1 overflow-y-auto no-scrollbar pb-12">
        <div className="aspect-[4/3] bg-slate-100 relative overflow-hidden">
          {unitData.images?.length > 0 ? (
            <img 
              src={unitData.images[0]} 
              className="w-full h-full object-cover" 
              onError={(e) => {
                e.target.onerror = null;
                e.target.src = "https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400";
              }}
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-slate-200"><ImageIcon size={64}/></div>
          )}
        </div>
        <div className="p-8 space-y-8">
          <div className="text-slate-900"><h2 className="text-4xl font-black leading-none mb-2 tracking-tighter font-black font-black font-black font-black">${parseInt(unitData.price || 0).toLocaleString()}</h2><h3 className="text-2xl font-bold text-slate-800 leading-tight mb-2 tracking-tight font-black font-black">{unitData.year} {unitData.title}</h3><p className="text-slate-400 text-sm font-black uppercase tracking-widest font-black">Delta, CO · Posted now</p></div>
          <div className="flex gap-3"><button className="flex-1 bg-[#0866FF] text-white py-4 rounded-[1.25rem] font-black text-sm shadow-xl shadow-blue-200 font-black">Message</button><button className="p-4 bg-slate-100 rounded-[1.25rem] text-slate-600 font-black"><Plus size={24}/></button></div>
          <div className="pt-8 border-t border-slate-100 text-slate-900 font-black">
            <h4 className="font-black text-[12px] uppercase text-slate-400 mb-5 tracking-[0.3em] font-black font-black font-black font-black">Description</h4>
            <div 
              className="text-[16px] text-slate-800 font-medium leading-relaxed font-black font-black rich-text-content"
              dangerouslySetInnerHTML={{ __html: unitData.description }}
            />
            <div 
              className="mt-8 p-6 bg-slate-50 rounded-[2rem] border-2 border-slate-100 border-dashed italic text-[11px] text-slate-500 leading-relaxed font-black font-black rich-text-content"
              dangerouslySetInnerHTML={{ __html: unitData.sellerInfo }}
            />
            {unitData.attachments?.length > 0 && (
              <div className="pt-8 border-t border-slate-100">
                <h4 className="font-black text-[12px] uppercase text-slate-400 mb-5 tracking-[0.3em]">Implements / Attachments</h4>
                <div className="space-y-4">
                  {unitData.attachments.map((imp, i) => (
                    <div key={i} className="flex gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                      <div className="w-20 h-20 bg-slate-200 rounded-xl overflow-hidden shrink-0">
                        <img src={imp.image} className="w-full h-full object-cover" alt={imp.title} />
                      </div>
                      <div className="flex-1">
                        <div className="flex justify-between items-start mb-1">
                          <h5 className="font-black text-sm text-slate-900 uppercase tracking-tight">{imp.title || 'Untitled Implement'}</h5>
                          {imp.price && <span className="text-green-600 font-black text-xs">${parseInt(imp.price).toLocaleString()}</span>}
                        </div>
                        <p className="text-[10px] text-slate-500 font-bold leading-relaxed">{imp.description}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
      <div className="p-8 bg-slate-50 border-t border-slate-200 shadow-inner font-black"><button onClick={onClose} className="w-full py-5 bg-slate-950 text-white font-black uppercase tracking-[0.4em] text-[11px] rounded-3xl shadow-3xl hover:bg-black transition-all font-black">Close Simulator</button></div>
    </div>
  </div>
);

const HistoryTab = ({ deletedItems, onRestore, onPermanentDelete }) => (
  <div className="bg-white rounded-[2rem] border border-slate-200/60 shadow-xl overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-500">
    <div className="p-8 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
      <div>
        <h3 className="text-xl font-black uppercase tracking-tight leading-none">Recycle Bin</h3>
        <p className="text-slate-400 font-black uppercase text-[9px] tracking-[0.3em] mt-2 italic">Items stay here until permanently deleted</p>
      </div>
      <div className="bg-amber-100 text-amber-700 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
        <AlertCircle size={14} /> {deletedItems.length} Items Found
      </div>
    </div>
    
    <div className="overflow-x-auto p-2 no-scrollbar">
      <table className="w-full text-left border-collapse min-w-[800px]">
        <thead>
          <tr className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
            <th className="px-6 py-5 w-24">STOCK #</th>
            <th className="px-6 py-5 w-28">PHOTO</th>
            <th className="px-6 py-5">EQUIPMENT</th>
            <th className="px-6 py-5">DELETED ON</th>
            <th className="px-6 py-5 text-right w-48">ACTIONS</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-50">
          {deletedItems.length === 0 ? (
            <tr>
              <td colSpan="5" className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">
                Recycle bin is empty
              </td>
            </tr>
          ) : (
            deletedItems.map((item) => (
              <tr key={item.id} className="hover:bg-slate-50 transition-all group">
                <td className="px-6 py-5 font-mono font-bold text-sm text-slate-400">{item.stock}</td>
                <td className="px-6 py-5">
                   <div className="w-16 h-12 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 grayscale opacity-60">
                     <img 
                       src={item.image} 
                       className="w-full h-full object-cover" 
                       onError={(e) => { e.target.src = "https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=100"; }}
                     />
                   </div>
                </td>
                <td className="px-6 py-5">
                  <p className="font-black text-slate-400 text-base uppercase leading-tight tracking-tight">{item.year} {item.make} {item.model}</p>
                  <p className="text-[9px] font-black uppercase tracking-widest mt-1 text-slate-300">{item.category}</p>
                </td>
                <td className="px-6 py-5">
                  <p className="text-[11px] font-black text-slate-400 uppercase tracking-tight">{new Date(item.deletedAt).toLocaleDateString()} @ {new Date(item.deletedAt).toLocaleTimeString()}</p>
                </td>
                <td className="px-6 py-5 text-right">
                   <div className="flex items-center justify-end gap-3">
                     <button 
                       onClick={() => onRestore(item.stock)}
                       className="bg-green-50 text-green-600 px-4 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-green-100 transition-all active:scale-95 border border-green-100"
                     >
                       <RotateCcw size={14} /> Restore
                     </button>
                     <button 
                       onClick={() => onPermanentDelete(item.stock)}
                       className="bg-slate-100 text-slate-400 p-2.5 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all active:scale-95 border border-slate-200"
                       title="Permanently Delete"
                     >
                       <Trash2 size={16} />
                     </button>
                   </div>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  </div>
);

export default App;