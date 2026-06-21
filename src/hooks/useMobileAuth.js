import { useState, useEffect } from 'react';

export function useMobileAuth() {
  const isMobileApp = window.varnerData?.is_mobile_app || window.location.pathname.includes('/mobile-app/');

  const _varnerToken = window.varnerData?.mobile_token || '';
  if (_varnerToken) localStorage.setItem('varner_mobile_token', _varnerToken);

  const [mobileToken, setMobileToken] = useState(
    _varnerToken ||
    new URLSearchParams(window.location.search).get('token') ||
    localStorage.getItem('varner_mobile_token') || ''
  );
  const [mobileActiveTab, setMobileActiveTab] = useState('edit');

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tokenFromUrl = urlParams.get('token');
    if (tokenFromUrl) {
      localStorage.setItem('varner_mobile_token', tokenFromUrl);
      setMobileToken(tokenFromUrl);
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }, []);

  return {
    isMobileApp,
    mobileToken,
    setMobileToken,
    mobileActiveTab,
    setMobileActiveTab,
  };
}
