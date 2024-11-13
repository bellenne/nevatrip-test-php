<?
require_once "lib/Database.php";
$random_data_list = [
    '{
    "user_id": 1,
    "event_id": 1,
    "date_id": 1,
    "tickets": {
        "kid": {
            "quantity": 1
        },
        "adult": {
            "quantity": 3
        },
        "group": {
            "quantity": 0
        },
        "preferential": {
            "quantity": 2
        }
    }
}',
'{
    "user_id": 2,
    "event_id": 2,
    "date_id": 3,
    "tickets": {
        "kid": {
            "quantity": 3
        },
        "adult": {
            "quantity": 2
        },
        "group": {
            "quantity": 0
        },
        "preferential": {
            "quantity": 0
        }
    }
}',
'{
    "user_id": 3,
    "event_id": 3,
    "date_id": 4,
    "tickets": {
        "kid": {
            "quantity": 0
        },
        "adult": {
            "quantity": 2
        },
        "group": {
            "quantity": 0
        },
        "preferential": {
            "quantity": 1
        }
    }
}',
'{
    "user_id": 4,
    "event_id": 4,
    "date_id": 5,
    "tickets": {
        "kid": {
            "quantity": 0
        },
        "adult": {
            "quantity": 2
        },
        "group": {
            "quantity": 1
        },
        "preferential": {
            "quantity": 0
        }
    }
}',
'{
    "user_id": 2,
    "event_id": 1,
    "date_id": 2,
    "tickets": {
        "kid": {
            "quantity": 3
        },
        "adult": {
            "quantity": 1
        },
        "group": {
            "quantity": 1
        },
        "preferential": {
            "quantity": 1
        }
    }
}'
];
$data = json_decode( $random_data_list[rand(0,count($random_data_list)-1)],false);

function generateBarcode($seq_len) {
    $result = array();
    for ($i=1; $i<=$seq_len; $i++)
        $result[] = mt_rand(1, 9);
    return implode("", $result);    
}

function prepareDataForApi($data){
    $db = new Database("events_price");
    $prices = $db->select("event_id, ticket_type_id, price, ticket_name")->join("ticket_type_id","tickets_type")->where(["event_id"=>$data->event_id])->run(false);

    $tickets = array();
    $tickets_quantity = 0;
    $equal_price = 0;
    foreach ($prices as $ticket_price) {
        $tickets_quantity += $data->tickets->{$ticket_price["ticket_name"]}->quantity;
        $equal_price += $ticket_price["price"];
        $tickets[$ticket_price["ticket_name"]] = ["quantity"=>$data->tickets->{$ticket_price["ticket_name"]}->quantity, "price"=>$ticket_price["price"], "barcode"=>generateBarcode(13)];
    }

    $db->table = "event_dates";
    $date = $db->getFromId($data->date_id);

    $preparedDataForApi = [
        "event_id" => $data->event_id,
        "event_date"=> $date["date"],
        "tickets"=>$tickets,
        "tickets_quantity"=>$tickets_quantity,
        "equal_price"=>$equal_price
    ];
    return $preparedDataForApi;
}

function sendApiBook($data = []){
    // CURL SENDING TO https://api.site.com/book

    // RANDOM RESULT, 2 положительных ответа, для больших шансов их получения
    $response = [
        '{"message":"order successfully booked"}',
        '{"message":"order successfully booked"}',
        '{"error":"barcode already exists"}',
    ];

    return json_decode($response[rand(0,2)], true);
}

function sendApiApprove($barcodes = []){
    // CURL SENDING TO https://api.site.com/approve
    // переписать api для использования массива или перебирать массив для одиночной отправки
    // RANDOM RESULT, несколько положительных ответов, для больших шансов их получения
    $response = [
        '{"message":"order successfully aproved"}',
        '{"message":"order successfully aproved"}',
        '{"message":"order successfully aproved"}',
        '{"message":"order successfully aproved"}',
        '{"message":"order successfully aproved"}',
        '{"message":"order successfully aproved"}',
        '{"error":"event cancelled"}',
        '{"error":"no tickets"}',
        '{"error":"no seats"}',
        '{"error":"fan removed"}',
    ];
    return json_decode($response[rand(0,9)], true);
}

function checkResponseFromApi($data){
    $preparedDataForApi = prepareDataForApi($data);
    $responseApi = sendApiBook($preparedDataForApi);
    if(isset($responseApi["error"])){ 
        
        return checkResponseFromApi($data);}
    else return $preparedDataForApi;
}

function getTicketsTypeList(){
    $db = new Database("orders");
    $db->table = "tickets_type";
    $tickets_types = $db->select("*")->run(false);
    $tickets_type_list = [];
    foreach ($tickets_types as $ticket_type) {
        $tickets_type_list[$ticket_type["ticket_name"]] = $ticket_type["id"];
    }
    return $tickets_type_list;
}

function createBarcodesArray($data){
    $barcodes = [];
    foreach ($data["tickets"] as $ticket) {
        array_push($barcodes, $ticket["barcode"]);
    }
    return $barcodes;
}

$responseBook = checkResponseFromApi($data);

$responseApprove = sendApiApprove(createBarcodesArray($responseBook));

if(isset($responseApprove["error"])){ echo $responseApprove["error"]; return;}

$preparedOrderData = [
    "user_id" => $data->user_id,
    "event_id" => $data->user_id,
    "event_date_id" => $data->user_id,
    "tickets_quantity" => $responseBook["tickets_quantity"],
    "equal_price" => $responseBook["equal_price"],
    "created" => date("Y-m-d H:m:s")
];

$db = new Database("orders");
$order_id = $db->insertGetId($preparedOrderData);

$tickets_type_list = getTicketsTypeList();

$ticket_ids_DB = [];
$db->table = "tickets";

foreach ($responseBook["tickets"] as $ticket_name => $ticket) {
    $ticket_id = $db->insertGetId(["order_id"=>$order_id, "ticket_type_id"=>$tickets_type_list[$ticket_name], "barcode" => $ticket["barcode"]]);
    array_push($ticket_ids_DB, $ticket_id);
}

echo "ID Заказа: $order_id; ID Билетов: " . implode(", ", $ticket_ids_DB);