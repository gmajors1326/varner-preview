import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import AnalyticsDashboard from './components/AnalyticsDashboard.jsx'
import { ErrorBoundary } from './components/ErrorBoundary'
import './index.css'

const MOUNT_SELECTORS = '#varner-inventory-app, .varner-inventory-app-mount';

const mount = () => {
  const uniqueElements = Array.from(new Set(
    Array.from(document.querySelectorAll(MOUNT_SELECTORS))
  ));

  uniqueElements.forEach(el => {
    if (!el.dataset.rendered) {
      el.dataset.rendered = "true";
      try {
        const root = ReactDOM.createRoot(el);
        root.render(
          <React.StrictMode>
            <ErrorBoundary name="Varner OS">
              <App />
            </ErrorBoundary>
          </React.StrictMode>
        );
      } catch (e) {
        console.error("Varner OS: Mounting failed:", e);
      }
    }
  });

  // Analytics dashboard mount
  const analyticsEl = document.querySelector('#varner-analytics-app #varner-analytics-mount');
  if (analyticsEl && !analyticsEl.dataset.rendered) {
    analyticsEl.dataset.rendered = "true";
    try {
      const root = ReactDOM.createRoot(analyticsEl);
      root.render(
        <React.StrictMode>
          <ErrorBoundary name="Analytics">
            <AnalyticsDashboard />
          </ErrorBoundary>
        </React.StrictMode>
      );
    } catch (e) {
      console.error("Analytics: Mounting failed:", e);
    }
  }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}

window.addEventListener('load', mount);

if (window.acf) {
    window.acf.addAction('render_block_preview', () => {
        setTimeout(mount, 200);
    });
}
