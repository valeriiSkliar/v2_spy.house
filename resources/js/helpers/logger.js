import { debagConfig } from "../config";

/**
 * Условный логгер, который выводит сообщения в консоль только если включен режим отладки
 * @param {...any} args - Аргументы для логирования
 */
function logger(...args) {
    if (debagConfig.debug) console.log(...args);
}

/**
 * Условный логгер для ошибок, который выводит сообщения в консоль только если включен режим отладки
 * @param {...any} args - Аргументы для логирования
 */
function loggerError(...args) {
    if (debagConfig.debug) console.error(...args);
}

export {logger, loggerError};