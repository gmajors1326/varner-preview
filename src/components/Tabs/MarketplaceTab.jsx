import React, { useState, useEffect } from 'react';
import { Facebook, List, CheckCircle2, BarChart3, Copy, Check, Loader2, AlertCircle, RefreshCw } from 'lucide-react';
import { apiFetch } from '../../utils/api';

export const MarketplaceTab = () => {
  const [copied, setCopied] = useState(false);
  const [logs, setLogs] = useState([]);
  const [health, setHealth] = useState({ match_rate: 100, image_optimization: 100, sync_latency: '0.1s' });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const siteUrl = window.varnerData?.site_url || '';
  const feedUrl = `${siteUrl}facebook-catalog.csv`;

  const fetchData = async (showLoadingSpinner = false) => {
    if (showLoadingSpinner) {
      setLoading(true);
    }
    try {
      const [logsData, healthData] = await Promise.all([
        apiFetch('/meta-sync/logs'),
        apiFetch('/meta-sync/health')
      ]);
      const safeLogs = Array.isArray(logsData) ? logsData : (Array.isArray(logsData?.logs) ? logsData.logs : []);
      setLogs(safeLogs);
      setHealth(healthData && typeof healthData === 'object' && !healthData.error ? healthData : { match_rate: 100, image_optimization: 100, sync_latency: '0.1s' });
      setError(null);
    } catch (err) {
      console.error('Failed to fetch meta sync data: ', err);
      setError('Failed to load sync data.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData(true);
    
    // Poll for changes every 5 minutes
    const interval = setInterval(() => fetchData(false), 300000);
    
    return () => {
      clearInterval(interval);
    };
  }, []);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(feedUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy feed URL: ', err);
    }
  };

  const getRelativeTime = (mysqlDate) => {
    if (!mysqlDate) return 'Just now';
    try {
      const t = mysqlDate.split(/[- :]/);
      const dateObj = new Date(t[0], t[1] - 1, t[2], t[3], t[4], t[5]);
      const diffMs = new Date() - dateObj;
      const diffSec = Math.max(0, Math.floor(diffMs / 1000));
      
      if (diffSec < 60) return 'Just now';
      const diffMin = Math.floor(diffSec / 60);
      if (diffMin < 60) return `${diffMin}m ago`;
      const diffHour = Math.floor(diffMin / 60);
      if (diffHour < 24) return `${diffHour}h ago`;
      const diffDay = Math.floor(diffHour / 24);
      return `${diffDay}d ago`;
    } catch (e) {
      return 'Recent';
    }
  };

  const safeLogs = Array.isArray(logs) ? logs : [];

  return (
    <div className="space-y-8 animate-in fade-in duration-500 text-slate-950 font-black">
      {/* Meta Commerce Engine Header */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-12 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10">
          <h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">Meta Commerce Engine</h3>
          <p className="text-white font-bold opacity-90 uppercase tracking-[0.3em] text-xs">API Health: Connected</p>
        </div>
        <Facebook size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* Facebook Catalog Feed URL Card */}
      <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 shadow-2xl border border-slate-200/60">
        <div className="flex items-center gap-4 mb-6 border-b border-slate-50 pb-4">
          <Facebook size={22} className="text-blue-600"/>
          <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Facebook Catalog Product Feed</h4>
        </div>
        <p className="text-sm font-bold text-slate-600 mb-6 uppercase tracking-tight">
          Use this product data feed URL to sync your inventory automatically with your Facebook Catalog / Commerce Manager.
        </p>
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="flex-1 bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 font-mono text-sm text-slate-700 break-all select-all flex items-center">
            {feedUrl}
          </div>
          <button
            onClick={handleCopy}
            className="bg-slate-900 hover:bg-black text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 transition-all active:scale-95 shrink-0 shadow-lg shadow-slate-950/20"
          >
            {copied ? <Check size={18} className="text-green-400" /> : <Copy size={18} />}
            {copied ? 'Copied' : 'Copy Feed URL'}
          </button>
        </div>
      </div>

      {/* Analytics & Metrics Overview */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-[2rem] p-6 sm:p-8 shadow-xl border border-slate-200/60 flex items-center gap-5">
          <div className="p-4 bg-green-50 text-green-600 rounded-2xl">
            <CheckCircle2 size={28} />
          </div>
          <div>
            <p className="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Catalog Match Rate</p>
            <p className="text-3xl font-black text-slate-900">{health.match_rate || 100}%</p>
          </div>
        </div>

        <div className="bg-white rounded-[2rem] p-6 sm:p-8 shadow-xl border border-slate-200/60 flex items-center gap-5">
          <div className="p-4 bg-blue-50 text-blue-600 rounded-2xl">
            <BarChart3 size={28} />
          </div>
          <div>
            <p className="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Image Optimization</p>
            <p className="text-3xl font-black text-slate-900">{health.image_optimization || 100}%</p>
          </div>
        </div>

        <div className="bg-white rounded-[2rem] p-6 sm:p-8 shadow-xl border border-slate-200/60 flex items-center gap-5">
          <div className="p-4 bg-purple-50 text-purple-600 rounded-2xl">
            <RefreshCw size={28} />
          </div>
          <div>
            <p className="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Sync Latency</p>
            <p className="text-3xl font-black text-slate-900">{health.sync_latency || '0.1s'}</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">
        <div className="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-5 sm:p-10 shadow-2xl border border-slate-200/60">
          <div className="flex items-center justify-between mb-10 border-b border-slate-50 pb-6">
            <div className="flex items-center gap-4">
              <List size={22} className="text-blue-600"/>
              <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Sync Activity Logs</h4>
            </div>
            <button 
              onClick={() => fetchData(true)} 
              disabled={loading}
              className="p-2 text-slate-400 hover:text-blue-600 disabled:opacity-50 transition-colors cursor-pointer rounded-lg hover:bg-slate-50"
              title="Refresh Logs"
              aria-label="Refresh logs"
            >
              <RefreshCw size={18} className={loading ? 'animate-spin' : ''} />
            </button>
          </div>
          
          {loading && safeLogs.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-slate-400">
              <Loader2 size={32} className="animate-spin text-blue-600 mb-4" />
              <p className="text-xs uppercase font-black tracking-widest">Loading Sync Activity...</p>
            </div>
          ) : error && safeLogs.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-red-500">
              <AlertCircle size={32} className="mb-4" />
              <p className="text-xs uppercase font-black tracking-widest">{error}</p>
            </div>
          ) : safeLogs.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-slate-400">
              <CheckCircle2 size={32} className="text-green-600 mb-4" />
              <p className="text-xs uppercase font-black tracking-widest">No sync logs recorded yet</p>
            </div>
          ) : (
            <div className="space-y-2 max-h-[500px] overflow-y-auto pr-2">
              {safeLogs.map((log, i) => (
                <div key={i} className="flex justify-between items-center p-6 bg-slate-50/50 rounded-2xl border-2 border-white mb-4 hover:bg-white transition-all shadow-sm group">
                  <div className="flex items-center gap-6">
                    <div className={`p-2.5 rounded-xl ${log.type === 'warning' ? 'bg-amber-100 text-amber-600' : log.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}`}>
                      <CheckCircle2 size={20} />
                    </div>
                    <span className="text-base font-black tracking-tight leading-none text-slate-800">{log.message}</span>
                  </div>
                  <span className="text-xs font-black text-slate-500 uppercase tracking-widest shrink-0 ml-4">{getRelativeTime(log.created_at)}</span>
                </div>
              ))}
            </div>
          )}
        </div>
        <div className="space-y-8">
          <div className="bg-white rounded-[2.5rem] p-10 shadow-2xl border border-slate-200/60">
            <div className="flex items-center gap-4 mb-8">
              <BarChart3 size={22} className="text-blue-600"/>
              <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">Distribution Health</h4>
            </div>
            <div className="space-y-6">
              {[
                ['Catalog Match Rate', `${health.match_rate}%`, 'blue'],
                ['Image Optimization', `${health.image_optimization}%`, 'green'],
                ['Sync Latency', health.sync_latency, 'blue']
              ].map(([l,v,c]) => (
                <div key={l} className="space-y-2">
                  <div className="flex justify-between">
                    <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">{l}</span>
                    <span className="text-[10px] font-black text-slate-900 uppercase">{v}</span>
                  </div>
                  <div className="h-1.5 w-full bg-slate-50 rounded-full overflow-hidden border border-slate-100">
                    <div className={`h-full ${c==='blue'?'bg-blue-600':'bg-green-600'} rounded-full`} style={{ width: v.includes('%') ? v : '100%' }}></div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
