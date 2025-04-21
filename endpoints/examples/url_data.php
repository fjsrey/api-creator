<?php
/*
Usando CURL en la Terminal:

    curl -X GET "http://127.0.0.1/apicreator/api/examples/api/data/EL_DATO_1/prueba/EL_DATO_2/try?data3=EL%20DATO%203&data4=EL%20DATO%204"

Respuesta esperada:

*/

// Obtener parámetros de la URL
$data1 = $_GET["params"][0] ?? null;   // Captura `{data1}`
$data2 = $_GET["params"][1] ?? null;   // Captura `{data2}`
$data3 = $_GET["data3"] ?? null;       // Captura `data3` de la query
$data4 = $_GET["data4"] ?? null;       // Captura `data3` de la query

// Simulación de respuesta basada en los datos recibidos
$response = [
    "data_1" => $data1,
    "data_2" => $data2,
    "data_3" => $data3,
    "data_4" => $data4
];

// Devolvemos el JSON
echo json_encode($response);
?>
