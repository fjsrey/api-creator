<?php
// public/index.php
require_once "./vars.php";
require_once './router.php';


// Verificar si la sesión no está activa antes de iniciarla
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Creamos la Base de datos de API Creator, si no existe, y controlamos login
require_once "./connection.php";

// Control de la sesión
require_once "./session_control.php";
?>
