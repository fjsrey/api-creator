<?php
$deactivateSessionControl = "yes"; // Indicamos que la llamada proviede de la API y no tenemos sesión
require_once "classes/EndpointManager.php";
require_once "vars.php";

function setHeaderStatus($code) {
    global $mensajes;

    // Asegurar que es un número válido
    if (!is_numeric($code)) {        
        $code = (int)$code;
    }

    if (!array_key_exists($code, $mensajes)) {
        $code = 500;
    }
    
    if (isset($mensajes[$code])) {
        header("HTTP/1.1 $code {$mensajes[$code]}");
    }
}

function generateSimpleTxtPdf($texto, $fileName) {
    // Codificamos el texto para PDF (escapamos paréntesis)
    $texto_pdf = str_replace(['(', ')'], ['\\(', '\\)'], $texto);

    // Calculamos la longitud del stream
    $stream = "BT\n/F1 24 Tf\n100 700 Td\n($texto_pdf) Tj\nET";
    $length = strlen($stream);

    // Salida de cabeceras
    //header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="'.$fileName.'"');

    // Estructura mínima de un PDF
    echo "%PDF-1.3\n";
    echo "1 0 obj\n<<>>\nendobj\n";
    echo "2 0 obj\n<< /Length $length >>\nstream\n$stream\nendstream\nendobj\n";
    echo "3 0 obj\n<< /Type /Page /Parent 4 0 R /MediaBox [0 0 612 792] /Contents 2 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
    echo "4 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    echo "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    echo "6 0 obj\n<< /Type /Catalog /Pages 4 0 R >>\nendobj\n";
    echo "xref\n0 7\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000174 00000 n \n0000000277 00000 n \n0000000345 00000 n \n0000000423 00000 n \n";
    echo "trailer\n<< /Size 7 /Root 6 0 R >>\nstartxref\n491\n%%EOF";
}


// Obtener la URI completa
$uri = $_SERVER['REQUEST_URI'];
$parsedUri = parse_url($uri);
$path = $parsedUri['path'] ?? '';
$query = $parsedUri['query'] ?? '';


// Ignorar cualquier ruta que no empiece por /apicreator/api
// Apache servirá el archivo real si existe.
if (!preg_match('#^/apicreator/api(?:/([^?]+))?#', $path, $matches)) {
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
        
        // Devolvemos el status indicado en el campo status no es NULL
        if(isset($endpoint["status"])) {
            $manager->incrementHits($endpoint["id"]);
            setHeaderStatus($endpoint["status"]);
            exit;
        } else {
            // Devolvemos la respuesta preparada si el campo return_text no es NULL
            if(isset($endpoint["return_text"])) {

                $return_text_error = false;
            
                // TODO: Dependiendo del tipo de salida mime, convertimos el valor predefinido para ajustarlo al mime
                //       para todos los permitidos
                try {
                    switch ($endpoint["output_mime"]) {
                        case "application/json":
                            
                            // Convertir la cadena JSON en un array PHP
                            $array = json_decode($endpoint["return_text"], true);

                            // Convertir el array PHP de nuevo en una cadena JSON
                            $newJsonString = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                            // Mostrar el resultado
                            echo $newJsonString;
                            break;

                        case "application/pdf":
                            if(isset($endpoint["return_text"])) {
                                $texto = $endpoint["return_text"];
                            } else {
                                $texto = "API Creator";
                            }

                            generateSimpleTxtPdf($texto, $fileName);
                            break;

                        default:
                            echo $endpoint["return_text"];
                            break;
                    }
                } catch(Exception $e) {
                    $return_text_error = true;
                }

                // Devolvemos un status 200 cuando la respuesta está predefinida 
                // a no ser que haya dado algún error en su uso.
                if ($return_text_error) {
                    setHeaderStatus(500);
                } else {
                    setHeaderStatus(200);
                }

            } elseif(isset($endpoint["script"])) {
                // En la ruta, ya viene la carpeta del usuario, el esquema y el script
                include __DIR__ . $endpoint["script"];
            } 

            $manager->incrementHits($endpoint["id"]);
            exit;
        }
    }

}

// Si no coincide ningún endpoint
http_response_code(404);
echo json_encode(["error" => "Endpoint no encontrado"]);
exit;
?>
