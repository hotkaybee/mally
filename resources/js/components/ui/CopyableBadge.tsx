import React, { useState } from 'react';

interface CopyableBadgeProps {
  text: string | number;
  label?: string;
  prefix?: string;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
  theme?: 'light' | 'dark';
}

export default function CopyableBadge({
  text,
  label,
  prefix = '',
  className = '',
  size = 'sm',
  theme = 'light',
}: CopyableBadgeProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = (e: React.MouseEvent) => {
    e.stopPropagation();
    e.preventDefault();
    const str = String(text).trim();
    if (navigator?.clipboard) {
      navigator.clipboard.writeText(str);
    }
    setCopied(true);
    setTimeout(() => setCopied(false), 1800);
  };

  const sizeClasses = {
    sm: 'text-[11px] px-2 py-0.5 gap-1',
    md: 'text-xs px-2.5 py-1 gap-1.5',
    lg: 'text-sm px-3.5 py-1.5 gap-2 font-bold',
  }[size];

  return (
    <button
      type="button"
      onClick={handleCopy}
      title={copied ? 'Скопировано в буфер!' : 'Нажмите, чтобы скопировать'}
      className={`inline-flex items-center justify-center rounded-lg font-mono font-medium whitespace-nowrap select-none transition-colors duration-150 ${sizeClasses} ${
        copied
          ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/40 shadow-xs'
          : theme === 'dark'
          ? 'bg-white/10 hover:bg-white/20 text-white/90 border border-white/15'
          : 'bg-black/5 hover:bg-black/10 text-stone-800 border border-stone-200'
      } ${className}`}
    >
      <span className="truncate">
        {prefix}
        {label || text}
      </span>
      <span className="w-3.5 h-3.5 inline-flex items-center justify-center shrink-0">
        {copied ? (
          <i className="fa-solid fa-check text-emerald-500 text-[11px]" />
        ) : (
          <i className="fa-solid fa-copy opacity-50 hover:opacity-100 text-[10px] transition-opacity" />
        )}
      </span>
    </button>
  );
}
