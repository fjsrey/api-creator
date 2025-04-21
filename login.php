<?php
// Creamos la Base de datos de API Creator, si no existe, y controlamos login
require_once "./head.php";

// Verificar si se ha enviado el formulario mediante POST,
// y  que los campos requeridos están presentes.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_key']) && isset($_POST['pwd_key']) ) {

    // Obtenemos los valores enviados desde el formulario
    $userKey = $_POST['user_key'];
    $pwdKey = $_POST['pwd_key'];

    //try {        
        $pdoApiCreator = getDatabaseConnection($databaseFile);

        // Consulta para verificar el valor del campo "user_key"
        $stmt = $pdoApiCreator->prepare("SELECT data FROM parameters WHERE key = 'admin-user' AND data = ?");
        $stmt->execute([$userKey]);
        $userResult = $stmt->fetchColumn();

        // Consulta para verificar el valor del campo "pwd_key"
        $stmt = $pdoApiCreator->prepare("SELECT data FROM parameters WHERE key = 'admin-password' AND data = ?");
        $hashedPwdKey = $salt.md5($pwdKey); // Generar el hash MD5 del valor ingresado
        $stmt->execute([$hashedPwdKey]);
        $pwdResult = $stmt->fetchColumn();

        // Validar si ambos valores coinciden
        if ($userResult>0 && $pwdResult>0) {
            // Crear la variable de sesión y redirigir al usuario
            $_SESSION['api_creator_user_session'] = 'OK';
            $_SESSION['message'] = '¡Bienvenido!';
            header('Location: endpointManager.php');
            exit();
        } else {
            // Redirigir a login.php si los valores no coinciden
            $_SESSION['message'] = "¿Te has equivocado?";            
            //header('Location: index.php');
            //exit();
        }

        closeConnection($pdoApiCreator);

    //} catch (PDOException $e) {
        // Manejo de errores en caso de problemas con la base de datos
    //    echo "Error en la conexión o consulta: " . $e->getMessage();
    //}


    
} else {

    if (!isset($_SESSION['api_creator_user_session'])) 
        if(!isset($_SESSION['api_creator_user_session']))
        {
        ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style type="text/css">
            #login-card {
                width:20em;
                margin-left:35%;
            }
            .centered {
                text-align: center;
            }
            .warning-color {
                color: orange;
            }
        </style>
    </head>
    <body class="container mt-5">
    <h1 class="text-center">Acceso</h1>

    <?php
    if(isset($_SESSION['message'])) {
        echo "<h5 class='centered warning-color'>".$_SESSION['message']."</h5>";
        $_SESSION['message'] = '';
    }
    ?>

    <form id="login-card" method="post" class="card p-4" action="./login.php">
        <div class="mb-3">
            <label for="user_key" class="form-label">Usuario:</label>
            <input id="user_key" name="user_key" type="text" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="pwd_key" class="form-label">Clave:</label>
            <input id="pwd_key" name="pwd_key" type="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>

    <?php include "./footer.html"; ?>
    
    </body>
    </html>
<?php
    }
}
?>