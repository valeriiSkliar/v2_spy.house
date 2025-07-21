<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новое сообщение с контактной формы</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .content {
            background: white;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .field {
            margin-bottom: 15px;
        }

        .field-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .field-value {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }

        .message-content {
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Новое сообщение с контактной формы</h1>
        <p>Получено новое сообщение через контактную форму сайта spy.house</p>
    </div>

    <div class="content">
        <div class="field">
            <div class="field-label">Имя отправителя:</div>
            <div class="field-value">{{ $name }}</div>
        </div>

        <div class="field">
            <div class="field-label">Email:</div>
            <div class="field-value">
                <a href="mailto:{{ $email }}">{{ $email }}</a>
            </div>
        </div>

        <div class="field">
            <div class="field-label">Сообщение:</div>
            <div class="field-value message-content">{{ $message }}</div>
        </div>

        <div class="field">
            <div class="field-label">Дата отправки:</div>
            <div class="field-value">{{ now()->format('d.m.Y H:i:s') }}</div>
        </div>
    </div>

    <div
        style="margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 4px; font-size: 12px; color: #6c757d;">
        <p><strong>Техническая информация:</strong></p>
        <p>Это автоматическое уведомление с сайта spy.house</p>
        <p>Для ответа используйте адрес: {{ $email }}</p>
    </div>
</body>

</html>