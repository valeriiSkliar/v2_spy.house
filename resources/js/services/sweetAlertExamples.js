import sweetAlertService, {
  confirm,
  error,
  info,
  input,
  select,
  success,
  timedWarning,
} from './sweetAlertService.js';

/**
 * Примеры использования SweetAlert2 Service
 * Этот файл содержит готовые примеры для всех типов модальных окон
 */

// Глобальная функция для демонстрации (можно удалить в продакшене)
window.sweetAlertExamples = {
  /**
   * Пример 1: Подтверждение удаления
   */
  async deleteConfirmation() {
    const result = await confirm(
      'Удалить запись?',
      'Это действие нельзя отменить. Все данные будут потеряны.',
      async () => {
        // Здесь выполняется удаление
        console.log('Запись удалена');
        await success('Удалено!', 'Запись успешно удалена');
      }
    );

    if (!result) {
      console.log('Удаление отменено');
    }
  },

  /**
   * Пример 2: Подтверждение отправки формы
   */
  async submitFormConfirmation() {
    const result = await confirm(
      'Отправить форму?',
      'Проверьте правильность введенных данных',
      async () => {
        // Имитация отправки формы
        console.log('Форма отправляется...');

        // Имитация задержки
        await new Promise(resolve => setTimeout(resolve, 1000));

        await success('Отправлено!', 'Форма успешно отправлена');
      },
      {
        confirmButtonText: 'Отправить',
        cancelButtonText: 'Проверить еще раз',
      }
    );
  },

  /**
   * Пример 3: Уведомление об успехе с автозакрытием
   */
  async successNotification() {
    await success('Данные сохранены!', 'Изменения успешно применены', { timer: 2000 });
  },

  /**
   * Пример 4: Уведомление об ошибке
   */
  async errorNotification() {
    await error(
      'Ошибка сети!',
      'Не удалось подключиться к серверу. Проверьте интернет-соединение.'
    );
  },

  /**
   * Пример 5: Ввод имени пользователя
   */
  async inputUserName() {
    const name = await input('Как вас зовут?', 'Введите ваше имя', 'text', value => {
      if (value.length < 2) {
        return 'Имя должно содержать минимум 2 символа';
      }
      return null;
    });

    if (name) {
      await success('Привет!', `Добро пожаловать, ${name}!`);
    }
  },

  /**
   * Пример 6: Ввод email
   */
  async inputEmail() {
    const email = await input('Введите email', 'example@domain.com', 'email');

    if (email) {
      await success('Email сохранен!', `Ваш email: ${email}`);
    }
  },

  /**
   * Пример 7: Предупреждение с таймером (сессия)
   */
  async sessionWarning() {
    const continued = await timedWarning(
      'Сессия истекает!',
      'Ваша сессия истечет через 10 секунд. Нажмите "Продолжить" для продления.',
      10000,
      async () => {
        console.log('Сессия продлена');
        await success('Сессия продлена!', 'Вы можете продолжить работу');
      }
    );

    if (!continued) {
      console.log('Сессия истекла');
      await error('Сессия истекла', 'Пожалуйста, войдите в систему заново');
    }
  },

  /**
   * Пример 8: Выбор роли пользователя
   */
  async selectUserRole() {
    const roles = [
      { value: 'admin', text: 'Администратор' },
      { value: 'manager', text: 'Менеджер' },
      { value: 'user', text: 'Пользователь' },
      { value: 'guest', text: 'Гость' },
    ];

    const selectedRole = await select(
      'Выберите роль',
      roles,
      'Выберите роль пользователя...',
      async role => {
        console.log('Выбрана роль:', role);
        const roleText = roles.find(r => r.value === role)?.text;
        await success('Роль назначена!', `Пользователю назначена роль: ${roleText}`);
      }
    );
  },

  /**
   * Пример 9: Выбор языка
   */
  async selectLanguage() {
    const languages = [
      { value: 'ru', text: 'Русский' },
      { value: 'en', text: 'English' },
      { value: 'de', text: 'Deutsch' },
      { value: 'fr', text: 'Français' },
    ];

    const selectedLang = await select(
      'Выберите язык',
      languages,
      'Выберите предпочитаемый язык...'
    );

    if (selectedLang) {
      const langText = languages.find(l => l.value === selectedLang)?.text;
      await info('Язык изменен', `Выбран язык: ${langText}`);
    }
  },

  /**
   * Пример 10: Информационное сообщение
   */
  async infoMessage() {
    await info(
      'Новая функция!',
      'Теперь вы можете использовать новые возможности системы. Ознакомьтесь с документацией для получения подробной информации.'
    );
  },

  /**
   * Пример 11: Цепочка модальных окон
   */
  async modalChain() {
    // Первое окно - информация
    await info('Начинаем процесс', 'Сейчас мы пройдем несколько шагов');

    // Второе окно - ввод данных
    const name = await input('Шаг 1', 'Введите ваше имя', 'text');

    if (!name) return;

    // Третье окно - выбор
    const options = [
      { value: 'option1', text: 'Вариант 1' },
      { value: 'option2', text: 'Вариант 2' },
      { value: 'option3', text: 'Вариант 3' },
    ];

    const choice = await select('Шаг 2', options, 'Выберите вариант...');

    if (!choice) return;

    // Четвертое окно - подтверждение
    const confirmed = await confirm(
      'Подтвердите данные',
      `Имя: ${name}\nВыбор: ${options.find(o => o.value === choice)?.text}`,
      async () => {
        await success('Готово!', 'Все данные сохранены');
      }
    );
  },

  /**
   * Пример 12: Обработка ошибок с повтором
   */
  async retryOperation() {
    const performOperation = async () => {
      // Имитация операции с возможной ошибкой
      const success = Math.random() > 0.5;

      if (success) {
        await sweetAlertService.success('Успех!', 'Операция выполнена успешно');
        return true;
      } else {
        const retry = await confirm(
          'Ошибка операции',
          'Операция не удалась. Попробовать еще раз?',
          null,
          {
            confirmButtonText: 'Повторить',
            cancelButtonText: 'Отмена',
          }
        );

        if (retry) {
          return await performOperation(); // Рекурсивный вызов
        }
        return false;
      }
    };

    await performOperation();
  },
};

// Экспорт примеров для использования в других модулях
export default window.sweetAlertExamples;
