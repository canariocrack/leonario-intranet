<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$error = '';
$ok = '';

// expedientes
$eq = $pdo->query("
  SELECT e.id, e.titulo, e.estado, e.asesor_id,
         c.nombre AS cliente_nombre,
         a.nombre AS asesor_nombre
  FROM expedientes e
  INNER JOIN usuarios c ON c.id = e.cliente_id
  LEFT JOIN usuarios a ON a.id = e.asesor_id
  ORDER BY e.created_at DESC
");
$expedientes = $eq->fetchAll();

// asesores
$aq = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol='asesor' ORDER BY nombre ASC");
$asesores = $aq->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $expediente_id = (int)($_POST['expediente_id'] ?? 0);
  $asesor_id = (int)($_POST['asesor_id'] ?? 0);

  if ($expediente_id <= 0 || $asesor_id <= 0) {
    $error = "Selecciona expediente y asesor.";
  } else {
    // validar expediente existe
    $ch = $pdo->prepare("SELECT id FROM expedientes WHERE id = ? LIMIT 1");
    $ch->execute([$expediente_id]);
    if (!$ch->fetch()) {
      $error = "Expediente no válido.";
    } else {
      // validar asesor existe
      $ah = $pdo->prepare("SELECT id FROM usuarios WHERE id=? AND rol='asesor' LIMIT 1");
      $ah->execute([$asesor_id]);
      if (!$ah->fetch()) {
        $error = "Asesor no válido.";
      } else {
        $up = $pdo->prepare("UPDATE expedientes SET asesor_id = ? WHERE id = ?");
        $up->execute([$asesor_id, $expediente_id]);

        $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
        $log->execute([$uid, "Admin asignó asesor $asesor_id al expediente #$expediente_id"]);

        $ok = "Asesor asignado correctamente.";
        header("Location: asignar-expediente.php?ok=1");
        exit;
      }
    }
  }
}

if (isset($_GET['ok'])) $ok = "Asesor asignado correctamente.";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asignar expediente | Admin</title>
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
        <div class="tag">Asignar expediente</div>
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
      <h1 class="section-title">Asignar expediente</h1>
      <p class="muted">Asigna un asesor a un expediente existente.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" class="formCard flat">
        <div class="field">
          <label>Expediente</label>
          <select name="expediente_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($expedientes as $e): ?>
              <option value="<?php echo (int)$e['id']; ?>">
                #<?php echo (int)$e['id']; ?> — <?php echo htmlspecialchars($e['titulo']); ?>
                · Cliente: <?php echo htmlspecialchars($e['cliente_nombre']); ?>
                · Asesor: <?php echo htmlspecialchars($e['asesor_nombre'] ?? 'Sin asignar'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Asesor</label>
          <select name="asesor_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($asesores as $a): ?>
              <option value="<?php echo (int)$a['id']; ?>">
                <?php echo htmlspecialchars($a['nombre']); ?> (<?php echo htmlspecialchars($a['email']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button class="btn primary" type="submit">Asignar</button>
        <a class="btn" href="dashboard.php">Volver</a>
      </form>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container muted">© <?php echo date('Y'); ?> Leonario Asesores</div>
</footer>

</body>
</html>
