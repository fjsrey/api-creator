<?php
$deactivateSessionControl = "yes";
require_once "classes/EndpointManager.php";

// Obtener la URI completa
$uri = $_SERVER['REQUEST_URI'];
$parsedUri = parse_url($uri);
$path = $parsedUri['path'] ?? '';
$query = $parsedUri['query'] ?? '';


// Ignorar cualquier ruta que no empiece por /apicreator/api
if (!preg_match('#^/apicreator/api(?:/([^?]+))?#', $path, $matches)) {
    // Ignorar y salir silenciosamente (Apache servirá el archivo real si existe)
    //http_response_code(404);
    //echo json_encode(["error" => "Ruta inválida. Debe comenzar con /apicreator/api"]);
    //exit;
    return;
}


// Capturamos el esquema: todo lo que sigue a /apicreator/api/
$scheme = $matches[1] ?? '';
$scheme = trim($scheme, '/');

// Obtener los endpoints asociados
$manager = new EndpointManager();
$endpoints = $manager->getEndpoints($scheme);

// Eliminar "/apicreator/api" del path para hacer matching con los endpoints
$processedPath = preg_replace('#^/apicreator/api#', '', $path);
$requestUri = $processedPath . ($query !== '' ? "?$query" : '');

// Información de la petición
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$input_mime = $_SERVER["CONTENT_TYPE"] ?? "none";

foreach ($endpoints as $endpoint) {
    $endpointUrl = $endpoint['url'];
    $parsedEndpoint = parse_url($endpointUrl);
    $endpointPath = $parsedEndpoint['path'] ?? '';
    $endpointQuery = $parsedEndpoint['query'] ?? '';

    // Convertimos {param} en grupos regex
    $pathPattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $endpointPath);
    $pathPattern = preg_replace('/([a-zA-Z0-9_]+)\{([a-zA-Z0-9_]+)\}/', '$1([^/]+)', $pathPattern);
    $pathPattern = str_replace('/', '\/', $pathPattern);

    // Procesar parámetros de query si existen
    $queryPattern = '';
    if ($endpointQuery !== '') {
        $queryParams = explode('&', $endpointQuery);
        $queryRegexParts = [];
        foreach ($queryParams as $param) {
            list($key, $value) = array_pad(explode('=', $param, 2), 2, '');
            $value = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^&]+)', $value);
            $queryRegexParts[] = preg_quote($key, '/') . '=' . $value;
        }
        $queryPattern = '\\?' . implode('&', $queryRegexParts);
    }

    $fullPattern = '/^' . $pathPattern . $queryPattern . '$/';

    // Comprobamos si hay coincidencia
    if (preg_match($fullPattern, $processedPath, $matches)) {
        if ($endpoint["method"] !== $method) {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
            exit;
        }

        if ($input_mime !== $endpoint["input_mime"]) {
            http_response_code(415);
            echo json_encode(["error" => "Formato [$input_mime] no soportado"]);
            exit;
        }

        array_shift($matches); // Eliminar match completo
        $_GET["params"] = $matches;


        // Ejecutamos el script o devolvemos el valor predefinido(preferencia del valor predefinido)
        header("Content-Type: " . $endpoint["output_mime"]);
        
        if(isset($endpoint["return_text"])) {
        
            // TODO: Dependiendo del tipo de salida mime, convertimos el valor predefinido para ajustarlo al mime
            //       para todos los permitidos
            switch ($endpoint["output_mime"]) {
                case "application/json":                    
                    
                    // Convertir la cadena JSON en un array PHP
                    $array = json_decode($endpoint["return_text"], true);

                    // Convertir el array PHP de nuevo en una cadena JSON
                    $newJsonString = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                    // Mostrar el resultado
                    echo $newJsonString;
                    break;
                default:
                    echo $endpoint["return_text"];
                    break;
            }


        } elseif(isset($endpoint["script"])) {
            include __DIR__ . $endpoint["script"];
        } 

        $manager->incrementHits($endpoint["id"]);
        exit;
    }
}

// Si no coincide ningún endpoint
http_response_code(404);
echo json_encode(["error" => "Endpoint no encontrado"]);
exit;
?>
