<?php
// Verificar si la sesión no está activa antes de iniciarla
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Se controla el tiempo activo de una sesion
include './session_time_controller.php';

// Si la sesión está creada es por que se ha identificado
if (isset($_SESSION['api_creator_user_session']) && $_SESSION['api_creator_user_session'] === "OK") {

    // Si existe el archivo de configuración, redirigir inmediatamente
    if (file_exists('./api_creator_config/db_config.php')) {
        include './api_creator_config/db_config.php';
    }

    // Administrador de endpoints
    include_once './endpointManager.php';
} else {
    // Vamos al login
    include './login.php';
    exit;
}
?>