# Компоненты JS

## Лоадер

Компонент для управления полноэкранным лоадером.

### Простое использование

```js
import { loader } from '@/components';

// Показать лоадер
loader.show();

// Скрыть лоадер
loader.hide();
```

### Использование при асинхронных запросах

```js
import { loader } from '@/components';

async function fetchData() {
  try {
    // Показываем лоадер перед запросом
    loader.show();
    
    // Выполняем асинхронный запрос
    const response = await fetch('/api/some-endpoint');
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('Ошибка при получении данных:', error);
    throw error;
  } finally {
    // Скрываем лоадер после завершения запроса (всегда выполняется)
    loader.hide();
  }
} 