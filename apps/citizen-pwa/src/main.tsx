import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';

export function App(): React.JSX.Element {
  return (
    <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4 text-center">
      <div className="max-w-md w-full p-6 bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-200">
        <h1 className="text-xl font-bold text-slate-900 mb-2">
          سامانه جامع خدمات شهروندی و پیشخوان هوشمند
        </h1>
        <p className="text-sm text-slate-600">
          سوپراپلیکیشن شهروندی PWA — نسل جدید
        </p>
      </div>
    </div>
  );
}

const rootElement = document.getElementById('root');
if (rootElement) {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <App />
    </React.StrictMode>,
  );
}
