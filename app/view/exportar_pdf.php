<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

require '../config/conexion.php';
require '../libs/fpdf/fpdf.php';

$id_usuario = $_SESSION['id_usuario'] ?? 0;

$stmt = $conexion->prepare("SELECT nombre, correo FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$datos_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$datos_usuario) die("❌ No se encontraron datos del usuario.");

$stmt = $conexion->prepare("
    SELECT a.nombre_aula, r.fecha, r.hora_inicio, r.hora_fin
    FROM reservas r
    JOIN aulas a ON r.id_aula = a.id_aula
    WHERE r.id_usuario = ?
    ORDER BY r.fecha DESC
");
$stmt->execute([$id_usuario]);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conexion->prepare("
    SELECT e.nombre_equipo, p.fecha_prestamo, p.fecha_devolucion, p.estado
    FROM prestamos p
    JOIN equipos e ON p.id_equipo = e.id_equipo
    WHERE p.id_usuario = ?
    ORDER BY p.fecha_prestamo DESC
");
$stmt->execute([$id_usuario]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Boleta de Historial de Uso',0,1,'C');

$pdf->SetFont('Arial','',12);
$pdf->Ln(5);
$pdf->Cell(0,8,'Nombre: '.$datos_usuario['nombre'],0,1);
$pdf->Cell(0,8,'Correo: '.$datos_usuario['correo'],0,1);
$pdf->Cell(0,8,'Fecha y Hora: '.date("Y-m-d H:i:s"),0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,'Reservas de Aulas',0,1);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(60,8,'Aula',1);
$pdf->Cell(40,8,'Fecha',1);
$pdf->Cell(45,8,'Hora Inicio',1);
$pdf->Cell(45,8,'Hora Fin',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
if ($reservas) {
    foreach($reservas as $r) {
        $pdf->Cell(60,8,$r['nombre_aula'],1);
        $pdf->Cell(40,8,$r['fecha'],1);
        $pdf->Cell(45,8,$r['hora_inicio'],1);
        $pdf->Cell(45,8,$r['hora_fin'],1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(190,8,'No hay reservas registradas.',1,1,'C');
}
$pdf->Ln(5);

$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,'Prestamos de Equipos',0,1);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(60,8,'Equipo',1);
$pdf->Cell(45,8,'Fecha Prestamo',1);
$pdf->Cell(45,8,'Devolucion',1);
$pdf->Cell(40,8,'Estado',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
if ($prestamos) {
    foreach($prestamos as $p) {
        $pdf->Cell(60,8,$p['nombre_equipo'],1);
        $pdf->Cell(45,8,$p['fecha_prestamo'],1);
        $pdf->Cell(45,8,$p['fecha_devolucion'] ?: 'Pendiente',1);
        $pdf->Cell(40,8,$p['estado'],1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(190,8,'No hay prestamos registrados.',1,1,'C');
}

$pdf->Output('I','Boleta_Historial.pdf');
?>
