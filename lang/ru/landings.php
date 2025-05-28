<?php

return [
    'index' => [
        'title' => 'Лендинги',
    ],
    'success' => [
        'download_ready' => 'Лендинг готов к скачиванию',
        'successfully_deleted_message_text' => 'Лендинг успешно удален',
        'successfully_loaded_message_text' => 'Лендинги успешно загружены',
    ],
    'errors' => [
        'file_not_found' => 'Файл не найден',
        'not_completed' => 'Лендинг не завершен',
        'error_occurred' => 'Произошла ошибка',
        'download_failed' => 'Ошибка загрузки',
    ],
    'form' => [
        'urlPlaceholder' => 'Введите ссылку для скачивания лендинга',
        'waitForDownloads' => 'Дождитесь завершения текущих загрузок',
        'submitButton' => 'Скачать',
    ],
    'table' => [
        'header' => [
            'id' => 'ID',
            'downloadLink' => 'Ссылка на скачивание',
            'dateAdded' => 'Дата добавления',
        ],
        'confirmDelete' => [
            'message' => 'Вы уверены, что хотите удалить этот лендинг? Это действие необратимо.',
            'title' => 'Подтвердите удаление',
            'confirmButton' => 'Удалить',
            'cancelButton' => 'Отмена',
        ],
        'status' => [
            'pending' => 'В ожидании',
            'completed' => 'Завершено',
            'failed' => 'Ошибка',
        ],
    ],
    'downloadFailedAuthorization' => [
        'title' => 'Ошибка загрузки',
        'description' => 'У вас нет прав для скачивания этого лендинга.',
    ],
    'downloadException' => [
        'title' => 'Ошибка загрузки',
        'description' => 'Произошла ошибка при скачивании лендинга.',
    ],
    'sourceFolderNotFound' => [
        'title' => 'Исходная папка не найдена',
        'description' => 'Исходная папка для лендинга не найдена.',
    ],
    'downloadCancelledByUser' => [
        'title' => 'Загрузка отменена',
        'description' => 'Загрузка отменена пользователем.',
    ],
    'alreadyInProgress' => [
        'title' => 'Загрузка уже выполняется',
        'description' => 'Загрузка по этому URL уже выполняется или находится в очереди.',
    ],
    'alreadyDownloaded' => [
        'title' => 'Уже скачано',
        'description' => 'Этот лендинг уже был скачан ранее.',
    ],
    'duplicateDownload' => [
        'title' => 'Дублирующий запрос',
        'description' => 'Запрос на скачивание по этому URL уже существует.',
    ],
    'downloadFailedUrlDisabled' => [
        'title' => 'Ошибка загрузки',
        'description' => 'Не удалось получить доступ к указанному URL. Возможно, он недоступен.',
    ],
    'antiFlood' => [
        'title' => 'Лимит исчерпан',
        'description' => 'Вы достигли лимита на скачивание. Пожалуйста, попробуйте позже.',
    ],
    'downloadStarted' => [
        'title' => 'Загрузка начата',
        'description' => 'Загрузка вашего лендинга началась.',
    ],
    'generalError' => [
        'title' => 'Ошибка',
        'description' => 'Произошла непредвиденная ошибка. Пожалуйста, попробуйте еще раз.',
    ],
    'downloadCancelled' => [
        'title' => 'Загрузка отменена',
        'description' => 'Загрузка была успешно отменена.',
    ],
    'downloadAlreadyCompleted' => [
        'title' => 'Уже завершено',
        'description' => 'Эта загрузка уже была завершена и не может быть отменена.',
    ],
    'downloadNotInProgress' => [
        'title' => 'Не в процессе',
        'description' => 'Эта загрузка в данный момент не ожидает или не выполняется.',
    ],
];
