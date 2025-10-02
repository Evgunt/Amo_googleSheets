<?php
function Write($filename, $text) // Логгирование
{
    $file = "logs/$filename.txt";

    if ((int)filesize($file) > 50000)
        file_put_contents($file, '');
    file_put_contents($file, sprintf(
        '%s%s========================================================================================================================%s',
        print_r([
            "data" => $text,
            "time" => date('d.m.Y H:i:s')
        ], true),
        PHP_EOL . PHP_EOL,
        PHP_EOL . PHP_EOL
    ), FILE_APPEND);
}

function phoneTransform($phone)
{
    $result = preg_replace("/[^,.0-9]/", '', $phone);

    if (strlen($result) <= 10) {
        return $result;
    } else {
        return substr($result, -10);
    }
}

function CreateContact($data) // Функция сборки массива данных для создания контакта
{
    $fields = [];
    $contact_data = [[
        "name" => $data['name']
    ]];
    if (!empty($data['phone'])) {
        array_push(
            $fields,
            [
                "field_code" => "PHONE",
                "values" => [
                    [
                        "value" => phoneTransform($data['phone']),
                    ]
                ]
            ]
        );
    }

    if (!empty($data['email'])) {
        array_push(
            $fields,
            [
                "field_code" => "EMAIL",
                "values" => [
                    [
                        "value" => $data['email'],
                    ]
                ]
            ]
        );
    }

    if (!empty($fields))
        $contact_data[0]['custom_fields_values'] = $fields;

    return _CreateContact($contact_data);
}

function _CreateContact($data) // Функция создания контакта
{
    global $amoV4Client;
    $contact = $amoV4Client->POSTRequestApi('contacts', $data);
    return $contact['_embedded']['contacts'][0]['id'];
}

function CreateLead($data) // Функция сборки массива данных для создания сделки
{
    $fields = [];
    $lead_data = [
        [
            'name' => "Новая сделка",
            'price' => (int)$data['price'],
            'pipeline_id' => PIPELINE_ID,
            'status_id' => STATUS_ID
        ],
    ];
    if (!empty($data['comment']) && !empty(COMMENTS)) {
        array_push(
            $fields,
            [
                "field_id" => COMMENTS,
                "values" => [
                    [
                        "value" => $data['comment'],
                    ]
                ]
            ]
        );
    }
    if ($fields != [])
        $lead_data[0]['custom_fields_values'] = $fields;

    return _CreateLead($lead_data);
}

function _CreateLead($data) // Функция создания сделки
{
    global $amoV4Client;
    $lead = $amoV4Client->POSTRequestApi('leads', $data);
    return $lead['_embedded']['leads'][0]['id'];
}

function LinkLead($leadId, $contactId) // Функция линковки контакта со сделкой
{
    global $amoV4Client;
    $amoV4Client->POSTRequestApi('leads/' . $leadId . '/link', [[
        'to_entity_id' => $contactId,
        'to_entity_type' => 'contacts',
    ]]);
}

function getContacts($phone) // Функция получения контактов по сделке
{
    global $amoV4Client;
    $contacts = $amoV4Client->GETRequestApi(
        'contacts',
        ['with' => 'leads', 'query' => phoneTransform($phone)]
    );

    return $contacts['_embedded']['contacts'];
}

function ChangeLead($data) // Функция сборки массива данных для создания сделки
{
    $fields = [];
    $lead_data = [
        [
            'id' => $data['id'],
            'name' => "Новая сделка",
            'price' => (int)$data['price'],
            'pipeline_id' => PIPELINE_ID,
            'status_id' => STATUS_ID
        ],
    ];
    if (!empty($data['comment'])) {
        array_push(
            $fields,
            [
                "field_id" => COMMENTS,
                "values" => [
                    [
                        "value" => $data['comment'],
                    ]
                ]
            ]
        );
    }
    if ($fields != [])
        $lead_data[0]['custom_fields_values'] = $fields;

    return _ChangeLead($lead_data);
}

function _ChangeLead($data) // Функция создания сделки
{
    global $amoV4Client;
    $lead = $amoV4Client->POSTRequestApi('leads/' . $data['id'], $data);
    return $lead['_embedded']['leads'][0]['id'];
}


function ChangeContact($data) // Функция сборки массива данных для создания контакта
{
    $fields = [];
    $contact_data = [[
        "id" => $data['id'],
        "name" => $data['name']
    ]];
    if (!empty($data['phone'])) {
        array_push(
            $fields,
            [
                "field_code" => "PHONE",
                "values" => [
                    [
                        "value" => phoneTransform($data['phone']),
                    ]
                ]
            ]
        );
    }

    if (!empty($data['email'])) {
        array_push(
            $fields,
            [
                "field_code" => "EMAIL",
                "values" => [
                    [
                        "value" => $data['email'],
                    ]
                ]
            ]
        );
    }

    if (!empty($fields))
        $contact_data[0]['custom_fields_values'] = $fields;

    return _CreateContact($contact_data);
}

function _ChangeContact($data) // Функция создания контакта
{
    global $amoV4Client;
    $contact = $amoV4Client->POSTRequestApi('contacts/' . $data['id'], $data);
    return $contact['_embedded']['contacts'][0]['id'];
}

function col0ToLetter(int $col0): string
{
    $col = $col0 + 1;
    $s = '';
    while ($col > 0) {
        $col--;
        $s = chr(65 + ($col % 26)) . $s;
        $col = intdiv($col, 26);
    }
    return $s;
}
