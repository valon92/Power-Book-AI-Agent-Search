const API_BASE = import.meta.env.VITE_API_URL || '/api';
const IMAGE_STORAGE_KEY = 'powerbook_search_image';
const SCOPE_STORAGE_KEY = 'powerbook_location_scope';

async function parseJsonResponse(res) {
  const text = await res.text();
  const trimmed = text.trim();

  if (trimmed.startsWith('<')) {
    throw new Error(
      'Server returned HTML instead of JSON. Ensure Laravel is running: php artisan serve'
    );
  }

  try {
    return JSON.parse(trimmed);
  } catch {
    throw new Error('Invalid JSON response from API');
  }
}

async function request(path, options = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...options.headers,
    },
    ...options,
  });

  const data = await parseJsonResponse(res);

  if (!res.ok) {
    throw new Error(data.message || `API error ${res.status}`);
  }

  return data;
}

export const api = {
  getGeo: () => request('/geo'),
  getTrending: () => request('/trending'),
  getExamples: () => request('/examples'),

  search: (query, filters = {}, locale = null, imageBase64 = null, locationScope = null) =>
    request('/search', {
      method: 'POST',
      body: JSON.stringify({
        q: query || '',
        filters,
        locale,
        image: imageBase64 || null,
        location_scope: locationScope || api.getLocationScope(),
      }),
    }),

  getLocationScope: () => localStorage.getItem(SCOPE_STORAGE_KEY) || 'auto',

  setLocationScope: (scope) => {
    if (scope) {
      localStorage.setItem(SCOPE_STORAGE_KEY, scope);
    } else {
      localStorage.removeItem(SCOPE_STORAGE_KEY);
    }
  },

  saveSearchImage: (base64) => {
    if (base64) {
      sessionStorage.setItem(IMAGE_STORAGE_KEY, base64);
    } else {
      sessionStorage.removeItem(IMAGE_STORAGE_KEY);
    }
  },

  loadSearchImage: () => sessionStorage.getItem(IMAGE_STORAGE_KEY),

  clearSearchImage: () => sessionStorage.removeItem(IMAGE_STORAGE_KEY),
};

export default api;
