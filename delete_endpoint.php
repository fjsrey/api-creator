<?php
function eliminarScriptYCarpetaSiVacia($scheme, $script) {
    $basePath = __DIR__ . '/endpoints';
    $folderPath = $basePath . '/' . $scheme;
    $scriptPath = $folderPath . '/' . $script;

    // Resultado
    $resultado = [
        'archivo_eliminado' => false,
        'carpeta_eliminada' => false,
        'mensajes' => []
    ];

    // Eliminar el archivo
    if (file_exists($scriptPath)) {
        if (unlink($scriptPath)) {
            $resultado['archivo_eliminado'] = true;
            $resultado['mensajes'][] = "Archivo eliminado: $scriptPath";
        } else {
            $resultado['mensajes'][] = "Error al eliminar el archivo: $scriptPath";
        }
    } else {
        $resultado['mensajes'][] = "El archivo no existe: $scriptPath";
    }

    // Verificar y eliminar carpeta si está vacía
    if (is_dir($folderPath)) {
        $files = array_diff(scandir($folderPath), ['.', '..']);
        if (empty($files)) {
            if (rmdir($folderPath)) {
                $resultado['carpeta_eliminada'] = true;
                $resultado['mensajes'][] = "Esquema eliminado: $folderPath";
            } else {
                $resultado['mensajes'][] = "Error al eliminar el esquema: $folderPath";
            }
        } else {
            $resultado['mensajes'][] = "El esquema '$folderPath' tiene mas elementos.";
        }
    } else {
        $resultado['mensajes'][] = "El esquema no existe: $folderPath";
    }

    return $resultado;
}

include './session_time_controller.php';


if (!isset($_SESSION['api_creator_user_session']) && $_SESSION['api_creator_user_session'] != "OK") {
    echo json_encode(["status" => "error", "message" => "Sesión expirada."]);
    exit();
}


// Creamos la Base de datos de API Creator, si no existe, y controlamos login
require_once "./connection.php";
$pdoApiCreator = getDatabaseConnection($databaseFile);

// Leer ID del POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

// Consultamos los datos para eliminar el script
$registro = [];
try {
    // Preparar la consulta
    $stmt = $pdoApiCreator->prepare("SELECT * FROM endpoints WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener el resultado
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    closeStmt($stmt);    

    if (!$registro) {
        echo json_encode(["status" => "error", "message" => "ID inválido."]);
        exit;
    }
} catch (PDOException $e) {
    closeStmt($stmt);
    echo json_encode(["status" => "error", "message" => "Error al recuperar el registro: " . $e->getMessage()]);
    exit;    
}


$msgFileDelete = "";
if ($registro) {

    $scheme = $registro['scheme'];
    $script = $registro['script'];

    $resultado = eliminarScriptYCarpetaSiVacia($scheme, $script);
    if ($resultado['archivo_eliminado'] || $resultado['carpeta_eliminada']) {
        $msgFileDelete = "Script eliminado correctamente.";
    } else {
        $msgFileDelete = "El Script no se ha podido eliminar.";
    }


    // Ejecutar DELETE
    try {
        $stmt = $pdoApiCreator->prepare("DELETE FROM endpoints WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        closeStmt($stmt);
        closeConnection($pdoApiCreator);
        echo json_encode(["status" => "error", "message" => "$msgFileDelete Error al eliminar el endpoint: " . $e->getMessage()]);
    }

    closeStmt($stmt);
    closeConnection($pdoApiCreator);
    
    echo json_encode(["status" => "success", "message" => "$msgFileDelete Endpoint eliminado correctamente."]);
}
?>
