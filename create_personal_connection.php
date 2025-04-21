<?php
require_once "head.php";

// Inicializar variables para evitar errores en la primera carga
$db_type = $host = $port = $dbname = $username = $password = $api_key = $access_key = '';
$error = '';
$success = false;
$connection_tested = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_type = $_POST['db_type'] ?? '';
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? '';
    $dbname = $_POST['dbname'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $api_key = $_POST['api_key'] ?? '';
    $access_key = $_POST['access_key'] ?? '';

    if (!$api_key || !$access_key) {
        $error = "Todos los campos deben estar completos.";
    } else {
        try {
            if ($db_type === 'oracle') {
                $dsn = "oci:dbname=//$host:$port/$dbname;charset=UTF8";
            } elseif ($db_type === 'mysql') {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
            } else {
                throw new Exception("Tipo de base de datos no válido");
            }

            // Crear carpeta de configuración si no existe
            if (!is_dir('api_creator_config')) {
                mkdir('api_creator_config', 0777, true);
            }

            // Guardar la configuración en un archivo PHP
            $config_content = "<?php\n";
            $config_content .= "\$dsn = '$dsn';\n";
            $config_content .= "\$username = '$username';\n";
            $config_content .= "\$password = '$password';\n";
            $config_content .= "\$api_key = '$api_key';\n";
            $config_content .= "\$access_key = '$access_key';\n";
            $config_content .= "\$pdo = new PDO(\$dsn, \$username, \$password);\n";
            $config_content .= "\$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";

            file_put_contents('api_creator_config/db_config.php', $config_content);

            // Indicamos que la sesión se ha iniciado, para el primer uso
            $_SESSION['api_creator_user_session'] = "OK";

            // Consultar los valores de la tabla API_CREATOR y almacenarlos en sesión
            //$stmt = $pdo->query("SELECT `KEY`, `DATA` FROM API_CREATOR");
            //$_SESSION['api_creator_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $success = true;
            $connection_tested = true;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            $success = false;
            $connection_tested = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="container mt-5">
<h1 class="text-center">API Creator Configuration</h1>
<form method="post" class="card p-4" action="<?php echo(($connection_tested && $success) ? "api_creator.php" : "index.php")?>">
    <div class="mb-3">
        <label for="db_type" class="form-label">Tipo de Base de Datos:</label>
        <select name="db_type" class="form-select" required>
            <option value="mysql" <?= $db_type === 'mysql' ? 'selected' : '' ?>>MySQL</option>
            <option value="oracle" <?= $db_type === 'oracle' ? 'selected' : '' ?>>Oracle</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="host" class="form-label">Host:</label>
        <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($host) ?>" required>
    </div>
    <div class="mb-3">
        <label for="port" class="form-label">Puerto:</label>
        <input type="text" name="port" class="form-control" value="<?= htmlspecialchars($port) ?>" required>
    </div>
    <div class="mb-3">
        <label for="dbname" class="form-label">Nombre de la Base de Datos:</label>
        <input type="text" name="dbname" class="form-control" value="<?= htmlspecialchars($dbname) ?>" required>
    </div>
    <div class="mb-3">
        <label for="username" class="form-label">Usuario:</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Contraseña:</label>
        <input type="password" name="password" class="form-control"  value="<?= htmlspecialchars($password) ?>" required>
    </div>
    <div class="mb-3">
        <label for="api_key" class="form-label">API-KEY:</label>
        <input type="text" name="api_key" class="form-control" value="<?= htmlspecialchars($api_key) ?>" required>
    </div>
    <div class="mb-3">
        <label for="access_key" class="form-label">Clave de acceso:</label>
        <input type="password" name="access_key" class="form-control" value="<?= htmlspecialchars($access_key) ?>" required>
    </div>
    <button type="submit" name="test_connection" class="btn btn-secondary">
        <?php echo( ($connection_tested && $success) ? "Continuar" : "Probar Conexión");?>
    </button>    
    <?php if ($error): ?>
        <div class="alert alert-danger mt-3">Error: <?= htmlspecialchars($error) ?></div>
    <?php elseif ($connection_tested && $success): ?>
        <div class="alert alert-success mt-3">Conexión exitosa.</div>        
    <?php endif; ?>
</form>
</body>
</html>