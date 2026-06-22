import React, { useState, useEffect } from 'react';
import {
  Smartphone, Zap, Loader2, AlertCircle, ShieldCheck, User, Lock
} from 'lucide-react';
import { apiFetch } from '../utils/api';

const InstallBanner = () => {
  const [isStandalone, setIsStandalone] = useState(true);
  useEffect(() => {
    const isM = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    setIsStandalone(!!isM);
  }, []);
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

export const PwaLoginGate = ({ mobileToken, setMobileToken, toast, onAuthenticated }) => {
  const [tokenInput, setTokenInput] = useState('');
  const [authError, setAuthError] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [loginMode, setLoginMode] = useState('credentials');
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

  useEffect(() => {
    if (mobileToken) {
      verifyAndSaveToken(mobileToken);
    }
  }, []);

  const verifyAndSaveToken = async (tokenToVerify, isManualSubmit = false) => {
    setIsVerifying(true);
    setAuthError('');
    try {
      localStorage.setItem('varner_mobile_token', tokenToVerify);
      localStorage.setItem('varner_mobile_token_created_at', String(Date.now()));
      // Use /me (lightweight) instead of /inventory (heavy — loads ALL equipment)
      // to verify the token. The full inventory loads after auth via useInventory.
      await apiFetch('/me');
      setMobileToken(tokenToVerify);
      onAuthenticated();
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

  const handleLoginSubmit = (e) => {
    e.preventDefault();
    const cleaned = tokenInput.trim().toUpperCase();
    if (!cleaned) return;
    verifyAndSaveToken(cleaned, true);
  };

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
      await verifyAndSaveToken(data.token, true);
    } catch (err) {
      setAuthError('Network error. Check your connection and try again.');
    } finally {
      setIsVerifying(false);
    }
  };

  if (mobileToken) return null;

  return (
    <div className="flex flex-col min-h-screen bg-[#0a0a0b] text-white font-sans selection:bg-red-500/30">
      {toast && (
        <div className="fixed top-6 left-6 right-6 z-[9999] px-6 py-4 rounded-2xl font-black text-sm text-center shadow-2xl bg-green-600 text-white animate-in slide-in-from-top-4">
          {toast.msg}
        </div>
      )}
      <InstallBanner />
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
};
