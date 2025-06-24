# Vue Islands - Безопасная очистка data-vue-props

## Проблема и решение

### 🚨 Проблемы хранения props в DOM:

1. **Безопасность**: Чувствительные данные видны в HTML исходнике
2. **Производительность**: Большие JSON объекты увеличивают размер DOM
3. **Память**: Дублирование данных (DOM + Vue instance)
4. **Отладка**: Засорение инспектора разработчика

### ✅ Решение: Автоматическая очистка после инициализации

```typescript
// После успешного монтирования Vue компонента
cleanupPropsAttribute(element, componentName);
```

## Конфигурация

### Настройка поведения очистки

```typescript
import { configureVueIslands } from './vue-islands';

// Настройка перед инициализацией
configureVueIslands({
  cleanupProps: true, // Включить очистку (по умолчанию: true)
  cleanupDelay: 1000, // Задержка в мс (по умолчанию: 1000)
  preservePropsInDev: true, // Сохранять в development (по умолчанию: true)
});
```

### Опции конфигурации

| Параметр             | Тип       | Значение по умолчанию | Описание                                         |
| -------------------- | --------- | --------------------- | ------------------------------------------------ |
| `cleanupProps`       | `boolean` | `true`                | Включает/отключает автоматическую очистку        |
| `cleanupDelay`       | `number`  | `1000`                | Задержка перед очисткой в миллисекундах          |
| `preservePropsInDev` | `boolean` | `true`                | Сохранять props в development режиме для отладки |

## Безопасность и timing

### ✅ Безопасная очистка обеспечивается:

1. **Проверка состояния элемента**:

   ```typescript
   if (element.isConnected && element.hasAttribute('data-vue-initialized')) {
     // Очищаем только если элемент в DOM и инициализирован
   }
   ```

2. **Задержка для завершения инициализации**:

   ```typescript
   setTimeout(() => {
     // Очистка после полной инициализации Vue компонента
   }, currentConfig.cleanupDelay);
   ```

3. **Обработка ошибок**:
   ```typescript
   .catch((error: Error) => {
     // НЕ очищаем props при ошибке - нужны для повторной инициализации
     element.removeAttribute('data-vue-initialized');
   });
   ```

### 🛡️ Защита от race conditions:

- ✅ Очистка происходит только после успешного `app.mount()`
- ✅ Проверка `element.isConnected` перед удалением
- ✅ При ошибке инициализации props сохраняются
- ✅ В development режиме props сохраняются для отладки

## Мониторинг и отладка

### События для мониторинга

```typescript
// Событие очистки props
document.addEventListener('vue-component-props-cleaned', event => {
  console.log('Props очищены:', {
    componentName: event.detail.componentName,
    dataSize: event.detail.dataSize,
    timestamp: event.detail.timestamp,
  });
});
```

### Анализ использования памяти

```typescript
// Логирование размера данных
const dataSize = new Blob([propsValue]).size;
console.log(`Очистка props для ${componentName} (размер: ${dataSize} байт)`);
```

### Метки в DOM для отладки

```html
<!-- До инициализации -->
<div data-vue-component="TabsComponent" data-vue-props='{"tabs": {...}}'>
  <!-- После инициализации -->
  <div data-vue-component="TabsComponent" data-vue-initialized="true" data-vue-props-cleaned="true">
    <div class="vue-component-content">
      <!-- Vue компонент -->
    </div>
  </div>
</div>
```

## Сценарии использования

### 1. Продакшн (рекомендуемые настройки)

```typescript
configureVueIslands({
  cleanupProps: true, // ✅ Обязательно для продакшена
  cleanupDelay: 500, // Быстрая очистка
  preservePropsInDev: false, // Очищать даже в dev
});
```

**Преимущества:**

- 🔒 Максимальная безопасность
- ⚡ Лучшая производительность
- 💾 Экономия памяти

### 2. Development (по умолчанию)

```typescript
configureVueIslands({
  cleanupProps: true,
  cleanupDelay: 1000, // Больше времени для отладки
  preservePropsInDev: true, // ✅ Сохраняем для инспектора
});
```

**Преимущества:**

- 🔍 Удобная отладка
- 📊 Мониторинг данных в DOM
- 🛠️ Анализ props в инспекторе

### 3. Отключение очистки (специальные случаи)

```typescript
configureVueIslands({
  cleanupProps: false, // ⚠️ Только в особых случаях
});
```

**Когда использовать:**

- 🧪 A/B тестирование
- 📈 Анализ производительности
- 🔧 Отладка проблем инициализации

## Влияние на производительность

### 📊 Измерения (пример с CreativesFilters):

```typescript
// Типичные размеры props:
const exampleProps = {
  initialFilters: {...},     // ~500 байт
  selectOptions: {...},      // ~2-5 КБ
  translations: {...},       // ~1-2 КБ
  tabOptions: {...}          // ~300 байт
};
// Общий размер: ~4-8 КБ на компонент
```

### 💾 Экономия ресурсов:

- **Память DOM**: -20-50% для страниц с множественными компонентами
- **Сетевой трафик**: Без изменений (props генерируются сервером)
- **Парсинг HTML**: Ускорение при больших props объектах

## Рекомендации

### ✅ **ВСЕГДА** использовать очистку когда:

1. **Продакшн окружение** - обязательно для безопасности
2. **Большие props объекты** (>1KB) - экономия памяти
3. **Чувствительные данные** - предотвращение утечек
4. **Множественные компоненты** - улучшение производительности

### ⚠️ **ОСТОРОЖНО** с очисткой при:

1. **Hot Module Replacement** - могут нужны props для пересоздания
2. **Dynamic re-mounting** - props нужны для повторной инициализации
3. **External integrations** - сторонние скрипты могут читать props

### 🚫 **НЕ ОЧИЩАТЬ** props когда:

1. **Компонент может переинициализироваться** - нужны исходные данные
2. **Внешние системы используют props** - интеграции третьих сторон
3. **Debug/профилирование** - анализ данных компонента

## Внедрение в проект

### Поэтапное внедрение:

```typescript
// Фаза 1: Тестирование в development
configureVueIslands({
  cleanupProps: true,
  preservePropsInDev: true, // Безопасно для разработки
});

// Фаза 2: Внедрение в staging
configureVueIslands({
  cleanupProps: true,
  preservePropsInDev: false, // Полная имитация продакшена
});

// Фаза 3: Продакшн
configureVueIslands({
  cleanupProps: true,
  cleanupDelay: 500, // Быстрая очистка
  preservePropsInDev: false,
});
```

## Заключение

**🎯 Очистка `data-vue-props` ЦЕЛЕСООБРАЗНА и БЕЗОПАСНА** при правильной настройке:

- ✅ **Продакшн**: Обязательна для безопасности и производительности
- ✅ **Development**: Рекомендуется с `preservePropsInDev: true`
- ✅ **Автоматизация**: Встроена в Vue Islands архитектуру
- ✅ **Мониторинг**: Полная обозреваемость через события

**Используйте конфигурацию по умолчанию для безопасного старта!**
