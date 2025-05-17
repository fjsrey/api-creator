<?php
/**
 * Mueve el contenido de una carpeta origen a una carpeta destino de forma recursiva.
 * 
 * - Si la carpeta destino no existe, se crea automáticamente.
 * - Si la carpeta destino está dentro de la carpeta origen, se ignora todo su contenido para evitar bucles infinitos.
 * - Archivos y subcarpetas se mueven uno por uno, y las carpetas vacías en origen se eliminan al final.
 * 
 * @param string $origen  Ruta de la carpeta origen.
 * @param string $destino Ruta de la carpeta destino.
 * 
 * @return void
 */
function moverContenido($origen, $destino) {
    // Convertir a rutas absolutas
    $origenReal = realpath($origen);
    $destinoReal = realpath($destino);

    // Si la carpeta destino está dentro de la carpeta origen, la registramos para excluir todo su contenido
    $excluirRuta = ($destinoReal !== false && strpos($destinoReal, $origenReal) === 0) ? $destinoReal : null;

    // Crear carpeta de destino si no existe
    if (!is_dir($destino)) {
        mkdir($destino, 0777, true);
        $destinoReal = realpath($destino); // actualizar realpath después de crearla
        if ($excluirRuta === null && strpos($destinoReal, $origenReal) === 0) {
            $excluirRuta = $destinoReal;
        }
    }

    // Obtener elementos en la carpeta origen
    $elementos = scandir($origen);
    foreach ($elementos as $elemento) {
        if ($elemento === '.' || $elemento === '..') continue;

        $rutaOrigen = $origen . DIRECTORY_SEPARATOR . $elemento;
        $rutaDestino = $destino . DIRECTORY_SEPARATOR . $elemento;

        $rutaOrigenReal = realpath($rutaOrigen);

        // Ignorar si está dentro de la carpeta destino
        if ($rutaOrigenReal !== false && $excluirRuta !== null && strpos($rutaOrigenReal, $excluirRuta) === 0) {
            continue;
        }

        if (is_dir($rutaOrigen)) {
            // Recursivamente mover subcarpeta
            moverContenido($rutaOrigen, $rutaDestino);
            // Eliminar carpeta vacía
            @rmdir($rutaOrigen);
        } else {
            // Mover archivo
            rename($rutaOrigen, $rutaDestino);
        }
    }
}
?>
