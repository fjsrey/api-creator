<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Se establece el tiempo de vida de la sesión "admin" en segundos (30 minutos = 1800 segundos)
$session_timeout = 30*60;

// Verificar si la sesión está activa
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    $_SESSION['api_creator_user_session'] === "KO";
    // Destruir la sesión si ha pasado el tiempo
    $_SESSION['message'] = "Por seguridad, la sesión ha caducado. Identifíquese de nuevo.";
    session_unset();
    //session_destroy();
    //header("Location: index.php"); // Redirigir al usuario a la página de inicio de sesión
    //exit();
}

// Actualizar la marca de tiempo de la última actividad
$_SESSION['LAST_ACTIVITY'] = time();
?>
