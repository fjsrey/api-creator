<?php
/*
Usando cURL en la Terminal:

    curl -X GET "http://127.0.0.1/apicreator/api/examples/api/json" -H "Content-Type: application/json" -d "{\"usuario\": \"Juan\", \"email\": \"juan@example.com\"}"

Respuesta esperada::

    {
        "mensaje": "Datos recibidos correctamente",
        "datos_recibidos": {
            "usuario": "Juan",
            "email": "juan@example.com"
        },
        "respuesta": {
            "id": 123,
            "nombre": "Ejemplo",
            "estado": "procesado"
        }
    }
*/

// Verifica el método de la solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Obtiene los datos de entrada en formato JSON
$inputData = json_decode(file_get_contents("php://input"), true);

// Si no hay datos de entrada, responde con error
if (!$inputData) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(["error" => "Datos JSON no válidos o vacíos"]);
    exit;
}

// Simulación de respuesta basada en los datos recibidos
$response = [
    "mensaje" => "Datos recibidos correctamente",
    "datos_recibidos" => $inputData,
    "respuesta" => [
        "id" => rand(),
        "nombre" => "Ejemplo",
        "estado" => "procesado"
    ]
];

// Configura la cabecera para responder con JSON
header("Content-Type: application/json");
echo json_encode($response);
?>
