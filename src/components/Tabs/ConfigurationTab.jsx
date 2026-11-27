import React, { useState } from 'react';
import {
  Sliders, Users, ShieldCheck, RotateCcw, Loader2, ChevronUp, ChevronDown,
  UserPlus, Trash2, Mail, CheckCircle, AlertCircle, X
} from 'lucide-react';
import { apiFetch } from '../../utils/api';

const ROLE_LABELS = { administrator: 'Admin', editor: 'Sales Staff' };
const ROLE_COLORS = {
  administrator: 'bg-indigo-50 text-indigo-700 border-indigo-100',
  editor:        'bg-blue-50 text-blue-700 border-blue-100',
};

export const ConfigurationTab = ({
  showToast,
  currentUser,
  sessionList,
  isLoading,
  loadSessions,
  activityList,
  isActivityLoading,
  loadActivity,
  onNav,
  handleFullEdit
}) => {
  const [activeSubTab, setActiveSubTab]           = useState('active-sessions');
  const [expandedActivityId, setExpandedActivityId] = useState(null);

  // ── Staff Users state ─────────────────────────────────────────────────────
  const [staffList, setStaffList]         = useState([]);
  const [isStaffLoading, setIsStaffLoading] = useState(false);
  const [showInviteForm, setShowInviteForm] = useState(false);
  const [isInviting, setIsInviting]       = useState(false);
  const [inviteResult, setInviteResult]   = useState(null); // {type:'success'|'error', msg}
  const [deletingId, setDeletingId]       = useState(null);
  const [confirmDeleteId, setConfirmDeleteId] = useState(null);
  const [inviteForm, setInviteForm]       = useState({ first_name: '', last_name: '', email: '', role: 'editor' });

  const loadStaff = async () => {
    setIsStaffLoading(true);
    try {
      const data = await apiFetch('/staff');
      setStaffList(Array.isArray(data) ? data : []);
    } catch (e) {
      showToast('Failed to load staff: ' + e.message, 'error');
    } finally {
      setIsStaffLoading(false);
    }
  };

  const handleInvite = async (e) => {
    e.preventDefault();
    setIsInviting(true);
    setInviteResult(null);
    try {
      const res = await apiFetch('/staff', {
        method: 'POST',
        body: JSON.stringify(inviteForm),
      });
      setInviteResult({ type: 'success', msg: res.message });
      setInviteForm({ first_name: '', last_name: '', email: '', role: 'editor' });
      setShowInviteForm(false);
      loadStaff();
    } catch (e) {
      setInviteResult({ type: 'error', msg: e.message });
    } finally {
      setIsInviting(false);
    }
  };

  const handleDeleteStaff = async (userId) => {
    setDeletingId(userId);
    setConfirmDeleteId(null);
    try {
      const res = await apiFetch(`/staff/${userId}`, { method: 'DELETE' });
      showToast(res.message || 'User removed.');
      setStaffList(prev => prev.filter(u => u.id !== userId));
    } catch (e) {
      showToast('Failed to remove user: ' + e.message, 'error');
    } finally {
      setDeletingId(null);
    }
  };

  const switchToStaff = () => {
    setActiveSubTab('staff-users');
    if (staffList.length === 0) loadStaff();
  };

  return (
    <div className="space-y-6 sm:space-y-8 animate-in fade-in duration-500 text-slate-900 pb-16">

      {/* HEADER */}
      <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-10 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10">
          <h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">System Settings &amp; Audit</h3>
          <p className="text-indigo-400 font-bold uppercase tracking-[0.3em] text-[10px]">
            Current User ID: {currentUser ? `#${currentUser.id}` : 'Loading...'}
          </p>
        </div>
        <Sliders size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* TWO-COLUMN GRID */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">

        {/* LEFT — USER PROFILE */}
        <div className="bg-white rounded-[2rem] p-6 sm:p-8 shadow-2xl border border-slate-200/60 flex flex-col justify-between">
          <div>
            <div className="flex items-center gap-4 mb-8 border-b border-slate-50 pb-6">
              <Users size={22} className="text-indigo-600"/>
              <h4 className="font-black text-xs uppercase tracking-widest text-slate-900">User Profile</h4>
            </div>
            <div className="flex flex-col items-center text-center space-y-4 mb-8">
              <div className="w-20 h-20 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-black shadow-lg">
                {currentUser?.initials || '?'}
              </div>
              <div>
                <h2 className="text-lg font-black text-slate-900 leading-tight">{currentUser?.display_name || 'Loading...'}</h2>
                <p className="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">Logged In User</p>
              </div>
            </div>
            <div className="space-y-4 border-t border-slate-100 pt-6">
              {[
                ['User ID',    currentUser?.id],
                ['First Name', currentUser?.first_name],
                ['Last Name',  currentUser?.last_name],
              ].map(([label, val]) => (
                <div key={label} className="flex justify-between items-center text-sm">
                  <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">{label}</span>
                  <span className="font-black text-slate-800">{val || '—'}</span>
                </div>
              ))}
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Role</span>
                <div className="flex gap-1 flex-wrap justify-end">
                  {currentUser?.roles?.map(role => (
                    <span key={role} className="bg-indigo-50 text-indigo-700 text-[9px] font-black uppercase px-2 py-0.5 rounded tracking-wider border border-indigo-100">
                      {role}
                    </span>
                  )) || <span className="text-slate-400 font-black text-xs">—</span>}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT — AUDIT / STAFF PANEL */}
        <div className="lg:col-span-2 bg-white rounded-[2rem] p-6 sm:p-8 shadow-2xl border border-slate-200/60 flex flex-col">

          {/* Panel header */}
          <div className="flex items-center justify-between mb-6 border-b border-slate-50 pb-6 gap-4">
            <div className="flex items-center gap-4 min-w-0">
              <ShieldCheck size={22} className="text-indigo-600 shrink-0"/>
              <div className="min-w-0">
                <h4 className="font-black text-xs uppercase tracking-widest text-slate-900 truncate">
                  {activeSubTab === 'active-sessions' ? 'Active Logged-In Users'
                    : activeSubTab === 'all-sessions' ? 'Security & Session Audits'
                    : activeSubTab === 'activity'     ? 'User Activity Feed'
                    : 'Staff User Management'}
                </h4>
                <p className="text-[9px] text-slate-400 font-bold mt-0.5 truncate">
                  {activeSubTab === 'active-sessions' ? 'Users currently connected to the Varner OS console'
                    : activeSubTab === 'all-sessions' ? 'Recent system logins and event logs'
                    : activeSubTab === 'activity'     ? 'Live updates of inventory edits, additions, and deletions'
                    : 'Invite and manage yard staff accounts'}
                </p>
              </div>
            </div>

            {/* Refresh / Invite buttons */}
            {activeSubTab === 'staff-users' ? (
              <div className="flex gap-2 shrink-0">
                <button onClick={loadStaff} disabled={isStaffLoading}
                  className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all font-black text-[10px] uppercase tracking-wider flex items-center gap-1.5 border border-indigo-100"
                  aria-label="Refresh staff list">
                  {isStaffLoading ? <Loader2 size={12} className="animate-spin"/> : <RotateCcw size={12}/>}
                </button>
                <button onClick={() => { setShowInviteForm(v => !v); setInviteResult(null); }}
                  className="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-black text-[10px] uppercase tracking-wider transition-all shadow-md">
                  {showInviteForm ? <X size={13}/> : <UserPlus size={13}/>}
                  {showInviteForm ? 'Cancel' : 'Invite Staff'}
                </button>
              </div>
            ) : (
              <button
                onClick={() => {
                  if (activeSubTab === 'activity') loadActivity();
                  else loadSessions(activeSubTab === 'active-sessions');
                }}
                disabled={isLoading || isActivityLoading}
                className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all font-black text-[10px] uppercase tracking-wider flex items-center gap-1.5 border border-indigo-100 shrink-0">
                {isLoading || isActivityLoading ? <Loader2 size={12} className="animate-spin"/> : <RotateCcw size={12}/>}
                Refresh
              </button>
            )}
          </div>

          {/* Sub-tab toggles */}
          <div className="flex gap-2 mb-6 border-b border-slate-100 pb-5 flex-wrap">
            {[
              { key: 'active-sessions', label: 'Logged In',     action: () => { setActiveSubTab('active-sessions'); loadSessions(true); } },
              { key: 'all-sessions',   label: 'Session History', action: () => { setActiveSubTab('all-sessions');   loadSessions(false); } },
              { key: 'activity',       label: 'Activity Log',    action: () => { setActiveSubTab('activity');       loadActivity(); } },
              { key: 'staff-users',    label: 'Staff Users',     action: switchToStaff },
            ].map(({ key, label, action }) => (
              <button key={key} onClick={action}
                className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${
                  activeSubTab === key
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-md'
                    : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'
                }`}>
                {label}
              </button>
            ))}
          </div>

          {/* ── STAFF USERS PANEL ────────────────────────────────────────── */}
          {activeSubTab === 'staff-users' && (
            <div className="flex flex-col gap-4">

              {/* Invite result banner */}
              {inviteResult && (
                <div className={`flex items-start gap-3 p-4 rounded-2xl border text-sm font-bold ${
                  inviteResult.type === 'success'
                    ? 'bg-green-50 border-green-150 text-green-800'
                    : 'bg-red-50 border-red-200 text-red-800'
                }`}>
                  {inviteResult.type === 'success'
                    ? <CheckCircle size={16} className="shrink-0 mt-0.5 text-green-600"/>
                    : <AlertCircle size={16} className="shrink-0 mt-0.5 text-red-500"/>}
                  <span>{inviteResult.msg}</span>
                  <button onClick={() => setInviteResult(null)} aria-label="Dismiss invite result" className="ml-auto text-current opacity-50 hover:opacity-100"><X size={14}/></button>
                </div>
              )}

              {/* Invite form */}
              {showInviteForm && (
                <form onSubmit={handleInvite}
                  className="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-5 space-y-4 animate-in slide-in-from-top-3 duration-300">
                  <p className="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-1">New Staff Account</p>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">First Name</label>
                      <input required value={inviteForm.first_name}
                        onChange={e => setInviteForm(f => ({ ...f, first_name: e.target.value }))}
                        placeholder="Jane"
                        className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-800 bg-white focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"/>
                    </div>
                    <div>
                      <label className="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Last Name</label>
                      <input required value={inviteForm.last_name}
                        onChange={e => setInviteForm(f => ({ ...f, last_name: e.target.value }))}
                        placeholder="Smith"
                        className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-800 bg-white focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"/>
                    </div>
                  </div>
                  <div>
                    <label className="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
                    <input required type="email" value={inviteForm.email}
                      onChange={e => setInviteForm(f => ({ ...f, email: e.target.value }))}
                      placeholder="jane@varnerequipment.com"
                      className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-800 bg-white focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"/>
                  </div>
                  <div>
                    <label className="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Role</label>
                    <select value={inviteForm.role}
                      onChange={e => setInviteForm(f => ({ ...f, role: e.target.value }))}
                      className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-800 bg-white focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                      <option value="editor">Sales Staff (Editor)</option>
                      <option value="administrator">Administrator</option>
                    </select>
                  </div>
                  <button type="submit" disabled={isInviting}
                    className="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white py-3 rounded-xl font-black uppercase tracking-wider text-xs transition-all shadow-md disabled:opacity-60">
                    {isInviting ? <Loader2 size={14} className="animate-spin"/> : <Mail size={14}/>}
                    {isInviting ? 'Sending Invite…' : 'Send Invite Email'}
                  </button>
                </form>
              )}

              {/* Staff list */}
              {isStaffLoading && staffList.length === 0 ? (
                <div className="py-16 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center gap-3">
                  <Loader2 className="animate-spin text-indigo-600" size={24}/>
                  Loading staff...
                </div>
              ) : staffList.length === 0 ? (
                <div className="py-16 text-center text-slate-400 font-black uppercase text-xs tracking-widest">
                  No staff accounts found.
                </div>
              ) : (
                <div className="space-y-3 max-h-[420px] overflow-y-auto pr-1 no-scrollbar">
                  {staffList.map(u => (
                    <div key={u.id}
                      className="flex items-center gap-4 p-4 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm">
                      {/* Avatar */}
                      <div className={`w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs shrink-0 ${
                        u.is_current
                          ? 'bg-indigo-600 text-white'
                          : 'bg-slate-100 text-slate-500'
                      }`}>
                        {u.initials}
                      </div>

                      {/* Info */}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <span className="text-sm font-black text-slate-800 truncate">{u.display_name}</span>
                          {u.is_current && (
                            <span className="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600 border border-indigo-200">You</span>
                          )}
                          {u.roles.map(r => (
                            <span key={r} className={`text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border ${ROLE_COLORS[r] || 'bg-slate-100 text-slate-600 border-slate-200'}`}>
                              {ROLE_LABELS[r] || r}
                            </span>
                          ))}
                        </div>
                        <p className="text-[10px] text-slate-400 font-bold mt-0.5 truncate">{u.email}</p>
                      </div>

                      {/* Delete */}
                      {!u.is_current && (
                        confirmDeleteId === u.id ? (
                          <div className="flex items-center gap-2 shrink-0">
                            <span className="text-[10px] font-black text-red-600 uppercase tracking-wider">Confirm?</span>
                            <button onClick={() => handleDeleteStaff(u.id)} disabled={deletingId === u.id}
                              className="px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white text-[10px] font-black uppercase tracking-wider rounded-lg transition-all">
                              {deletingId === u.id ? <Loader2 size={10} className="animate-spin"/> : 'Remove'}
                            </button>
                            <button onClick={() => setConfirmDeleteId(null)}
                              className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-black uppercase tracking-wider rounded-lg transition-all">
                              Cancel
                            </button>
                          </div>
                        ) : (
                          <button onClick={() => setConfirmDeleteId(u.id)}
                            className="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all shrink-0"
                            aria-label="Delete staff member">
                            <Trash2 size={14}/>
                          </button>
                        )
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* ── ACTIVITY / SESSIONS (existing) ──────────────────────────── */}
          {activeSubTab !== 'staff-users' && (
            <>
              {activeSubTab === 'activity' ? (
                isActivityLoading && activityList.length === 0 ? (
                  <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
                    <Loader2 className="animate-spin text-indigo-600" size={24}/> Loading activity log...
                  </div>
                ) : activityList.length === 0 ? (
                  <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest">No recent user activity recorded.</div>
                ) : (
                  <div className="space-y-4 max-h-[480px] overflow-y-auto pr-1 no-scrollbar">
                    {activityList.map((act) => {
                      let timeStr = 'Unknown';
                      try { if (act.created_at) timeStr = new Date(act.created_at.replace(/-/g, '/')).toLocaleString(); } catch (e) {}
                      let badge = { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200', label: 'ACTION' };
                      if (act.action === 'create')           badge = { bg: 'bg-green-50',  text: 'text-green-700',  border: 'border-green-150',  label: 'CREATE'  };
                      else if (act.action === 'update')      badge = { bg: 'bg-blue-50',   text: 'text-blue-700',   border: 'border-blue-150',   label: 'EDIT'    };
                      else if (act.action === 'delete')      badge = { bg: 'bg-red-50',    text: 'text-red-700',    border: 'border-red-150',    label: 'DELETE'  };
                      else if (act.action === 'restore')     badge = { bg: 'bg-amber-50',  text: 'text-amber-700',  border: 'border-amber-150',  label: 'RESTORE' };
                      else if (act.action === 'permanent_delete') badge = { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-150',  label: 'PURGE'   };
                      const hasDiff  = act.action === 'update' && act.details?.diff && Object.keys(act.details.diff).length > 0;
                      const isExpanded = expandedActivityId === act.id;
                      return (
                        <div key={act.id} className="flex flex-col p-5 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm gap-3">
                          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <div className="flex items-center gap-4">
                              <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs shrink-0">{act.initials || '??'}</div>
                              <div>
                                <div className="flex items-center gap-2 flex-wrap">
                                  <span className="text-sm font-black text-slate-800">{act.display_name || 'System'}</span>
                                  <span className="text-[10px] text-slate-400 font-bold">({timeStr})</span>
                                </div>
                                <div className="text-[10px] font-bold text-slate-500 mt-0.5">
                                  {act.action !== 'permanent_delete' ? (
                                    <button onClick={() => { handleFullEdit(act.post_id); onNav('inventory'); }} className="text-indigo-600 hover:text-indigo-800 hover:underline text-left font-black">
                                      {act.post_title || `Unit ID #${act.post_id}`}
                                    </button>
                                  ) : <span className="text-slate-500 font-bold">{act.post_title || `Unit ID #${act.post_id}`}</span>}
                                </div>
                              </div>
                            </div>
                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border ${badge.bg} ${badge.text} ${badge.border}`}>{badge.label}</span>
                          </div>
                          <div className="text-xs font-medium text-slate-600 bg-white/40 p-3 rounded-xl border border-slate-100">{act.summary}</div>
                          {hasDiff && (
                            <div>
                              <button onClick={() => setExpandedActivityId(isExpanded ? null : act.id)} className="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-all">
                                {isExpanded ? <ChevronUp size={12}/> : <ChevronDown size={12}/>}
                                {isExpanded ? 'Hide Changed Fields' : 'View Changed Fields'}
                              </button>
                              {isExpanded && (
                                <div className="mt-3 p-4 bg-white rounded-xl border border-slate-200/80 space-y-2 animate-in slide-in-from-top-2 duration-300">
                                  {Object.entries(act.details.diff).map(([field, val]) => (
                                    <div key={field} className="flex justify-between items-start gap-4 py-2 border-b border-slate-100 last:border-b-0 text-[10px]">
                                      <span className="font-black text-slate-400 shrink-0">{field.replace(/_/g, ' ').toUpperCase()}</span>
                                      <span className="font-bold text-slate-800 break-all text-right flex-1 flex justify-end gap-2 flex-wrap items-center">
                                        <span className="text-red-500 line-through bg-red-50 px-1.5 py-0.5 rounded font-mono">{String(val.from || '—')}</span>
                                        <span className="text-slate-300">➔</span>
                                        <span className="text-green-600 font-black bg-green-50 px-1.5 py-0.5 rounded font-mono">{String(val.to || '—')}</span>
                                      </span>
                                    </div>
                                  ))}
                                </div>
                              )}
                            </div>
                          )}
                        </div>
                      );
                    })}
                  </div>
                )
              ) : (
                isLoading && sessionList.length === 0 ? (
                  <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
                    <Loader2 className="animate-spin text-indigo-600" size={24}/> Loading sessions...
                  </div>
                ) : sessionList.length === 0 ? (
                  <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest">
                    {activeSubTab === 'active-sessions' ? 'No active users logged in.' : 'No session logs found.'}
                  </div>
                ) : (
                  <div className="space-y-4 max-h-[480px] overflow-y-auto pr-1 no-scrollbar">
                    {sessionList.map((session) => {
                      const isActive = !session.logout_at;
                      let loginTimeStr = 'Unknown';
                      try { if (session.login_at) loginTimeStr = new Date(session.login_at.replace(/-/g, '/')).toLocaleString(); } catch (e) {}
                      let lastActiveTimeStr = '';
                      try { if (session.last_activity_at) lastActiveTimeStr = new Date(session.last_activity_at.replace(/-/g, '/')).toLocaleString(); } catch (e) {}
                      let endedStr = '';
                      try { if (!isActive && session.logout_at) endedStr = new Date(session.logout_at.replace(/-/g, '/')).toLocaleString(); } catch (e) {}
                      const ua = session.user_agent || '';
                      let device = 'Desktop / Browser';
                      if (/mobile/i.test(ua))      device = /iphone/i.test(ua) ? 'Apple iPhone' : 'Android Device';
                      else if (/ipad/i.test(ua))   device = 'iPad Tablet';
                      return (
                        <div key={session.id} className="flex flex-col sm:flex-row justify-between items-start sm:items-center p-5 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm group">
                          <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs shrink-0">{session.initials || '??'}</div>
                            <div className="min-w-0">
                              <div className="flex items-center gap-2">
                                <span className="text-sm font-black text-slate-800 truncate">{session.display_name || 'System User'}</span>
                                <span className="text-[10px] text-slate-400 font-bold truncate">({session.ip || 'Unknown IP'})</span>
                              </div>
                              <div className="text-[10px] text-slate-400 font-bold mt-0.5 truncate flex items-center gap-1.5 flex-wrap">
                                <span>{device}</span>
                                <span className="text-slate-300">•</span>
                                <span>Logged in: {loginTimeStr}</span>
                                {isActive && lastActiveTimeStr && <><span className="text-slate-300">•</span><span className="text-indigo-600 font-black">Active: {lastActiveTimeStr}</span></>}
                              </div>
                              {!isActive && endedStr && <div className="text-[9px] text-slate-400 mt-0.5 font-bold">Logged out: {endedStr} {session.ended_reason ? `(${session.ended_reason})` : ''}</div>}
                            </div>
                          </div>
                          <div className="mt-3 sm:mt-0 self-end sm:self-center shrink-0">
                            {isActive ? (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-[9px] font-black uppercase tracking-wider border border-green-150">
                                <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Active Session
                              </span>
                            ) : (
                              <span className="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-black uppercase tracking-wider border border-slate-200">Ended</span>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};
