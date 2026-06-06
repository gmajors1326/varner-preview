import React from 'react';

export const CollapsiblePanel = ({ title, icon, isOpen, onToggle, children }) => (
  <div className="bg-white rounded-[2rem] shadow-xl border border-slate-200/60 overflow-hidden transition-all duration-300">
    <button
      onClick={onToggle}
      className="w-full flex items-center justify-between p-6 sm:p-8 text-left hover:bg-slate-50/50 transition-colors"
    >
      <div className="flex items-center gap-4">
        <div className="bg-red-50 text-red-600 p-3 rounded-2xl">
          {icon}
        </div>
        <div>
          <h4 className="font-black text-base uppercase tracking-tight text-slate-900">{title}</h4>
        </div>
      </div>
      <span className="text-slate-400 font-black text-lg select-none mr-2">
        {isOpen ? '−' : '+'}
      </span>
    </button>
    {isOpen && (
      <div className="p-6 sm:p-8 border-t border-slate-100 bg-white space-y-6">
        {children}
      </div>
    )}
  </div>
);
