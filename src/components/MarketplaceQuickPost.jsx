import React, { useState } from 'react';
import JSZip from 'jszip';
import { Download, Image as ImageIcon, Loader2, CheckCircle2, Facebook, Copy, Check, ExternalLink } from 'lucide-react';
import { apiFetch } from '../utils/api';

const toJpegBlob = (blob, quality = 0.9) =>
  new Promise((resolve, reject) => {
    const url = URL.createObjectURL(blob);
    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = img.naturalWidth;
      canvas.height = img.naturalHeight;
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0);
      URL.revokeObjectURL(url);
      canvas.toBlob(
        (out) => (out ? resolve(out) : reject(new Error('JPEG conversion failed'))),
        'image/jpeg',
        quality
      );
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('Image could not be loaded for conversion'));
    };
    img.src = url;
  });

const htmlToPlainText = (html) => {
  if (!html) return '';
  let s = String(html);
  if (/&(lt|gt|amp|quot|#\d+);/i.test(s)) {
    const ta = document.createElement('textarea');
    ta.innerHTML = s;
    s = ta.value;
  }
  s = s.replace(/<\/(p|div|li|h[1-6])>/gi, '\n').replace(/<br\s*\/?>/gi, '\n');
  const doc = new DOMParser().parseFromString(s, 'text/html');
  const text = doc.body.textContent || '';
  return text.replace(/\n{3,}/g, '\n\n').replace(/[ \t]+\n/g, '\n').trim();
};

const CopyRow = ({ label, value }) => {
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    if (!value) return;
    try {
      await navigator.clipboard.writeText(value);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Copy failed:', err);
    }
  };

  return (
    <div className="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 group hover:border-blue-200 transition-all">
      <div className="min-w-0 flex-1">
        <p className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{label}</p>
        <p className="text-sm font-bold text-slate-900 truncate">{value || '\u2014'}</p>
      </div>
      <button
        onClick={handleCopy}
        disabled={!value}
        className={`ml-3 p-2.5 rounded-xl shrink-0 transition-all ${
          copied
            ? 'bg-green-100 text-green-600'
            : 'bg-white text-slate-400 hover:bg-blue-50 hover:text-blue-600 border border-slate-200'
        } disabled:opacity-30 disabled:cursor-not-allowed`}
        aria-label={`Copy ${label}`}
      >
        {copied ? <Check size={16} /> : <Copy size={16} />}
      </button>
    </div>
  );
};

export const MarketplaceQuickPost = ({ unitData, onUnitUpdated }) => {
  const [zipState, setZipState] = useState('idle');
  const [zipError, setZipError] = useState('');
  const [posted, setPosted] = useState(Boolean(unitData?.marketplace_posted));
  const [postedDate, setPostedDate] = useState(unitData?.marketplace_posted_date || null);
  const [marking, setMarking] = useState(false);
  const [openedFacebook, setOpenedFacebook] = useState(false);

  React.useEffect(() => {
    setPosted(Boolean(unitData?.marketplace_posted));
    setPostedDate(unitData?.marketplace_posted_date || null);
  }, [unitData?.id, unitData?.marketplace_posted, unitData?.marketplace_posted_date]);

  React.useEffect(() => {
    setZipState('idle');
    setZipError('');
    setOpenedFacebook(false);
  }, [unitData?.id]);

  const images = unitData.images || [];

  const slugify = (s) => (s || 'images').replace(/[^a-zA-Z0-9-_ ]/g, '').replace(/\s+/g, '-').toLowerCase();
  const buildTitle = (d) => `${d.year || ''} ${d.make || ''} ${d.model || ''}`.trim() || d.title || 'inventory-images';

  const downloadPhotos = async () => {
    if (!images.length) return;
    setZipState('working');
    setZipError('');
    try {
      const zip = new JSZip();
      let added = 0;
      await Promise.all(
        images.map(async (url, i) => {
          try {
            const res = await fetch(url);
            if (!res.ok) return;
            const srcBlob = await res.blob();
            const jpeg = await toJpegBlob(srcBlob, 0.9);
            zip.file(`img-${i + 1}.jpg`, jpeg);
            added += 1;
          } catch {}
        })
      );
      if (!added) throw new Error('No photos could be downloaded.');
      const out = await zip.generateAsync({ type: 'blob' });
      const href = URL.createObjectURL(out);
      const a = document.createElement('a');
      a.href = href;
      a.download = `${slugify(buildTitle(unitData))}.zip`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(href);
      setZipState('idle');
    } catch (err) {
      console.error('Photo download failed:', err);
      setZipError(err.message || 'Could not build the photo file.');
      setZipState('error');
    }
  };

  const togglePosted = async (next) => {
    if (!unitData?.id) return;
    setMarking(true);
    try {
      const updated = await apiFetch(`/inventory/${unitData.id}/marketplace-posted`, {
        method: 'POST',
        body: JSON.stringify({ posted: next }),
      });
      setPosted(Boolean(updated?.marketplace_posted));
      setPostedDate(updated?.marketplace_posted_date || null);
      if (onUnitUpdated && updated) onUnitUpdated(updated);
    } catch (err) {
      console.error('Mark-as-posted failed:', err);
    } finally {
      setMarking(false);
    }
  };

  const calculateDays = (dateStr) => {
    if (!dateStr) return null;
    const start = new Date(dateStr.replace(' ', 'T'));
    const today = new Date();
    start.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);
    const diff = today.getTime() - start.getTime();
    return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)));
  };

  const daysPosted = posted && postedDate ? calculateDays(postedDate) : null;

  const getStatusBadge = () => {
    if (!unitData?.id) return null;
    if (!posted || daysPosted === null) {
      return (
        <div className="flex items-center gap-2 bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-800">
          <span className="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)] shrink-0" />
          <span className="text-[9px] font-black uppercase tracking-widest text-slate-400 whitespace-nowrap">Not Posted</span>
        </div>
      );
    }
    
    const isStale = daysPosted >= 30;
    const dotColor = isStale ? 'bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.5)]' : 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]';
    const label = daysPosted === 0 ? 'Posted Today' : `${daysPosted} Day${daysPosted !== 1 ? 's' : ''}`;
    
    return (
      <div className="flex items-center gap-2 bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-800">
        <span className={`w-2 h-2 rounded-full ${dotColor} ${isStale ? 'animate-pulse' : ''} shrink-0`} />
        <span className="text-[9px] font-black uppercase tracking-widest text-slate-300 whitespace-nowrap">
          {label}
        </span>
      </div>
    );
  };

  return (
    <div className="bg-white rounded-[2rem] overflow-hidden shadow-2xl border border-slate-200/60 flex flex-col">
      <div className="bg-slate-950 p-6 text-white flex items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <div className="bg-blue-600 p-2.5 rounded-xl shrink-0"><Facebook size={20} fill="white" /></div>
          <div>
            <h4 className="font-black text-sm uppercase tracking-tight leading-tight mb-1">
              Marketplace<br />Quick Post
            </h4>
            <p className="text-[8px] text-slate-500 uppercase font-black tracking-widest">
              {images.length} image{images.length !== 1 ? 's' : ''} ready
            </p>
          </div>
        </div>
        <div className="shrink-0 hidden sm:block">
          {getStatusBadge()}
        </div>
      </div>
      {/* Mobile Badge fallback */}
      <div className="sm:hidden px-6 pt-4 pb-2 bg-slate-50 border-b border-slate-100 flex justify-center">
         {getStatusBadge()}
      </div>
      <div className="p-6 space-y-6 bg-white text-slate-900">
        {/* Step 1 — Download photos */}
        <div>
          <p className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
            <span className="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[8px]">1</span>
            Download Photos
          </p>
          {images.length > 0 ? (
            <div className="grid grid-cols-3 gap-2 mb-3">
              {images.slice(0, 6).map((url, i) => (
                <div key={i} className="aspect-square bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                  <img
                    src={url}
                    alt={`${unitData.title || 'image'} ${i + 1}`}
                    className="w-full h-full object-cover"
                    onError={e => { e.target.style.display = 'none'; }}
                  />
                </div>
              ))}
              {images.length > 6 && (
                <div className="aspect-square bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 font-black text-sm">
                  +{images.length - 6}
                </div>
              )}
            </div>
          ) : (
            <div className="flex flex-col items-center justify-center py-6 text-slate-300 gap-2 mb-3">
              <ImageIcon size={32} />
              <p className="text-[9px] font-black uppercase tracking-widest">No images added yet</p>
            </div>
          )}
          <button
            onClick={downloadPhotos}
            disabled={zipState === 'working' || images.length === 0}
            className="w-full bg-slate-950 text-white py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-black transition-all active:scale-95 shadow-lg shadow-slate-200 disabled:opacity-40 disabled:cursor-not-allowed leading-none"
          >
            {zipState === 'working' ? (
              <><Loader2 size={14} className="animate-spin" /> Zipping Images...</>
            ) : zipState === 'error' ? (
              <><span className="text-red-400">Failed</span></>
            ) : (
              <><Download size={14} /> Download All as ZIP</>
            )}
          </button>
          {zipError && (
            <p className="text-[9px] font-bold text-red-500 text-center mt-2">{zipError}</p>
          )}
        </div>

        {/* Step 2 — Open Facebook */}
        <div>
          <p className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
            <span className="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[8px]">2</span>
            Open Facebook Marketplace
          </p>
          <button
            type="button"
            onClick={(e) => {
              e.preventDefault();
              setOpenedFacebook(true);
              window.open(
                'https://www.facebook.com/marketplace/create/item',
                'fbMarketplaceWindow',
                'width=1200,height=800,scrollbars=yes,resizable=yes,left=100,top=100'
              );
            }}
            className="w-full bg-[#0866FF] text-white !hover:text-white py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-[#0752d4] hover:text-white transition-all active:scale-95 shadow-lg shadow-blue-100 leading-none"
          >
            <ExternalLink size={18} /> Open Facebook Marketplace
          </button>
        </div>

        {/* Step 3 — Copy listing fields */}
        <div>
          <p className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
            <span className="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[8px]">3</span>
            Copy Listing Fields
          </p>
          <div className="space-y-2">
            <CopyRow label="Title" value={unitData.title} />
            <CopyRow label="Price" value={unitData.price ? `$${Number(unitData.price).toLocaleString()}` : ''} />
            <CopyRow label="Description" value={htmlToPlainText(unitData.description)} />
          </div>
        </div>

        {/* Posted status */}
        <div className="pt-5 border-t border-slate-100">
          {posted ? (
            <div className="flex flex-col items-center">
              <div className="flex items-center gap-3 p-4 w-full bg-green-50 border-2 border-green-100 rounded-[1.25rem]">
                <CheckCircle2 size={20} className="text-green-600 shrink-0" />
                <div>
                  <p className="text-[11px] font-black text-green-800 uppercase tracking-widest leading-none mb-1">
                    Posted to Marketplace
                  </p>
                  {postedDate && (
                    <p className="text-[9px] font-black text-green-500 uppercase tracking-widest">
                      {new Date(postedDate.replace(' ', 'T')).toLocaleDateString('en-US', { timeZone: 'America/Denver', month: 'short', day: 'numeric', year: 'numeric' })}
                    </p>
                  )}
                </div>
              </div>
              <button
                type="button"
                onClick={() => togglePosted(false)}
                disabled={marking}
                className="mt-4 text-[10px] font-black text-slate-400 uppercase tracking-widest underline underline-offset-2 hover:text-slate-600 disabled:opacity-50 transition-colors"
              >
                Undo
              </button>
            </div>
          ) : (
            <button
              type="button"
              onClick={() => togglePosted(true)}
              disabled={marking || !unitData?.id}
              className={`w-full py-5 rounded-[1.25rem] font-black text-[12px] uppercase tracking-[0.15em] flex items-center justify-center gap-3 transition-all active:scale-95 shadow-xl
                ${!unitData?.id 
                  ? 'bg-slate-50 text-slate-400 border-2 border-slate-200 cursor-not-allowed'
                  : openedFacebook
                    ? 'bg-green-600 text-white hover:bg-green-700 shadow-green-100 border-b-4 border-green-800 ring-4 ring-green-200 animate-pulse'
                    : 'bg-white text-slate-700 border-2 border-slate-200 hover:border-green-300 hover:text-green-700 shadow-slate-100'}`}
            >
              {marking
                ? <><Loader2 size={18} className="animate-spin" /> Saving\u2026</>
                : <><CheckCircle2 size={18} /> {unitData?.id ? 'Mark as Posted to Marketplace' : 'Save Unit First to Post'}</>}
            </button>
          )}
        </div>
      </div>
    </div>
  );
};
