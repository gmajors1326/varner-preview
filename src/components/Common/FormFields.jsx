import React from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { ChevronRight } from 'lucide-react';

export const QUILL_STYLES = `
  .rich-text-field .ql-toolbar.ql-snow {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    padding: 12px 20px;
    border-top-left-radius: 1.35rem;
    border-top-right-radius: 1.35rem;
  }
  .rich-text-field .ql-container.ql-snow {
    border: none;
    font-family: inherit;
    font-size: 14px;
    min-height: 150px;
    border-bottom-left-radius: 1.35rem;
    border-bottom-right-radius: 1.35rem;
  }
  .rich-text-field .ql-editor {
    padding: 20px;
    color: #1e293b;
    line-height: 1.6;
  }
  .rich-text-field .ql-editor.ql-blank::before {
    color: #94a3b8;
    font-style: normal;
    left: 20px;
  }
  .rich-text-field .ql-snow .ql-picker.ql-color-picker .ql-picker-options {
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }
`;

const COLOR_PALETTE = [
  false, // Default color (clears styles to restore original state)
  '#000000', '#e60000', '#ff9900', '#ffff00', '#008a00', '#0066cc', '#9933ff',
  '#ffffff', '#facccc', '#ffebcc', '#ffffcc', '#cce8cc', '#cce0f5', '#ebd6ff',
  '#bbbbbb', '#f06666', '#ffc266', '#ffff66', '#66b966', '#66a3e0', '#c285ff',
  '#888888', '#a10000', '#b26b00', '#b2b200', '#006100', '#0047b2', '#6b24b2',
  '#444444', '#5c0000', '#663d00', '#666600', '#003700', '#002966', '#3d1466'
];

export const InputField = ({ label, value, onChange, error }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <input 
      type="text" 
      value={value} 
      onChange={e => onChange(e.target.value)} 
      className={`w-full bg-slate-50 border-2 rounded-xl p-4 font-black text-slate-900 outline-none transition-all shadow-sm text-xl leading-none min-h-[64px] ${error ? 'border-red-400 focus:border-red-500 bg-red-50/40' : 'border-slate-100 focus:border-slate-300 focus:bg-white'}`}
    />
    {error && <p className="text-[10px] font-bold text-red-600 pl-1" role="alert">{error}</p>}
  </div>
);

export const convertRgbToHexInHtml = (html) => {
  if (!html) return '';
  
  // Replace rgb(r, g, b)
  let result = html.replace(/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/g, (match, r, g, b) => {
    const red = parseInt(r, 10);
    const green = parseInt(g, 10);
    const blue = parseInt(b, 10);
    return "#" + ((1 << 24) + (red << 16) + (green << 8) + blue).toString(16).slice(1);
  });
  
  // Replace rgba(r, g, b, a)
  result = result.replace(/rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([\d.]+)\s*\)/g, (match, r, g, b, a) => {
    const red = parseInt(r, 10);
    const green = parseInt(g, 10);
    const blue = parseInt(b, 10);
    const alpha = parseFloat(a);
    if (alpha === 0) return 'transparent';
    return "#" + ((1 << 24) + (red << 16) + (green << 8) + blue).toString(16).slice(1);
  });
  
  return result;
};

export const TextAreaField = ({ label, value, onChange }) => (
  <div className="space-y-3 rich-text-field">
    <style dangerouslySetInnerHTML={{ __html: QUILL_STYLES }} />
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <div className="bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] overflow-visible focus-within:border-red-500 transition-all shadow-sm">
      <ReactQuill 
        theme="snow" 
        value={value} 
        onChange={onChange}
        modules={{ clipboard: { matchVisual: false }, toolbar: [
          [{ header: [1, 2, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ color: COLOR_PALETTE }, { background: COLOR_PALETTE }],
          [{ list: 'ordered'}, { list: 'bullet' }],
          ['clean']
        ] }}
        className="bg-transparent"
      />
    </div>
  </div>
);

export const SelectField = ({ label, options, value, onChange, placeholder, error }) => (
  <div className="space-y-3">
    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block pl-1">{label}</label>
    <div className={`relative flex items-center bg-slate-50 border-2 rounded-xl transition-all shadow-sm min-h-[64px] ${error ? 'border-red-400 focus-within:border-red-500 bg-red-50/40' : 'border-slate-100 focus-within:border-slate-300 focus-within:bg-white'}`}>
      <select 
        value={value} 
        onChange={e => onChange(e.target.value)} 
        className="w-full bg-transparent p-4 pr-12 font-black text-slate-900 outline-none appearance-none cursor-pointer text-xl leading-none"
        style={{ border: 'none', background: 'transparent', height: '60px', minHeight: '60px', padding: '1rem 3rem 1rem 1rem', outline: 'none', boxShadow: 'none' }}
      >
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((o, i) => <option key={i} value={o}>{o}</option>)}
      </select>
      <div className="absolute inset-y-0 right-5 flex items-center pointer-events-none text-slate-400">
        <ChevronRight size={24} className="rotate-90"/>
      </div>
    </div>
    {error && <p className="text-[10px] font-bold text-red-600 pl-1" role="alert">{error}</p>}
  </div>
);
