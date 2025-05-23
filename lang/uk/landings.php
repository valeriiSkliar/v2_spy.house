<?php

return [
    'index' => [
        'title' => 'Лендінги',
        'sort' => [
            'placeholder' => 'Сортувати за — :default',
            'options' => [
                'newest' => 'Найновіші',
                'oldest' => 'Найстаріші',
                'status_az' => 'Статус (А-Я)',
                'status_za' => 'Статус (Я-А)',
                'url_az' => 'URL (А-Я)',
                'url_za' => 'URL (Я-А)',
            ],
        ],
        'pagination' => [
            'placeholder' => 'На сторінці — :default',
            'options' => [
                '12' => '12',
                '24' => '24',
                '48' => '48',
                '96' => '96',
            ],
        ],
    ],
    'form' => [
        'urlPlaceholder' => 'Введіть посилання для завантаження Лендінгу',
        'waitForDownloads' => 'Зачекайте, доки завершаться поточні завантаження',
        'submitButton' => 'Завантажити',
    ],
    'table' => [
        'header' => [
            'id' => 'ID',
            'downloadLink' => 'Посилання для завантаження',
            'dateAdded' => 'Дата додавання',
        ],
        'confirmDelete' => [
            'message' => 'Ви впевнені, що хочете видалити цей лендінг? Цю дію неможливо скасувати.',
            'title' => 'Підтвердження видалення',
            'confirmButton' => 'Видалити',
            'cancelButton' => 'Скасувати',
        ],
        'status' => [
            'pending' => 'Очікує',
            'completed' => 'Завершено',
            'failed' => 'Не вдалося',
        ],
    ],
    'downloadFailedAuthorization' => [
        'title' => 'Скачування не вдалося',
        'description' => 'Ви не маєте доступу до скачування цієї сторінки.',
    ],
    'downloadException' => [
        'title' => 'Скачування не вдалося',
        'description' => 'Сталася помилка при скачуванні сторінки.',
    ],
    'sourceFolderNotFound' => [
        'title' => 'Папка джерела не знайдена',
        'description' => 'Папка джерела для сторінки не знайдена.',
    ],
    'downloadCancelledByUser' => [
        'title' => 'Скачування скасовано',
        'description' => 'Скачування скасовано користувачем.',
    ],
    'alreadyInProgress' => [
        'title' => 'Скачування в процесі',
        'description' => 'Скачування для цього URL вже в процесі або очікується.',
    ],
    'alreadyDownloaded' => [
        'title' => 'Сторінка вже скачана',
        'description' => 'Ця сторінка вже була скачана.',
    ],
    'duplicateDownload' => [
        'title' => 'Дублікат запиту',
        'description' => 'Запит на скачування для цього URL вже існує.',
    ],
    'downloadFailedUrlDisabled' => [
        'title' => 'Скачування не вдалося',
        'description' => 'Не вдалося отримати доступ до запропонованого URL. Він може бути недоступним.',
    ],
    'antiFlood' => [
        'title' => 'Досягнуто ліміту',
        'description' => 'Ви досягли ліміту скачування. Будь ласка, спробуйте пізніше.',
    ],
    'downloadStarted' => [
        'title' => 'Скачування почато',
        'description' => 'Скачування вашої сторінки почалося.',
    ],
    'generalError' => [
        'title' => 'Помилка',
        'description' => 'Сталася несподівана помилка. Будь ласка, спробуйте ще раз.',
    ],
    'downloadCancelled' => [
        'title' => 'Скачування скасовано',
        'description' => 'Скачування було успішно скасовано.',
    ],
    'downloadAlreadyCompleted' => [
        'title' => 'Скачування завершено',
        'description' => 'Скачування вже було завершено і не може бути скасовано.',
    ],
    'downloadNotInProgress' => [
        'title' => 'Скачування не в процесі',
        'description' => 'Скачування не в даний момент очікується або в процесі.',
    ],
];
