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

          <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
            <div className="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Top Countries</div>
            <BarTable data={data?.top_countries} labelKey="country" valueKey="users" emptyMsg="No country data yet" />
          </div>
        </div>
      </div>

      {/* Row 3: Three equal cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
          <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">New Users by Source</div>
          <BarTable data={data?.top_sources} labelKey="source" valueKey="new_users" emptyMsg="No source data yet" />
        </div>
        <div className="bg-white rounded-xl border p-5" style={{ borderColor: CARD_BORDER }}>
          <div className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Users by Country</div>
          <BarTable data={data?.top_countries} labelKey="country" valueKey="users" emptyMsg="No country data yet" />
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
