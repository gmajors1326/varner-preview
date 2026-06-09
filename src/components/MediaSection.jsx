import React, { useState, useEffect, useRef } from 'react';
import {
  DndContext, 
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  SortableContext,
  sortableKeyboardCoordinates,
  rectSortingStrategy,
  useSortable
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronLeft, ChevronRight, Image as ImageIcon, X, Plus, Loader2 } from 'lucide-react';

export const MiniCarousel = ({ images = [], alt = '' }) => {
  const [idx, setIdx] = useState(0);
  const count = images.length;
  const fallback = 'https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=100';

  useEffect(() => {
    if (count <= 1) return;
    const t = setInterval(() => setIdx(i => (i + 1) % count), 3000);
    return () => clearInterval(t);
  }, [count]);

  const go = (e, dir) => { e.stopPropagation(); setIdx(i => (i + dir + count) % count); };

  if (!count) return (
    <div className="w-full h-full flex items-center justify-center bg-slate-100">
      <ImageIcon size={16} className="text-slate-300"/>
    </div>
  );

  return (
    <div className="relative w-full h-full group/mc">
      {images.map((src, i) => (
        <img key={i} src={src} alt={alt}
             className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-500 ${i === idx ? 'opacity-100' : 'opacity-0'}`}
             onError={e => { e.target.onerror = null; e.target.src = fallback; }}/>
      ))}
      {count > 1 && <>
        <button onClick={e => go(e, -1)}
                className="absolute left-0.5 top-1/2 -translate-y-1/2 bg-black/60 text-white rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover/mc:opacity-100 transition-opacity z-10">
          <ChevronLeft size={11}/>
        </button>
        <button onClick={e => go(e, 1)}
                className="absolute right-0.5 top-1/2 -translate-y-1/2 bg-black/60 text-white rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover/mc:opacity-100 transition-opacity z-10">
          <ChevronRight size={11}/>
        </button>
        <div className="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-0.5 z-10">
          {images.map((_, i) => (
            <div key={i} className={`rounded-full transition-all duration-300 ${i === idx ? 'w-2 h-1.5 bg-white' : 'w-1.5 h-1.5 bg-white/50'}`}/>
          ))}
        </div>
      </>}
    </div>
  );
};

export const SortableImage = ({ img, i, onRemove }) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging
  } = useSortable({ id: img });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: isDragging ? 100 : 'auto',
    opacity: isDragging ? 0.3 : 1,
  };

  return (
    <div 
      ref={setNodeRef} 
      style={style} 
      className="aspect-[4/3] bg-slate-50 rounded-[1.5rem] overflow-hidden relative shadow-md group border-2 border-transparent hover:border-red-500 transition-all cursor-grab active:cursor-grabbing"
    >
      <div {...attributes} {...listeners} className="absolute inset-0 z-10" />
      <button 
        onClick={e => { e.stopPropagation(); onRemove(i); }} 
        className="absolute top-2 right-2 bg-red-600 text-white p-1.5 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-20 hover:bg-red-700"
      >
        <X size={14}/>
      </button>
      <img 
        src={img} 
        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000" 
        alt={`Image ${i+1}`} 
        onError={e => { e.target.onerror=null; e.target.src='https://images.unsplash.com/photo-1594495894542-a46cc73e081a?auto=format&fit=crop&q=80&w=400'; }}
      />
      {i === 0 && (
        <div className="absolute bottom-3 left-3 bg-red-600 text-white text-[8px] font-black px-3 py-1.5 rounded uppercase tracking-widest shadow-xl z-20">
          MASTER PHOTO
        </div>
      )}
    </div>
  );
};

export const MediaSection = ({ title, images, onAddFiles, onRemove, onReorder, isUploading }) => {
  const ref = useRef(null);
  
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const handleDragEnd = (event) => {
    const { active, over } = event;
    if (over && active.id !== over.id) {
      const oldIndex = images.indexOf(active.id);
      const newIndex = images.indexOf(over.id);
      onReorder(oldIndex, newIndex);
    }
  };

  return (
    <div className="bg-white rounded-[2rem] p-4 sm:p-6 lg:p-10 shadow-xl border border-slate-200/60">
      <div className="flex justify-between items-center mb-6 sm:mb-10">
        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 flex items-center gap-2">
          <ImageIcon size={14} className="text-red-600"/>{title}
        </h3>
        <span className="hidden sm:block bg-slate-50 text-slate-400 text-[9px] font-black uppercase italic px-4 py-2 rounded-full border border-slate-100 tracking-widest shadow-sm">
          Drag to Reorder
        </span>
      </div>
      
      <input 
        type="file" 
        multiple 
        accept="image/*" 
        className="hidden" 
        ref={ref} 
        onChange={e => { if (e.target.files?.length) onAddFiles(Array.from(e.target.files)); e.target.value = null; }}
      />

      <DndContext 
        sensors={sensors}
        collisionDetection={closestCenter}
        onDragEnd={handleDragEnd}
      >
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
          <SortableContext 
            items={images || []}
            strategy={rectSortingStrategy}
          >
            {images && images.map((img, i) => (
              <SortableImage key={img} img={img} i={i} onRemove={onRemove} />
            ))}
          </SortableContext>
          
          <div 
            onClick={() => !isUploading && ref.current?.click()} 
            className={`aspect-[4/3] border-2 border-dashed border-slate-200 rounded-[1.5rem] flex flex-col items-center justify-center transition-all bg-white group ${isUploading ? 'cursor-not-allowed text-slate-300 opacity-60' : 'text-slate-300 hover:text-red-600 hover:bg-red-50/20 cursor-pointer'}`}
          >
            <div className="bg-white p-3 rounded-full shadow-lg mb-2 border border-slate-50 group-hover:scale-110 transition-transform flex items-center justify-center">
              {isUploading ? <Loader2 className="animate-spin text-red-600" size={28} /> : <Plus size={28}/>}
            </div>
            <span className="text-[9px] font-black uppercase tracking-[0.2em]">{isUploading ? 'Uploading...' : 'Add Images'}</span>
          </div>
        </div>
      </DndContext>
    </div>
  );
};
