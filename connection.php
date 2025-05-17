<?php
// Control de versión de la aplicación para migración de BBDD
$actual_version = "0.7.0-beta"; // Versión actual de la aplicación
$bbdd_version   = "";           // Versión indicada en la BBDD
$currentUser    = 0;            // ID del usuario logado (0=ninguno)

// Otras variables para el funcionamiento de la APP
$salt = "1b3caf2c";

// Comprobamos si la Base de datos de la aplicación esta creada
$databaseFile = __DIR__ . '/api_creator_config/apicreator.sqlite';

require_once "utils.php";

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
    //////////////////////
    // Versión 0.51.0-beta
    //////////////////////

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


    ///////////////////////////////////
    // Versión 0.5.1-beta, 0.7.0-beta
    ///////////////////////////////////

    // Verificar si la tabla parameters existe o no
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
    } else {
        /////////////////////////////
        // Versión 0.70.0 en adelante
        /////////////////////////////

        // La tabla parameters ya existe.
        // Obtenemos la versión actual de la applicación indicada en la BBDD
        $stmt = $pdoApiCreator->prepare("SELECT data FROM parameters WHERE key = 'app-version' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Inicializar la variable $bbdd_version
        if ($row === false) {
            $bbdd_version = "";
        } else {
            $bbdd_version = $row['data'];
        }
    }

    if ($bbdd_version === "") {
        // Control de actualización de la BBDD entre versiones de la aplicación
        $bbdd_version = "0.5.1-beta"; // Version anterior para migración de BBDD
        $stmt = $pdoApiCreator->prepare("INSERT INTO parameters (key, data) VALUES (:key, :data)");
        $stmt->execute([
            ':key' => 'app-version',
            ':data' => "$bbdd_version"
        ]); 
        
    }


    /////////////////////
    // Versión 0.7.1-beta
    /////////////////////

    // Verificar si la tabla USERS existe
    $result = $pdoApiCreator->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    
    if ($result->fetch() === false) {
        // Crear la tabla USERS si no existe
        $query = "
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT  NOT NULL UNIQUE,
                password TEXT NOT NULL,
                is_admin INTEGER DEFAULT 0,
                login_ok INTEGER DEFAULT 0,
                login_ko INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_access DATETIME NULL
            );
        ";
        $pdoApiCreator->exec($query);

        // Recogemos el usuario y clave del Admin
        // Consulta para verificar el valor del campo "user_key"
        $query = "SELECT data FROM parameters WHERE key = 'admin-user'";
        $stmt = $pdoApiCreator->prepare($query);
        $stmt->execute();
        $adminUser = $stmt->fetchColumn();

        // Consulta para verificar el valor del campo "pwd_key"
        $query = "SELECT data FROM parameters WHERE key = 'admin-password'";
        $stmt = $pdoApiCreator->prepare($query);
        $stmt->execute();
        $adminPwd = $stmt->fetchColumn();

        // Insertamos el usuario ADMIN por defecto
        // añadiendo el nombre y clave del usuario actual
        $query = "
            INSERT INTO users (id, name, password, is_admin, login_ok, login_ko)
            VALUES (1, '$adminUser', '$adminPwd', 1, 0, 0);
        ";
        $pdoApiCreator->exec($query);

        // Eliminamos de la tabla "parameters" las key del usuario por defecto antiguo
        $query = "DELETE FROM parameters WHERE key IN('admin-user', 'admin-password');";
        $stmt->execute();

        closeStmt($stmt);

        // Movemos los endpoints existentes dentro de una carpeta "1", correspondiente la usuario admin
        $origen = __DIR__ . '/endpoints';
        $destino = __DIR__ . '/endpoints/1';
        moverContenido($origen, $destino);
    }


    // Comprobamos si existe el campo CODE en la tabla ENDPOINTS, si no existe, se añade
    try {
        // Comprobar si el campo "status" ya existe en la tabla "endpoints"
        $stmt = $pdoApiCreator->query("PRAGMA table_info(endpoints)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $codeExists = false;
        foreach ($columns as $column) {
            if (strcasecmp($column['name'], 'status') === 0) {
                $codeExists = true;
                break;
            }
        }

        // Si no existe, añadir el campo "status"
        if (!$codeExists) {
            $pdoApiCreator->exec("ALTER TABLE endpoints ADD COLUMN status INTEGER NULL");
        } 
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }



    // Comparamos la versión para actualizar la BBDD si es necesario a la última versión
    if (version_compare($bbdd_version, $actual_version, "<")) {
        
        $bbdd_version = $actual_version;
        $pdoApiCreator->exec("UPDATE parameters SET data = '$actual_version' WHERE key = 'app-version';");

        // Resto de cambios en la BBDD para la versión 0.7.0
        // Añadimos el campo 'user_id' si no existe en la tabla 'endpoints'
        $stmt = $pdoApiCreator->query("PRAGMA table_info(endpoints)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $userIdExists = false;
        foreach ($columns as $column) {
            if (strtolower($column['name']) === 'user_id') {
                $userIdExists = true;
                break;
            }
        }

        if (!$userIdExists) {
            $pdoApiCreator->exec("ALTER TABLE endpoints ADD COLUMN user_id NUMERIC NOT NULL DEFAULT 0");
            $pdoApiCreator->exec("UPDATE endpoints SET user_id = 1");
        }

        closeStmt($stmt);
    }


    //////////////////////
    // Versión siguiente
    //////////////////////

    closeConnection($pdoApiCreator);


} catch (PDOException $e) {
    closeStmt($stmt);
    closeConnection($pdoApiCreator);
    echo "Error: " . $e->getMessage();
}

?>