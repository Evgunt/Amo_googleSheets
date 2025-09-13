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

use Google\Client;
use Google\Service\Sheets;

// Получение данных хука
// $data = json_decode(file_get_contents('php://input'), true);
$data['event']['data']['id'] = 53833647;
$data['event']['type_code'] = 'lead_status_changed';
try {
    if ($data['event']['type_code'] != 'lead_status_changed')
        throw new Exception('Не верный метод', 400);

    // Создаем экземпляры классов
    $amoV4Client = new AmoCrmV4Client(SUB_DOMAIN, CLIENT_ID, CLIENT_SECRET, CODE, REDIRECT_URL);
    $googleSheets = new googleSheets();

    // id сделки
    $lead_id = $data['event']['data']['id'];
    $lead = $amoV4Client->GETRequestApi('leads/' . $lead_id, ['with' => 'contacts']);
    $contact_id = $lead['_embedded']['contacts'][0]['id'];
    $contact = $amoV4Client->GETRequestApi('contacts/' . $contact_id);

    //Данные контакта
    $phone = $amoV4Client->GETField($contact['custom_fields_values'], 'PHONE');
    $email = $amoV4Client->GETField($contact['custom_fields_values'], 'EMAIL');
    $name = $contact['name'];
    // Данные сделки
    $price = $lead['price'];
    $comment = $amoV4Client->GETField($lead['custom_fields_values'], COMMENTS);
    $row_data = [
        1 => $name,
        2 => $phone,
        3 => $email,
        4 => $price,
        5 => $comment,
    ];
    // Инициализация клиента для чтения всех значений (для поиска)
    $client = new Client();
    $client->setAuthConfig(SHEET_PATH);
    $client->addScope(Sheets::SPREADSHEETS);
    $service = new Sheets($client);

    // Читаем весь лист (или определённый диапазон)
    $response = $service->spreadsheets_values->get(SHEET_ID, 'Лист1!A1:ZZ');
    $values = $response->getValues() ?: [];

    // Ищем строку по телефону
    $phoneToFind = $phone;
    $rowNumber = $googleSheets->findRowByPhone($values, $phoneToFind);

    // Если не найдено — добавляем в конец
    if ($rowNumber === null) {
        $rowNumber = count($values) + 1;
    }
    $updatedCells = $googleSheets->changeSheetRow($row_data, $rowNumber, SHEET_ID);
    echo "Updated cells: $updatedCells\n";
} catch (Exception $ex) {
    http_response_code($ex->getCode());
    echo json_encode([
        'message' => $ex->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    Write('main_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
}
