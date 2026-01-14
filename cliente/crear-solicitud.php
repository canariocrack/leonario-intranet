<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$nombre = $_SESSION['nombre'] ?? 'Cliente';

$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = trim($_POST['titulo'] ?? '');

  if ($titulo === '') {
    $error = "Indica un título o motivo de la solicitud.";
  } else {
    $ins = $pdo->prepare("INSERT INTO expedientes (titulo, estado, asesor_id, cliente_id) VALUES (?, 'Pendiente', NULL, ?)");
    $ins->execute([$titulo, $uid]);

    // log
    $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
    $log->execute([$uid, "Cliente creó solicitud/expediente: $titulo"]);

    $ok = "Solicitud enviada correctamente. Se ha generado un expediente pendiente.";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Enviar solicitud | Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Enviar solicitud</div>
      </div>
    </a>

    <nav class="nav">
      <a href="dashboard.php">Volver</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="card">
      <h1 style="margin:0 0 10px;font-weight:980;">Enviar solicitud</h1>
      <p class="muted">Hola, <?php echo htmlspecialchars($nombre); ?>. Describe brevemente el motivo para que el equipo lo gestione.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" class="formCard" style="box-shadow:none;">
        <div class="field">
          <label>Motivo / Título</label>
          <input type="text" name="titulo" placeholder="Ej: Revisión IRPF, alta autónomo, consulta laboral..." required>
        </div>

        <button class="btn primary" type="submit">Enviar</button>
        <a class="btn" href="dashboard.php">Cancelar</a>
      </form>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores
  </div>
</footer>

</body>
</html>
