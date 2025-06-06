/**
 * Утилиты и вспомогательные функции для креативов
 */

/**
 * Форматирует размер файла в читаемом виде
 * @param {number} bytes - Размер в байтах
 * @returns {string} Отформатированный размер
 */
export function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Форматирует дату в читаемом виде
 * @param {string|Date} date - Дата для форматирования
 * @param {string} format - Формат вывода ('relative' | 'short' | 'full')
 * @returns {string} Отформатированная дата
 */
export function formatDate(date, format = 'relative') {
    if (!date) return '';
    
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diffTime = Math.abs(now - dateObj);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    switch (format) {
        case 'relative':
            if (diffDays === 0) return 'Сегодня';
            if (diffDays === 1) return 'Вчера';
            if (diffDays <= 7) return `${diffDays} дней назад`;
            if (diffDays <= 30) return `${Math.ceil(diffDays / 7)} недель назад`;
            if (diffDays <= 365) return `${Math.ceil(diffDays / 30)} месяцев назад`;
            return `${Math.ceil(diffDays / 365)} лет назад`;
            
        case 'short':
            return dateObj.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
        case 'full':
            return dateObj.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
        default:
            return dateObj.toLocaleDateString('ru-RU');
    }
}

/**
 * Проверяет, является ли файл изображением
 * @param {string} fileType - Тип файла
 * @returns {boolean}
 */
export function isImageFile(fileType) {
    if (!fileType) return false;
    const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    return imageTypes.includes(fileType.toLowerCase());
}

/**
 * Проверяет, является ли файл видео
 * @param {string} fileType - Тип файла
 * @returns {boolean}
 */
export function isVideoFile(fileType) {
    if (!fileType) return false;
    const videoTypes = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'wmv', 'flv'];
    return videoTypes.includes(fileType.toLowerCase());
}

/**
 * Получает иконку для типа файла
 * @param {string} fileType - Тип файла
 * @returns {string} CSS класс иконки
 */
export function getFileIcon(fileType) {
    if (!fileType) return 'fas fa-file';
    
    const type = fileType.toLowerCase();
    
    if (isImageFile(type)) return 'fas fa-image';
    if (isVideoFile(type)) return 'fas fa-video';
    
    const iconMap = {
        pdf: 'fas fa-file-pdf',
        doc: 'fas fa-file-word',
        docx: 'fas fa-file-word',
        xls: 'fas fa-file-excel',
        xlsx: 'fas fa-file-excel',
        ppt: 'fas fa-file-powerpoint',
        pptx: 'fas fa-file-powerpoint',
        txt: 'fas fa-file-alt',
        zip: 'fas fa-file-archive',
        rar: 'fas fa-file-archive',
        '7z': 'fas fa-file-archive'
    };
    
    return iconMap[type] || 'fas fa-file';
}

/**
 * Обрезает текст до указанной длины
 * @param {string} text - Исходный текст
 * @param {number} maxLength - Максимальная длина
 * @param {string} suffix - Суффикс для обрезанного текста
 * @returns {string} Обрезанный текст
 */
export function truncateText(text, maxLength = 50, suffix = '...') {
    if (!text || text.length <= maxLength) return text || '';
    return text.substring(0, maxLength).trim() + suffix;
}

/**
 * Дебаунс функции
 * @param {Function} func - Функция для дебаунса
 * @param {number} wait - Время ожидания в мс
 * @returns {Function} Дебаунсированная функция
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle функции
 * @param {Function} func - Функция для throttle
 * @param {number} limit - Лимит времени в мс
 * @returns {Function} Throttled функция
 */
export function throttle(func, limit) {
    let inThrottle;
    return function executedFunction(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Копирует текст в буфер обмена
 * @param {string} text - Текст для копирования
 * @returns {Promise<boolean>} Результат операции
 */
export async function copyToClipboard(text) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        } else {
            // Fallback для старых браузеров
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            const result = document.execCommand('copy');
            document.body.removeChild(textArea);
            return result;
        }
    } catch (error) {
        console.error('Failed to copy text:', error);
        return false;
    }
}

/**
 * Скачивает файл по URL
 * @param {string} url - URL файла
 * @param {string} filename - Имя файла для скачивания
 */
export async function downloadFile(url, filename) {
    try {
        const response = await fetch(url);
        const blob = await response.blob();
        
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = filename || 'download';
        a.style.display = 'none';
        
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        window.URL.revokeObjectURL(downloadUrl);
    } catch (error) {
        console.error('Download failed:', error);
        throw new Error('Ошибка при скачивании файла');
    }
}

/**
 * Генерирует уникальный ID
 * @returns {string} Уникальный ID
 */
export function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

/**
 * Проверяет, является ли значение пустым
 * @param {any} value - Значение для проверки
 * @returns {boolean}
 */
export function isEmpty(value) {
    if (value == null) return true;
    if (typeof value === 'string') return value.trim() === '';
    if (Array.isArray(value)) return value.length === 0;
    if (typeof value === 'object') return Object.keys(value).length === 0;
    return false;
}

/**
 * Глубокое клонирование объекта
 * @param {any} obj - Объект для клонирования
 * @returns {any} Клонированный объект
 */
export function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') return obj;
    if (obj instanceof Date) return new Date(obj.getTime());
    if (obj instanceof Array) return obj.map(item => deepClone(item));
    
    const cloned = {};
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            cloned[key] = deepClone(obj[key]);
        }
    }
    return cloned;
}

/**
 * Сравнивает два объекта на равенство
 * @param {any} a - Первый объект
 * @param {any} b - Второй объект
 * @returns {boolean}
 */
export function isEqual(a, b) {
    if (a === b) return true;
    if (a == null || b == null) return false;
    if (typeof a !== typeof b) return false;
    
    if (typeof a === 'object') {
        if (Array.isArray(a) !== Array.isArray(b)) return false;
        
        const keysA = Object.keys(a);
        const keysB = Object.keys(b);
        
        if (keysA.length !== keysB.length) return false;
        
        for (const key of keysA) {
            if (!keysB.includes(key)) return false;
            if (!isEqual(a[key], b[key])) return false;
        }
        
        return true;
    }
    
    return false;
}

/**
 * Форматирует число с разделителями разрядов
 * @param {number} number - Число для форматирования
 * @returns {string} Отформатированное число
 */
export function formatNumber(number) {
    if (typeof number !== 'number') return '0';
    return number.toLocaleString('ru-RU');
}

/**
 * Получает контрастный цвет для фона
 * @param {string} backgroundColor - Цвет фона в hex
 * @returns {string} Контрастный цвет ('black' или 'white')
 */
export function getContrastColor(backgroundColor) {
    if (!backgroundColor) return 'black';
    
    const hex = backgroundColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    
    const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return brightness > 128 ? 'black' : 'white';
}

/**
 * Валидирует URL
 * @param {string} url - URL для валидации
 * @returns {boolean}
 */
export function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

/**
 * Экранирует HTML
 * @param {string} html - HTML для экранирования
 * @returns {string} Экранированный HTML
 */
export function escapeHtml(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}

/**
 * Создает объект с состоянием загрузки
 * @returns {object} Объект состояния загрузки
 */
export function createLoadingState() {
    return {
        loading: false,
        error: null,
        data: null,
        
        setLoading(loading) {
            this.loading = loading;
            if (loading) {
                this.error = null;
            }
        },
        
        setError(error) {
            this.error = error;
            this.loading = false;
        },
        
        setData(data) {
            this.data = data;
            this.loading = false;
            this.error = null;
        },
        
        reset() {
            this.loading = false;
            this.error = null;
            this.data = null;
        }
    };
}