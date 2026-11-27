const API = (typeof window !== 'undefined' && window.location?.origin)
  ? window.location.origin.replace(/\/$/, '') + '/wp-json/varner/v1'
  : (window.varnerData?.rest_url ? window.varnerData.rest_url.replace(/\/$/, '') + '/varner/v1' : '/wp-json/varner/v1');

let _nonce = window.varnerData?.nonce ?? '';

// Adopted by the login gate after a successful /login (post-auth nonce).
export function setNonce(n) { if (typeof n === 'string' && n) _nonce = n; }

// Fetch a fresh nonce from our own cookie-validated endpoint.
// NOT the REST index — /wp-json/ does not expose a nonce (verified).
let _nonceRefreshPromise = null;
let _hasSettled = false;
async function refreshNonce() {
  if (_nonceRefreshPromise) return _nonceRefreshPromise;
  _nonceRefreshPromise = (async () => {
    try {
      const res = await fetch(API + '/session', { credentials: 'include' });
      if (!res.ok) return false;            // 401 here = session genuinely gone
      const data = await res.json();
      if (data?.nonce && typeof data.nonce === 'string') { _nonce = data.nonce; return true; }
    } catch {}
    return false;
  })();
  const ok = await _nonceRefreshPromise;
  _nonceRefreshPromise = null;
  return ok;
}

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

const MAX_RETRIES = 2;
const RETRY_DELAYS = [1500, 3000]; // ms — escalating backoff

const MOCK_CATEGORY_TREE = {
  "Utility Vehicles": { "Utility": [] },
  "Tractors": {
    "175 HP to 299 HP": [],
    "100 HP to 174 HP": [],
    "40 HP to 99 HP": [],
    "Less than 40 HP": []
  },
  "Planting Equipment": { "Other": [] },
  "Tillage Equipment": {
    "Chisel Plows": [],
    "Disks": [],
    "Plows": [],
    "Rippers": [],
    "Rotary Tillage": [],
    "Row Crop Cultivators": [],
    "Other": []
  },
  "Hay and Forage Equipment": {
    "Bale Accumulators / Movers": [],
    "Disc Mowers": [],
    "Mower Conditioners/Windrowers": ["Self-Propelled", "Pull-Type", "Mounted"],
    "Hay Rakes": [],
    "Tedders": [],
    "Rotary Mowers": [],
    "Round Balers": [],
    "Square Balers": ["Large", "Small"],
    "Tub Grinders/Bale Processors": [],
    "Other": []
  },
  "Chemical Applicators": { "Sprayers": ["3 pt/Mounted"] },
  "Grain Handling / Storage Equipment": { "Grain Augers": [] },
  "Ag Trailers": { "Other": [] },
  "Snow Equipment": {
    "Lawn Mowers": ["Riding"],
    "Snow Blowers": []
  },
  "Implements": {
    "Blades/Box Scrapers": [],
    "Manure Spreaders": ["Dry"]
  },
  "Turf Equipment": { "Mowers": ["Fairway"] },
  "Trucks": {
    "Pickup Trucks": ["1/2 Ton"],
    "Service Trucks / Utility Trucks / Mechanic Trucks": [],
    "Truck Bodies Only": ["Other"]
  },
  "Semi-Trailers": { "Log Trailers": [] },
  "Trailers": {
    "Car Hauler Trailers": ["Enclosed", "Open"],
    "Cargo / Enclosed Trailers": [],
    "Dump Trailers": [],
    "Flatbed / Tag Trailers": [],
    "Livestock Trailers": [],
    "Tilt Trailers": [],
    "Landscaping Trailers": [],
    "Utility Trailers": ["ATV", "Snowmobile"],
    "Other Trailers": []
  }
};

function handleMockApi(path, options) {
  const cleanPath = path.split('?')[0];
  if (cleanPath === '/me') {
    return Promise.resolve({ id: 1, name: 'Greg', roles: ['administrator', 'editor'] });
  }
  if (cleanPath === '/brands') {
    return Promise.resolve(['Mahindra', 'Zetor', 'Titan Trailers', 'Big Tex']);
  }
  if (cleanPath === '/years') {
    return Promise.resolve(['2026', '2025', '2024', '2023', '2022']);
  }
  if (cleanPath === '/categories') {
    return Promise.resolve(Object.keys(MOCK_CATEGORY_TREE));
  }
  if (cleanPath === '/subcategories') {
    const subs = [];
    Object.values(MOCK_CATEGORY_TREE).forEach(o => {
      if (Array.isArray(o)) {
        subs.push(...o);
      } else if (o && typeof o === 'object') {
        subs.push(...Object.keys(o));
      }
    });
    return Promise.resolve([...new Set(subs)]);
  }
  if (cleanPath === '/category-tree') {
    return Promise.resolve(MOCK_CATEGORY_TREE);
  }
  if (cleanPath === '/inventory') {
    return Promise.resolve({ items: [], total: 0 });
  }
  if (cleanPath === '/inventory/deleted') {
    return Promise.resolve({ items: [], total: 0 });
  }
  if (cleanPath === '/session' || cleanPath === '/sessions') {
    return Promise.resolve({ items: [] });
  }
  if (cleanPath === '/ledger') {
    return Promise.resolve({ items: [] });
  }
  return Promise.resolve({});
}

export async function apiFetch(path, options = {}) {
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    return handleMockApi(path, options);
  }
  const headers = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': _nonce,
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

  for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
    const fetchOpts = {
      credentials: 'include',
      ...options,
      headers: { ...headers, 'X-WP-Nonce': _nonce },
      ...(body ? { body } : {}),
    };
    const res = await fetch(`${API}${path}`, fetchOpts);

    // Retry on rate-limit (429) or server overload (503) with backoff
    if ((res.status === 429 || res.status === 503) && attempt < MAX_RETRIES) {
      const delay = RETRY_DELAYS[attempt] ?? 3000;
      console.warn(`[Varner] ${res.status} on ${path} — retrying in ${delay}ms (attempt ${attempt + 1}/${MAX_RETRIES})`);
      await new Promise(r => setTimeout(r, delay));
      continue;
    }

    if (!res.ok) {
      // ── Stale-nonce auto-recovery: 403 + rest_cookie_invalid_nonce ──
      if (res.status === 403 && attempt === 0) {
        const errBody = await res.clone().json().catch(() => ({}));
        if (errBody.code === 'rest_cookie_invalid_nonce') {
          if (!_hasSettled) {
            _hasSettled = true;
            await new Promise(r => setTimeout(r, 400));
          }
          if (await refreshNonce()) continue;        // got a fresh nonce → retry once
          // refresh failed → session is actually gone; fall into the re-auth path below
          if (window.varnerData?.is_mobile_app) {
            window.dispatchEvent(new CustomEvent('varner:token-expired'));
          }
        }
      }

      // Existing 401 handler — session/token gone, NOT a nonce problem. Unchanged.
      if (res.status === 401 && window.varnerData?.is_mobile_app) {
        window.dispatchEvent(new CustomEvent('varner:token-expired'));
      }

      const errBody = await res.json().catch(() => ({}));
      const error = new Error(errBody.message ?? `Request failed: ${res.status}`);
      error.code = errBody.code;
      error.data = errBody.data;
      throw error;
    }
    return res.json();
  }
}

export async function uploadFile(file) {
  const headers = {
    'X-WP-Nonce': _nonce,
  };

  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API}/media`, {
    method: 'POST',
    headers,
    credentials: 'include',
    body: form,
  });
  if (!res.ok) {
    const text = await res.text().catch(() => '');
    throw new Error(`Upload failed (${res.status}): ${text.slice(0, 200)}`);
  }
  return res.json(); // { id, url }
}
