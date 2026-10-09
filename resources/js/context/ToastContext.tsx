import React, { createContext, useContext, useState, useCallback } from 'react';

export type ToastType = 'success' | 'error' | 'info' | 'warning';

export interface ToastItem {
  id: string;
  message: string;
  type: ToastType;
  duration?: number;
}

interface ToastContextType {
  toasts: ToastItem[];
  showToast: (message: string, type?: ToastType, duration?: number) => void;
  removeToast: (id: string) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<ToastItem[]>([]);

  const removeToast = useCallback((id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const showToast = useCallback((message: string, type: ToastType = 'info', duration: number = 4000) => {
    const id = 'toast_' + Date.now() + Math.random().toString(36).substring(2, 6);
    setToasts((prev) => [...prev.slice(-2), { id, message, type, duration }]);

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
  }, [removeToast]);

  return (
    <ToastContext.Provider value={{ toasts, showToast, removeToast }}>
      {children}
      {/* Toast floating notification container */}
      <div
        className="fixed top-4 right-4 z-[99999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
        aria-live="polite"
      >
        {toasts.map((toast) => {
          const bgStyles = {
            success: 'bg-[#1C1917] text-white border-emerald-500/40 shadow-emerald-950/20',
            error: 'bg-[#1C1917] text-white border-rose-500/40 shadow-rose-950/20',
            warning: 'bg-[#1C1917] text-white border-amber-500/40 shadow-amber-950/20',
            info: 'bg-[#1C1917] text-white border-[#EAE3DE]/20 shadow-black/20',
          }[toast.type];

          const faIconClass = {
            success: 'fa-solid fa-circle-check text-emerald-400',
            error: 'fa-solid fa-triangle-exclamation text-rose-400',
            warning: 'fa-solid fa-bell text-amber-400',
            info: 'fa-solid fa-circle-info text-sky-400',
          }[toast.type];

          const accentColor = {
            success: '#10b981',
            error: '#f43f5e',
            warning: '#f59e0b',
            info: '#0ea5e9',
          }[toast.type];

          return (
            <div
              key={toast.id}
              className={`pointer-events-auto flex items-start gap-3 p-3.5 rounded-2xl border shadow-xl backdrop-blur-md transition-all duration-300 animate-in slide-in-from-top-3 fade-in ${bgStyles}`}
              style={{
                boxShadow: `0 12px 30px -4px rgba(0,0,0,0.35), 0 0 0 1px ${accentColor}33`,
              }}
            >
              <div
                className="w-7 h-7 rounded-xl flex items-center justify-center flex-shrink-0 text-sm"
                style={{ background: `${accentColor}25` }}
              >
                <i className={faIconClass} />
              </div>
              <div className="flex-1 text-xs leading-relaxed pt-0.5 font-medium pr-1 text-[#F5F1ED]">
                {toast.message}
              </div>
              <button
                type="button"
                onClick={() => removeToast(toast.id)}
                className="text-[#A8A29E] hover:text-white transition p-1 -mr-1 -mt-1 text-xs rounded-lg cursor-pointer"
                aria-label="Закрыть"
              >
                <i className="fa-solid fa-xmark" />
              </button>
            </div>
          );
        })}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
}
