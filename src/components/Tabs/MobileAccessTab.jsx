import React, { useState } from 'react';
import { Smartphone, Loader2, Zap, ScanText, Clock, ExternalLink, AlertCircle } from 'lucide-react';
import { apiFetch } from '../../utils/api';

export const MobileAccessTab = () => {
  const [token, setToken]               = useState(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [expiry, setExpiry]             = useState(null);
  const [qrUrl, setQrUrl]               = useState('');
  const [genError, setGenError]         = useState('');

  const generateToken = async () => {
    setIsGenerating(true);
    setGenError('');
    try {
      const data = await apiFetch('/mobile/token', { method: 'POST' });
      setToken(data.token);
      setQrUrl(data.url);
      setExpiry(new Date(Date.now() + data.expires_in * 1000).toLocaleTimeString());
    } catch (e) {
      setGenError(e.message || 'Failed to generate token. Please try again.');
    } finally {
      setIsGenerating(false);
    }
  };

  return (
    <div className="space-y-8 animate-in fade-in slide-in-from-bottom-6 duration-700">
      <div className="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-12 text-white shadow-2xl relative overflow-hidden border border-slate-700">
        <div className="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 items-center">
          <div>
            <div className="inline-flex items-center gap-2 bg-blue-500/20 text-blue-400 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 border border-blue-500/30">
              <Smartphone size={14}/> Mobile Companion v2.0
            </div>
            <h2 className="text-3xl sm:text-5xl font-black tracking-tighter leading-[0.9] mb-4 sm:mb-6 uppercase text-white">
              Varner <span className="text-blue-500">Mobile</span> Companion
            </h2>
            <p className="text-slate-400 text-lg leading-relaxed mb-8 max-w-md font-medium">
              Provision mobile devices for field inventory audits, photo uploads, and real-time stock scanning.
            </p>

            {/* Error display */}
            {genError && (
              <div className="flex items-start gap-3 bg-red-500/15 border border-red-500/30 rounded-2xl px-4 py-3 mb-4 text-red-300 text-xs font-bold">
                <AlertCircle size={14} className="shrink-0 mt-0.5"/>
                <span>{genError}</span>
              </div>
            )}

            {!token ? (
              <button
                onClick={generateToken}
                disabled={isGenerating}
                className="mt-4 bg-blue-600 hover:bg-blue-500 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-2xl flex items-center gap-3 active:scale-95 disabled:opacity-50"
              >
                {isGenerating ? <Loader2 className="animate-spin"/> : <Zap size={18}/>} Generate Secure Access
              </button>
            ) : (
              <div className="space-y-3 mt-4">
                {/* Primary CTA — tap to launch on this device */}
                <a
                  href={qrUrl}
                  className="flex items-center justify-center gap-3 bg-blue-600 hover:bg-blue-500 active:scale-95 text-white px-8 py-5 rounded-2xl font-black uppercase tracking-[0.15em] text-xs shadow-2xl transition-all w-full text-center"
                >
                  <ExternalLink size={16}/> Launch on This Device
                </a>
                <button
                  onClick={() => { setToken(null); setGenError(''); }}
                  className="w-full text-slate-500 hover:text-white text-[10px] font-black uppercase tracking-widest py-2"
                >
                  Revoke &amp; Reset
                </button>
              </div>
            )}
          </div>
          <div className="flex justify-center lg:justify-end">
            <div className={`bg-white p-8 rounded-[2.5rem] shadow-2xl transition-all duration-700 ${token ? 'scale-100 opacity-100' : 'scale-90 opacity-20 blur-sm'}`}>
              <div className="w-64 h-64 bg-slate-50 rounded-2xl flex items-center justify-center border-2 border-slate-100">
                {token ? (
                  <img src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrUrl)}`} className="w-full h-full p-4" alt="QR"/>
                ) : (
                  <div className="text-center p-8">
                    <ScanText size={48} className="text-slate-200 mx-auto mb-4"/>
                    <p className="text-[10px] font-black uppercase tracking-widest text-slate-300">Token Required</p>
                  </div>
                )}
              </div>
              {token && (
                <div className="mt-6 text-center">
                  <p className="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Scan to hand off to another device</p>
                  <p className="text-xl font-mono font-black text-slate-900 tracking-wider">{token}</p>
                  <div className="mt-4 flex items-center justify-center gap-2 text-amber-500">
                    <Clock size={12}/>
                    <span className="text-[9px] font-black uppercase tracking-widest">Expires at {expiry}</span>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
