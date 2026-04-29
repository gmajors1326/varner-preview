import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import './index.css'

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error("Varner OS Error:", error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          padding: '40px',
          background: '#fef2f2',
          border: '2px solid #ef4444',
          borderRadius: '12px',
          margin: '20px',
          fontFamily: 'sans-serif'
        }}>
          <h2 style={{ color: '#991b1b', margin: '0 0 10px 0' }}>Plugin Failed to Initialize</h2>
          <pre style={{ padding: '15px', background: '#fff', borderRadius: '8px', fontSize: '12px', border: '1px solid #fee2e2' }}>
            {this.state.error?.toString()}
          </pre>
        </div>
      );
    }
    return this.props.children;
  }
}

const mount = () => {
  // Use a Set to ensure we never process the same element twice in one pass
  const selectors = [
    '#varner-inventory-app',
    '.varner-inventory-app-mount'
  ];
  
  const uniqueElements = Array.from(new Set(
    Array.from(document.querySelectorAll(selectors.join(', ')))
  ));
  
  uniqueElements.forEach(el => {
    if (!el.dataset.rendered) {
      el.dataset.rendered = "true";
      try {
        const root = ReactDOM.createRoot(el);
        root.render(
          <React.StrictMode>
            <ErrorBoundary>
              <App />
            </ErrorBoundary>
          </React.StrictMode>
        );
      } catch (e) {
        console.error("Varner OS: Mounting failed:", e);
      }
    }
  });
};

// Initial trigger
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}

// Global window event for manual re-trigger
window.addEventListener('load', mount);

// Periodic check for dynamic content
setInterval(mount, 2000);

// Specific support for ACF and Gutenberg block previews
if (window.acf) {
    window.acf.addAction('render_block_preview', () => {
        setTimeout(mount, 200);
    });
}
