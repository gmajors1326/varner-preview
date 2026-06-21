import React from 'react';

export class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error(`Varner OS Error (${this.props.name || 'unknown'}):`, error, errorInfo);
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
          <h2 style={{ color: '#991b1b', margin: '0 0 10px 0' }}>
            {this.props.name || 'Tab'} Failed to Load
          </h2>
          <p style={{ color: '#7f1d1d', fontSize: '13px', margin: '0 0 10px 0' }}>
            An unexpected error occurred. Please try refreshing the page.
          </p>
          <pre style={{ padding: '15px', background: '#fff', borderRadius: '8px', fontSize: '12px', border: '1px solid #fee2e2' }}>
            {this.state.error?.toString()}
          </pre>
        </div>
      );
    }
    return this.props.children;
  }
}
