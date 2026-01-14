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

// clientes
$cq = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol='cliente' ORDER BY nombre ASC");
$clientes = $cq->fetchAll();

// asesores
$aq = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol='asesor' ORDER BY nombre ASC");
$asesores = $aq->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = trim($_POST['titulo'] ?? '');
  $cliente_id = (int)($_POST['cliente_id'] ?? 0);
  $asesor_id = (int)($_POST['asesor_id'] ?? 0);
  $estado = $_POST['estado'] ?? 'Pendiente';

  $allowed = ['Pendiente','En Revisión','Finalizado'];

  if ($titulo === '' || $cliente_id <= 0) {
    $error = "Título y cliente son obligatorios.";
  } elseif (!in_array($estado, $allowed, true)) {
    $error = "Estado no válido.";
  } else {
    // comprobar cliente existe
    $ch = $pdo->prepare("SELECT id FROM usuarios WHERE id=? AND rol='cliente' LIMIT 1");
    $ch->execute([$cliente_id]);
    if (!$ch->fetch()) {
      $error = "Cliente no válido.";
    } else {
      // asesor opcional
      if ($asesor_id > 0) {
        $ah = $pdo->prepare("SELECT id FROM usuarios WHERE id=? AND rol='asesor' LIMIT 1");
        $ah->execute([$asesor_id]);
        if (!$ah->fetch()) {
          $error = "Asesor no válido.";
        }
      }

      if (!$error) {
        $ins = $pdo->prepare("INSERT INTO expedientes (titulo, estado, asesor_id, cliente_id) VALUES (?,?,?,?)");
        $ins->execute([$titulo, $estado, ($asesor_id>0?$asesor_id:null), $cliente_id]);

        $eid = (int)$pdo->lastInsertId();

        $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
        $log->execute([$uid, "Admin creó expediente #$eid para cliente $cliente_id (asesor=".($asesor_id?:'NULL').")"]);

        $ok = "Expediente creado correctamente (#$eid).";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear expediente | Admin</title>
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
        <div class="tag">Crear expediente</div>
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
      <h1 style="margin:0 0 10px;font-weight:980;">Crear expediente</h1>
      <p class="muted">Genera expedientes y (si quieres) asigna asesor desde el inicio.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" class="formCard" style="box-shadow:none;">
        <div class="field">
          <label>Título</label>
          <input type="text" name="titulo" required placeholder="Ej: Alta autónomo, IVA trimestral, nóminas...">
        </div>

        <div class="field">
          <label>Cliente</label>
          <select name="cliente_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>">
                <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['email']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Asesor (opcional)</label>
          <select name="asesor_id">
            <option value="0">Sin asignar</option>
            <?php foreach ($asesores as $a): ?>
              <option value="<?php echo (int)$a['id']; ?>">
                <?php echo htmlspecialchars($a['nombre']); ?> (<?php echo htmlspecialchars($a['email']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Estado</label>
          <select name="estado">
            <option value="Pendiente">Pendiente</option>
            <option value="En Revisión">En Revisión</option>
            <option value="Finalizado">Finalizado</option>
          </select>
        </div>

        <button class="btn primary" type="submit">Crear expediente</button>
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
