import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  Sparkles, Image as ImageIcon, Loader2, Upload, Trash2, Mail, Clock, Plus,
  Briefcase, ChevronUp, ChevronDown, Save, DollarSign, FileText
} from 'lucide-react';
import { apiFetch, uploadFile } from '../../utils/api';
import { InputField, TextAreaField } from '../Common/FormFields';
import { CollapsiblePanel } from '../CollapsiblePanel';

const PlayIcon = ({ className }) => (
  <svg className={className} viewBox="0 0 24 24">
    <path d="M8 5v14l11-7z" fill="currentColor" />
  </svg>
);

export const SettingsTab = ({ showToast }) => {
  const [settings, setSettings] = useState({
    hero_title: '',
    hero_subtitle: '',
    hero_button1_text: '',
    hero_button1_link: '',
    hero_button2_text: '',
    hero_button2_link: '',
    hero_video_url: '',
    support_hub_service_link: '',
    support_hub_parts_link: '',
    support_hub_finance_link: '',
    youtube_tagline: '',
    youtube_title: '',
    youtube_paragraph: '',
    youtube_channel_url: '',
    youtube_video_id: '',
    youtube_custom_thumbnail: '',
    cta_title: '',
    cta_text: '',
    cta_button_text: '',
    cta_button_link: '',
    about_why_choose_us_title: '',
    about_why_choose_us_bullets: [],
    contact_email: '',
    contact_phone: '',
    contact_phone_raw: '',
    contact_address_line1: '',
    contact_address_line2: '',
    contact_map_link: '',
    contact_map_embed_url: '',
    hours_mon_fri: '',
    hours_sat: '',
    hours_sun: '',
    social_facebook: '',
    social_youtube: '',
    social_custom_links: [],
    employment_tagline: '',
    employment_headline: '',
    employment_intro: '',
    employment_jobs: [],
    finance_cards: [],
  });

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isUploadingThumbnail, setIsUploadingThumbnail] = useState(false);
  const thumbnailInputRef = useRef(null);
  const pdfInputRefs = useRef({});
  const logoInputRefs = useRef({});

  const iframeRef = useRef(null);
  const iframeReady = useRef(false);
  const pendingScroll = useRef(null);

  const [pages, setPages] = useState([]);
  const [pageTemplates, setPageTemplates] = useState([]);
  const [isLoadingPages, setIsLoadingPages] = useState(false);

  const [openSections, setOpenSections] = useState({
    hero: false,
    support: false,
    youtube: false,
    contact: false,
    hours: false,
    about: false,
    careers: false,
    finance: false,
    pages: false,
  });

  // Section key → anchor ID on the live site (must match id= in index.php)
  const SECTION_ANCHORS = {
    hero:    'hero-parallax',
    support: 'section-support',
    youtube: 'section-youtube',
    contact: 'varner-map',       // contact/map is in footer.php
    hours:   'varner-map',       // same section — hours live near the map
    about:   'section-cta',
    careers: 'section-cta',     // careers page is separate; scroll to CTA as closest
    finance: 'applications',    // section id in page-finance.php
    pages:   '',
  };

  const scrollIframeTo = (anchor) => {
    if (!anchor || !iframeRef.current) return;
    if (iframeReady.current) {
      try {
        iframeRef.current.contentWindow?.postMessage(
          { type: 'varner_scroll_to', anchor },
          '*'
        );
      } catch (e) { /* cross-origin — ignore */ }
    } else {
      pendingScroll.current = anchor; // fire it once iframe finishes loading
    }
  };

  const toggleSection = (key) => {
    setOpenSections(prev => {
      const isOpening = !prev[key];
      if (isOpening) scrollIframeTo(SECTION_ANCHORS[key]);
      return { ...prev, [key]: isOpening };
    });
  };

  const [previewUrl, setPreviewUrl] = useState('');

  useEffect(() => {
    const fetchSettings = async () => {
      setIsLoading(true);
      try {
        const data = await apiFetch('/settings');
        setSettings(data);
      } catch (err) {
        showToast('Failed to load settings: ' + err.message, 'error');
      } finally {
        setIsLoading(false);
      }
    };
    fetchSettings();
  }, [showToast]);

  useEffect(() => {
    if (!isLoading) {
      iframeReady.current = false;
      const baseUrl = window.varnerData?.site_url || '/';
      setPreviewUrl(`${baseUrl}?varner_preview=1`);
    }
  }, [isLoading]);

  const fetchPages = useCallback(async () => {
    setIsLoadingPages(true);
    try {
      const [pagesData, templatesData] = await Promise.all([
        apiFetch('/pages'),
        apiFetch('/page-templates'),
      ]);
      setPages(pagesData);
      setPageTemplates(templatesData);
    } catch (err) {
      showToast('Failed to load pages: ' + err.message, 'error');
    } finally {
      setIsLoadingPages(false);
    }
  }, [showToast]);

  useEffect(() => {
    if (openSections.pages) {
      fetchPages();
    }
  }, [openSections.pages, fetchPages]);

  useEffect(() => {
    if (isLoading) return;
    const timer = setTimeout(async () => {
      try {
        await apiFetch('/settings/preview', {
          method: 'POST',
          body: JSON.stringify(settings),
        });
        iframeReady.current = false;
        const baseUrl = window.varnerData?.site_url || '/';
        setPreviewUrl(`${baseUrl}?varner_preview=1&t=${Date.now()}`);
      } catch (err) {
        console.error('Failed to sync preview: ' + err.message);
      }
    }, 600);
    return () => clearTimeout(timer);
  }, [settings, isLoading]);

  const handleFieldChange = (key, value) => {
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handleThumbnailUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setIsUploadingThumbnail(true);
    try {
      const result = await uploadFile(file);
      handleFieldChange('youtube_custom_thumbnail', result.url);
      showToast('Custom thumbnail uploaded successfully!');
    } catch (err) {
      showToast('Thumbnail upload failed: ' + err.message, 'error');
    } finally {
      setIsUploadingThumbnail(false);
      if (thumbnailInputRef.current) thumbnailInputRef.current.value = null;
    }
  };

  const handleRemoveThumbnail = () => {
    handleFieldChange('youtube_custom_thumbnail', '');
    showToast('Custom thumbnail removed.');
  };

  const handleAddSocialLink = () => {
    handleFieldChange('social_custom_links', [
      ...(settings.social_custom_links || []),
      { platform: 'facebook', url: '', label: '' }
    ]);
  };

  const handleUpdateSocialLink = (index, field, value) => {
    const updated = [...(settings.social_custom_links || [])];
    updated[index] = { ...updated[index], [field]: value };
    handleFieldChange('social_custom_links', updated);
  };

  const handleRemoveSocialLink = (index) => {
    const updated = (settings.social_custom_links || []).filter((_, i) => i !== index);
    handleFieldChange('social_custom_links', updated);
  };

  const handleSaveSettings = async () => {
    setIsSaving(true);
    try {
      const result = await apiFetch('/settings', {
        method: 'POST',
        body: JSON.stringify(settings),
      });
      if (result.success) {
        setSettings(result.settings);
        showToast('Settings saved successfully!');
      }
    } catch (err) {
      showToast('Failed to save settings: ' + err.message, 'error');
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className="p-20 text-center text-slate-300 font-black uppercase text-xs tracking-widest">
        Loading configuration settings…
      </div>
    );
  }

  return (
    <div className="flex flex-col lg:flex-row gap-8 w-full max-w-[1600px] mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500 text-slate-900 pb-16">
      
      {/* LEFT COLUMN: Controls Panel */}
      <div 
        id="settings-editor-container" 
        className="w-full lg:w-[420px] xl:w-[480px] shrink-0 space-y-6 sm:space-y-8 lg:max-h-[calc(100vh-12rem)] lg:overflow-y-auto pr-2 no-scrollbar pb-24"
      >
        
        {/* SECTION QUICK-JUMP NAV */}
        <div className="sticky top-0 z-50 bg-white -mx-2 px-2 pt-0 pb-3 mb-2 border-b border-slate-100 flex flex-wrap gap-1.5">
          {[
            { key: 'hero',    label: 'Hero' },
            { key: 'support', label: 'Support' },
            { key: 'youtube', label: 'YouTube' },
            { key: 'contact', label: 'Contact' },
            { key: 'hours',   label: 'Hours' },
            { key: 'about',   label: 'About' },
            { key: 'careers', label: 'Careers' },
            { key: 'finance', label: 'Finance' },
            { key: 'pages',   label: 'Pages' },
          ].map(s => (
            <button
              key={s.key}
              type="button"
              onClick={() => {
                const el = document.getElementById(`editor-section-${s.key}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (!openSections[s.key]) toggleSection(s.key);
              }}
              className={`text-[9px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-lg transition-all ${
                openSections[s.key]
                  ? 'bg-red-600 text-white shadow-sm'
                  : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
              }`}
            >
              {s.label}
            </button>
          ))}
        </div>
        
        {/* 1. HERO SECTION */}
        <div id="editor-section-hero">
          <CollapsiblePanel
            title="Hero Section"
            icon={<Sparkles size={20} />}
            isOpen={openSections.hero}
            onToggle={() => toggleSection('hero')}
          >
            <div className="space-y-4">
              <TextAreaField
                label="Hero Title"
                value={settings.hero_title}
                onChange={v => handleFieldChange('hero_title', v)}
              />

              <TextAreaField
                label="Hero Subtitle"
                value={settings.hero_subtitle}
                onChange={v => handleFieldChange('hero_subtitle', v)}
              />

              <InputField
                label="Custom Hero Video URL (Optional)"
                value={settings.hero_video_url}
                onChange={v => handleFieldChange('hero_video_url', v)}
                placeholder="e.g. /wp-content/uploads/... or leave blank for default"
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Primary Button Text"
                  value={settings.hero_button1_text}
                  onChange={v => handleFieldChange('hero_button1_text', v)}
                />
                <InputField
                  label="Primary Button Link"
                  value={settings.hero_button1_link}
                  onChange={v => handleFieldChange('hero_button1_link', v)}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Secondary Button Text"
                  value={settings.hero_button2_text}
                  onChange={v => handleFieldChange('hero_button2_text', v)}
                />
                <InputField
                  label="Secondary Button Link"
                  value={settings.hero_button2_link}
                  onChange={v => handleFieldChange('hero_button2_link', v)}
                />
              </div>
            </div>
          </CollapsiblePanel>
        </div>


        {/* 2. SUPPORT HUB LINKS */}
        <div id="editor-section-support">
          <CollapsiblePanel
            title="Support Hub Links"
            icon={<Briefcase size={20} />}
            isOpen={openSections.support}
            onToggle={() => toggleSection('support')}
          >
            <div className="space-y-4">
              <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wide pl-1">
                Quick-access links displayed in the Support Hub section on the homepage.
              </p>
              <InputField
                label="Service Department Link"
                value={settings.support_hub_service_link}
                onChange={v => handleFieldChange('support_hub_service_link', v)}
                placeholder="e.g. /service"
              />
              <InputField
                label="Parts Department Link"
                value={settings.support_hub_parts_link}
                onChange={v => handleFieldChange('support_hub_parts_link', v)}
                placeholder="e.g. /parts"
              />
              <InputField
                label="Finance / Apply Link"
                value={settings.support_hub_finance_link}
                onChange={v => handleFieldChange('support_hub_finance_link', v)}
                placeholder="e.g. /financing"
              />
            </div>
          </CollapsiblePanel>
        </div>

        {/* 3. YOUTUBE MEDIA SECTION */}
        <div id="editor-section-youtube">
          <CollapsiblePanel
            title="YouTube Media Section"
            icon={<ImageIcon size={20} />}
            isOpen={openSections.youtube}
            onToggle={() => toggleSection('youtube')}
          >
            <div className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="YouTube Tagline"
                  value={settings.youtube_tagline}
                  onChange={v => handleFieldChange('youtube_tagline', v)}
                />
                <InputField
                  label="YouTube Channel URL"
                  value={settings.youtube_channel_url}
                  onChange={v => handleFieldChange('youtube_channel_url', v)}
                />
              </div>

              <TextAreaField
                label="Section Title"
                value={settings.youtube_title}
                onChange={v => handleFieldChange('youtube_title', v)}
              />

              <TextAreaField
                label="Description Paragraph"
                value={settings.youtube_paragraph}
                onChange={v => handleFieldChange('youtube_paragraph', v)}
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                <InputField
                  label="YouTube Video ID (e.g. goF_3TspZ6k)"
                  value={settings.youtube_video_id}
                  onChange={v => handleFieldChange('youtube_video_id', v)}
                />

                <div>
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1 mb-2">YouTube Custom Thumbnail</label>
                  <input
                    type="file"
                    accept="image/*"
                    className="hidden"
                    ref={thumbnailInputRef}
                    onChange={handleThumbnailUpload}
                  />
                  <div className="flex gap-3">
                    <button
                      type="button"
                      onClick={() => thumbnailInputRef.current?.click()}
                      disabled={isUploadingThumbnail}
                      className="bg-slate-950 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all flex items-center gap-2"
                    >
                      {isUploadingThumbnail ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
                      Upload Custom Image
                    </button>
                    {settings.youtube_custom_thumbnail && (
                      <button
                        type="button"
                        onClick={handleRemoveThumbnail}
                        className="bg-red-50 text-red-600 border border-red-100 px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-red-100 transition-all flex items-center gap-2"
                      >
                        <Trash2 size={16} />
                        Revert To Default
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* YouTube Thumbnail Preview */}
              <div className="mt-4">
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 pl-1">Thumbnail Preview</p>
                <div className="relative aspect-video max-w-md bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-lg">
                  <img
                    src={settings.youtube_custom_thumbnail || `https://img.youtube.com/vi/${settings.youtube_video_id || 'goF_3TspZ6k'}/maxresdefault.jpg`}
                    alt="YouTube Thumbnail"
                    className="w-full h-full object-cover"
                    onError={e => { e.target.onerror = null; e.target.src = 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}
                  />
                  <div className="absolute inset-0 flex items-center justify-center bg-black/30 pointer-events-none">
                    <div className="w-16 h-16 bg-red-600 text-white rounded-full flex items-center justify-center shadow-2xl">
                      <PlayIcon className="ml-1 w-6 h-6 fill-current text-white" />
                    </div>
                  </div>
                  <div className="absolute bottom-3 left-3 bg-slate-900/90 text-white text-[8px] font-black px-2 py-1 rounded uppercase tracking-widest">
                    {settings.youtube_custom_thumbnail ? 'CUSTOM THUMBNAIL' : 'YOUTUBE DEFAULT'}
                  </div>
                </div>
              </div>
            </div>
          </CollapsiblePanel>
        </div>

        {/* 4. BUSINESS DETAILS & CONTACTS */}
        <div id="editor-section-contact">
          <CollapsiblePanel
            title="Business Details & Contacts"
            icon={<Mail size={20} />}
            isOpen={openSections.contact}
            onToggle={() => toggleSection('contact')}
          >
            <div className="space-y-4">
              <div>
                <InputField
                  label="Primary Notification Email Address"
                  value={settings.contact_email}
                  onChange={v => handleFieldChange('contact_email', v)}
                />
                <p className="text-[10px] font-bold text-slate-400 pl-1 mt-1.5 uppercase tracking-wide">
                  * Critical: This email receives all submissions from the frontend Chatbox, Contact Form, Parts Request, and Service Request forms.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Display Phone Number"
                  value={settings.contact_phone}
                  onChange={v => handleFieldChange('contact_phone', v)}
                  placeholder="e.g. (970) 874-0612"
                />
                <InputField
                  label="Raw Phone Digits (for calling links)"
                  value={settings.contact_phone_raw}
                  onChange={v => handleFieldChange('contact_phone_raw', v)}
                  placeholder="e.g. 9708740612"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="Address Line 1"
                  value={settings.contact_address_line1}
                  onChange={v => handleFieldChange('contact_address_line1', v)}
                />
                <InputField
                  label="Address Line 2"
                  value={settings.contact_address_line2}
                  onChange={v => handleFieldChange('contact_address_line2', v)}
                />
              </div>

              <InputField
                label="Google Maps Navigation Directions URL"
                value={settings.contact_map_link}
                onChange={v => handleFieldChange('contact_map_link', v)}
              />

              <InputField
                label="Google Maps Embed Iframe URL"
                value={settings.contact_map_embed_url}
                onChange={v => handleFieldChange('contact_map_embed_url', v)}
              />
            </div>
          </CollapsiblePanel>
        </div>

        {/* 5. DEALER HOURS & SOCIALS */}
        <div id="editor-section-hours">
          <CollapsiblePanel
            title="Dealer Hours & Socials"
            icon={<Clock size={20} />}
            isOpen={openSections.hours}
            onToggle={() => toggleSection('hours')}
          >
            <div className="space-y-6">
              <div>
                <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Dealership Business Hours</h5>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <InputField
                    label="Monday - Friday Hours"
                    value={settings.hours_mon_fri}
                    onChange={v => handleFieldChange('hours_mon_fri', v)}
                  />
                  <InputField
                    label="Saturday Hours"
                    value={settings.hours_sat}
                    onChange={v => handleFieldChange('hours_sat', v)}
                  />
                  <InputField
                    label="Sunday Hours"
                    value={settings.hours_sun}
                    onChange={v => handleFieldChange('hours_sun', v)}
                  />
                </div>
              </div>

              <div>
                <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Social Media Profile Links</h5>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <InputField
                    label="Facebook Page URL"
                    value={settings.social_facebook}
                    onChange={v => handleFieldChange('social_facebook', v)}
                  />
                  <InputField
                    label="YouTube Channel URL"
                    value={settings.social_youtube}
                    onChange={v => handleFieldChange('social_youtube', v)}
                  />
                </div>
              </div>

              <div className="mt-6 pt-6 border-t border-slate-100">
                <div className="flex items-center justify-between mb-4">
                  <h5 className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Additional Social Links</h5>
                  <button
                    type="button"
                    onClick={handleAddSocialLink}
                    className="bg-slate-900 text-white px-4 py-2.5 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-black transition-all flex items-center gap-1.5 active:scale-95 shadow-sm"
                  >
                    <Plus size={12} />
                    Add Link
                  </button>
                </div>
                
                <div className="space-y-4">
                  {settings.social_custom_links && settings.social_custom_links.map((link, idx) => (
                    <div key={idx} className="flex flex-col sm:flex-row gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 items-end relative">
                      <div className="flex-1 w-full grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="space-y-2">
                          <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">Platform</label>
                          <select
                            value={link.platform || 'custom'}
                            onChange={e => handleUpdateSocialLink(idx, 'platform', e.target.value)}
                            className="w-full bg-white border border-slate-200 rounded-xl p-3 font-bold text-slate-900 outline-none focus:border-red-500 transition-all text-xs"
                          >
                            <option value="facebook">Facebook</option>
                            <option value="youtube">YouTube</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">X / Twitter</option>
                            <option value="tiktok">TikTok</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="pinterest">Pinterest</option>
                            <option value="custom">Custom / Other</option>
                          </select>
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                          <label className="text-[9px] font-black text-slate-500 uppercase tracking-widest block pl-1">URL Target</label>
                          <input
                            type="text"
                            value={link.url || ''}
                            onChange={e => handleUpdateSocialLink(idx, 'url', e.target.value)}
                            placeholder="e.g. https://instagram.com/mydealership"
                            className="w-full bg-white border border-slate-200 rounded-xl p-3 font-bold text-slate-900 outline-none focus:border-red-500 transition-all text-xs"
                          />
                        </div>
                      </div>
                      <button
                        type="button"
                        onClick={() => handleRemoveSocialLink(idx)}
                        className="bg-red-50 text-red-600 border border-red-100 p-3 rounded-xl hover:bg-red-100 transition-all active:scale-95 flex items-center justify-center shrink-0 w-full sm:w-auto h-[46px]"
                        title="Remove Link"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  ))}

                  {(!settings.social_custom_links || settings.social_custom_links.length === 0) && (
                    <p className="text-[10px] text-slate-400 font-bold uppercase tracking-widest text-center py-4 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200/60">
                      No additional social links configured
                    </p>
                  )}
                </div>
              </div>
            </div>
          </CollapsiblePanel>
        </div>

        {/* 6. ABOUT / CTA SECTION */}
        <div id="editor-section-about">
          <CollapsiblePanel
            title="About / Call-to-Action Section"
            icon={<Sparkles size={20} />}
            isOpen={openSections.about}
            onToggle={() => toggleSection('about')}
          >
            <div className="space-y-4">
              <TextAreaField
                label="CTA Section Title"
                value={settings.cta_title}
                onChange={v => handleFieldChange('cta_title', v)}
              />
              <TextAreaField
                label="CTA Body Text"
                value={settings.cta_text}
                onChange={v => handleFieldChange('cta_text', v)}
              />
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField
                  label="CTA Button Text"
                  value={settings.cta_button_text}
                  onChange={v => handleFieldChange('cta_button_text', v)}
                  placeholder="e.g. View Inventory"
                />
                <InputField
                  label="CTA Button Link"
                  value={settings.cta_button_link}
                  onChange={v => handleFieldChange('cta_button_link', v)}
                  placeholder="e.g. /inventory"
                />
              </div>

              <div className="border-t border-slate-100 pt-4">
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1 mb-3">Why Choose Us — Bullet Points</label>
                {(settings.about_why_choose_us_bullets || []).map((bullet, idx) => (
                  <div key={idx} className="flex items-center gap-2 mb-2">
                    <input
                      type="text"
                      value={bullet}
                      onChange={e => {
                        const updated = [...(settings.about_why_choose_us_bullets || [])];
                        updated[idx] = e.target.value;
                        handleFieldChange('about_why_choose_us_bullets', updated);
                      }}
                      placeholder={`Bullet point ${idx + 1}`}
                      className="flex-1 bg-slate-50 border-2 border-slate-100 rounded-xl p-3 font-bold text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                    />
                    <button
                      type="button"
                      onClick={() => {
                        const updated = (settings.about_why_choose_us_bullets || []).filter((_, i) => i !== idx);
                        handleFieldChange('about_why_choose_us_bullets', updated);
                      }}
                      className="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                ))}
                <button
                  type="button"
                  onClick={() => handleFieldChange('about_why_choose_us_bullets', [...(settings.about_why_choose_us_bullets || []), ''])}
                  className="mt-2 w-full py-3 border-2 border-dashed border-slate-200 rounded-xl text-slate-400 font-black uppercase tracking-widest text-[10px] hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center gap-2"
                >
                  <Plus size={14} /> Add Bullet Point
                </button>
              </div>
            </div>
          </CollapsiblePanel>
        </div>

        {/* 7. EMPLOYMENT & CAREERS */}
        <div id="editor-section-careers">
          <CollapsiblePanel
            title="Employment & Careers"
            icon={<Briefcase size={20} />}
            isOpen={openSections.careers}
            onToggle={() => toggleSection('careers')}
          >
            <div className="space-y-6">
              <InputField
                label="Careers Page Tagline"
                value={settings.employment_tagline}
                onChange={v => handleFieldChange('employment_tagline', v)}
              />

              <InputField
                label="Careers Page Headline"
                value={settings.employment_headline}
                onChange={v => handleFieldChange('employment_headline', v)}
              />

              <TextAreaField
                label="Careers Page Introduction Text"
                value={settings.employment_intro}
                onChange={v => handleFieldChange('employment_intro', v)}
              />

              <div className="border-t border-slate-100 pt-6">
                <div className="flex justify-between items-center mb-4">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Current Job Openings</label>
                  <span className="bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-3 py-1.5 rounded-full border border-slate-100 tracking-widest shadow-sm">
                    {settings.employment_jobs ? settings.employment_jobs.length : 0} Openings
                  </span>
                </div>

                <div className="space-y-4">
                  {settings.employment_jobs && settings.employment_jobs.map((job, idx) => (
                    <div key={idx} className="bg-slate-50 rounded-[1.5rem] p-6 border-2 border-slate-100 space-y-4 relative group">
                      <div className="absolute top-4 right-4 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                          type="button"
                          onClick={() => {
                            if (idx === 0) return;
                            const updated = [...settings.employment_jobs];
                            const temp = updated[idx];
                            updated[idx] = updated[idx - 1];
                            updated[idx - 1] = temp;
                            handleFieldChange('employment_jobs', updated);
                          }}
                          disabled={idx === 0}
                          className="bg-white border border-slate-200 text-slate-400 p-2 rounded-xl hover:text-slate-900 disabled:opacity-30"
                          title="Move Up"
                        >
                          <ChevronUp size={14} />
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            if (idx === settings.employment_jobs.length - 1) return;
                            const updated = [...settings.employment_jobs];
                            const temp = updated[idx];
                            updated[idx] = updated[idx + 1];
                            updated[idx + 1] = temp;
                            handleFieldChange('employment_jobs', updated);
                          }}
                          disabled={idx === settings.employment_jobs.length - 1}
                          className="bg-white border border-slate-200 text-slate-400 p-2 rounded-xl hover:text-slate-900 disabled:opacity-30"
                          title="Move Down"
                        >
                          <ChevronDown size={14} />
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            const updated = settings.employment_jobs.filter((_, i) => i !== idx);
                            handleFieldChange('employment_jobs', updated);
                          }}
                          className="bg-white border border-slate-200 text-red-600 p-2 rounded-xl hover:bg-red-50 hover:border-red-100"
                          title="Delete Job Opening"
                        >
                          <Trash2 size={14} />
                        </button>
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div className="sm:col-span-2">
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Job Title</label>
                          <input
                            type="text"
                            placeholder="e.g. Heavy Equipment Mechanic"
                            value={job.job_title || ''}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_title: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                          />
                        </div>

                        <div>
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Job Type</label>
                          <select
                            value={job.job_type || 'Full-Time'}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_type: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm appearance-none cursor-pointer"
                          >
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                          </select>
                        </div>

                        <div>
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Location</label>
                          <input
                            type="text"
                            placeholder="e.g. Delta, CO"
                            value={job.job_location || ''}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_location: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                          />
                        </div>

                        <div className="sm:col-span-2">
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Job Description</label>
                          <textarea
                            placeholder="Detail job requirements, responsibilities, and benefits..."
                            value={job.job_description || ''}
                            onChange={e => {
                              const updated = [...settings.employment_jobs];
                              updated[idx] = { ...updated[idx], job_description: e.target.value };
                              handleFieldChange('employment_jobs', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm h-24 resize-none"
                          />
                        </div>

                        <div className="sm:col-span-2 flex items-center gap-4 bg-white p-4 rounded-xl border border-slate-150">
                          <label className="flex items-center gap-2 cursor-pointer select-none">
                            <input
                              type="checkbox"
                              checked={!!job.job_show_badge}
                              onChange={e => {
                                const updated = [...settings.employment_jobs];
                                updated[idx] = { ...updated[idx], job_show_badge: e.target.checked };
                                handleFieldChange('employment_jobs', updated);
                              }}
                              className="w-4 h-4 accent-red-600 cursor-pointer"
                            />
                            <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest">Show Status Badge</span>
                          </label>
                          
                          {job.job_show_badge && (
                            <input
                              type="text"
                              placeholder="e.g. Urgently Hiring"
                              value={job.job_badge_text || ''}
                              onChange={e => {
                                const updated = [...settings.employment_jobs];
                                updated[idx] = { ...updated[idx], job_badge_text: e.target.value };
                                handleFieldChange('employment_jobs', updated);
                              }}
                              className="flex-1 bg-slate-50 border-2 border-slate-100 rounded-lg p-2 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-xs"
                            />
                          )}
                        </div>
                      </div>
                    </div>
                  ))}

                  {(!settings.employment_jobs || settings.employment_jobs.length === 0) && (
                    <div className="p-12 text-center border-2 border-dashed border-slate-100 rounded-3xl text-slate-400 uppercase text-[10px] font-black tracking-widest">
                      No job openings listed.
                    </div>
                  )}
                </div>

                <button
                  type="button"
                  onClick={() => {
                    const jobs = settings.employment_jobs || [];
                    handleFieldChange('employment_jobs', [
                      ...jobs,
                      {
                        job_title: '',
                        job_type: 'Full-Time',
                        job_location: 'Delta, CO',
                        job_description: '',
                        job_show_badge: false,
                        job_badge_text: 'Urgently Hiring'
                      }
                    ]);
                  }}
                  className="mt-4 w-full py-4 border-2 border-dashed border-slate-200 rounded-[1.5rem] text-slate-400 font-black uppercase tracking-widest text-[10px] hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center gap-2"
                >
                  <Plus size={16} /> Add Job Opening
                </button>
              </div>
            </div>
          </CollapsiblePanel>
        </div>

        {/* 8. FINANCE CARDS */}
        <div id="editor-section-finance">
          <CollapsiblePanel
            title="Finance Partners"
            icon={<DollarSign size={20} />}
            isOpen={openSections.finance}
            onToggle={() => toggleSection('finance')}
          >
            <div className="space-y-6">
              <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wide pl-1">
                Finance partner cards displayed on the /finance page. Each card shows a logo, name, description, and an Apply link.
              </p>

              <div className="space-y-4">
                {settings.finance_cards && settings.finance_cards.map((card, idx) => (
                  <div key={idx} className="bg-slate-50 rounded-[1.5rem] p-6 border-2 border-slate-100 space-y-4 relative group">
                    <div className="absolute top-4 right-4 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button
                        type="button"
                        onClick={() => {
                          if (idx === 0) return;
                          const updated = [...settings.finance_cards];
                          const temp = updated[idx];
                          updated[idx] = updated[idx - 1];
                          updated[idx - 1] = temp;
                          handleFieldChange('finance_cards', updated);
                        }}
                        disabled={idx === 0}
                        className="bg-white border border-slate-200 text-slate-400 p-2 rounded-xl hover:text-slate-900 disabled:opacity-30"
                        title="Move Up"
                      >
                        <ChevronUp size={14} />
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          if (idx === settings.finance_cards.length - 1) return;
                          const updated = [...settings.finance_cards];
                          const temp = updated[idx];
                          updated[idx] = updated[idx + 1];
                          updated[idx + 1] = temp;
                          handleFieldChange('finance_cards', updated);
                        }}
                        disabled={idx === settings.finance_cards.length - 1}
                        className="bg-white border border-slate-200 text-slate-400 p-2 rounded-xl hover:text-slate-900 disabled:opacity-30"
                        title="Move Down"
                      >
                        <ChevronDown size={14} />
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          const updated = settings.finance_cards.filter((_, i) => i !== idx);
                          handleFieldChange('finance_cards', updated);
                        }}
                        className="bg-white border border-slate-200 text-red-600 p-2 rounded-xl hover:bg-red-50 hover:border-red-100"
                        title="Delete Card"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div className="sm:col-span-2">
                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Partner Name</label>
                        <input
                          type="text"
                          placeholder="e.g. Wells Fargo"
                          value={card.name || ''}
                          onChange={e => {
                            const updated = [...settings.finance_cards];
                            updated[idx] = { ...updated[idx], name: e.target.value };
                            handleFieldChange('finance_cards', updated);
                          }}
                          className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                        />
                      </div>

                      <div className="sm:col-span-2">
                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Logo Image</label>
                        <input
                          type="file"
                          accept="image/*"
                          className="hidden"
                          ref={el => { logoInputRefs.current[idx] = el; }}
                          onChange={async (e) => {
                            const file = e.target.files?.[0];
                            if (!file) return;
                            e.target.value = '';
                            const updated = [...settings.finance_cards];
                            updated[idx] = { ...updated[idx], _uploading_logo: true };
                            handleFieldChange('finance_cards', updated);
                            try {
                              const result = await uploadFile(file);
                              const updated2 = [...settings.finance_cards];
                              updated2[idx] = { ...updated2[idx], logo: result.url, _uploading_logo: false };
                              handleFieldChange('finance_cards', updated2);
                              showToast('Logo uploaded successfully!');
                            } catch (err) {
                              const updated3 = [...settings.finance_cards];
                              updated3[idx] = { ...updated3[idx], _uploading_logo: false };
                              handleFieldChange('finance_cards', updated3);
                              showToast('Logo upload failed: ' + err.message, 'error');
                            }
                          }}
                        />
                        <div className="flex gap-3">
                          <button
                            type="button"
                            onClick={() => logoInputRefs.current[idx]?.click()}
                            disabled={card._uploading_logo}
                            className="bg-slate-950 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all flex items-center gap-2 disabled:opacity-50"
                          >
                            {card._uploading_logo ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
                            {card._uploading_logo ? 'Uploading…' : card.logo ? 'Replace Logo' : 'Upload Logo'}
                          </button>
                          {card.logo && (
                            <button
                              type="button"
                              onClick={() => {
                                const updated = [...settings.finance_cards];
                                updated[idx] = { ...updated[idx], logo: '' };
                                handleFieldChange('finance_cards', updated);
                              }}
                              className="bg-red-50 text-red-600 border border-red-100 px-4 py-4 rounded-xl hover:bg-red-100 transition-all flex items-center gap-2"
                              title="Remove Logo"
                            >
                              <Trash2 size={14} />
                            </button>
                          )}
                        </div>
                        {card.logo && card.logo.match(/^https?:\/\//) && (
                          <div className="mt-3 bg-white rounded-xl border border-slate-100 p-4 flex items-center justify-center">
                            <img
                              src={card.logo}
                              alt={card.alt || card.name || 'Finance partner logo'}
                              className="h-24 w-24 object-contain"
                              onError={e => { e.target.style.display = 'none'; }}
                            />
                          </div>
                        )}
                        <div className="mt-3">
                          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Alt Text (for accessibility)</label>
                          <input
                            type="text"
                            placeholder="e.g. Wells Fargo logo"
                            value={card.alt || ''}
                            onChange={e => {
                              const updated = [...settings.finance_cards];
                              updated[idx] = { ...updated[idx], alt: e.target.value };
                              handleFieldChange('finance_cards', updated);
                            }}
                            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                          />
                        </div>
                      </div>
                      
                      <div className="sm:col-span-2">
                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Application PDF</label>
                        <input
                          type="file"
                          accept=".pdf,application/pdf"
                          className="hidden"
                          ref={el => { pdfInputRefs.current[idx] = el; }}
                          onChange={async (e) => {
                            const file = e.target.files?.[0];
                            if (!file) return;
                            e.target.value = '';
                            const updated = [...settings.finance_cards];
                            updated[idx] = { ...updated[idx], _uploading: true };
                            handleFieldChange('finance_cards', updated);
                            try {
                              const result = await uploadFile(file);
                              const updated2 = [...settings.finance_cards];
                              updated2[idx] = { ...updated2[idx], application_pdf: result.url, _uploading: false };
                              handleFieldChange('finance_cards', updated2);
                              showToast('PDF uploaded successfully!');
                            } catch (err) {
                              const updated3 = [...settings.finance_cards];
                              updated3[idx] = { ...updated3[idx], _uploading: false };
                              handleFieldChange('finance_cards', updated3);
                              showToast('PDF upload failed: ' + err.message, 'error');
                            }
                          }}
                        />
                        <div className="flex gap-3">
                          <button
                            type="button"
                            onClick={() => pdfInputRefs.current[idx]?.click()}
                            disabled={card._uploading}
                            className="bg-slate-950 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all flex items-center gap-2 disabled:opacity-50"
                          >
                            {card._uploading ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
                            {card._uploading ? 'Uploading…' : card.application_pdf ? 'Replace PDF' : 'Upload PDF'}
                          </button>
                          {card.application_pdf && (
                            <>
                              <a
                                href={card.application_pdf}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="bg-slate-100 text-slate-600 px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center gap-2"
                              >
                                View PDF
                              </a>
                              <button
                                type="button"
                                onClick={() => {
                                  const updated = [...settings.finance_cards];
                                  updated[idx] = { ...updated[idx], application_pdf: '' };
                                  handleFieldChange('finance_cards', updated);
                                }}
                                className="bg-red-50 text-red-600 border border-red-100 px-4 py-4 rounded-xl hover:bg-red-100 transition-all flex items-center gap-2"
                                title="Remove PDF"
                              >
                                <Trash2 size={14} />
                              </button>
                            </>
                          )}
                        </div>
                      </div>

                      <div className="sm:col-span-2">
                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Short Description (optional)</label>
                        <input
                          type="text"
                          placeholder="e.g. Flexible financing for agricultural equipment"
                          value={card.description || ''}
                          onChange={e => {
                            const updated = [...settings.finance_cards];
                            updated[idx] = { ...updated[idx], description: e.target.value };
                            handleFieldChange('finance_cards', updated);
                          }}
                          className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
                        />
                      </div>

                    </div>
                  </div>
                ))}

                {(!settings.finance_cards || settings.finance_cards.length === 0) && (
                  <div className="p-12 text-center border-2 border-dashed border-slate-100 rounded-3xl text-slate-400 uppercase text-[10px] font-black tracking-widest">
                    No finance partners configured.
                  </div>
                )}
              </div>

              <button
                type="button"
                onClick={() => {
                  const cards = settings.finance_cards || [];
                  handleFieldChange('finance_cards', [
                    ...cards,
                    {
                      name: '',
                      logo: '',
                      application_pdf: '',
                      description: '',
                      alt: '',
                    }
                  ]);
                }}
                className="mt-4 w-full py-4 border-2 border-dashed border-slate-200 rounded-[1.5rem] text-slate-400 font-black uppercase tracking-widest text-[10px] hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center gap-2"
              >
                <Plus size={16} /> Add Finance Partner
              </button>
            </div>
          </CollapsiblePanel>
        </div>

        {/* 9. PAGES */}
        <div id="editor-section-pages">
          <CollapsiblePanel
            title="Pages"
            icon={<FileText size={20} />}
            isOpen={openSections.pages}
            onToggle={() => toggleSection('pages')}
          >
            <div className="space-y-6">
              <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wide pl-1">
                Manage WordPress pages. Create new pages with a template, or edit/delete existing ones.
              </p>

              {isLoadingPages ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 size={20} className="animate-spin text-slate-400" />
                </div>
              ) : (
                <div className="space-y-3">
                  {pages.length === 0 ? (
                    <p className="text-[10px] font-bold text-slate-400 text-center py-4">No pages found.</p>
                  ) : (
                    pages.map((page) => (
                      <div key={page.id} className="bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-center justify-between gap-4">
                        <div className="min-w-0 flex-1">
                          <div className="text-sm font-black text-slate-900 truncate">{page.title}</div>
                          <div className="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">
                            /{page.slug}
                            <span className="mx-1.5">·</span>
                            {page.template === 'default' ? 'Default Template' : page.template.replace(/^page-|\.php$/g, '')}
                            <span className="mx-1.5">·</span>
                            <span className={page.status === 'publish' ? 'text-green-600' : 'text-amber-500'}>{page.status}</span>
                          </div>
                        </div>
                        <div className="flex gap-1.5 shrink-0">
                          <a
                            href={page.link}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="bg-white border border-slate-200 text-slate-400 p-2 rounded-lg hover:text-slate-900 transition-colors"
                            title="View Page"
                          >
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                          </a>
                          <button
                            type="button"
                            onClick={async () => {
                              if (!window.confirm(`Delete "${page.title}"? It will be moved to trash.`)) return;
                              try {
                                await apiFetch(`/pages/${page.id}`, { method: 'DELETE' });
                                setPages(prev => prev.filter(p => p.id !== page.id));
                                showToast(`"${page.title}" moved to trash.`, 'success');
                              } catch (err) {
                                showToast('Delete failed: ' + err.message, 'error');
                              }
                            }}
                            className="bg-white border border-slate-200 text-red-600 p-2 rounded-lg hover:bg-red-50 transition-colors"
                            title="Trash Page"
                          >
                            <Trash2 size={14} />
                          </button>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              )}

              <hr className="border-slate-200" />

              <NewPageForm
                pageTemplates={pageTemplates}
                onCreated={(newPage) => {
                  setPages(prev => [...prev, newPage]);
                  fetchPages();
                }}
                showToast={showToast}
              />
            </div>
          </CollapsiblePanel>
        </div>

        {/* STATIONARY SAVE CONFIGURATION BAR */}
        <div className="bg-white rounded-[2rem] p-6 border border-slate-200/60 flex items-center justify-center mt-8 shadow-xl">
          <button
            onClick={handleSaveSettings}
            disabled={isSaving}
            className="w-full bg-red-600 hover:bg-red-700 text-white px-10 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.25em] flex items-center justify-center gap-3 active:scale-95 transition-all border-b-4 border-red-800 disabled:opacity-50 shadow-xl shadow-red-200"
          >
            {isSaving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
            {isSaving ? 'SAVING CHANGES…' : 'SAVE CONFIGURATION'}
          </button>
        </div>

      </div>

      {/* RIGHT COLUMN: Live Visual Preview Simulator */}
      <div className="flex-1 hidden lg:flex flex-col bg-slate-900 border border-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden h-[calc(100vh-12rem)] sticky top-4">
        {/* Browser Header Bar */}
        <div className="flex gap-1.5 px-6 py-4 bg-slate-950 items-center border-b border-slate-900 shrink-0">
          <div className="flex gap-1.5">
            <span className="w-3 h-3 rounded-full bg-[#ff5f56]" />
            <span className="w-3 h-3 rounded-full bg-[#ffbd2e]" />
            <span className="w-3 h-3 rounded-full bg-[#27c93f]" />
          </div>
          <div className="flex-1 flex justify-center max-w-md mx-auto">
            <div className="bg-slate-900 border border-slate-800 text-slate-400 px-4 py-1.5 rounded-xl text-[10px] font-mono select-none flex items-center gap-2 w-full justify-center">
              <svg className="w-3 h-3 text-green-500 fill-current" viewBox="0 0 24 24">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
              </svg>
              varnerequipment.com/preview
            </div>
          </div>
          <div className="w-16"></div>
        </div>
        {/* Live Website Preview Frame (Iframe) */}
        <div className="flex-1 bg-slate-950 relative overflow-hidden">
          {previewUrl ? (
            <iframe 
              ref={iframeRef}
              src={previewUrl} 
              className="w-full h-full border-none bg-white"
              title="Varner Equipment Site Preview"
              onLoad={() => {
                iframeReady.current = true;
                if (pendingScroll.current) {
                  scrollIframeTo(pendingScroll.current);
                  pendingScroll.current = null;
                }
              }}
            />
          ) : (
            <div className="w-full h-full flex flex-col items-center justify-center text-slate-500 gap-3">
              <Loader2 className="animate-spin text-red-600" size={24} />
              <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">Loading site preview...</span>
            </div>
          )}
        </div>
      </div>

    </div>
  );
};

const NewPageForm = ({ pageTemplates, onCreated, showToast }) => {
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [template, setTemplate] = useState('');
  const [isCreating, setIsCreating] = useState(false);
  const [autoSlug, setAutoSlug] = useState(true);

  const handleTitleChange = (val) => {
    setTitle(val);
    if (autoSlug) {
      setSlug(val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
    }
  };

  const handleCreate = async () => {
    if (!title.trim()) {
      showToast('Page title is required.', 'error');
      return;
    }
    setIsCreating(true);
    try {
      const result = await apiFetch('/pages', {
        method: 'POST',
        body: JSON.stringify({
          title: title.trim(),
          slug: slug.trim() || undefined,
          template: template || undefined,
        }),
      });
      showToast(`"${title.trim()}" page created!`, 'success');
      setTitle('');
      setSlug('');
      setTemplate('');
      setAutoSlug(true);
      onCreated(result);
    } catch (err) {
      showToast('Failed to create page: ' + err.message, 'error');
    } finally {
      setIsCreating(false);
    }
  };

  return (
    <div className="bg-slate-50 rounded-[1.5rem] p-6 border-2 border-dashed border-slate-200 space-y-4">
      <h4 className="text-[10px] font-black uppercase tracking-widest text-slate-500">Add New Page</h4>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div className="sm:col-span-2">
          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Page Title</label>
          <input
            type="text"
            placeholder="e.g. Our Services"
            value={title}
            onChange={e => handleTitleChange(e.target.value)}
            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
          />
        </div>
        <div>
          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Slug</label>
          <div className="flex gap-2 items-center">
            <span className="text-[10px] font-bold text-slate-400">/</span>
            <input
              type="text"
              placeholder="our-services"
              value={slug}
              onChange={e => { setSlug(e.target.value); setAutoSlug(false); }}
              className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
            />
          </div>
        </div>
        <div>
          <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">Template</label>
          <select
            value={template}
            onChange={e => setTemplate(e.target.value)}
            className="w-full bg-white border-2 border-slate-100 rounded-xl p-3 font-black text-slate-900 outline-none focus:border-red-500 transition-all text-sm"
          >
            <option value="">Default Template</option>
            {pageTemplates.filter(t => t.file).map(t => (
              <option key={t.file} value={t.file}>{t.name}</option>
            ))}
          </select>
        </div>
      </div>
      <div className="flex justify-end">
        <button
          type="button"
          onClick={handleCreate}
          disabled={isCreating}
          className="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 transition-all disabled:opacity-50"
        >
          {isCreating ? <Loader2 size={14} className="animate-spin" /> : <Plus size={14} />}
          {isCreating ? 'Creating...' : 'Create Page'}
        </button>
      </div>
    </div>
  );
};
