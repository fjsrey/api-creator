<?php
// Comprobamos si la Base de datos de la aplicación esta creada
$databaseFile = __DIR__ . '/api_creator_config/apicreator.sqlite';

if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection($databaseFile) {
        static $pdoApiCreator = null;

        if ($pdoApiCreator === null) {
            try {
                $pdoApiCreator = new PDO("sqlite:" . $databaseFile);
                $pdoApiCreator->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdoApiCreator->exec('PRAGMA busy_timeout = 5000'); // Espera hasta 5 segundos si está bloqueada
                return $pdoApiCreator;
            } catch (PDOException $e) {
                // Si ocurre un error, lo mostramos
                echo "Error al conectar a la base de datos: " . $e->getMessage();
                return null;
            }
        }
        return $pdoApiCreator;
    }
}

if (!function_exists('closeConnection')) {
    function closeConnection(&$pdoApiCreator) {
        // Cerramos la conexión
        $pdoApiCreator = null;
    }
}

if (!function_exists('closeStmt')) {
    function closeStmt(&$stmt) {
        // Cerramos el stmt
        $stmt = null;
    }
}

try {
    // Variables para el funcionamiento de la APP
    $salt = "1b3caf2c";

    // Conectar o crear la base de datos    
    $pdoApiCreator = getDatabaseConnection($databaseFile);

    // Verificar si la tabla endpoints existe
    $result = $pdoApiCreator->query("SELECT name FROM sqlite_master WHERE type='table' AND name='endpoints'");

    if ($result->fetch() === false) {
        // Crear la tabla prices si no existe
        $pdoApiCreator->exec("CREATE TABLE endpoints (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            scheme      TEXT,
            url         TEXT NOT NULL,
            method      TEXT NOT NULL,
            input_mime  TEXT NOT NULL,
            output_mime TEXT NOT NULL,
            script      TEXT NOT NULL,
            return_text TEXT NOT NULL,
            hits        INTEGER DEFAULT 0
        )");

        // Insertar registros de Endpoints de ejemplo
        $pdoApiCreator->exec("INSERT INTO endpoints (scheme, url, method, input_mime, output_mime, script, hits) VALUES ('examples', '/api/json', 'GET', 'application/json', 'application/json', 'json.php', 0)");
        $pdoApiCreator->exec("INSERT INTO endpoints (scheme, url, method, input_mime, output_mime, script, hits) VALUES ('examples', '/api/image/{id}', 'GET', 'none', 'image/png', 'image.php', 0)");
        $pdoApiCreator->exec("INSERT INTO endpoints (scheme, url, method, input_mime, output_mime, script, hits) VALUES ('examples', '/api/data/{data1}/prueba/{data2}/try', 'GET', 'none', 'application/json', 'url_data.php', 0)");
    }

    // Verificar si la tabla parameters existe
    $result = $pdoApiCreator->query("SELECT name FROM sqlite_master WHERE type='table' AND name='parameters'");

    if ($result->fetch() === false) {
        // Crear la tabla parameters si no existe
        $pdoApiCreator->exec("CREATE TABLE parameters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT NOT NULL CHECK(length(key) <= 50),
            data TEXT NOT NULL CHECK(length(data) <= 1000)
        )");
        
        // Insertar el registro inicial para admin, con el valor md5 de la clave administrativa
        $dataValue = $salt."7b24afc8bc80e548d66c4e7ff72171c5"; // toor - password by default.
        
        // Admin user
        $stmt = $pdoApiCreator->prepare("INSERT INTO parameters (key, data) VALUES (:key, :data)");
        $stmt->execute([
            ':key' => 'admin-user',
            ':data' => 'admin'
        ]);

        // Admin password
        $stmt = $pdoApiCreator->prepare("INSERT INTO parameters (key, data) VALUES (:key, :data)");
        $stmt->execute([
            ':key' => 'admin-password',
            ':data' => "$dataValue"
        ]);

        closeStmt($stmt);
    }

    // Verificar si la tabla mimetypes existe
    $result = $pdoApiCreator->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mimetypes'");

    if ($result->fetch() === false) {
        // Crear la tabla parameters si no existe
        $pdoApiCreator->exec("CREATE TABLE mimetypes (
            mime TEXT NOT NULL,
            name TEXT NOT NULL,
            CONSTRAINT mimetypes_pk PRIMARY KEY (mime)
        )");
        
        // Lista de MIME types comunes por defecto
        $mimeTypes = [
            ['none','none'], 
            ['application/json', 'JSON'],
            ['application/xml', 'XML'],
            ['text/plain', 'Texto plano'],
            ['application/x-www-form-urlencoded', 'Form URL Encoded'],
            ['multipart/form-data', 'Multipart Form Data'],
            ['application/octet-stream', 'Binario'],
            ['text/html', 'HTML'],
            ['application/pdf', 'PDF'],
            ['image/jpeg', 'JPEG'],
            ['image/png', 'PNG']
        ];
        
        foreach ($mimeTypes as $item) {
            $stmt = $pdoApiCreator->prepare("INSERT INTO mimetypes (mime, name) VALUES (:mime, :name)");        
            $stmt->execute([
                ':mime' => "$item[0]",
                ':name' => "$item[1]"
            ]);
        }
        closeStmt($stmt);
    }  

    closeConnection($pdoApiCreator);


} catch (PDOException $e) {
    closeConnection($pdoApiCreator);
    echo "Error: " . $e->getMessage();
}

?>