import { useState, useEffect } from 'react';

export function useMobileAuth() {
  const isMobileApp = window.varnerData?.is_mobile_app || window.location.pathname.includes('/mobile-app/');

  const _varnerToken = window.varnerData?.mobile_token || '';
  if (_varnerToken) {
    localStorage.setItem('varner_mobile_token', _varnerToken);
    localStorage.setItem('varner_mobile_token_created_at', String(Date.now()));
  }

  const TOKEN_MAX_AGE = 24 * 60 * 60 * 1000;

  const storedCreatedAt = localStorage.getItem('varner_mobile_token_created_at');
  if (storedCreatedAt && Date.now() - Number(storedCreatedAt) > TOKEN_MAX_AGE) {
    localStorage.removeItem('varner_mobile_token');
    localStorage.removeItem('varner_mobile_token_created_at');
  }

  const [mobileToken, setMobileToken] = useState(
    localStorage.getItem('varner_mobile_token') || ''
  );

  return {
    isMobileApp,
    mobileToken,
    setMobileToken,
  };
}
