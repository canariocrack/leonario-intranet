<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$nombre_asesor = $_SESSION['nombre'] ?? 'Asesor';

$flashOk = '';
$flashErr = '';
if (isset($_SESSION['flash_ok'])) { $flashOk = (string)$_SESSION['flash_ok']; unset($_SESSION['flash_ok']); }
if (isset($_SESSION['flash_err'])) { $flashErr = (string)$_SESSION['flash_err']; unset($_SESSION['flash_err']); }

$search = trim($_GET['search'] ?? '');
$estado_filtro = $_GET['estado'] ?? 'todos';
$search_param = "%" . $search . "%";

// KPIs
$kpi_total = 0; $kpi_pend = 0; $kpi_fin = 0;
$q_kpi = mysqli_prepare($conexion, "SELECT estado, COUNT(*) c FROM expedientes WHERE asesor_id = ? GROUP BY estado");
if ($q_kpi) {
  mysqli_stmt_bind_param($q_kpi, "i", $uid);
  mysqli_stmt_execute($q_kpi);
  $res_kpi = mysqli_stmt_get_result($q_kpi);
  while ($row = mysqli_fetch_assoc($res_kpi)) {
    $kpi_total += (int)$row['c'];
    if ($row['estado'] === 'Pendiente') $kpi_pend = (int)$row['c'];
    if ($row['estado'] === 'Finalizado') $kpi_fin = (int)$row['c'];
  }
  mysqli_stmt_close($q_kpi);
}

// Lista expedientes con filtros
$q_exp = "
  SELECT e.id, e.titulo, e.estado, e.created_at, u.nombre AS cliente_nombre, u.dni AS cliente_dni
  FROM expedientes e
  INNER JOIN usuarios u ON u.id = e.cliente_id
  WHERE e.asesor_id = ?
    AND (e.titulo LIKE ? OR u.nombre LIKE ? OR IFNULL(u.dni, '') LIKE ?)
";
if ($estado_filtro !== 'todos') {
  $q_exp .= " AND e.estado = ? ORDER BY e.created_at DESC";
  $stmt = mysqli_prepare($conexion, $q_exp);
  mysqli_stmt_bind_param($stmt, "issss", $uid, $search_param, $search_param, $search_param, $estado_filtro);
} else {
  $q_exp .= " ORDER BY e.created_at DESC";
  $stmt = mysqli_prepare($conexion, $q_exp);
  mysqli_stmt_bind_param($stmt, "isss", $uid, $search_param, $search_param, $search_param);
}
mysqli_stmt_execute($stmt);
$expedientes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

function estadoClass($e){ if($e==='Pendiente') return 'st-pendiente'; if($e==='Finalizado') return 'st-finalizado'; return 'st-revision'; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Asesor | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
</head>
<body class="app-shell">

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Panel Asesor</div>
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

    <div class="app-page-head">
      <div>
        <div class="kicker">Panel asesor</div>
        <h1>Expedientes asignados</h1>
        <p class="muted">Gestiona estados, documentación y respuestas a clientes desde una vista de trabajo.</p>
      </div>
    </div>

    <?php if ($flashErr): ?>
      <div class="alert err"><?php echo htmlspecialchars($flashErr); ?></div>
    <?php endif; ?>
    <?php if ($flashOk): ?>
      <div class="alert ok"><?php echo htmlspecialchars($flashOk); ?></div>
    <?php endif; ?>

    <section class="hero" style="background:
      linear-gradient(135deg, rgba(7,26,43,.92), rgba(30,64,175,.55)),
      url('../assets/img/admin.jpg');
      background-size:cover;background-position:center;">
      <div class="content">
        <div>
          <div class="kicker">Área de trabajo</div>
          <h1>Hola, <?php echo htmlspecialchars($nombre_asesor); ?> 👋</h1>
          <p>Gestiona los expedientes asignados, comunícate con los clientes y revisa su documentación.</p>
        </div>
        <div class="logoCard">
          <img src="../assets/img/logo-leonario.png" alt="Logo Leonario">
        </div>
      </div>
    </section>

    <div style="height:14px"></div>

    <section class="grid grid3">
      <div class="card kpi-card"><h3><?php echo $kpi_total; ?></h3><p class="muted">Expedientes asignados</p></div>
      <div class="card kpi-card"><h3><?php echo $kpi_pend; ?></h3><p class="muted">Pendientes</p></div>
      <div class="card kpi-card"><h3><?php echo $kpi_fin; ?></h3><p class="muted">Finalizados</p></div>
    </section>

    <div style="height:14px"></div>

    <section class="card">
      <h2 style="margin:0 0 14px;font-weight:980;">Mis expedientes</h2>

      <form class="filterbar" method="get" action="dashboard.php" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
        <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:200px;">
          <label style="font-size:12px;font-weight:700;color:#4b5563;text-transform:uppercase;">Buscar cliente, DNI o trámite</label>
          <input type="text" name="search" style="padding:10px 14px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;" placeholder="Ej: Antonio, 29511912Z, IRPF..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:12px;font-weight:700;color:#4b5563;text-transform:uppercase;">Estado</label>
          <select name="estado" style="padding:10px 14px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;">
            <option value="todos" <?php echo $estado_filtro==='todos'?'selected':''; ?>>Todos</option>
            <option value="Pendiente" <?php echo $estado_filtro==='Pendiente'?'selected':''; ?>>Pendiente</option>
            <option value="En Revisión" <?php echo $estado_filtro==='En Revisión'?'selected':''; ?>>En Revisión</option>
            <option value="Finalizado" <?php echo $estado_filtro==='Finalizado'?'selected':''; ?>>Finalizado</option>
          </select>
        </div>
        <button type="submit" class="btn primary" style="height:42px;">Filtrar</button>
        <?php if ($search !== '' || $estado_filtro !== 'todos'): ?>
          <a href="dashboard.php" class="btn" style="height:42px;display:flex;align-items:center;">Limpiar</a>
        <?php endif; ?>
      </form>

      <table class="table">
        <thead>
          <tr>
            <th>Trámite</th>
            <th>Cliente</th>
            <th>DNI / CIF</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th style="text-align:right;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($expedientes)): ?>
            <tr><td colspan="6" style="text-align:center;color:#6b7280;">No se encontraron expedientes con esos filtros.</td></tr>
          <?php else: ?>
            <?php foreach ($expedientes as $exp): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($exp['titulo']); ?></strong></td>
                <td><?php echo htmlspecialchars($exp['cliente_nombre']); ?></td>
                <td style="color:#6b7280;font-family:monospace;font-size:13px;"><?php echo htmlspecialchars($exp['cliente_dni'] ?: '—'); ?></td>
                <td><span class="status <?php echo estadoClass($exp['estado']); ?>"><?php echo htmlspecialchars($exp['estado']); ?></span></td>
                <td style="color:#6b7280;font-size:13px;"><?php echo date('d/m/Y', strtotime($exp['created_at'])); ?></td>
                <td style="text-align:right;">
                  <a class="btn" href="ver-expediente.php?id=<?php echo (int)$exp['id']; ?>">Gestionar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores · Panel Asesor
  </div>
</footer>

</body>
</html>
