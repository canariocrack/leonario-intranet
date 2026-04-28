<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];

$error = '';
$ok = '';

// obtener usuario
$st = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE id = ? LIMIT 1");
$st->execute([$uid]);
$user = $st->fetch();

if (!$user) {
  header('Location: dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');

  if ($nombre === '') {
    $error = "El nombre no puede estar vacío.";
  } else {
    $up = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
    $up->execute([$nombre, $uid]);

    $_SESSION['nombre'] = $nombre;

    $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
    $log->execute([$uid, "Cliente actualizó su perfil (nombre)"]);

    $ok = "Perfil actualizado correctamente.";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi perfil | Cliente</title>
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
        <div class="tag">Mi perfil</div>
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
      <h1 class="section-title">Mi perfil</h1>
      <p class="muted">Actualiza tus datos básicos para mantener tu perfil al día.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" class="formCard flat">
        <div class="field">
          <label>Nombre</label>
          <input type="text" name="nombre" required value="<?php echo htmlspecialchars($user['nombre']); ?>">
        </div>

        <div class="field">
          <label>Email</label>
          <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
          <small class="muted">El email no se puede modificar desde aquí.</small>
        </div>

        <button class="btn primary" type="submit">Guardar cambios</button>
        <a class="btn" href="dashboard.php">Volver</a>
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
