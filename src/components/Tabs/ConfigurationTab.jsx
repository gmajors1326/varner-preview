import React, { useState } from 'react';
import {
  Sliders, Users, ShieldCheck, RotateCcw, Loader2, ChevronUp, ChevronDown
} from 'lucide-react';

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
  const [activeSubTab, setActiveSubTab] = useState('active-sessions');
  const [expandedActivityId, setExpandedActivityId] = useState(null);

  return (
    <div className="space-y-6 sm:space-y-8 animate-in fade-in duration-500 text-slate-900 pb-16">
      
      {/* HEADER WELCOME BANNER */}
      <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-10 text-white shadow-2xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10">
          <h3 className="text-xl sm:text-3xl font-black tracking-tighter mb-2 uppercase leading-none text-white">System Settings & Audit</h3>
          <p className="text-indigo-400 font-bold uppercase tracking-[0.3em] text-[10px]">
            Current User ID: {currentUser ? `#${currentUser.id}` : 'Loading...'}
          </p>
        </div>
        <Sliders size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* TWO-COLUMN GRID */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        
        {/* LEFT COLUMN: USER PROFILE DETAILS */}
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
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">User ID</span>
                <span className="font-black text-slate-800">{currentUser?.id || '—'}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Role</span>
                <div className="flex gap-1">
                  {currentUser?.roles ? (
                    currentUser.roles.map(role => (
                      <span key={role} className="bg-indigo-50 text-indigo-700 text-[9px] font-black uppercase px-2 py-0.5 rounded tracking-wider border border-indigo-100">
                        {role}
                      </span>
                    ))
                  ) : (
                    <span className="text-slate-400 font-black text-xs">—</span>
                  )}
                </div>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">First Name</span>
                <span className="font-black text-slate-800">{currentUser?.first_name || '—'}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Last Name</span>
                <span className="font-black text-slate-800">{currentUser?.last_name || '—'}</span>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT COLUMN: SECURITY & AUDIT LOG */}
        <div className="lg:col-span-2 bg-white rounded-[2rem] p-6 sm:p-8 shadow-2xl border border-slate-200/60 flex flex-col">
          <div className="flex items-center justify-between mb-6 border-b border-slate-50 pb-6 gap-4">
            <div className="flex items-center gap-4 min-w-0">
              <ShieldCheck size={22} className="text-indigo-600 shrink-0"/>
              <div className="min-w-0">
                <h4 className="font-black text-xs uppercase tracking-widest text-slate-900 truncate">
                  {activeSubTab === 'active-sessions' ? 'Active Logged In Users' : activeSubTab === 'all-sessions' ? 'Security & Session Audits' : 'User Activity Feed'}
                </h4>
                <p className="text-[9px] text-slate-400 font-bold mt-0.5 truncate">
                  {activeSubTab === 'active-sessions' ? 'Users currently connected to the Varner OS console' : activeSubTab === 'all-sessions' ? 'Recent system logins and event logs' : 'Live updates of inventory edits, additions, and deletions'}
                </p>
              </div>
            </div>
            <button
              onClick={() => {
                if (activeSubTab === 'activity') {
                  loadActivity();
                } else {
                  loadSessions(activeSubTab === 'active-sessions');
                }
              }}
              className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all font-black text-[10px] uppercase tracking-wider flex items-center gap-1.5 border border-indigo-100 shrink-0"
              disabled={isLoading || isActivityLoading}
            >
              {isLoading || isActivityLoading ? <Loader2 size={12} className="animate-spin" /> : <RotateCcw size={12} />}
              Refresh
            </button>
          </div>

          {/* TOGGLE BUTTONS */}
          <div className="flex gap-2 mb-6 border-b border-slate-100 pb-5 flex-wrap">
            <button 
              onClick={() => { setActiveSubTab('active-sessions'); loadSessions(true); }}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${activeSubTab === 'active-sessions' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'}`}
            >
              Logged In Users
            </button>
            <button 
              onClick={() => { setActiveSubTab('all-sessions'); loadSessions(false); }}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${activeSubTab === 'all-sessions' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'}`}
            >
              Session History
            </button>
            <button 
              onClick={() => { setActiveSubTab('activity'); loadActivity(); }}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${activeSubTab === 'activity' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-500 border-slate-200/60 hover:bg-slate-100'}`}
            >
              Live Activity Log
            </button>
          </div>

          {/* TAB CONTENTS */}
          {activeSubTab === 'activity' ? (
            isActivityLoading && activityList.length === 0 ? (
              <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
                <Loader2 className="animate-spin text-indigo-600" size={24} />
                Loading activity log...
              </div>
            ) : activityList.length === 0 ? (
              <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest">
                No recent user activity recorded.
              </div>
            ) : (
              <div className="space-y-4 max-h-[480px] overflow-y-auto pr-1 no-scrollbar">
                {activityList.map((act) => {
                  let timeStr = 'Unknown';
                  try {
                    if (act.created_at) {
                      const d = new Date(act.created_at.replace(/-/g, '/'));
                      timeStr = d.toLocaleString();
                    }
                  } catch (err) {}

                  let actionBadge = { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200', label: 'ACTION' };
                  if (act.action === 'create') actionBadge = { bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-150', label: 'CREATE' };
                  else if (act.action === 'update') actionBadge = { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-150', label: 'EDIT' };
                  else if (act.action === 'delete') actionBadge = { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-150', label: 'DELETE' };
                  else if (act.action === 'restore') actionBadge = { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-150', label: 'RESTORE' };
                  else if (act.action === 'permanent_delete') actionBadge = { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-150', label: 'PURGE' };

                  const hasDiff = act.action === 'update' && act.details && act.details.diff && Object.keys(act.details.diff).length > 0;
                  const isExpanded = expandedActivityId === act.id;

                  return (
                    <div key={act.id} className="flex flex-col p-5 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm gap-3">
                      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div className="flex items-center gap-4">
                          <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs shrink-0">
                            {act.initials || '??'}
                          </div>
                          <div>
                            <div className="flex items-center gap-2 flex-wrap">
                              <span className="text-sm font-black text-slate-800">{act.display_name || 'System'}</span>
                              <span className="text-[10px] text-slate-400 font-bold">({timeStr})</span>
                            </div>
                            <div className="text-[10px] font-bold text-slate-500 mt-0.5">
                              {act.action !== 'permanent_delete' ? (
                                <button 
                                  onClick={() => {
                                    handleFullEdit(act.post_id);
                                    onNav('inventory');
                                  }}
                                  className="text-indigo-600 hover:text-indigo-800 hover:underline text-left font-black"
                                >
                                  {act.post_title || `Unit ID #${act.post_id}`}
                                </button>
                              ) : (
                                <span className="text-slate-500 font-bold">{act.post_title || `Unit ID #${act.post_id}`}</span>
                              )}
                            </div>
                          </div>
                        </div>
                        <div className="shrink-0 flex items-center gap-2">
                          <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border ${actionBadge.bg} ${actionBadge.text} ${actionBadge.border}`}>
                            {actionBadge.label}
                          </span>
                        </div>
                      </div>
                      
                      <div className="text-xs font-medium text-slate-600 bg-white/40 p-3 rounded-xl border border-slate-100">
                        {act.summary}
                      </div>

                      {hasDiff && (
                        <div>
                          <button
                            onClick={() => setExpandedActivityId(isExpanded ? null : act.id)}
                            className="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-all"
                          >
                            {isExpanded ? <ChevronUp size={12}/> : <ChevronDown size={12}/>}
                            {isExpanded ? 'Hide Changed Fields' : 'View Changed Fields'}
                          </button>

                          {isExpanded && (
                            <div className="mt-3 p-4 bg-white rounded-xl border border-slate-200/80 space-y-2 animate-in slide-in-from-top-2 duration-300">
                              {Object.entries(act.details.diff).map(([field, val]) => {
                                const cleanField = field.replace(/_/g, ' ').toUpperCase();
                                return (
                                  <div key={field} className="flex justify-between items-start gap-4 py-2 border-b border-slate-100 last:border-b-0 text-[10px]">
                                    <span className="font-black text-slate-400 shrink-0">{cleanField}</span>
                                    <span className="font-bold text-slate-800 break-all text-right flex-1 flex justify-end gap-2 flex-wrap items-center">
                                      <span className="text-red-500 line-through bg-red-50 px-1.5 py-0.5 rounded font-mono">{String(val.from || '—')}</span>
                                      <span className="text-slate-300">➔</span>
                                      <span className="text-green-600 font-black bg-green-50 px-1.5 py-0.5 rounded font-mono">{String(val.to || '—')}</span>
                                    </span>
                                  </div>
                                );
                              })}
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
                <Loader2 className="animate-spin text-indigo-600" size={24} />
                Loading sessions...
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
                  try {
                    if (session.login_at) {
                      const d = new Date(session.login_at.replace(/-/g, '/'));
                      loginTimeStr = d.toLocaleString();
                    }
                  } catch (err) {}

                  let lastActiveTimeStr = '';
                  try {
                    if (session.last_activity_at) {
                      const d = new Date(session.last_activity_at.replace(/-/g, '/'));
                      lastActiveTimeStr = d.toLocaleString();
                    }
                  } catch (err) {}

                  let endedStr = '';
                  if (!isActive && session.logout_at) {
                    try {
                      const d = new Date(session.logout_at.replace(/-/g, '/'));
                      endedStr = d.toLocaleString();
                    } catch (err) {}
                  }

                  let device = 'Desktop / Browser';
                  const ua = session.user_agent || '';
                  if (/mobile/i.test(ua)) {
                    device = 'Mobile Device';
                    if (/iphone/i.test(ua)) device = 'Apple iPhone';
                    else if (/android/i.test(ua)) device = 'Android Device';
                  } else if (/ipad/i.test(ua)) {
                    device = 'iPad Tablet';
                  }

                  return (
                    <div key={session.id} className="flex flex-col sm:flex-row justify-between items-start sm:items-center p-5 bg-slate-50/50 rounded-2xl border-2 border-white hover:bg-white transition-all shadow-sm group">
                      <div className="flex items-center gap-4 w-full sm:w-auto">
                        <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs shrink-0">
                          {session.initials || '??'}
                        </div>
                        <div className="min-w-0">
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-black text-slate-800 truncate">{session.display_name || 'System User'}</span>
                            <span className="text-[10px] text-slate-400 font-bold truncate">({session.ip || 'Unknown IP'})</span>
                          </div>
                          <div className="text-[10px] text-slate-400 font-bold mt-0.5 truncate flex items-center gap-1.5 flex-wrap">
                            <span>{device}</span>
                            <span className="text-slate-300">•</span>
                            <span>Logged in: {loginTimeStr}</span>
                            {isActive && lastActiveTimeStr && (
                              <>
                                <span className="text-slate-300">•</span>
                                <span className="text-indigo-600 font-black">Active: {lastActiveTimeStr}</span>
                              </>
                            )}
                          </div>
                          {!isActive && endedStr && (
                            <div className="text-[9px] text-slate-400 mt-0.5 font-bold">
                              Logged out: {endedStr} {session.ended_reason ? `(${session.ended_reason})` : ''}
                            </div>
                          )}
                        </div>
                      </div>
                      <div className="mt-3 sm:mt-0 self-end sm:self-center shrink-0">
                        {isActive ? (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-[9px] font-black uppercase tracking-wider border border-green-150">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            Active Session
                          </span>
                        ) : (
                          <span className="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-black uppercase tracking-wider border border-slate-200">
                            Ended
                          </span>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )
          )}
        </div>

      </div>
    </div>
  );
};
