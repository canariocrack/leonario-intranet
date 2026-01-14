<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];

$id = (int)($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';

$allowed = ['Pendiente','En Revisión','Finalizado'];
if ($id <= 0 || !in_array($estado, $allowed, true)) {
  header('Location: dashboard.php');
  exit;
}

// solo si es su expediente
$check = $pdo->prepare("SELECT id FROM expedientes WHERE id = ? AND asesor_id = ? LIMIT 1");
$check->execute([$id, $uid]);
if (!$check->fetch()) {
  die("No tienes permisos para modificar este expediente.");
}

$up = $pdo->prepare("UPDATE expedientes SET estado = ? WHERE id = ? AND asesor_id = ?");
$up->execute([$estado, $id, $uid]);

try{
  $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
  $log->execute([$uid, "Asesor actualizó expediente $id a estado: $estado"]);
}catch(Exception $e){}

header('Location: ver-expediente.php?id=' . $id);
exit;
