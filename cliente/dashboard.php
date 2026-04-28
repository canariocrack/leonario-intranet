<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

$nombre = $_SESSION['nombre'] ?? 'Cliente';
$uid = (int)$_SESSION['user_id'];

$tab = $_GET['tab'] ?? 'expedientes';

function estadoClass($estado){
  if ($estado === 'Pendiente') return 'st-pendiente';
  if ($estado === 'Finalizado') return 'st-finalizado';
  return 'st-revision';
}

// KPIs del cliente
$total = 0; $pend = 0; $rev = 0; $fin = 0;
try {
  $s = $pdo->prepare("SELECT estado, COUNT(*) c FROM expedientes WHERE cliente_id = ? GROUP BY estado");
  $s->execute([$uid]);
  foreach ($s->fetchAll() as $row) {
    $total += (int)$row['c'];
    if (($row['estado'] ?? '') === 'Pendiente') $pend = (int)$row['c'];
    if (($row['estado'] ?? '') === 'En Revisión') $rev = (int)$row['c'];
    if (($row['estado'] ?? '') === 'Finalizado') $fin = (int)$row['c'];
  }
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Cliente | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="../index.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Área Cliente</div>
      </div>
    </a>

    <nav class="nav">
      <a href="../index.php">Web pública</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero hero--cliente">
      <div class="content">
        <div>
          <div class="kicker">Área privada</div>
          <h1>Hola, <?php echo htmlspecialchars($nombre); ?> 👋</h1>
          <p>Desde aquí puedes consultar el estado de tus trámites, subir documentación y enviar solicitudes.</p>
          <div class="actions">
            <a class="btn primary" href="crear-solicitud.php">Enviar solicitud</a>
            <a class="btn soft" href="subir-documento.php">Subir documentación</a>
          </div>
        </div>

        <div class="logoCard">
          <img src="../assets/img/logo-leonario.png" alt="Logo Leonario">
        </div>
      </div>
    </section>

    <div class="spacer-14"></div>

    <section class="grid grid3">
      <div class="card">
        <h3><?php echo (int)$total; ?></h3>
        <p class="muted">Total expedientes</p>
      </div>
      <div class="card">
        <h3><?php echo (int)$pend; ?></h3>
        <p class="muted">Pendientes</p>
      </div>
      <div class="card">
        <h3><?php echo (int)$fin; ?></h3>
        <p class="muted">Finalizados</p>
      </div>
    </section>

    <div class="spacer-14"></div>

    <section class="card tabs-card">
      <div class="nav tabs">
        <a class="<?php echo $tab==='expedientes'?'active':''; ?>" href="dashboard.php?tab=expedientes">Mis expedientes</a>
        <a class="<?php echo $tab==='docs'?'active':''; ?>" href="dashboard.php?tab=docs">Mis documentos</a>
        <a class="<?php echo $tab==='perfil'?'active':''; ?>" href="dashboard.php?tab=perfil">Mi perfil</a>
      </div>
    </section>

    <div class="spacer-14"></div>

    <?php if ($tab === 'expedientes'): ?>
      <section class="card">
        <h2 class="section-title">Mis expedientes</h2>

        <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Título</th>
              <th>Estado</th>
              <th>Asesor</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $q = $pdo->prepare("
                SELECT e.titulo, e.estado, e.created_at, u.nombre AS asesor_nombre
                FROM expedientes e
                LEFT JOIN usuarios u ON u.id = e.asesor_id
                WHERE e.cliente_id = ?
                ORDER BY e.created_at DESC
              ");
              $q->execute([$uid]);
              $rows = $q->fetchAll();

              if (!$rows) {
                echo '<tr><td colspan="4">Todavía no tienes expedientes.</td></tr>';
              } else {
                foreach ($rows as $r) {
                  $st = $r['estado'] ?? '';
                  $cls = estadoClass($st);
                  echo '<tr>';
                  echo '<td>'.htmlspecialchars($r['titulo']).'</td>';
                  echo '<td><span class="status '.$cls.'">'.htmlspecialchars($st).'</span></td>';
                  echo '<td>'.htmlspecialchars($r['asesor_nombre'] ?? 'Sin asignar').'</td>';
                  echo '<td>'.htmlspecialchars($r['created_at']).'</td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
          </table>
      </div>
      </section>

    <?php elseif ($tab === 'docs'): ?>
      <section class="card">
        <h2 class="section-title">Documentos subidos</h2>

        <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Expediente</th>
              <th>Archivo</th>
              <th>Fecha</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $q = $pdo->prepare("
                SELECT d.id, d.archivo, d.fecha, e.titulo
                FROM documentos d
                INNER JOIN expedientes e ON e.id = d.expediente_id
                WHERE e.cliente_id = ?
                ORDER BY d.fecha DESC
              ");
              $q->execute([$uid]);
              $rows = $q->fetchAll();

              if (!$rows) {
                echo '<tr><td colspan="4">Aún no has subido documentación.</td></tr>';
              } else {
                foreach ($rows as $r) {
                  echo '<tr>';
                  echo '<td>'.htmlspecialchars($r['titulo']).'</td>';
                  echo '<td>'.htmlspecialchars($r['archivo']).'</td>';
                  echo '<td>'.htmlspecialchars($r['fecha']).'</td>';
                  echo '<td><a class="btn" href="../files/download.php?id='.(int)$r['id'].'">Descargar</a></td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
          </table>
      </div>

        <div class="spacer-10"></div>
        <a class="btn primary" href="subir-documento.php">Subir un documento</a>
      </section>

    <?php else: ?>
      <section class="card">
        <h2 class="section-title">Mi perfil</h2>
        <p class="muted">Gestiona tus datos y mantén tu información actualizada.</p>
        <a class="btn primary" href="perfil.php">Editar perfil</a>
      </section>
    <?php endif; ?>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores · Área Cliente
  </div>
</footer>

</body>
</html>
