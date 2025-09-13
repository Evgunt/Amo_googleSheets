<?php
// Необходимые заголовки 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Content-type: application/json");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Подключаем файлы
require_once "src/functions.php";
require_once "src/constants.php";
require_once "src/AmoCrm.php";
require_once "src/googleSheets.php";
require_once 'vendor/autoload.php';

try {
    // Создаем экземпляры классов
    $amoV4Client = new AmoCrmV4Client(SUB_DOMAIN, CLIENT_ID, CLIENT_SECRET, CODE, REDIRECT_URL);
    $googleSheets = new googleSheets();
    // Получаем данные из таблицы
    $values = $googleSheets->getSheets(SHEET_ID);

    if (empty($values))
        // Если таблица пустая упадет в catch
        throw new Exception('Нет данных в таблице', 404);
    else {
        // Перебираем все данные таблицы
        foreach ($values as $value) {
            $contact = [];
            $lead = [];
            // Данные для контакта
            $contact = [
                'name' => $value[0],
                "phone" => $value[1],
                "email" => $value[2]
            ];
            // Создание контакта
            $contact_id = CreateContact($contact);
            // Данные для сделки
            $lead = [
                'price' => $value[3],
                'comment' => $value[4],
            ];
            // Создание сделки
            $lead_id = CreateLead($lead);
            // Линковка контакта со сделкой
            LinkLead($lead_id, $contact_id);
        }
        // Проверку на дубли не стал делать, потому что тестовое задание не требует этого
    }
} catch (Exception $ex) {
    http_response_code($ex->getCode());
    echo json_encode([
        'message' => $ex->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    Write('main_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
}
