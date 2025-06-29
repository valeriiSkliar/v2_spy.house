// Настройка jsdom для тестирования DOM
import { afterAll, beforeAll, vi } from 'vitest';

// Мокаем global объекты которые могут понадобиться в Laravel проекте
global.window = window;
global.document = document;

// Настройка для Vue тестов
global.customElements = window.customElements;

// Мокаем Laravel Mix переменные если они используются
global.process = global.process || {};
global.process.env = global.process.env || {};

// Базовая настройка для Alpine.js если используется
Object.defineProperty(window, 'Alpine', {
  value: {},
  writable: true,
});

// Мокаем jQuery если используется глобально
global.$ = vi.fn();
global.jQuery = vi.fn();

// Настройка для axios если используется
vi.mock('axios', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ data: {} })),
    post: vi.fn(() => Promise.resolve({ data: {} })),
    put: vi.fn(() => Promise.resolve({ data: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
  },
}));

// Мокаем window.axios для тестов
global.window.axios = {
  get: vi.fn(() => Promise.resolve({ data: { data: {} } })),
  post: vi.fn(() => Promise.resolve({ data: { data: {} } })),
  delete: vi.fn(() => Promise.resolve({ data: { data: {} } })),
};

// Отключаем console.warn для тестов
const originalWarn = console.warn;
beforeAll(() => {
  console.warn = vi.fn();
});

afterAll(() => {
  console.warn = originalWarn;
});
