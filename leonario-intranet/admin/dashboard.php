<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificamos que sea admin
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php');
  exit;
}

$nombre = $_SESSION['nombre'] ?? 'Administrador';
$tab = $_GET['tab'] ?? 'expedientes';

$flashOk = '';
$flashErr = '';
if (isset($_SESSION['flash_ok'])) {
  $flashOk = (string)$_SESSION['flash_ok'];
  unset($_SESSION['flash_ok']);
}
if (isset($_SESSION['flash_err'])) {
  $flashErr = (string)$_SESSION['flash_err'];
  unset($_SESSION['flash_err']);
}

function estadoClass($estado){
  if ($estado === 'Pendiente') return 'st-pendiente';
  if ($estado === 'Finalizado') return 'st-finalizado';
  return 'st-revision';
}

// ==========================================
// KPIs globales (SIN OBJETOS)
// ==========================================
$total = 0; $pend=0; $rev=0; $fin=0;

// mysqli_query ejecuta la consulta directamente pasándole la $conexion
$s = mysqli_query($conexion, "SELECT estado, COUNT(*) c FROM expedientes GROUP BY estado");
if ($s) {
  // mysqli_fetch_all convierte el resultado en un array de PHP
  $filas = mysqli_fetch_all($s, MYSQLI_ASSOC);
  foreach ($filas as $row) {
    $total += (int)$row['c'];
    if (($row['estado'] ?? '') === 'Pendiente') $pend = (int)$row['c'];
    if (($row['estado'] ?? '') === 'En Revisión') $rev = (int)$row['c'];
    if (($row['estado'] ?? '') === 'Finalizado') $fin = (int)$row['c'];
  }
}

// ==========================================
// KPIs usuarios (SIN OBJETOS)
// ==========================================
$uTotal=0; $uAdmin=0; $uAsesor=0; $uCliente=0;

$q = mysqli_query($conexion, "SELECT rol, COUNT(*) c FROM usuarios GROUP BY rol");
if ($q) {
  $filasUsuarios = mysqli_fetch_all($q, MYSQLI_ASSOC);
  foreach($filasUsuarios as $r){
    $uTotal += (int)$r['c'];
    if ($r['rol']==='admin') $uAdmin = (int)$r['c'];
    if ($r['rol']==='asesor') $uAsesor = (int)$r['c'];
    if ($r['rol']==='cliente') $uCliente = (int)$r['c'];
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
</head>
<body class="app-shell">

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="../index.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Administración</div>
      </div>
    </a>

    <nav class="nav">
      <a class="active" href="dashboard.php">Dashboard</a>
      <a href="../index.php">Web pública</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <?php if ($flashErr): ?>
      <div class="alert err"><?php echo htmlspecialchars($flashErr); ?></div>
    <?php endif; ?>
    <?php if ($flashOk): ?>
      <div class="alert ok"><?php echo htmlspecialchars($flashOk); ?></div>
    <?php endif; ?>

    <section class="hero" style="background:
      linear-gradient(135deg, rgba(7,26,43,.90), rgba(11,42,74,.62)),
      url('../assets/img/admin.jpg');
      background-size:cover;background-position:center;">
      <div class="content">
        <div>
          <div class="kicker">Panel de control</div>
          <h1>Hola, <?php echo htmlspecialchars($nombre); ?> 👋</h1>
          <p>Gestión global del sistema: usuarios, expedientes, asignaciones y trazabilidad.</p>
          <div class="actions">
            <a class="btn primary" href="crear-expediente.php">Crear expediente</a>
            <a class="btn soft" href="asignar-expediente.php">Asignar asesor</a>
          </div>
        </div>

        <div class="logoCard">
          <img src="../assets/img/logo-leonario.png" alt="Logo Leonario">
        </div>
      </div>
    </section>

    <div style="height:14px"></div>

    <section class="grid grid3">
      <div class="card kpi-card"><h3><?php echo (int)$total; ?></h3><p class="muted">Expedientes totales</p></div>
      <div class="card kpi-card"><h3><?php echo (int)$pend; ?></h3><p class="muted">Pendientes</p></div>
      <div class="card kpi-card"><h3><?php echo (int)$fin; ?></h3><p class="muted">Finalizados</p></div>
    </section>

    <div style="height:14px"></div>

    <section class="grid grid3">
      <div class="card kpi-card"><h3><?php echo (int)$uTotal; ?></h3><p class="muted">Usuarios totales</p></div>
      <div class="card kpi-card"><h3><?php echo (int)$uAsesor; ?></h3><p class="muted">Asesores</p></div>
      <div class="card kpi-card"><h3><?php echo (int)$uCliente; ?></h3><p class="muted">Clientes</p></div>
    </section>

    <div style="height:14px"></div>

    <section class="card app-tabs" style="padding:10px;">
      <div class="nav" style="gap:6px;">
        <a class="<?php echo $tab==='expedientes'?'active':''; ?>" href="dashboard.php?tab=expedientes">Expedientes</a>
        <a class="<?php echo $tab==='usuarios'?'active':''; ?>" href="dashboard.php?tab=usuarios">Usuarios</a>
        <a class="<?php echo $tab==='logs'?'active':''; ?>" href="dashboard.php?tab=logs">Logs</a>
      </div>
    </section>

    <div style="height:14px"></div>

    <?php if ($tab === 'expedientes'): ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Expedientes</h2>

        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>Cliente</th>
              <th>Asesor</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php
              // Consulta procedimental de expedientes
              $q_exp = mysqli_query($conexion, "
                SELECT e.id, e.titulo, e.estado, e.created_at,
                       c.nombre AS cliente_nombre,
                       a.nombre AS asesor_nombre
                FROM expedientes e
                INNER JOIN usuarios c ON c.id = e.cliente_id
                LEFT JOIN usuarios a ON a.id = e.asesor_id
                ORDER BY e.created_at DESC
              ");
              
              $rows = $q_exp ? mysqli_fetch_all($q_exp, MYSQLI_ASSOC) : [];

              if (!$rows) {
                echo '<tr><td colspan="7">No hay expedientes todavía.</td></tr>';
              } else {
                foreach ($rows as $r) {
                  $st = $r['estado'] ?? '';
                  $cls = estadoClass($st);
                  echo '<tr>';
                  echo '<td>#'.(int)$r['id'].'</td>';
                  echo '<td>'.htmlspecialchars($r['titulo']).'</td>';
                  echo '<td>'.htmlspecialchars($r['cliente_nombre']).'</td>';
                  echo '<td>'.htmlspecialchars($r['asesor_nombre'] ?? 'Sin asignar').'</td>';
                  echo '<td><span class="status '.$cls.'">'.htmlspecialchars($st).'</span></td>';
                  echo '<td>'.htmlspecialchars($r['created_at']).'</td>';
                  echo '<td style="text-align:right;"><a class="btn" href="ver-expediente.php?id='.(int)$r['id'].'">Ver</a></td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </section>

    <?php elseif ($tab === 'usuarios'): ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Usuarios</h2>

        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Alta</th>
            </tr>
          </thead>
          <tbody>
            <?php
              // Consulta procedimental de usuarios
              $q_usr = mysqli_query($conexion, "SELECT id, nombre, email, rol, created_at FROM usuarios ORDER BY created_at DESC");
              $rows = $q_usr ? mysqli_fetch_all($q_usr, MYSQLI_ASSOC) : [];

              foreach ($rows as $r) {
                echo '<tr>';
                echo '<td>#'.(int)$r['id'].'</td>';
                echo '<td>'.htmlspecialchars($r['nombre']).'</td>';
                echo '<td>'.htmlspecialchars($r['email']).'</td>';
                echo '<td>'.htmlspecialchars($r['rol']).'</td>';
                echo '<td>'.htmlspecialchars($r['created_at']).'</td>';
                echo '</tr>';
              }
            ?>
          </tbody>
        </table>
      </section>

    <?php else: ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Logs del sistema</h2>

        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Usuario</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php
              // Consulta procedimental de logs
              $q_logs = mysqli_query($conexion, "
                SELECT l.fecha, u.nombre, l.accion
                FROM logs l
                INNER JOIN usuarios u ON u.id = l.usuario_id
                ORDER BY l.fecha DESC
                LIMIT 200
              ");
              $rows = $q_logs ? mysqli_fetch_all($q_logs, MYSQLI_ASSOC) : [];

              if (!$rows) {
                echo '<tr><td colspan="3">No hay logs todavía.</td></tr>';
              } else {
                foreach ($rows as $r) {
                  echo '<tr>';
                  echo '<td>'.htmlspecialchars($r['fecha']).'</td>';
                  echo '<td>'.htmlspecialchars($r['nombre']).'</td>';
                  echo '<td>'.htmlspecialchars($r['accion']).'</td>';
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
    © <?php echo date('Y'); ?> Leonario Asesores · Administración
  </div>
</footer>

</body>
</html>

