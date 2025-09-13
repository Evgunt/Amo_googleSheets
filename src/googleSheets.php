<?php

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class googleSheets
{
    private $path = "src/sheets_api.json";
    /**
     * Get all data from the spreadsheet.
     *
     * This method retrieves all data from the spreadsheet.
     *
     * @return array The data from the spreadsheet.
     */
    public function getSheets($spreadsheetId)
    {
        $client = new Google_Client();

        $range = 'Лист1!A1:ZZ'; // Диапазон, охватывающий все ячейки

        $client->setAuthConfig($this->path); // Токен из файла 
        $client->addScope('https://www.googleapis.com/auth/spreadsheets');

        $service = new Google_Service_Sheets($client);
        $response = $service->spreadsheets_values->get($spreadsheetId, $range); // Получение всех данных
        $values = $response->getValues();
        unset($values[0]); // удаляем заголовки
        return $values;
    }

    // Преобразование 0-based индекса колонки в буквенный адрес (0→A, 26→AA)
    function col0ToLetter(int $col0): string
    {
        $col = $col0 + 1;
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = intdiv($col, 26);
        }
        return $letter;
    }

    // Поиск строки по значению телефона в массиве значений, возвращает номер строки 1-based или null
    function findRowByPhone(array $values, string $phone)
    {
        foreach ($values as $rowIndex => $rowData) {
            foreach ($rowData as $cellValue) {
                if ((string)$cellValue === (string)$phone) {
                    return $rowIndex + 1; // 1-based
                }
            }
        }
        return null;
    }

    // Запись одной строки в указанную строку листа
    // $rowData — ассоциативный или индексный массив (ключи будут отсортированы)
    // $rowNumber — 1-based номер строки для записи
    // $spreadsheetId, $credentialsPath, $sheetName — параметры
    function changeSheetRow(array $rowData, int $rowNumber, string $spreadsheetId, string $sheetName = 'Лист1')
    {
        $client = new Client();
        $client->setAuthConfig($this->path);
        $client->addScope(Sheets::SPREADSHEETS);
        $service = new Sheets($client);

        // Подготовка значений: сортируем по ключам и берём значения в порядке
        ksort($rowData);
        $values = [array_values($rowData)]; // одна строка

        // Диапазон — от A до нужной колонки
        $startColLetter = col0ToLetter(0); // A
        $endColLetter = col0ToLetter(count($rowData) - 1);
        $range = sprintf('%s!%s%d:%s%d', $sheetName, $startColLetter, $rowNumber, $endColLetter, $rowNumber);

        $body = new ValueRange(['values' => $values]);
        $params = ['valueInputOption' => 'RAW'];

        $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        return $result->getUpdatedCells();
    }
}
