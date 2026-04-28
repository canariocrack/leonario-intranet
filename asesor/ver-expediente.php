<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$nombre = $_SESSION['nombre'] ?? 'Asesor';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: dashboard.php');
  exit;
}

// comprobar asignación
$q = $pdo->prepare("
  SELECT e.*, u.nombre AS cliente_nombre, u.email AS cliente_email
  FROM expedientes e
  INNER JOIN usuarios u ON u.id = e.cliente_id
  WHERE e.id = ? AND e.asesor_id = ?
  LIMIT 1
");
$q->execute([$id, $uid]);
$exp = $q->fetch();

if (!$exp) {
  die("No tienes permisos para ver este expediente.");
}

// docs del expediente
$dq = $pdo->prepare("SELECT id, archivo, fecha FROM documentos WHERE expediente_id = ? ORDER BY fecha DESC");
$dq->execute([$id]);
$docs = $dq->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Expediente #<?php echo (int)$id; ?> | Asesor</title>
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
        <div class="tag">Expediente #<?php echo (int)$id; ?></div>
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
      <h1 class="section-title">Expediente #<?php echo (int)$id; ?></h1>
      <p class="muted"><strong>Cliente:</strong> <?php echo htmlspecialchars($exp['cliente_nombre']); ?> · <?php echo htmlspecialchars($exp['cliente_email']); ?></p>
      <p class="muted"><strong>Título:</strong> <?php echo htmlspecialchars($exp['titulo']); ?></p>
      <p class="muted"><strong>Estado actual:</strong> <?php echo htmlspecialchars($exp['estado']); ?></p>

      <div class="spacer-10"></div>

      <div class="formCard flat">
        <h3 class="section-title sm">Actualizar estado</h3>
        <form method="post" action="actualizar-expediente.php">
          <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
          <div class="field">
            <label>Nuevo estado</label>
            <select name="estado" required>
              <option value="Pendiente" <?php echo $exp['estado']==='Pendiente'?'selected':''; ?>>Pendiente</option>
              <option value="En Revisión" <?php echo $exp['estado']==='En Revisión'?'selected':''; ?>>En Revisión</option>
              <option value="Finalizado" <?php echo $exp['estado']==='Finalizado'?'selected':''; ?>>Finalizado</option>
            </select>
          </div>
          <button class="btn primary" type="submit">Guardar</button>
        </form>
      </div>

      <div class="spacer-12"></div>

      <h3 class="section-title sm">Documentos</h3>
      <div class="table-wrap">
        <table class="table">
        <thead>
          <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$docs): ?>
            <tr><td colspan="3">No hay documentos asociados.</td></tr>
          <?php else: ?>
            <?php foreach ($docs as $d): ?>
              <tr>
                <td><?php echo htmlspecialchars($d['archivo']); ?></td>
                <td><?php echo htmlspecialchars($d['fecha']); ?></td>
                <td><a class="btn" href="../files/download.php?id=<?php echo (int)$d['id']; ?>">Descargar</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
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
