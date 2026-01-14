<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
  header('Location: ../auth/login.php');
  exit;
}

$tab = $_GET['tab'] ?? 'expedientes';
$nombre = $_SESSION['nombre'] ?? 'Asesor';
$uid = (int)$_SESSION['user_id'];

function estadoClass($estado){
  if ($estado === 'Pendiente') return 'st-pendiente';
  if ($estado === 'Finalizado') return 'st-finalizado';
  return 'st-revision';
}

// KPIs
$total = 0; $pend=0; $rev=0; $fin=0;
try {
  $s = $pdo->prepare("SELECT estado, COUNT(*) c FROM expedientes WHERE asesor_id = ? GROUP BY estado");
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
  <title>Panel Asesor | Leonario Asesores</title>
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
        <div class="tag">Área Asesor</div>
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

    <section class="hero" style="background:
      linear-gradient(135deg, rgba(7,26,43,.90), rgba(11,42,74,.62)),
      url('../assets/img/asesor.jpg');
      background-size:cover;background-position:center;">
      <div class="content">
        <div>
          <div class="kicker">Área privada</div>
          <h1>Hola, <?php echo htmlspecialchars($nombre); ?> 👋</h1>
          <p>Gestiona expedientes asignados, revisa documentación y actualiza estados de trámite.</p>
          <div class="actions">
            <a class="btn primary" href="dashboard.php?tab=expedientes">Ver expedientes</a>
            <a class="btn soft" href="dashboard.php?tab=clientes">Ver clientes</a>
          </div>
        </div>

        <div class="logoCard">
          <img src="../assets/img/logo-leonario.png" alt="Logo Leonario">
        </div>
      </div>
    </section>

    <div style="height:14px"></div>

    <section class="grid grid3">
      <div class="card"><h3><?php echo (int)$total; ?></h3><p class="muted">Total asignados</p></div>
      <div class="card"><h3><?php echo (int)$pend; ?></h3><p class="muted">Pendientes</p></div>
      <div class="card"><h3><?php echo (int)$fin; ?></h3><p class="muted">Finalizados</p></div>
    </section>

    <div style="height:14px"></div>

    <section class="card" style="padding:10px;">
      <div class="nav" style="gap:6px;">
        <a class="<?php echo $tab==='expedientes'?'active':''; ?>" href="dashboard.php?tab=expedientes">Expedientes</a>
        <a class="<?php echo $tab==='clientes'?'active':''; ?>" href="dashboard.php?tab=clientes">Clientes</a>
        <a class="<?php echo $tab==='documentos'?'active':''; ?>" href="dashboard.php?tab=documentos">Documentos</a>
      </div>
    </section>

    <div style="height:14px"></div>

    <?php if ($tab === 'expedientes'): ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Expedientes asignados</h2>

        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $q = $pdo->prepare("
                SELECT e.id, e.titulo, e.estado, u.nombre AS cliente_nombre
                FROM expedientes e
                INNER JOIN usuarios u ON u.id = e.cliente_id
                WHERE e.asesor_id = ?
                ORDER BY e.created_at DESC
              ");
              $q->execute([$uid]);
              $rows = $q->fetchAll();

              if (!$rows) {
                echo '<tr><td colspan="5">No tienes expedientes asignados.</td></tr>';
              } else {
                foreach ($rows as $r) {
                  $st = $r['estado'] ?? '';
                  $cls = estadoClass($st);
                  echo '<tr>';
                  echo '<td>#'.(int)$r['id'].'</td>';
                  echo '<td>'.htmlspecialchars($r['titulo']).'</td>';
                  echo '<td>'.htmlspecialchars($r['cliente_nombre']).'</td>';
                  echo '<td><span class="status '.$cls.'">'.htmlspecialchars($st).'</span></td>';
                  echo '<td>
                          <a class="btn" href="ver-expediente.php?id='.(int)$r['id'].'">Abrir</a>
                        </td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </section>

    <?php elseif ($tab === 'clientes'): ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Clientes vinculados</h2>

        <table class="table">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Email</th>
              <th>Expedientes</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $q = $pdo->prepare("
                SELECT u.nombre, u.email, COUNT(e.id) total_exp
                FROM expedientes e
                INNER JOIN usuarios u ON u.id = e.cliente_id
                WHERE e.asesor_id = ?
                GROUP BY u.id, u.nombre, u.email
                ORDER BY total_exp DESC
              ");
              $q->execute([$uid]);
              $rows = $q->fetchAll();

              if (!$rows) {
                echo '<tr><td colspan="3">No hay clientes todavía.</td></tr>';
              } else {
                foreach ($rows as $r) {
                  echo '<tr>';
                  echo '<td>'.htmlspecialchars($r['nombre']).'</td>';
                  echo '<td>'.htmlspecialchars($r['email']).'</td>';
                  echo '<td>'.(int)$r['total_exp'].'</td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </section>

    <?php else: ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Documentos</h2>
        <p class="muted">Descarga documentos de los expedientes asignados.</p>

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
                WHERE e.asesor_id = ?
                ORDER BY d.fecha DESC
              ");
              $q->execute([$uid]);
              $rows = $q->fetchAll();

              if (!$rows) {
                echo '<tr><td colspan="4">No hay documentos aún.</td></tr>';
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
      </section>
    <?php endif; ?>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores · Área Asesor
  </div>
</footer>

</body>
</html>
