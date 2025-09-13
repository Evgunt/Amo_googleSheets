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
    // Перебираем все данные таблицы
    foreach ($values as $value) {
        $contact = getContacts($value[1]);
        $lead = $contact[0]['_embedded']['leads'][0]['id'];
        // Данные для сделки
        $lead_array = [
            'id' => $lead,
            'price' => $value[3],
            'comment' => $value[4],
        ];
        // Изменение сделки
        ChangeLead($lead_array);
        // Данные для контакта
        $contact_data = [
            'id' => $contact[0]['id'],
            'name' => $value[0],
            "phone" => $value[1],
            "email" => $value[2]
        ];
        // Изменение контакта
        ChangeContact($contact_data);
    }
} catch (Exception $ex) {
    http_response_code($ex->getCode());
    echo json_encode([
        'message' => $ex->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    Write('main_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
}
