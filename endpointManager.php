<?php
require_once "head.php";
require_once "classes/Endpoint.php";
require_once "classes/EndpointManager.php";

$manager = new EndpointManager();

// Lista de MIME types comunes
$mimeTypes = $manager->getMimetypes();

// Indica si estamos creando o editando
$CREATE = "create";
$EDIT = "edit";

// Valores por defecto
$mode       = $CREATE;
$id         = null;
$url        = null;
$method     = null;
$inputMime  = null;
$outputMime = null;
$scheme     = null;
$returnText = null;
$scriptName = null;
$status     = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['mode'])) {
        $mode = $_POST['mode'];
    }

    if ( !isset($_POST['url'], $_POST['method'], $_POST['inputMime'], $_POST['outputMime'], $_POST['scheme'], $_POST['status']) || (!isset($_FILES['script']) && !isset($_POST['returntext'])) || ($mode==$EDIT && !isset($_POST['id'])) ) {
        $error = "Todos los campos son obligatorios. Debe escoger un script PHP o indicar un texto a devolver.";
    } else {
        $userId = $_SESSION['user_id'];

        if($mode==$EDIT) {
            $id = $_POST['id'];
        }
        $url = $_POST['url'];
        $method = strtoupper($_POST['method']);
        $inputMime = $_POST['inputMime'];
        $outputMime = $_POST['outputMime'];
        $scheme = $_POST['scheme'];
        $returnText = "";
        if(isset($_POST['returntext'])) {
            $returnText = $_POST['returntext'];
        }
        $scriptName = "";
        if(isset($_FILES['script'])) {
            $scriptName = basename($_FILES['script']['name']);
        }
        $scriptPath = $scriptName;

        $status = $_POST['status'];

        // Ruta desde la raíz del sitio web
        $ruta = $_SERVER['DOCUMENT_ROOT'] . "/apicreator/endpoints/$userId/$scheme";
        
        // Creación
        if(isset($_FILES['script'])) {

            // Crear la carpeta si no existe
            if (!is_dir($ruta)) {
                if (mkdir($ruta, 0777, true)) {

                    switch ($mode) {
                        case $CREATE:
                            $success = "Esquema creado correctamente.";
                            break;
                        case $EDIT:
                            $success = "Esquema modificado correctamente.";
                            break;
                        default:
                            echo "No hay 'modo' indicado.";
                            break;
                    }

                } else {
                    
                    switch ($mode) {
                        case $CREATE:
                            $error = "Error al crear el esquema.";
                            break;
                        case $EDIT:
                            $error = "Error al modificar el esquema.";
                            break;
                        default:
                            echo "No hay 'modo' indicado.";
                            break;
                    }

                }
            }

            // Solo intentamos subir el fichero si se ha indicado alguno al crear/modificar
            if($scriptPath != "") {                
                if (!move_uploaded_file($_FILES['script']['tmp_name'], "endpoints/$userId/$scheme/".$scriptPath)) {
                    $error = "Error al subir el Script.";
                }            
            }
        }


        $endpoint = new Endpoint($url, $method, $inputMime, $outputMime, $scriptPath, $returnText, $scheme, $status);
        if($mode==$CREATE) { 
            $manager->saveEndpoint($endpoint);            
        } elseif($mode==$EDIT) { 
            $manager->updateEndpoint($endpoint, $id);
            $mode==$CREATE; // Restauramos modo de creación
        }

    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Creator - Mantenimiento de Endpoints</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://apalfrey.github.io/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<style type="text/css">
    #endpoints-table {
        font-size: 12px;
    }

    .reducir {
        --escala: 1;
        transform: scale(var(--escala));
        transform-origin: center center;
    }

    .flex {
        display: flex;
    }
</style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">API Creator - Mantenimiento de Endpoints</h1>
        
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php } ?>


        <?php 
        if (isset($success)) { 
            if($success!="") {
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
                <?php
            }
        }
        ?>

        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <input name="mode" id="mode" type="hidden" value="<?php echo (isset($mode) ? $mode : "" );?>">
            <input name="id" id="id" type="hidden" value="<?php echo (isset($id) ? $id : "" );?>">

            <div class="mb-3">
                <label for="scheme" class="form-label">Esquema:</label>
                <select class="form-select" id="scheme" name="scheme" data-placeholder="Selecciona o escribe nuevo esquema">
                    <?php
                    $schemes = $manager->getSchemes();
                    foreach ($schemes as $scheme) {
                    ?>
                        <option value="<?php echo $scheme['scheme'];?>" <?php echo ($scheme['scheme'] == $scheme ? "selected" : "")?>><?php echo $scheme['scheme'];?></option>
                    <?php } ?>
                </select>
            </div>          

            <div class="mb-3">
                <label for="url" class="form-label">URL del Endpoint:</label>
                <input type="text" class="form-control" id="url" name="url" value="<?php echo (isset($url) ? $url : "" );?>" required>
            </div>

            <div class="mb-3">
                <label for="method" class="form-label">Método:</label>
                <select class="form-select" id="method" name="method" required>
                    <?php
                    foreach (["GET", "POST", "DELETE", "PUT"] as $m) {
                        $selected = "";
                        if(isset($method)) {
                            if($method == $m) {
                                $selected = " selected";
                            }
                        }
                        echo "<option$selected>$m</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="inputMime" class="form-label">MIME Type Entrada:</label>
                <select class="form-select" id="inputMime" name="inputMime" required>
                    <?php foreach ($mimeTypes as $mime): 
                        $selected = "";
                        if(isset($inputMime)) {
                            if($inputMime == $mime['mime']) {
                                $selected = " selected";
                            }
                        }
                    ?>
                        <option<?php echo $selected;?> value="<?php echo $mime['mime']; ?>"><?php echo $mime['name'].' ('.$mime['mime'].')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="outputMime" class="form-label">MIME Type Salida:</label>
                <select class="form-select" id="outputMime" name="outputMime" required>
                    <?php foreach ($mimeTypes as $mime): ?>
                        $selected = "";
                        if(isset($outputMime)) {
                            if($outputMime == $mime['mime']) {
                                $selected = " selected";
                            }
                        }
                        <option<?php echo $selected;?> value="<?php echo $mime['mime']; ?>"><?php echo $mime['name'].' ('.$mime['mime'].')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
                        

            <!-- Pestañas código/texto -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="input-tab" data-bs-toggle="tab" data-bs-target="#input" type="button" role="tab" aria-controls="input" aria-selected="true">
                  Ejecutable
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="textarea-tab" data-bs-toggle="tab" data-bs-target="#textarea" type="button" role="tab" aria-controls="textarea" aria-selected="false">
                  Valor predefinido
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status-code" type="button" role="tab" aria-controls="status-code" aria-selected="false">
                  Status predefinido
                </button>
              </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content mt-3">
              <div class="tab-pane fade show active" id="input" role="tabpanel" aria-labelledby="input-tab">
                <div class="mb-3">
                    <label for="script" class="form-label">Código PHP:</label>
                    <!-- TODO: Al editar, dar opcion para no modificar el ejecutable o para descargarlo -->
                    <input type="file" class="form-control" id="script" name="script" accept=".php">
                </div>
              </div>
              <div class="tab-pane fade" id="textarea" role="tabpanel" aria-labelledby="textarea-tab">
                <div class="mb-3">
                  <label for="returntext" class="form-label">Texto a devolver:</label>
                  <textarea class="form-control" name="returntext" id="returntext" rows="4" placeholder="Escribe aquí..."><?php echo (isset($returnText) ? $returnText : "");?></textarea>
                </div>
              </div>
              <div class="tab-pane fade" id="status-code" role="tabpanel" aria-labelledby="status-tab">
                <div class="mb-3">
                  <div class="mb-3">
                      <label for="status" class="form-label">Código de Status</label>
                      <select class="form-select" id="status" name="status">
                        <option value="">Ninguno</option>
                        <option value="200">200 OK</option>
                        <option value="201">201 Created</option>
                        <option value="204">204 No Content</option>
                        <option value="400">400 Bad Request</option>
                        <option value="401">401 Unauthorized</option>
                        <option value="403">403 Forbidden</option>
                        <option value="404">404 Not Found</option>
                        <option value="409">409 Conflict</option>
                        <option value="422">422 Unprocessable Entity</option>
                        <option value="500">500 Internal Server Error</option>
                        <option value="502">502 Bad Gateway</option>
                        <option value="503">503 Service Unavailable</option>
                      </select>
                    </div>
                </div>
              </div>
            </div>

            <div id="register-buttons">
                <button id="register-button" type="submit" class="btn btn-primary">Registrar</button>
            </div>

            <div id="modify-buttons" style="display:none;">
                <button id="modify-button" type="submit" class="btn btn-warning">Modificar</button>
                <button id="cancel-modify-button" type="button" class="btn btn-secondary">Cancelar</button>
            </div>

        </form>
        
        <h2 class="mb-3">Endpoints Registrados</h2>
        <ul class="list-group">
            <?php
            $endpoints = $manager->getEndpoints();
            ?>
            <div class="container mt-5">
              <table id="endpoints-table" class="table table-bordered table-striped">
                <thead class="table-dark">
                  <tr>
                    <th>ID&nbsp;-&nbsp;Esquema(Hits)</th>
                    <th>URL</th>
                    <th>Método</th>
                    <th>Input MIME</th>
                    <th>Output MIME</th>
                    <th>Script/Text/Status</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($endpoints as $ep) {
                        $title = "";

                        if($ep['status']!="") {
                            $title = "HTTP/1.1 $code {$mensajes[$ep['status']]}";
                        } else if ($ep['return_text']!="") {
                            $title = htmlspecialchars($ep['return_text'] ?? '', ENT_QUOTES, 'UTF-8');
                        } else if ($ep['script']!="") {
                            $title = $ep['script'];
                        }
                    ?>
                    <tr>
                      <!-- Información de endpoint -->
                      <td><?php echo $ep['id'].'&nbsp;-&nbsp;'.$ep['scheme'].'('.$ep['hits'].')';?></td>
                      <td><?php echo $ep['url'];?></td>
                      <td><?php echo $ep['method'];?></td>
                      <td><?php echo $ep['input_mime'];?></td>
                      <td><?php echo $ep['output_mime'];?></td>
                      <td title="<?php echo $title;?>">
                        <?php 
                        if($ep['status']!="") {
                            echo $ep['status'];
                        } else if ($ep['return_text']!="") {
                            echo 'Texto predefinido';
                        } else if ($ep['script']!="") {
                            echo 'script';
                        }
                        ?>
                      </td>
                      <td>
                        <!-- Botones de acción -->
                        <?php
                        if(($ep['user_id']===$_SESSION['user_id']) || $_SESSION['admin_user'] === 1) {
                            ?>
                            <div class="flex">
                                <button id="editendpoint_<?php echo $ep['id'];?>" class="btn btn-primary btn-sm reducir" style="--escala: 0.68;" data-id="<?php echo $ep['id'];?>">Editar</button>
                                <button id="deleteendpoint_<?php echo $ep['id'];?>" class="btn btn-danger btn-sm reducir" style="--escala: 0.68;" data-id="<?php echo $ep['id'];?>">Eliminar</button>
                            </div>
                            <?php
                        }                        
                        ?>
                      </td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
              </table>
            </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>

        /**
         * Selecciona un valor en un <select> por su valor.
         * Si no existe, lo añade como nueva opción y lo selecciona.
         * @param {string} selectId - ID del elemento <select>
         * @param {string} valueToSelect - Valor a buscar o añadir
         */
        function selectOrAddOption(selectId, valueToSelect) {
            var select = document.getElementById(selectId);
            if (!select) return; // Si no existe el select, salir

            // Buscar si el valor ya existe
            var found = false;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === valueToSelect) {
                    select.selectedIndex = i;
                    found = true;
                    break;
                }
            }

            // Si no existe, añadirlo y seleccionarlo
            if (!found) {
                var newOption = document.createElement("option");
                newOption.value = valueToSelect;
                newOption.text = valueToSelect;
                select.appendChild(newOption);
                select.selectedIndex = select.options.length - 1;
            }
        }


        /**
         * Selecciona un valor en un select2 por su valor.
         * Si no existe, lo añade como nueva opción y lo selecciona.
         * @param {string} selectId - ID del elemento <select>
         * @param {string} valueToSelect - Valor a buscar o añadir
         */
        function select2OrAddOption(selectId, valueToSelect) {
            var $select = $('#' + selectId);

            // Buscar si el valor ya existe
            var optionExists = $select.find('option[value="' + valueToSelect + '"]').length > 0;

            if (!optionExists) {
                // Añadir nueva opción
                var newOption = new Option(valueToSelect, valueToSelect, true, true);
                $select.append(newOption).trigger('change');
            } else {
                // Seleccionar la opción existente
                $select.val(valueToSelect).trigger('change');
            }
        }



        /**
         * Selecciona un <option> en un <select> por valor o texto.
         * @param {string} selectId - El id del elemento <select>.
         * @param {string} valueToFind - El valor o texto a buscar.
         * @returns {boolean} - true si encontró y seleccionó el option, false si no lo encontró.
         */
        function selectOptionByValueOrText(selectId, valueToFind) {
            var select = document.getElementById(selectId);
            if (!select) return false;

            for (var i = 0; i < select.options.length; i++) {
                var option = select.options[i];
                if (option.value === valueToFind || option.text === valueToFind) {
                    select.selectedIndex = i;
                    return true;
                }
            }
            return false;
        }

        
        // Recorre todos los formularios de la página y los resetea
        function resetForm() {
            for (let i = 0; i < document.forms.length; i++) {
              document.forms[i].reset();
            }
        }


        function showRegisterZone() {
            document.getElementById('mode').value = '<?php echo $CREATE;?>';
            document.getElementById('register-buttons').style.display = '';
            document.getElementById('modify-buttons').style.display = 'none'; 

            enableEndpointButtons();

            resetForm();
        }

        function showModifyZone() {
            document.getElementById('mode').value = '<?php echo $EDIT;?>';
            document.getElementById('register-buttons').style.display = 'none';
            document.getElementById('modify-buttons').style.display = '';

            disableEndpointButtons();

            goTop();
        }

        function goTop() {
            if (window.scrollY > 0) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }


        // Desactiva los botones de edición y borrado de endpoints
        function disableEndpointButtons() {
            document.querySelectorAll('button[id^="editendpoint_"], button[id^="deleteendpoint_"]').forEach(function(btn) {
                btn.disabled = true;
            });
        }

        // Activa los botones de edición y borrado de endpoints
        function enableEndpointButtons() {
            document.querySelectorAll('button[id^="editendpoint_"], button[id^="deleteendpoint_"]').forEach(function(btn) {
                btn.disabled = false;
            });
        }





        $(document).ready(function() {

            $('#scheme').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                tags: true  // Permite añadir nuevos valores
            });

            resetForm();

            document.getElementById('cancel-modify-button').addEventListener('click', function() {
                showRegisterZone();                
            });


            // Botones eliminar
            document.querySelectorAll('button[id^="deleteendpoint_"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const endpointId = this.getAttribute('data-id');

                    if (confirm("¿Seguro que quieres eliminar este endpoint?")) {
                        fetch('delete_endpoint.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'id=' + encodeURIComponent(endpointId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                alert(data.message);
                                // Opcional: eliminar el elemento de la vista
                                document.getElementById('deleteendpoint_' + endpointId).closest('tr').remove();
                            } else {
                                alert("Error: " + data.message);
                            }
                        })
                        .catch(error => {
                            alert("Error AJAX: " + error);
                        });
                    }
                });
            });


            // Botones editar
            document.querySelectorAll('button[id^="editendpoint_"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var endpointId = this.getAttribute('data-id');

                    fetch('get_endpoint.php?id=' + encodeURIComponent(endpointId))
                        .then(response => response.json())
                        .then(data => {
                            //alert(JSON.stringify(data, null, 2)); // Para depuración                            

                            if (data.success && data.endpoint) {
                                resetForm();
                                
                                document.getElementById('id').value = data.endpoint.id || '';
                                document.getElementById('url').value = data.endpoint.url || '';
                                document.getElementById('method').value = data.endpoint.method || '';
                                
                                // Selecciona el <select> de inputMime y outputMime
                                selectOptionByValueOrText("inputMime", data.endpoint.input_mime);
                                selectOptionByValueOrText("outputMime", data.endpoint.output_mime);
                                                                
                                if((data.endpoint.return_text || '') !="") {
                                    document.getElementById('returntext').value = data.endpoint.return_text || '';
                                    var tabTrigger = document.getElementById('textarea-tab');
                                    var tab = new bootstrap.Tab(tabTrigger);
                                    tab.show();
                                }

                                // Seleccionamos la pestaña status si tiene valor
                                if(!isNaN(data.endpoint.status)) {
                                    select2OrAddOption("status", data.endpoint.status);
                                    var tabTrigger = document.getElementById('status-tab');
                                    var tab = new bootstrap.Tab(tabTrigger);
                                    tab.show();
                                } else {
                                    select2OrAddOption("status", "Ninguno");
                                }

                                select2OrAddOption("scheme", data.endpoint.scheme);
                                showModifyZone();

                            } else {                                
                                alert('No se encontró el endpoint.');
                                showRegisterZone();
                            }
                        })
                        .catch(err => {
                            alert('Error al obtener los datos del endpoint.'+err);
                            showRegisterZone();
                            console.error(err);
                        });
                });
            });


        });


    </script>

    <?php include "./footer.php"; ?>

</body>
</html>