import { vi } from 'vitest';

const axiosMock = {
  get: vi.fn((url) => {
    if (url === '/api/creatives/filter-presets') {
      return Promise.resolve({ data: { data: [] } });
    }
    if (url === '/api/creatives/favorites/ids') {
      return Promise.resolve({ data: { data: { ids: [], count: 0 } } });
    }
    return Promise.resolve({ data: {} });
  }),
  post: vi.fn(() => Promise.resolve({ data: {} })),
  put: vi.fn(() => Promise.resolve({ data: {} })),
  delete: vi.fn(() => Promise.resolve({ data: {} })),
};

window.axios = axiosMock;

export default axiosMock;
