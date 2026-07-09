import { useState } from 'react';

export function useMobileAuth() {
  const isMobileApp = window.varnerData?.is_mobile_app || window.location.pathname.includes('/mobile-app/');

  const [mobileToken, setMobileToken] = useState(
    isMobileApp ? 'cookie-auth' : ''
  );

  return {
    isMobileApp,
    mobileToken,
    setMobileToken,
  };
}
