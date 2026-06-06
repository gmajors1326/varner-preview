import React, { useState, useEffect, useRef } from 'react';
import {
  Sparkles, Image as ImageIcon, Loader2, Upload, Trash2, Mail, Clock, Plus,
  Briefcase, ChevronUp, ChevronDown, Save
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
  });

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isUploadingThumbnail, setIsUploadingThumbnail] = useState(false);
  const thumbnailInputRef = useRef(null);

  const [openSections, setOpenSections] = useState({
    hero: false,
    support: false,
    youtube: false,
    contact: false,
    hours: false,
    about: false,
    careers: false,
  });

  const toggleSection = (key) => {
    setOpenSections(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
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
      const baseUrl = window.varnerData?.site_url || '/';
      setPreviewUrl(`${baseUrl}?varner_preview=1`);
    }
  }, [isLoading]);

  useEffect(() => {
    if (isLoading) return;
    const timer = setTimeout(async () => {
      try {
        await apiFetch('/settings/preview', {
          method: 'POST',
          body: JSON.stringify(settings),
        });
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
              src={previewUrl} 
              className="w-full h-full border-none bg-white"
              title="Varner Equipment Site Preview"
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
