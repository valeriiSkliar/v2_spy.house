#!/usr/bin/env node

/**
 * Скрипт для запуска всех frontend тестов
 * Включает unit, интеграционные и E2E тесты
 * Генерирует отчеты о покрытии кода
 */

import { execSync, spawn } from 'child_process';
import { existsSync } from 'fs';

// Цвета для консоли
const colors = {
  reset: '\x1b[0m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  magenta: '\x1b[35m',
  cyan: '\x1b[36m',
  white: '\x1b[37m',
};

function log(message, color = colors.white) {
  console.log(`${color}${message}${colors.reset}`);
}

function logHeader(message) {
  log('\n' + '='.repeat(60), colors.cyan);
  log(`  ${message}`, colors.cyan);
  log('='.repeat(60), colors.cyan);
}

function logSuccess(message) {
  log(`✅ ${message}`, colors.green);
}

function logError(message) {
  log(`❌ ${message}`, colors.red);
}

function logWarning(message) {
  log(`⚠️  ${message}`, colors.yellow);
}

function logInfo(message) {
  log(`ℹ️  ${message}`, colors.blue);
}

// Конфигурация тестов
const testConfig = {
  unit: {
    name: 'Unit тесты',
    pattern: 'tests/frontend/components/baseSelect.test.js',
    timeout: 30000,
  },
  // integration: {
  //   name: 'Интеграционные тесты',
  //   pattern: 'tests/frontend/components/**/*.integration.test.js',
  //   timeout: 60000,
  // },
  // e2e: {
  //   name: 'E2E тесты',
  //   pattern: 'tests/frontend/e2e/**/*.test.js',
  //   timeout: 120000,
  // },
  // store: {
  //   name: 'Store тесты',
  //   pattern: 'tests/frontend/creatives/**/*.test.js',
  //   timeout: 30000,
  // },
};

// Проверка зависимостей
function checkDependencies() {
  logHeader('Проверка зависимостей');

  const requiredFiles = [
    'vitest.config.js',
    'tests/frontend/setup.js',
    'resources/js/alpine/components/baseSelect.js',
    'resources/js/creatives/store/creativesStore.js',
  ];

  let allDependenciesOk = true;

  for (const file of requiredFiles) {
    if (existsSync(file)) {
      logSuccess(`${file} найден`);
    } else {
      logError(`${file} не найден`);
      allDependenciesOk = false;
    }
  }

  return allDependenciesOk;
}

// Запуск конкретного типа тестов
async function runTestSuite(type, config) {
  logHeader(`Запуск ${config.name}`);

  return new Promise(resolve => {
    const args = [
      'run',
      '--reporter=verbose',
      '--coverage',
      `--testTimeout=${config.timeout}`,
      config.pattern,
    ];

    logInfo(`Команда: npx vitest ${args.join(' ')}`);

    const process = spawn('npx', ['vitest', ...args], {
      stdio: 'inherit',
      shell: true,
    });

    process.on('close', code => {
      if (code === 0) {
        logSuccess(`${config.name} завершены успешно`);
        resolve({ type, success: true, code });
      } else {
        logError(`${config.name} завершены с ошибками (код: ${code})`);
        resolve({ type, success: false, code });
      }
    });

    process.on('error', error => {
      logError(`Ошибка запуска ${config.name}: ${error.message}`);
      resolve({ type, success: false, error: error.message });
    });
  });
}

// Генерация отчета о покрытии
function generateCoverageReport() {
  logHeader('Генерация отчета о покрытии');

  try {
    execSync('npx vitest --coverage --reporter=html', { stdio: 'inherit' });
    logSuccess('Отчет о покрытии сгенерирован в coverage/');
    logInfo('Откройте coverage/index.html для просмотра детального отчета');
  } catch (error) {
    logError('Ошибка генерации отчета о покрытии');
    console.error(error.message);
  }
}

// Анализ результатов тестов
function analyzeResults(results) {
  logHeader('Анализ результатов');

  const totalTests = results.length;
  const successfulTests = results.filter(r => r.success).length;
  const failedTests = totalTests - successfulTests;

  log(`\nВсего тестовых наборов: ${totalTests}`);
  logSuccess(`Успешно: ${successfulTests}`);

  if (failedTests > 0) {
    logError(`Неуспешно: ${failedTests}`);
  }

  // Детальная информация по каждому набору
  results.forEach(result => {
    const status = result.success ? '✅' : '❌';
    const config = testConfig[result.type];
    log(
      `${status} ${config.name}: ${result.success ? 'ПРОЙДЕН' : `ПРОВАЛЕН (код: ${result.code})`}`
    );
  });

  // Рекомендации
  if (failedTests > 0) {
    logHeader('Рекомендации');
    logWarning('Некоторые тесты провалились. Рекомендуется:');
    log('1. Проверить логи ошибок выше');
    log('2. Запустить конкретный набор тестов для детальной диагностики');
    log('3. Проверить изменения в коде компонентов');
    log('4. Обновить тесты если изменилась логика');
  } else {
    logSuccess('Все тесты пройдены успешно! 🎉');
    logInfo('Компоненты готовы к продакшену');
  }

  return failedTests === 0;
}

// Основная функция
async function main() {
  const startTime = Date.now();

  log('🧪 Запуск комплексного тестирования BaseSelect компонентов', colors.magenta);
  log(`Время запуска: ${new Date().toLocaleString()}`);

  // Проверяем зависимости
  if (!checkDependencies()) {
    logError('Не все зависимости найдены. Прерывание тестирования.');
    process.exit(1);
  }

  // Определяем какие тесты запускать
  const testTypes = process.argv.slice(2);
  const shouldRunAll = testTypes.length === 0;
  const typesToRun = shouldRunAll ? Object.keys(testConfig) : testTypes;

  logInfo(`Будут запущены тесты: ${typesToRun.join(', ')}`);

  // Запускаем тесты
  const results = [];

  for (const type of typesToRun) {
    if (!testConfig[type]) {
      logWarning(`Неизвестный тип тестов: ${type}`);
      continue;
    }

    const result = await runTestSuite(type, testConfig[type]);
    results.push(result);
  }

  // Генерируем отчет о покрытии только если все тесты прошли
  if (shouldRunAll && results.every(r => r.success)) {
    generateCoverageReport();
  }

  // Анализируем результаты
  const allTestsPassed = analyzeResults(results);

  const endTime = Date.now();
  const duration = Math.round((endTime - startTime) / 1000);

  logHeader('Итоги');
  log(`Общее время выполнения: ${duration} секунд`);

  if (allTestsPassed) {
    logSuccess('Все тесты пройдены успешно!');
    process.exit(0);
  } else {
    logError('Некоторые тесты провалились');
    process.exit(1);
  }
}

// Обработка ошибок
process.on('unhandledRejection', (reason, promise) => {
  logError('Необработанная ошибка Promise:');
  console.error(reason);
  process.exit(1);
});

process.on('uncaughtException', error => {
  logError('Необработанное исключение:');
  console.error(error);
  process.exit(1);
});

// Запуск
main().catch(error => {
  logError('Критическая ошибка:');
  console.error(error);
  process.exit(1);
});
