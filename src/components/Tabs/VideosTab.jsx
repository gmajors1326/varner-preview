import React, { useState, useEffect } from 'react';
import {
  Loader2, Camera, Plus, List, Edit2, Trash2, X, Upload, Video, Youtube
} from 'lucide-react';
import { apiFetch, uploadFile } from '../../utils/api';
import { InputField } from '../Common/FormFields';

export const PlayIcon = ({ className }) => (
  <svg className={className} viewBox="0 0 24 24">
    <path d="M8 5v14l11-7z" fill="currentColor" />
  </svg>
);

export const VideosTab = ({ showToast }) => {
  const [videos, setVideos] = useState([]);
  const [categories, setCategories] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  const [selectedCategoryFilter, setSelectedCategoryFilter] = useState('all');
  
  // Video Modal State
  const [isVideoModalOpen, setIsVideoModalOpen] = useState(false);
  const [editingVideo, setEditingVideo] = useState(null);
  const [videoForm, setVideoForm] = useState({ title: '', youtube_link: '', video_file_url: '', video_file_id: 0, category_id: '' });
  const [uploadMode, setUploadMode] = useState('youtube'); // 'youtube' | 'upload'
  const [isUploading, setIsUploading] = useState(false);
  
  // Category Modal State
  const [isCatModalOpen, setIsCatModalOpen] = useState(false);
  const [newCatForm, setNewCatForm] = useState({ name: '', description: '' });

  const loadData = async () => {
    setIsLoading(true);
    try {
      const catsData = await apiFetch('/video-categories');
      const vidsData = await apiFetch('/videos');
      setCategories(catsData);
      setVideos(vidsData);
    } catch (e) {
      showToast('Failed to load videos data: ' + e.message, 'error');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleOpenVideoModal = (video = null) => {
    if (video) {
      setEditingVideo(video);
      setVideoForm({
        title: video.title,
        youtube_link: video.youtube_link,
        video_file_url: video.video_file_url || '',
        video_file_id: video.video_file_id || 0,
        category_id: String(video.category_id || '')
      });
      setUploadMode(video.video_file_url ? 'upload' : 'youtube');
    } else {
      setEditingVideo(null);
      setVideoForm({
        title: '',
        youtube_link: '',
        video_file_url: '',
        video_file_id: 0,
        category_id: categories[0] ? String(categories[0].id) : ''
      });
      setUploadMode('youtube');
    }
    setIsVideoModalOpen(true);
  };

  const handleUploadVideoFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Warn if file exceeds 50MB — server may reject it
    if (file.size > 50 * 1024 * 1024) {
      showToast('File is over 50MB. The server may reject it. Try compressing the video first.', 'error');
    }

    setIsUploading(true);
    try {
      const result = await uploadFile(file);
      setVideoForm(f => ({ ...f, video_file_url: result.url, video_file_id: result.id }));
      showToast('Video uploaded successfully!');
    } catch (err) {
      showToast('Video upload failed: ' + err.message, 'error');
    } finally {
      setIsUploading(false);
      e.target.value = '';
    }
  };

  const handleSaveVideo = async (e) => {
    e.preventDefault();
    if (!videoForm.title || !videoForm.category_id) {
      showToast('Please fill out all required fields.', 'error');
      return;
    }
    if (uploadMode === 'youtube' && !videoForm.youtube_link) {
      showToast('Please enter a YouTube link.', 'error');
      return;
    }
    if (uploadMode === 'upload' && !videoForm.video_file_url) {
      showToast('Please upload a video file.', 'error');
      return;
    }

    try {
      if (editingVideo) {
        await apiFetch(`/videos/${editingVideo.id}`, {
          method: 'PATCH',
          body: JSON.stringify(videoForm)
        });
        showToast('Video updated successfully!');
      } else {
        await apiFetch('/videos', {
          method: 'POST',
          body: JSON.stringify(videoForm)
        });
        showToast('Video added successfully!');
      }
      setIsVideoModalOpen(false);
      loadData();
    } catch (err) {
      showToast('Failed to save video: ' + err.message, 'error');
    }
  };

  const handleDeleteVideo = async (id) => {
    if (!window.confirm('Are you sure you want to delete this video?')) return;
    try {
      await apiFetch(`/videos/${id}`, { method: 'DELETE' });
      showToast('Video deleted successfully!');
      loadData();
    } catch (err) {
      showToast('Failed to delete video: ' + err.message, 'error');
    }
  };

  const handleSaveCategory = async (e) => {
    e.preventDefault();
    if (!newCatForm.name) {
      showToast('Category name is required.', 'error');
      return;
    }
    try {
      await apiFetch('/video-categories', {
        method: 'POST',
        body: JSON.stringify(newCatForm)
      });
      showToast('Category added successfully!');
      setNewCatForm({ name: '', description: '' });
      loadData();
    } catch (err) {
      showToast('Failed to add category: ' + err.message, 'error');
    }
  };

  const handleDeleteCategory = async (id) => {
    if (!window.confirm('Are you sure you want to delete this category? All videos in this category will become Uncategorized.')) return;
    try {
      await apiFetch(`/video-categories/${id}`, { method: 'DELETE' });
      showToast('Category deleted successfully!');
      loadData();
    } catch (err) {
      showToast('Failed to delete category: ' + err.message, 'error');
    }
  };

  const getYouTubeId = (url) => {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
  };

  const filteredVideos = selectedCategoryFilter === 'all' 
    ? videos 
    : videos.filter(v => String(v.category_id) === String(selectedCategoryFilter));

  if (isLoading) {
    return (
      <div className="py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest flex flex-col items-center justify-center gap-3">
        <Loader2 className="animate-spin text-red-600" size={24} />
        Loading Videos Console...
      </div>
    );
  }

  return (
    <div className="space-y-6 sm:space-y-8 animate-in fade-in duration-500 text-slate-900 pb-16">
      
      {/* HEADER WELCOME BANNER */}
      <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2rem] sm:rounded-[3rem] p-6 sm:p-10 text-white shadow-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between relative overflow-hidden gap-6">
        <div className="relative z-10">
          <h2 className="text-2xl sm:text-4xl font-black tracking-tighter mb-2 uppercase leading-none text-white">Videos Manager</h2>
          <p className="text-indigo-400 font-bold uppercase tracking-[0.3em] text-[10px]">
            Manage all video walkthroughs and showcase sections.
          </p>
        </div>
        <div className="relative z-10 flex gap-3 flex-wrap">
          <button
            onClick={() => setIsCatModalOpen(true)}
            className="bg-slate-800 border border-slate-700 hover:bg-slate-750 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 active:scale-95 transition-all"
          >
            <List size={16}/>
            Categories
          </button>
          <button
            onClick={() => handleOpenVideoModal()}
            className="bg-red-600 hover:bg-red-700 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest flex items-center gap-2 active:scale-95 transition-all shadow-xl shadow-red-950/20"
          >
            <Plus size={16}/>
            Add Video
          </button>
        </div>
        <Camera size={80} className="absolute -right-4 -bottom-4 sm:-right-8 sm:-bottom-8 opacity-10 rotate-12 sm:w-[120px] sm:h-[120px]"/>
      </div>

      {/* CATEGORY BAR */}
      <div className="flex gap-2 border-b border-slate-200 pb-4 overflow-x-auto no-scrollbar">
        <button 
          onClick={() => setSelectedCategoryFilter('all')}
          className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${selectedCategoryFilter === 'all' ? 'bg-red-600 text-white border-red-600 shadow-md' : 'bg-white text-slate-500 border-slate-200/60 hover:bg-slate-50'}`}
        >
          All Categories ({videos.length})
        </button>
        {categories.map(cat => {
          const count = videos.filter(v => String(v.category_id) === String(cat.id)).length;
          return (
            <button 
              key={cat.id}
              onClick={() => setSelectedCategoryFilter(cat.id)}
              className={`px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all border leading-none ${String(selectedCategoryFilter) === String(cat.id) ? 'bg-red-600 text-white border-red-600 shadow-md' : 'bg-white text-slate-500 border-slate-200/60 hover:bg-slate-50'}`}
            >
              {cat.name} ({count})
            </button>
          );
        })}
      </div>

      {/* VIDEOS GRID */}
      {filteredVideos.length === 0 ? (
        <div className="p-20 text-center border-2 border-dashed border-slate-200 rounded-[2rem] text-slate-400 font-black uppercase text-xs tracking-widest bg-white">
          No videos listed in this category
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredVideos.map(vid => {
            const ytId = getYouTubeId(vid.youtube_link);
            const isUploaded = !!vid.video_file_url;
            
            return (
              <div key={vid.id} className="bg-white rounded-[2rem] overflow-hidden shadow-xl border border-slate-100 hover:shadow-2xl hover:-translate-y-0.5 transition-all group flex flex-col">
                <div className="aspect-video w-full bg-slate-900 relative overflow-hidden shrink-0">
                  {isUploaded ? (
                    <video src={vid.video_file_url} controls className="w-full h-full object-cover" preload="metadata">
                      Your browser does not support the video tag.
                    </video>
                  ) : (
                    <>
                      <img src={ytId ? `https://img.youtube.com/vi/${ytId}/mqdefault.jpg` : 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt={vid.title}/>
                      <div className="absolute inset-0 bg-black/20 flex items-center justify-center pointer-events-none">
                        <div className="w-12 h-12 bg-red-600/90 text-white rounded-full flex items-center justify-center shadow-lg group-hover:bg-red-600 group-hover:scale-110 transition-all duration-300">
                          <PlayIcon className="ml-1 w-5 h-5 fill-current text-white" />
                        </div>
                      </div>
                    </>
                  )}
                  <span className="absolute bottom-3 left-3 bg-slate-900/95 text-white text-[8px] font-black px-2 py-1 rounded uppercase tracking-widest border border-slate-800">
                    {vid.category_name}
                  </span>
                </div>
                <div className="p-6 flex-1 flex flex-col justify-between gap-4">
                  <h3 className="text-base font-black text-slate-900 leading-snug truncate-2-lines">{vid.title}</h3>
                  <div className="flex gap-2 border-t border-slate-50 pt-4 mt-auto">
                    <button
                      onClick={() => handleOpenVideoModal(vid)}
                      className="flex-1 bg-slate-50 border border-slate-200 text-slate-600 py-3 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-slate-100 hover:text-slate-900 transition-all flex items-center justify-center gap-1.5 active:scale-95"
                    >
                      <Edit2 size={12}/> Edit
                    </button>
                    <button
                      onClick={() => handleDeleteVideo(vid.id)}
                      className="bg-red-50 border border-red-100 text-red-600 p-3 rounded-xl hover:bg-red-100 transition-all active:scale-95 flex items-center justify-center"
                      title="Delete Video"
                    >
                      <Trash2 size={12}/>
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* VIDEO DIALOG */}
      {isVideoModalOpen && (
        <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4 animate-in fade-in duration-200" onClick={e => { if (e.target === e.currentTarget) setIsVideoModalOpen(false); }}>
          <div className="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-[512px] flex flex-col overflow-hidden animate-in zoom-in duration-300">
            <div className="flex items-center justify-between p-6 sm:p-8 border-b border-slate-100 shrink-0">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">{editingVideo ? 'Edit Video' : 'Add New Video'}</h3>
                <p className="text-[9px] text-slate-400 font-bold uppercase mt-1.5">Showcase a new walkthrough or product highlight</p>
              </div>
              <button onClick={() => setIsVideoModalOpen(false)} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
            </div>
            <form onSubmit={handleSaveVideo} className="p-6 sm:p-8 space-y-5">
              <InputField
                label="Video Title"
                value={videoForm.title}
                onChange={v => setVideoForm(f => ({ ...f, title: v }))}
                placeholder="e.g. Mahindra 2638 Loader Work"
              />

              {/* Upload mode toggle */}
              <div className="flex bg-slate-100 rounded-xl p-1">
                <button type="button" onClick={() => setUploadMode('youtube')}
                  className={`flex-1 py-3 rounded-lg text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all ${uploadMode === 'youtube' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}>
                  <Youtube size={14}/> YouTube Link
                </button>
                <button type="button" onClick={() => setUploadMode('upload')}
                  className={`flex-1 py-3 rounded-lg text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all ${uploadMode === 'upload' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}>
                  <Upload size={14}/> Upload Video
                </button>
              </div>

              {uploadMode === 'youtube' ? (
                <InputField
                  label="YouTube Video Link"
                  value={videoForm.youtube_link}
                  onChange={v => setVideoForm(f => ({ ...f, youtube_link: v }))}
                  placeholder="e.g. https://www.youtube.com/watch?v=goF_3TspZ6k"
                />
              ) : (
                <div className="space-y-3">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Upload Video File</label>
                  <div className="flex items-center gap-3">
                    <label className={`flex-1 flex items-center justify-center gap-2 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest cursor-pointer transition-all active:scale-95 ${isUploading ? 'bg-slate-100 text-slate-400' : 'bg-slate-100 border-2 border-dashed border-slate-300 hover:border-red-500 hover:bg-red-50 text-slate-600 hover:text-red-600'}`}>
                      <Upload size={16}/>
                      {isUploading ? 'Uploading...' : (videoForm.video_file_url ? 'Replace Video' : 'Choose Video File')}
                      <input type="file" accept="video/*" className="hidden" onChange={handleUploadVideoFile} disabled={isUploading} />
                    </label>
                  </div>
                  {videoForm.video_file_url && (
                    <div className="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl p-3">
                      <Video size={16} className="text-green-600 shrink-0"/>
                      <span className="text-[10px] font-bold text-green-700 truncate flex-1">{videoForm.video_file_url.split('/').pop()}</span>
                      <button type="button" onClick={() => setVideoForm(f => ({ ...f, video_file_url: '', video_file_id: 0 }))}
                        className="text-green-600 hover:text-green-800 p-1">
                        <X size={14}/>
                      </button>
                    </div>
                  )}
                </div>
              )}

              <div className="space-y-3">
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">Video Category</label>
                <div className="relative flex items-center bg-slate-50 border-2 border-slate-100 rounded-xl transition-all shadow-sm min-h-[60px] focus-within:border-red-500 focus-within:bg-white">
                  <select
                    value={videoForm.category_id}
                    onChange={e => setVideoForm(f => ({ ...f, category_id: e.target.value }))}
                    className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-sm"
                  >
                    <option value="" disabled>Select a Category...</option>
                    {categories.map(cat => <option key={cat.id} value={cat.id}>{cat.name}</option>)}
                  </select>
                </div>
              </div>
              <button type="submit" className="w-full bg-red-600 hover:bg-red-700 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-red-950/20 active:scale-95 transition-all mt-4 border-b-4 border-red-800">
                {editingVideo ? 'SAVE VIDEO CHANGES' : 'PUBLISH VIDEO'}
              </button>
            </form>
          </div>
        </div>
      )}

      {/* CATEGORIES DIALOG */}
      {isCatModalOpen && (
        <div className="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4 animate-in fade-in duration-200" onClick={e => { if (e.target === e.currentTarget) setIsCatModalOpen(false); }}>
          <div className="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md flex flex-col overflow-hidden max-h-[85vh] animate-in zoom-in duration-300">
            <div className="flex items-center justify-between p-6 sm:p-8 border-b border-slate-100 shrink-0">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-widest text-sm leading-none">Manage Categories</h3>
                <p className="text-[9px] text-slate-400 font-bold uppercase mt-1.5">{categories.length} Categories configured</p>
              </div>
              <button onClick={() => setIsCatModalOpen(false)} className="text-slate-400 hover:text-slate-700 transition-colors"><X size={20}/></button>
            </div>
            
            {/* Create Category form */}
            <form onSubmit={handleSaveCategory} className="p-6 border-b border-slate-100 bg-slate-50/50 space-y-4 shrink-0">
              <div className="flex gap-2 items-end">
                <div className="flex-1">
                  <InputField
                    label="Add Category Name"
                    value={newCatForm.name}
                    onChange={v => setNewCatForm(f => ({ ...f, name: v }))}
                    placeholder="e.g. Parts Counter"
                  />
                </div>
                <button type="submit" className="bg-slate-950 text-white px-5 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all active:scale-95 mb-0.5 h-[52px] leading-none">
                  Create
                </button>
              </div>
            </form>

            {/* List Categories */}
            <div className="overflow-y-auto flex-1 p-6 space-y-3 no-scrollbar">
              {categories.map(cat => (
                <div key={cat.id} className="flex items-center justify-between px-5 py-4 bg-slate-50 rounded-2xl group hover:bg-red-50 hover:border-red-100 border border-slate-100/50 transition-all">
                  <div>
                    <span className="font-black text-sm text-slate-900 uppercase tracking-tight">{cat.name}</span>
                  </div>
                  <button 
                    onClick={() => handleDeleteCategory(cat.id)} 
                    className="text-slate-300 hover:text-red-600 transition-colors p-1"
                    title="Delete Category"
                  >
                    <X size={16}/>
                  </button>
                </div>
              ))}
              {categories.length === 0 && (
                <p className="text-slate-400 text-center font-black uppercase text-[10px] tracking-widest py-8">No Categories Registered</p>
              )}
            </div>
          </div>
        </div>
      )}

    </div>
  );
};
