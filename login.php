<?php
// Creamos la Base de datos de API Creator, si no existe, y controlamos login
require_once "./head.php";

// Verificar si se ha enviado el formulario mediante POST,
// y  que los campos requeridos están presentes.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_key']) && isset($_POST['pwd_key']) ) {

    // Obtenemos los valores enviados desde el formulario
    $userName = $_POST['user_key'];
    $userPwd  = $_POST['pwd_key'];
    $userPwd  = $salt.md5($userPwd);

    //try {        
        $pdoApiCreator = getDatabaseConnection($databaseFile);

        // Consulta para verificar los datos introducidos del usuario
        $sql = "SELECT COUNT(*) FROM users WHERE name = :name AND password = :password";        
        $stmt = $pdoApiCreator->prepare($sql);
        $stmt->bindParam(':name', $userName, PDO::PARAM_STR);
        $stmt->bindParam(':password', $userPwd, PDO::PARAM_STR);
        $stmt->execute();

        $exists = $stmt->fetchColumn() > 0;

        // Se valida si el usuario y clave existen
        if ($exists) {

            // Vemos si el usuario es de tipo ADMIN
            $sql = "SELECT id, is_admin FROM users WHERE name = :name AND password = :password";
            $stmt = $pdoApiCreator->prepare($sql);
            $stmt->bindParam(':name', $userName, PDO::PARAM_STR);
            $stmt->bindParam(':password', $userPwd, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = intval($user['id']);
            $isAdmin = intval($user['is_admin']);

            $_SESSION['admin_user'] = 0;
            if ($isAdmin === 1) {
                $_SESSION['admin_user'] = $isAdmin; // Indicamos que el usuario logado es de tipo Admin
            }

            // Incrementamos el valor del LOGIN_OK y la fecha de acceso
            $now = date('Y-m-d H:i:s');  // formato compatible con SQLite
            $sql = "UPDATE users SET login_ok = login_ok + 1, last_access = :actual_date WHERE id = :id";
            $stmt = $pdoApiCreator->prepare($sql);
            $stmt->bindParam(':actual_date', $now, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Crear la variable de sesión y redirigir al usuario
            $_SESSION['api_creator_user_session'] = 'OK';
            $_SESSION['message'] = '¡Bienvenido!';
            $_SESSION['user_id'] = $userId;

            header('Location: endpointManager.php');
            exit();
        } else {
            // Incrementamos el valor del LOGIN_KO
            $sql = "UPDATE users SET login_ok = login_ko + 1 WHERE id = :id";
            $stmt = $pdoApiCreator->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Redirigir a login.php si los valores no coinciden
            $_SESSION['message'] = "¿Te has equivocado?";            
            header('Location: index.php');
            exit();
        }

        //closeStmt($stmt);
        closeConnection($pdoApiCreator);

    //} catch (PDOException $e) {
        // Manejo de errores en caso de problemas con la base de datos
    //    echo "Error en la conexión o consulta: " . $e->getMessage();
    //}


    
} else {

    if (!isset($_SESSION['api_creator_user_session'])) 
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

    <?php include "./footer.php"; ?>
    
    </body>
    </html>
<?php
    }
}
?>