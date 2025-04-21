<?php
include './session_time_controller.php';

if (!isset($_SESSION['api_creator_user_session']) && $_SESSION['api_creator_user_session'] != "OK") {
    echo json_encode(["status" => "error", "message" => "Sesión expirada."]);
    exit();
}

// Creamos la Base de datos de API Creator, si no existe, y controlamos login
require_once "./connection.php";
$pdoApiCreator = getDatabaseConnection($databaseFile);

// Leer ID del POST
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

// Consultamos los datos del endpoint
$registro = [];
try {
    // Preparar la consulta
    $stmt = $pdoApiCreator->prepare("SELECT * FROM endpoints WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener el resultado
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    closeStmt($stmt);

    if(!isset($registro['return_text'])) {
        $registro['return_text'] = "";
    } 

    if (!$registro) {
        echo json_encode(['success' => false, 'error' => 'No encontrado']);
        exit;
    }
} catch (PDOException $e) {
    closeStmt($stmt);
    echo json_encode(['success' => false, 'error' => "Error al recuperar el registro $id"]);
    exit;    
}

closeStmt($stmt);
closeConnection($pdoApiCreator);
    
echo json_encode(['success' => true, 'endpoint' => $registro]);
?>
