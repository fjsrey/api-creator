<?php
// Control de la sesión
if(!isset($deactivateSessionControl)) {
    require_once "./session_control.php";
}

class EndpointManager {

    public function getEndpoints($scheme = "") {

        // Creamos la Base de datos de API Creator, si no existe, y controlamos login
        //include_once "./api_creator_config/db_config.php";
        include_once "./connection.php";
        global $pdoApiCreator;
        global $databaseFile;

        // Clase necesaria
        include_once "Endpoint.php";

        // Consulta para obtener todos los registros de la tabla endpoints
        try {
            $pdoApiCreator = getDatabaseConnection($databaseFile);
            $query = "SELECT * FROM endpoints";    
            $stmt = $pdoApiCreator->prepare($query);
            $stmt->execute();

            // Crear un array para almacenar los registros
            $endpointsData = array();

            // Recorrer los resultados y añadirlos al array
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($scheme!="") {
                    $row['url'] = '/'.$row['scheme'].$row['url'];
                    $row['script'] = '/endpoints/'.$row['scheme'].'/'.$row['script'];
                    $row['return_text'] = $row['return_text'];
                }
                $endpointsData[] = $row;
            }

            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return $endpointsData;
        } catch (PDOException $e) {
            // Manejo de errores de conexión o consulta
            echo "Error: " . $e->getMessage();
            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return [];
        }
    }


    public function getSchemes() {        

        // Conexión a la BBDD
        //include_once "./api_creator_config/db_config.php";
        include_once "./connection.php";
        global $pdoApiCreator;
        global $databaseFile;

        // Consulta para obtener todos los registros de la tabla endpoints
        try {
            $pdoApiCreator = getDatabaseConnection($databaseFile);
            $query = "SELECT distinct(scheme) FROM endpoints";
            $stmt = $pdoApiCreator->prepare($query);
            $stmt->execute();

            // Crear un array para almacenar los registros
            $schemaData = array();

            // Recorrer los resultados y añadirlos al array
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schemaData[] = $row;
            }

            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return $schemaData;

        } catch (PDOException $e) {
            // Manejo de errores de conexión o consulta
            echo "Error: " . $e->getMessage();
            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return [];
        }
    }


    public function getMimetypes() {

        // Conexión a la BBDD        
        //include_once "./api_creator_config/db_config.php";
        include_once "./connection.php";        
        global $pdoApiCreator;
        global $databaseFile;

        // Consulta para obtener todos los mimetypes registrados
        try {
            $pdoApiCreator = getDatabaseConnection($databaseFile);
            $query = "SELECT mime, name FROM mimetypes";
            $stmt = $pdoApiCreator->prepare($query);
            $stmt->execute();

            // Crear un array para almacenar los registros
            $mimeData = array();

            // Recorrer los resultados y añadirlos al array
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mimeData[] = $row;
            }

            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return $mimeData;

        } catch (PDOException $e) {
            // Manejo de errores de conexión o consulta
            echo "Error: " . $e->getMessage();
            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return [];
        }
    }

    public function saveEndpoint($endpoint) {
        global $error;
        global $success;
            
        // Creamos la Base de datos de API Creator, si no existe, y controlamos login
        //include_once "./api_creator_config/db_config.php";
        include_once "./connection.php";
        global $pdoApiCreator;        
        global $databaseFile;

        // Clase necesaria
        include_once "Endpoint.php";

        try {
            $pdoApiCreator = getDatabaseConnection($databaseFile);
            $sql = "INSERT INTO endpoints(url, method, input_mime, output_mime, script, return_text, scheme) 
                    VALUES (:url, :method, :input, :output, :script, :return_text, :scheme)";
            
            $url         = $endpoint->getUrl();
            $method      = $endpoint->getMethod();
            $inputMime   = $endpoint->getInputMime();
            $outputMime  = $endpoint->getOutputMime();
            $script      = $endpoint->getScript();
            $return_text = $endpoint->getReturnText();
            $scheme      = $endpoint->getScheme();

            $stmt = $pdoApiCreator->prepare($sql);
            $stmt->bindParam(':url',    $url);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':input',  $inputMime);
            $stmt->bindParam(':output', $outputMime);
            $stmt->bindParam(':script', $script);
            $stmt->bindParam(':return_text', $return_text);
            $stmt->bindParam(':scheme', $scheme);
            $stmt->execute();

            $success = $success." Endpoint registrado correctamente.";
            
            closeStmt($stmt);
            closeConnection($pdoApiCreator);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'database is locked') !== false) {
                $error = $error." ERROR: La base de datos está bloqueada. Inténtalo de nuevo más tarde.";
            } else {                
                $error = $error." ERROR: SQL no ejecutado: " . $e->getMessage();
            }

            closeStmt($stmt);
            closeConnection($pdoApiCreator);
        }
    }


    public function updateEndpoint($endpoint, $id) {
        global $error;
        global $success;
            
        // Creamos la Base de datos de API Creator, si no existe, y controlamos login
        //include_once "./api_creator_config/db_config.php";
        include_once "./connection.php";
        global $pdoApiCreator;        
        global $databaseFile;

        // Clase necesaria
        include_once "Endpoint.php";

        // TODO: Cargar el endpoint original, para compararlo con los nuevos datos

        try {
            $pdoApiCreator = getDatabaseConnection($databaseFile);
            $sql = "UPDATE endpoints SET url = :url, method = :method, input_mime = :input, output_mime = :output ".($endpoint->getScript() != null ? ", script = :script" : "").", return_text = :return_text, scheme = :scheme WHERE ID = :id";

            // $error = $sql; // Depuración

            $url         = $endpoint->getUrl();
            $method      = $endpoint->getMethod();
            $inputMime   = $endpoint->getInputMime();
            $outputMime  = $endpoint->getOutputMime();

            // TODO: Hacer que localize si tiene script nuevo o no, para subirlo y borrar el anterior(si lo tenía)
            $script      = $endpoint->getScript();

            $return_text = $endpoint->getReturnText();
            if($return_text == "") {
               $return_text = null; 
            }

            $scheme      = $endpoint->getScheme();

            $stmt = $pdoApiCreator->prepare($sql);
            $stmt->bindParam(':id',     $id);
            $stmt->bindParam(':url',    $url);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':input',  $inputMime);
            $stmt->bindParam(':output', $outputMime);
            
            // Solo modificamos el script si se ha indicado uno nuevo
            if($script!=null) {
                $stmt->bindParam(':script', $script);
            }
            
            $stmt->bindParam(':return_text', $return_text);
            $stmt->bindParam(':scheme', $scheme);
            $stmt->execute();

            $success = $success." Endpoint modificado correctamente.";
            
            closeStmt($stmt);
            closeConnection($pdoApiCreator);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'database is locked') !== false) {
                $error = $error." ERROR: La base de datos está bloqueada. Inténtalo de nuevo más tarde.";
            } else {                
                $error = $error." ERROR: SQL no ejecutado: " . $e->getMessage();
            }

            closeStmt($stmt);
            closeConnection($pdoApiCreator);
        }
    }


    public function incrementHits($id) {
        try {
            global $error;
            global $success;
                
            // Creamos la Base de datos de API Creator, si no existe, y controlamos login
            //include_once "./api_creator_config/db_config.php";
            include_once "./connection.php";
            global $pdoApiCreator;        
            global $databaseFile;

            // Preparar y ejecutar el UPDATE
            $pdoApiCreator = getDatabaseConnection($databaseFile);
            $stmt = $pdoApiCreator->prepare("UPDATE endpoints SET hits = hits + 1 WHERE ID = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return true; // Se actualizó
            } else {
                return false; // No se encontró el ID o no se cambió nada
            }

            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            
        } catch (PDOException $e) {
            // Manejo de errores
            error_log("Error al incrementar hits: " . $e->getMessage());
            closeStmt($stmt);
            closeConnection($pdoApiCreator);
            return false;
        }
    }


}
?>
