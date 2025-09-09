<?php
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

// Crear instancia
$dompdf = new Dompdf();

// Contenido del PDF
$html = "<h1>Hola desde Dompdf en la partición E:</h1>";
$dompdf->loadHtml($html);

// Configurar tamaño y orientación
$dompdf->setPaper('A4', 'portrait');

// Renderizar
$dompdf->render();

// Descargar el PDF
$dompdf->stream("prueba.pdf", ["Attachment" => true]);
