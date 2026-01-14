<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);
$rol = $_SESSION['rol'] ?? 'cliente';
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
  die("Documento no válido.");
}

$q = $pdo->prepare("
  SELECT d.id, d.archivo, d.expediente_id,
         e.cliente_id, e.asesor_id
  FROM documentos d
  INNER JOIN expedientes e ON e.id = d.expediente_id
  WHERE d.id = ?
  LIMIT 1
");
$q->execute([$id]);
$doc = $q->fetch();

if (!$doc) {
  die("Documento no encontrado.");
}

$allowed = false;
if ($rol === 'admin') $allowed = true;
if ($rol === 'cliente' && (int)$doc['cliente_id'] === $uid) $allowed = true;
if ($rol === 'asesor' && (int)$doc['asesor_id'] === $uid) $allowed = true;

if (!$allowed) {
  die("No tienes permisos para descargar este documento.");
}

$path = __DIR__ . '/../uploads/docs/' . $doc['archivo'];
if (!is_file($path)) {
  die("Archivo no disponible.");
}

// headers
$filename = basename($doc['archivo']);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: ' . filesize($path));
readfile($path);

// log
try{
  $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
  $log->execute([$uid, "Descargó documento $filename (doc_id=$id)"]);
}catch(Exception $e){}

exit;
