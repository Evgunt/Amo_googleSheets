# Документация

Синхронизация AmoCrm и GoogleSheets

## Файл fromSheets.php
Обработка запросов из GoogleSheets. Добавление сделок в Amo;

Использовалась библеотека Google Api (через composer.json все устанавливается);

## Файл changeLead.php
Обработка запросов из GoogleSheets. Изменение сделок в Amo;

## Файл webhook.php
Обработка запросов из AmoCrm. Изменение колонок в GoogleShhets;

Сам webhook в Amo не ставил т.к. нет сервера;

## Дополнительные файлы
В src классы для AmoCrm и GoogleSheets;

Так же отедельные функции в function.php;

Неизменяемые переменные в constants.php;


В каждом файле есть минимум комментариев для понимание кода


