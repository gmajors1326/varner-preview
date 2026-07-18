import React, { useState, useEffect, useCallback } from 'react';
import {
  LineChart, Line, AreaChart, Area, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer
} from 'recharts';

const API = window.varnerData?.rest_url
  ? window.varnerData.rest_url.replace(/\/$/, '') + '/varner/v1'
  : '/wp-json/varner/v1';
const NONCE = window.varnerData?.nonce ?? '';

const ACCENT = '#2563eb';
const ACCENT_LIGHT = '#dbeafe';
const BG = '#f8f9fa';
const CARD_BORDER = '#e2e8f0';

function SkeletonCard({ h }) {
  return (
    <div className="bg-white rounded-xl border" style={{ borderColor: CARD_BORDER, height: h || 200, padding: 20 }}>
      <div className="animate-pulse space-y-3">
        <div className="h-3 bg-slate-100 rounded w-1/3" />
        <div className="h-8 bg-slate-100 rounded w-1/2" />
        <div className="h-3 bg-slate-100 rounded w-2/3" />
      </div>
    </div>
  );
}

function MetricCard({ label, value, subtext }) {
  return (
    <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
      <div className="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">{label}</div>
      <div className="text-3xl font-black text-slate-900 tracking-tight">{value}</div>
      {subtext && <div className="text-xs text-slate-400 font-bold mt-0.5">{subtext}</div>}
    </div>
  );
}

function BarTable({ data, labelKey, valueKey, maxValue, emptyMsg }) {
  if (!data || data.length === 0) {
    return <div className="text-sm text-slate-400 py-8 text-center font-bold">{emptyMsg || 'No data yet'}</div>;
  }
  const max = maxValue || Math.max(...data.map(d => d[valueKey]));
  return (
    <div className="space-y-3">
      {data.map((d, i) => (
        <div key={i}>
          <div className="flex justify-between text-sm mb-1">
            <span className="font-bold text-slate-800 truncate">{d[labelKey]}</span>
            <span className="font-black text-slate-500 ml-2">{d[valueKey].toLocaleString()}</span>
          </div>
          <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div className="h-full rounded-full transition-all duration-500" style={{ width: `${(d[valueKey] / max) * 100}%`, backgroundColor: ACCENT }} />
          </div>
        </div>
      ))}
    </div>
  );
}

function InfoTooltip({ text }) {
  return (
    <span className="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-slate-200 text-slate-500 text-[9px] font-black cursor-help ml-1" title={text}>?</span>
  );
}

function LocationPanel({ countries, regions }) {
  const [tab, setTab] = useState('regions'); // 'regions' | 'countries'
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(5);
  const [sortCol, setSortCol] = useState('users');
  const [sortDir, setSortDir] = useState('desc');

  const isRegions = tab === 'regions';
  const rawData = isRegions ? (regions || []) : (countries || []).slice(0, 20);

  // Filter by search
  const filtered = rawData.filter(row => {
    const label = isRegions ? row.region : (row.name || row.country);
    return label.toLowerCase().includes(search.toLowerCase());
  });

  // Sort
  const sorted = [...filtered].sort((a, b) => {
    const aVal = a[sortCol] ?? 0;
    const bVal = b[sortCol] ?? 0;
    if (typeof aVal === 'string') return sortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    return sortDir === 'asc' ? aVal - bVal : bVal - aVal;
  });

  const totalRows = sorted.length;
  const totalPages = Math.ceil(totalRows / rowsPerPage);
  const pageData = sorted.slice(page * rowsPerPage, (page + 1) * rowsPerPage);

  const handleSort = (col) => {
    if (sortCol === col) {
      setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      setSortCol(col);
      setSortDir('desc');
    }
    setPage(0);
  };

  const handleTabChange = (t) => {
    setTab(t);
    setPage(0);
    setSearch('');
  };

  const SortArrow = ({ col }) => {
    if (sortCol !== col) return null;
    return <span style={{ marginLeft: 4, fontSize: 11 }}>{sortDir === 'asc' ? '↑' : '↓'}</span>;
  };

  const columns = [
    { key: isRegions ? 'region' : 'name', label: isRegions ? 'Region' : 'Country', align: 'left', sortKey: isRegions ? 'region' : 'name' },
    { key: 'users', label: 'Users', align: 'right' },
    { key: 'views', label: 'Page Views', align: 'right' },
    { key: 'new_users', label: 'New Users', align: 'right' },
    { key: 'pct', label: '% of Total', align: 'right', fmt: v => `${v}%` },
  ];

  const cellStyle = { padding: '12px 16px', borderBottom: '1px solid #f1f5f9', fontSize: 13 };
  const headerStyle = { ...cellStyle, fontWeight: 700, fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.05em', color: '#64748b', borderBottom: '1px solid #e2e8f0', cursor: 'pointer', userSelect: 'none', whiteSpace: 'nowrap' };

  return (
    <div className="bg-white rounded-xl border overflow-hidden" style={{ borderColor: CARD_BORDER }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '20px 20px 0' }}>
        <div style={{ fontSize: 15, fontWeight: 800, color: '#1e293b' }}>
          Users by location
          <InfoTooltip text="Geographic distribution of your visitors based on Accept-Language header" />
        </div>
      </div>

      {/* Tabs */}
      <div style={{ display: 'flex', gap: 0, padding: '16px 20px 0', borderBottom: '1px solid #e2e8f0' }}>
        {[{ key: 'regions', label: 'Regions' }, { key: 'countries', label: 'Top 20 countries' }].map(t => (
          <button
            key={t.key}
            onClick={() => handleTabChange(t.key)}
            style={{
              padding: '8px 16px',
              fontSize: 13,
              fontWeight: tab === t.key ? 700 : 500,
              color: tab === t.key ? '#1e293b' : '#64748b',
              background: 'none',
              border: 'none',
              borderBottom: tab === t.key ? '2px solid #2563eb' : '2px solid transparent',
              cursor: 'pointer',
              marginBottom: -1,
              transition: 'all 0.15s',
            }}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Search */}
      <div style={{ padding: '16px 20px' }}>
        <div style={{ position: 'relative', maxWidth: 240 }}>
          <svg style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', width: 16, height: 16, color: '#94a3b8' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search"
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(0); }}
            style={{
              width: '100%',
              padding: '8px 12px 8px 34px',
              fontSize: 13,
              border: '1px solid #e2e8f0',
              borderRadius: 8,
              outline: 'none',
              color: '#334155',
              background: '#fff',
            }}
          />
        </div>
      </div>

      {/* Table */}
      <div style={{ overflowX: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr>
              {columns.map(col => (
                <th
                  key={col.key}
                  onClick={() => handleSort(col.key === (isRegions ? 'region' : 'name') ? col.key : col.key)}
                  style={{ ...headerStyle, textAlign: col.align }}
                >
                  {col.label}<SortArrow col={col.key} />
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {pageData.length === 0 ? (
              <tr>
                <td colSpan={columns.length} style={{ ...cellStyle, textAlign: 'center', color: '#94a3b8', padding: '32px 16px', fontWeight: 600 }}>
                  {search ? 'No results match your search' : 'No location data yet'}
                </td>
              </tr>
            ) : (
              pageData.map((row, i) => (
                <tr key={i} style={{ transition: 'background 0.1s' }} onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
                  {columns.map(col => {
                    const val = row[col.key];
                    const isNameCol = col.key === 'region' || col.key === 'name';
                    return (
                      <td key={col.key} style={{ ...cellStyle, textAlign: col.align, fontWeight: isNameCol ? 600 : 400, color: isNameCol ? '#6d28d9' : '#334155' }}>
                        {col.fmt ? col.fmt(val) : (typeof val === 'number' ? val.toLocaleString() : val)}
                      </td>
                    );
                  })}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {totalRows > 0 && (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 16, padding: '12px 20px', borderTop: '1px solid #f1f5f9', fontSize: 13, color: '#64748b' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            <span>Rows per page:</span>
            <select
              value={rowsPerPage}
              onChange={e => { setRowsPerPage(Number(e.target.value)); setPage(0); }}
              style={{ border: '1px solid #e2e8f0', borderRadius: 4, padding: '2px 4px', fontSize: 13, color: '#334155', background: '#fff', cursor: 'pointer' }}
            >
              {[5, 10, 25].map(n => <option key={n} value={n}>{n}</option>)}
            </select>
          </div>
          <span>{page * rowsPerPage + 1}–{Math.min((page + 1) * rowsPerPage, totalRows)} of {totalRows}</span>
          <div style={{ display: 'flex', gap: 4 }}>
            <button
              disabled={page === 0}
              onClick={() => setPage(p => p - 1)}
              style={{ width: 28, height: 28, display: 'flex', alignItems: 'center', justifyContent: 'center', border: '1px solid #e2e8f0', borderRadius: 6, background: '#fff', cursor: page === 0 ? 'default' : 'pointer', opacity: page === 0 ? 0.4 : 1, fontSize: 14, color: '#334155' }}
            >‹</button>
            <button
              disabled={page >= totalPages - 1}
              onClick={() => setPage(p => p + 1)}
              style={{ width: 28, height: 28, display: 'flex', alignItems: 'center', justifyContent: 'center', border: '1px solid #e2e8f0', borderRadius: 6, background: '#fff', cursor: page >= totalPages - 1 ? 'default' : 'pointer', opacity: page >= totalPages - 1 ? 0.4 : 1, fontSize: 14, color: '#334155' }}
            >›</button>
          </div>
        </div>
      )}
    </div>
  );
}

export default function AnalyticsDashboard() {
  const mountEl = document.getElementById('varner-analytics-app');
  const initialRange = mountEl?.getAttribute('data-range') || '30';
  const [range, setRange] = useState(initialRange);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchSummary = useCallback(async (r) => {
    setLoading(true);
    try {
      const res = await fetch(`${API}/analytics/summary?range=${r}`, {
        headers: { 'X-WP-Nonce': NONCE },
      });
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setData(json);
    } catch (e) {
      setData(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchSummary(range); }, [range, fetchSummary]);

  const handleRangeChange = (r) => {
    setRange(r);
    const url = new URL(window.location.href);
    url.searchParams.set('range', r);
    window.history.replaceState({}, '', url);
  };

  // Realtime poll
  useEffect(() => {
    const id = setInterval(() => fetchSummary(range), 60000);
    return () => clearInterval(id);
  }, [range, fetchSummary]);

  if (loading && !data) {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-3 gap-4">
          {[1,2,3].map(i => <SkeletonCard key={i} h={100} />)}
        </div>
        <div className="grid grid-cols-12 gap-4">
          <div className="col-span-8"><SkeletonCard h={380} /></div>
          <div className="col-span-4"><SkeletonCard h={380} /></div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          {[1,2,3].map(i => <SkeletonCard key={i} h={200} />)}
        </div>
        <div className="grid grid-cols-2 gap-4">
          {[1,2].map(i => <SkeletonCard key={i} h={200} />)}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 font-sans" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
      {/* Row 1: Range selector */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2 px-3 py-1.5 bg-white border rounded-lg" style={{ borderColor: CARD_BORDER }}>
          <span className="text-xs font-black text-slate-400 uppercase tracking-wider">All Users</span>
        </div>
        <div className="flex gap-1 bg-white border rounded-lg p-0.5" style={{ borderColor: CARD_BORDER }}>
          {['7','30','90'].map(r => (
            <button key={r} onClick={() => handleRangeChange(r)}
              className={`px-3 py-1.5 rounded-md text-xs font-black uppercase tracking-wider transition-all ${range === r ? 'bg-red-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'}`}>
              {r === '7' ? '7 Day' : r === '30' ? '30 Day' : '90 Day'}
            </button>
          ))}
        </div>
      </div>

      {/* Row 2: Main card (8col) + Side card (4col) */}
      <div className="grid grid-cols-12 gap-4">
        {/* Main card */}
        <div className="col-span-12 lg:col-span-8 bg-white rounded-xl border overflow-hidden" style={{ borderColor: CARD_BORDER }}>
          {/* KPI strip */}
          <div className="grid grid-cols-3 gap-px bg-slate-100">
            {[
              { label: 'Users', key: 'users', tooltip: 'Total unique visitors in period' },
              { label: 'New Users', key: 'new_users', tooltip: 'First-time visitors' },
              { label: 'Avg Engagement Time', key: 'avg_engagement_seconds', tooltip: 'Average session duration', fmt: v => `${Math.round(v/60)}s` },
            ].map(kpi => (
              <div key={kpi.key} className="bg-white p-4 sm:p-5">
                <div className="text-[11px] font-black uppercase tracking-widest text-slate-400 flex items-center">
                  {kpi.label}<InfoTooltip text={kpi.tooltip} />
                </div>
                <div className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                  {data?.kpis ? (kpi.fmt ? kpi.fmt(data.kpis[kpi.key]) : data.kpis[kpi.key].toLocaleString()) : '—'}
                </div>
              </div>
            ))}
          </div>
          {/* Line chart */}
          <div className="p-4 sm:p-5">
            <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-3">Users per day</div>
            {data?.timeseries && data.timeseries.length > 0 ? (
              <ResponsiveContainer width="100%" height={260}>
                <AreaChart data={data.timeseries} margin={{ top: 5, right: 5, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="colorUsers" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor={ACCENT} stopOpacity={0.12} />
                      <stop offset="95%" stopColor={ACCENT} stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid stroke="#f1f5f9" vertical={false} />
                  <XAxis dataKey="date" tick={{ fontSize: 10, fill: '#94a3b8' }} tickFormatter={v => {
                    const d = new Date(v + 'T00:00:00');
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                  }} interval="preserveStartEnd" minTickGap={30} />
                  <YAxis tick={{ fontSize: 10, fill: '#94a3b8' }} />
                  <Tooltip
                    contentStyle={{ borderRadius: 8, border: '1px solid #e2e8f0', fontSize: 12, fontWeight: 700 }}
                    labelFormatter={v => new Date(v + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                  />
                  <Area type="monotone" dataKey="users" stroke={ACCENT} strokeWidth={2} fill="url(#colorUsers)" dot={false} activeDot={{ r: 4, fill: ACCENT }} />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="text-sm text-slate-400 text-center py-16 font-bold">No data yet</div>
            )}
          </div>
        </div>

        {/* Side card: Active users */}
        <div className="col-span-12 lg:col-span-4 space-y-4">
          <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
            <div className="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Active users last 30 minutes</div>
            <div className="text-4xl font-black text-slate-900 tracking-tight">{data?.realtime?.active_last_30min ?? '—'}</div>
            {data?.realtime?.per_minute && (
              <div className="mt-3">
                <ResponsiveContainer width="100%" height={50}>
                  <AreaChart data={data.realtime.per_minute.map((v, i) => ({ m: i, v }))} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                    <Area type="monotone" dataKey="v" stroke={ACCENT} strokeWidth={1.5} fill={ACCENT_LIGHT} dot={false} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            )}
          </div>

        </div>
      </div>

      {/* Row 3: Users by Location panel */}
      <LocationPanel countries={data?.top_countries} regions={data?.top_regions} />

      {/* Row 4: Two cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
          <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">New Users by Source</div>
          <BarTable data={data?.top_sources} labelKey="source" valueKey="new_users" emptyMsg="No source data yet" />
        </div>
        <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
          <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Top Pages</div>
          <BarTable data={data?.top_pages} labelKey="path" valueKey="views" emptyMsg="No page data yet" />
        </div>
      </div>

      {/* Row 4: Two cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
          <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Device Category</div>
          {data?.devices ? (
            <div className="flex items-center gap-6">
              <ResponsiveContainer width={160} height={160}>
                <PieChart>
                  <Pie data={[
                    { name: 'Mobile', value: data.devices.mobile },
                    { name: 'Desktop', value: data.devices.desktop },
                    { name: 'Tablet', value: data.devices.tablet },
                  ]} cx="50%" cy="50%" innerRadius={45} outerRadius={72} paddingAngle={2} dataKey="value">
                    <Cell fill="#2563eb" />
                    <Cell fill="#475569" />
                    <Cell fill="#94a3b8" />
                  </Pie>
                  <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #e2e8f0', fontSize: 12, fontWeight: 700 }} />
                </PieChart>
              </ResponsiveContainer>
              <div className="space-y-2">
                {[
                  { name: 'Mobile', color: '#2563eb', val: data.devices.mobile },
                  { name: 'Desktop', color: '#475569', val: data.devices.desktop },
                  { name: 'Tablet', color: '#94a3b8', val: data.devices.tablet },
                ].map(d => (
                  <div key={d.name} className="flex items-center gap-2 text-sm">
                    <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ backgroundColor: d.color }} />
                    <span className="font-bold text-slate-700">{d.name}</span>
                    <span className="font-black text-slate-400">{d.val.toLocaleString()}</span>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <div className="text-sm text-slate-400 text-center py-8 font-bold">No device data yet</div>
          )}
        </div>
        <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
          <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Top Referrers</div>
          <BarTable data={data?.top_referrers} labelKey="source" valueKey="count" emptyMsg="No referrer data yet" />
        </div>
      </div>
    </div>
  );
}
