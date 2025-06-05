# Frontend тестирование BaseSelect компонентов

Этот набор тестов обеспечивает комплексное покрытие компонентов `baseSelect.js`, `creativesStore.js` и их интеграции.

## Структура тестов

```
tests/frontend/
├── components/
│   ├── baseSelect.test.js              # Unit тесты baseSelect компонента
│   └── baseSelectStore.integration.test.js # Интеграционные тесты с store
├── creatives/
│   └── creativesStore.test.js          # Unit тесты creativesStore
├── e2e/
│   └── baseSelectE2E.test.js           # E2E тесты UI взаимодействий
├── setup.js                            # Настройка тестовой среды
├── runAllTests.js                      # Скрипт запуска всех тестов
└── README.md                           # Эта документация
```

## Типы тестов

### 1. Unit тесты (`*.test.js`)

**Что тестируют:**

- Инициализацию компонентов
- Обработку событий
- Валидацию входных данных
- Граничные случаи
- Обработку ошибок

**Покрывают:**

- `baseSelect.js`: все методы, состояние, события
- `creativesStore.js`: все методы store, API вызовы, кэширование

### 2. Интеграционные тесты (`*.integration.test.js`)

**Что тестируют:**

- Синхронизацию между компонентом и store
- Передачу данных через storePath
- Работу callback'ов
- Обновления URL и состояния

**Покрывают:**

- Связку baseSelect + creativesStore
- Различные сценарии использования (perPage, фильтры)
- Восстановление после ошибок

### 3. E2E тесты (`*E2E.test.js`)

**Что тестируют:**

- Пользовательские взаимодействия
- API интеграцию
- Производительность
- Доступность

**Покрывают:**

- Клики по dropdown'ам
- Сетевые запросы
- Обновление UI
- Обработку ошибок сети

## Команды запуска

### Запуск всех тестов

```bash
node tests/frontend/runAllTests.js
```

### Запуск конкретного типа тестов

```bash
# Только unit тесты
node tests/frontend/runAllTests.js unit

# Только интеграционные тесты
node tests/frontend/runAllTests.js integration

# Только E2E тесты
node tests/frontend/runAllTests.js e2e

# Только store тесты
node tests/frontend/runAllTests.js store
```

### Запуск через npm/vitest напрямую

```bash
# Все тесты с покрытием
npm run test:frontend

# Конкретный файл
npx vitest tests/frontend/components/baseSelect.test.js

# В watch режиме
npx vitest --watch tests/frontend/components/

# С подробным выводом
npx vitest --reporter=verbose
```

## Граничные случаи и сценарии

### Базовый компонент

- ✅ Инициализация с пустыми опциями
- ✅ Неизвестные начальные значения
- ✅ Null/undefined значения
- ✅ Быстрые множественные клики
- ✅ Некорректные типы данных
- ✅ Отсутствующие методы в store

### Store интеграция

- ✅ Отсутствующий store
- ✅ Некорректный storePath
- ✅ Глубокая вложенность путей
- ✅ Отсутствующие callback'и
- ✅ Конкурентные обновления

### Сетевые запросы

- ✅ Медленная сеть
- ✅ Сетевые ошибки
- ✅ HTTP ошибки (500, 404)
- ✅ Невалидные ответы API
- ✅ Тайм-ауты запросов

### Пользовательский опыт

- ✅ Клавиатурная навигация
- ✅ Accessibility атрибуты
- ✅ Производительность (< 500ms)
- ✅ Большое количество опций (100+)
- ✅ Восстановление после ошибок

## Мокирование

### DOM и Browser API

```javascript
// Мокаем fetch для API вызовов
global.fetch = vi.fn();

// Мокаем window объекты
Object.defineProperty(window, 'location', {
  value: { pathname: '/creatives', search: '' },
  writable: true,
});

// Мокаем Alpine.js контекст
component.$store = mockStore;
component.$dispatch = mockDispatch;
component.$watch = mockWatch;
```

### Playwright для E2E

```javascript
// Мокаем browser actions
const mockBrowser = {
  navigate: vi.fn(),
  snapshot: vi.fn(),
  click: vi.fn(),
  networkRequests: vi.fn(),
};
```

## Отчеты и метрики

### Покрытие кода

- Автоматически генерируется в `coverage/`
- HTML отчет доступен в `coverage/index.html`
- Минимальный порог: 90% для critical paths

### Метрики производительности

- Время ответа UI: < 500ms
- Время API запросов: зависит от сети
- Инициализация компонента: < 100ms

## Отладка тестов

### Запуск конкретного теста

```bash
# С фильтром по названию
npx vitest --run --reporter=verbose -t "должен инициализироваться"

# Конкретный файл с debug
npx vitest --inspect-brk tests/frontend/components/baseSelect.test.js
```

### Логирование в тестах

```javascript
// Включить console.log в тестах
beforeEach(() => {
  vi.spyOn(console, 'log').mockImplementation(() => {});
});

// Посмотреть actual vs expected
console.log('Actual:', component.selectedOption);
console.log('Expected:', expectedOption);
```

### Мок диагностика

```javascript
// Проверить все вызовы мока
console.log('Mock calls:', mockFunction.mock.calls);

// Проверить что мок был вызван с правильными параметрами
expect(mockFunction).toHaveBeenCalledWith(expectedArgs);
```

## CI/CD интеграция

### GitHub Actions пример

```yaml
- name: Run Frontend Tests
  run: |
    npm ci
    node tests/frontend/runAllTests.js

- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    file: ./coverage/lcov.info
```

### Pre-commit hooks

```bash
# В .git/hooks/pre-commit
npm run test:frontend:quick
```

## Поддержка и развитие

### Добавление новых тестов

1. Определить тип теста (unit/integration/e2e)
2. Создать файл в соответствующей папке
3. Использовать существующие паттерны мокирования
4. Добавить в `runAllTests.js` если нужен новый тип

### Обновление при изменении кода

1. Проверить какие тесты затронуты
2. Обновить моки если изменился API
3. Добавить тесты для новой функциональности
4. Обновить граничные случаи

### Оптимизация производительности

1. Использовать `vi.mock()` для тяжелых зависимостей
2. Группировать схожие тесты в `describe` блоки
3. Переиспользовать setup логику через `beforeEach`
4. Очищать моки через `afterEach`

## Troubleshooting

### Частые проблемы

**Тесты падают с "Cannot find module"**

```bash
# Проверить алиасы в vitest.config.js
# Убедиться что пути правильные
```

**Моки не работают**

```bash
# Проверить порядок импортов
# vi.mock() должен быть до импорта модуля
```

**Тайм-ауты в E2E тестах**

```bash
# Увеличить timeout в конфигурации
# Проверить что все асинхронные операции ожидаются
```

**Нестабильные тесты**

```bash
# Добавить больше waitFor() вызовов
# Использовать детерминированные данные
# Проверить race conditions в асинхронном коде
```
