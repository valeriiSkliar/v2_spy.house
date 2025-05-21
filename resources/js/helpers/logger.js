import { debagConfig } from '../config';

/**
 * Проверяет, должно ли сообщение быть выведено в консоль
 * @param {Object|undefined} options - Опции логирования
 * @returns {boolean} - Возвращает true, если сообщение должно быть выведено
 */
function shouldLog(options) {
  // Если передан объект options с полем debug, используем его значение
  if (options && typeof options.debug !== 'undefined') {
    return options.debug;
  }
  // Иначе используем глобальную настройку
  return debagConfig.debug;
}

/**
 * Условный логгер, который выводит сообщения в консоль только если включен режим отладки
 * @param {...any} args - Аргументы для логирования, последний может быть объектом с опцией debug
 */
function logger(...args) {
  // Проверяем, не является ли последний аргумент объектом опций
  const lastArg = args[args.length - 1];
  const hasOptions = lastArg && typeof lastArg === 'object' && 'debug' in lastArg;

  // Если последний аргумент - объект с опциями, извлекаем его
  const options = hasOptions ? args.pop() : undefined;

  // Проверяем, нужно ли выводить сообщение
  if (!shouldLog(options)) return;

  // Если первый аргумент - строка, добавляем стиль
  if (typeof args[0] === 'string') {
    console.log(
      '%c[LOG]%c ' + args[0],
      'color: #2196F3; font-weight: bold;', // Синий стиль для префикса
      'color: yellow; font-weight: normal;', // Обычный текст
      ...args.slice(1)
    );
  } else {
    console.log('%c[LOG]', 'color: #2196F3; font-weight: bold;', ...args);
  }
}

/**
 * Условный логгер для ошибок, который выводит сообщения в консоль только если включен режим отладки
 * @param {...any} args - Аргументы для логирования, последний может быть объектом с опцией debug
 */
function loggerError(...args) {
  // Проверяем, не является ли последний аргумент объектом опций
  const lastArg = args[args.length - 1];
  const hasOptions = lastArg && typeof lastArg === 'object' && 'debug' in lastArg;

  // Если последний аргумент - объект с опциями, извлекаем его
  const options = hasOptions ? args.pop() : undefined;

  // Проверяем, нужно ли выводить сообщение
  if (!shouldLog(options)) return;

  // Если первый аргумент - строка, добавляем стиль
  if (typeof args[0] === 'string') {
    console.error(
      '%c[ERROR]%c ' + args[0],
      'color: #F44336; font-weight: bold;', // Красный стиль для префикса
      'color: #333; font-weight: normal;', // Обычный текст
      ...args.slice(1)
    );
  } else {
    console.error('%c[ERROR]', 'color: #F44336; font-weight: bold;', ...args);
  }
}

/**
 * Условный логгер для предупреждений, выводит сообщения в консоль только если включен режим отладки
 * @param {...any} args - Аргументы для логирования, последний может быть объектом с опцией debug
 */
function loggerWarn(...args) {
  // Проверяем, не является ли последний аргумент объектом опций
  const lastArg = args[args.length - 1];
  const hasOptions = lastArg && typeof lastArg === 'object' && 'debug' in lastArg;

  // Если последний аргумент - объект с опциями, извлекаем его
  const options = hasOptions ? args.pop() : undefined;

  // Проверяем, нужно ли выводить сообщение
  if (!shouldLog(options)) return;

  // Если первый аргумент - строка, добавляем стиль
  if (typeof args[0] === 'string') {
    console.warn(
      '%c[WARN]%c ' + args[0],
      'color: #FF9800; font-weight: bold;', // Оранжевый стиль для префикса
      'color: #333; font-weight: normal;', // Обычный текст
      ...args.slice(1)
    );
  } else {
    console.warn('%c[WARN]', 'color: #FF9800; font-weight: bold;', ...args);
  }
}

/**
 * Условный логгер для успешных операций, выводит сообщения в консоль только если включен режим отладки
 * @param {...any} args - Аргументы для логирования, последний может быть объектом с опцией debug
 */
function loggerSuccess(...args) {
  // Проверяем, не является ли последний аргумент объектом опций
  const lastArg = args[args.length - 1];
  const hasOptions = lastArg && typeof lastArg === 'object' && 'debug' in lastArg;

  // Если последний аргумент - объект с опциями, извлекаем его
  const options = hasOptions ? args.pop() : undefined;

  // Проверяем, нужно ли выводить сообщение
  if (!shouldLog(options)) return;

  // Если первый аргумент - строка, добавляем стиль
  if (typeof args[0] === 'string') {
    console.log(
      '%c[SUCCESS]%c ' + args[0],
      'color: #4CAF50; font-weight: bold;', // Зеленый стиль для префикса
      'color: #333; font-weight: normal;', // Обычный текст
      ...args.slice(1)
    );
  } else {
    console.log('%c[SUCCESS]', 'color: #4CAF50; font-weight: bold;', ...args);
  }
}

export { logger, loggerError, loggerSuccess, loggerWarn };
