<?php
/*
Usando CURL en la Terminal:
    curl -X GET "http://127.0.0.1/apicreator/api/examples/api/image/12345" --output imagen.png

Respuesta esperada:
    Imagen PNG
*/

// Obtener el parámetro dinámico desde la URL
$imageId = $_GET['params'][0] ?? 'default';

// Crear imagen con el texto recibido
$image = imagecreate(400, 200);
$bgColor = imagecolorallocate($image, 255, 255, 255); // Fondo blanco
$textColor = imagecolorallocate($image, 0, 0, 0); // Texto negro
imagestring($image, 5, 50, 90, "ID: " . $imageId, $textColor);

imagepng($image); // Para PNG
//imagejpeg($image, null, 85); // Para JPG (calidad 85)
imagedestroy($image);