const API = window.varnerData?.rest_url
  ? window.varnerData.rest_url.replace(/\/$/, '') + '/varner/v1'
  : '/wp-json/varner/v1';

const NONCE = window.varnerData?.nonce ?? '';

export const getMobileToken = () => localStorage.getItem('varner_mobile_token') || '';

function convertRgbToHexInHtml(html) {
  if (typeof html !== 'string') return html;
  
  // Replace rgb(r, g, b)
  let result = html.replace(/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/g, (match, r, g, b) => {
    const red = parseInt(r, 10);
    const green = parseInt(g, 10);
    const blue = parseInt(b, 10);
    return "#" + ((1 << 24) + (red << 16) + (green << 8) + blue).toString(16).slice(1);
  });
  
  // Replace rgba(r, g, b, a)
  result = result.replace(/rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([\d.]+)\s*\)/g, (match, r, g, b, a) => {
    const red = parseInt(r, 10);
    const green = parseInt(g, 10);
    const blue = parseInt(b, 10);
    const alpha = parseFloat(a);
    if (alpha === 0) return 'transparent';
    return "#" + ((1 << 24) + (red << 16) + (green << 8) + blue).toString(16).slice(1);
  });
  
  return result;
}

function sanitizePayload(obj) {
  if (typeof obj === 'string') {
    return convertRgbToHexInHtml(obj);
  }
  if (Array.isArray(obj)) {
    return obj.map(sanitizePayload);
  }
  if (obj !== null && typeof obj === 'object') {
    const res = {};
    for (const key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        res[key] = sanitizePayload(obj[key]);
      }
    }
    return res;
  }
  return obj;
}

export async function apiFetch(path, options = {}) {
  const token = getMobileToken();
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { 'X-Varner-Mobile-Token': token } : { 'X-WP-Nonce': NONCE }),
    ...(options.headers ?? {}),
  };

  let body = options.body;
  if (body && typeof body === 'string') {
    try {
      const parsed = JSON.parse(body);
      const sanitized = sanitizePayload(parsed);
      body = JSON.stringify(sanitized);
    } catch (e) {
      body = convertRgbToHexInHtml(body);
    }
  }

  const res = await fetch(`${API}${path}`, {
    ...options,
    headers,
    ...(body ? { body } : {}),
  });
  if (!res.ok) {
    // Mobile context: 401 means the token expired server-side.
    // Clear it and signal the auth gate to reset — no hard page reload needed.
    if (res.status === 401 && getMobileToken()) {
      localStorage.removeItem('varner_mobile_token');
      window.dispatchEvent(new CustomEvent('varner:token-expired'));
    }
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message ?? `Request failed: ${res.status}`);
  }
  return res.json();
}

export async function uploadFile(file) {
  const token = getMobileToken();
  const headers = {
    ...(token ? { 'X-Varner-Mobile-Token': token } : { 'X-WP-Nonce': NONCE }),
  };

  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API}/media`, {
    method: 'POST',
    headers,
    body: form,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message ?? `Upload failed: ${res.status}`);
  }
  return res.json(); // { id, url }
}
