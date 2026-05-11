<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

$nombre = $_SESSION['nombre'] ?? 'Cliente';
$uid = (int)$_SESSION['user_id'];

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

$tab = $_GET['tab'] ?? 'expedientes';

function estadoClass($estado){
  if ($estado === 'Pendiente') return 'st-pendiente';
  if ($estado === 'Finalizado') return 'st-finalizado';
  return 'st-revision';
}

// KPIs del cliente (Procedimental)
$total = 0; $pend = 0; $rev = 0; $fin = 0;
$q_kpi = "SELECT estado, COUNT(*) as c FROM expedientes WHERE cliente_id = ? GROUP BY estado";
$stmt_kpi = mysqli_prepare($conexion, $q_kpi);
if ($stmt_kpi) {
    mysqli_stmt_bind_param($stmt_kpi, "i", $uid);
    mysqli_stmt_execute($stmt_kpi);
    $res_kpi = mysqli_stmt_get_result($stmt_kpi);
    while ($row = mysqli_fetch_assoc($res_kpi)) {
        $c = (int)$row['c'];
        $total += $c;
        if (($row['estado'] ?? '') === 'Pendiente') $pend = $c;
        if (($row['estado'] ?? '') === 'En Revisión') $rev = $c;
        if (($row['estado'] ?? '') === 'Finalizado') $fin = $c;
    }
    mysqli_stmt_close($stmt_kpi);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Cliente | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
  <style>
    /* Estilos para las etiquetas de respuesta integradas en tu diseño */
    .badge-nueva-res { background-color: #007bff; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; margin-top: 5px; display: inline-block; }
    .badge-doc-res { background-color: #e7f1ff; color: #007bff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; border: 1px solid #b8daff; }

    .exp-groups { display: grid; gap: 14px; }
    .exp-group { border: 1px solid #e6edf5; border-radius: 14px; background: #f8fbff; overflow: hidden; }
    .exp-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding: 12px 14px; background:#eef4ff; border-bottom:1px solid #dbe7fb; }
    .exp-head h3 { margin:0; font-size:15px; font-weight:900; color:#0b1b2b; }
    .exp-count { font-size:12px; font-weight:800; color:#46607a; background:#fff; border:1px solid #d9e3ef; border-radius:999px; padding:3px 9px; }
    .exp-body { padding: 10px; display:grid; gap:8px; }
    .doc-item { display:grid; grid-template-columns: 1fr auto; gap:12px; align-items:center; background:#fff; border:1px solid #e6edf5; border-radius:12px; padding: 10px 12px; }
    .doc-title { margin:0; font-size:14px; font-weight:850; color:#0b1b2b; overflow-wrap:anywhere; }
    .doc-meta { margin:4px 0 0; font-size:12px; color:#60758a; font-weight:700; }
    .doc-actions { display:flex; align-items:center; gap:8px; justify-content:flex-end; }
    .doc-actions .btn { min-width:96px; text-align:center; padding:8px 10px; }

    .doc-viewer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9000; align-items:center; justify-content:center; }
    .doc-viewer-overlay.active { display:flex; }
    .doc-viewer-box { background:#fff; border-radius:10px; overflow:hidden; width:90vw; max-width:960px; height:88vh; display:flex; flex-direction:column; }
    .doc-viewer-header { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#f9fafb; border-bottom:1px solid #e5e7eb; flex-shrink:0; }
    .doc-viewer-header span { font-weight:700; font-size:14px; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .viewer-actions { display:flex; gap:8px; align-items:center; }
    .doc-viewer-close { background:none; border:none; cursor:pointer; font-size:22px; color:#6b7280; line-height:1; padding:0 4px; }
    .doc-viewer-body { flex:1; overflow:hidden; }
    .doc-viewer-body iframe { width:100%; height:100%; border:none; }
    .doc-viewer-body .img-viewer { display:flex; align-items:center; justify-content:center; height:100%; background:#111; }
    .doc-viewer-body .img-viewer img { max-width:100%; max-height:100%; object-fit:contain; }

    .profile-wrap { display: grid; grid-template-columns: 92px 1fr; gap: 18px; align-items: start; padding: 18px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 14px; }
    .profile-avatar { width: 92px; height: 92px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
    .profile-initial { width: 92px; height: 92px; border-radius: 50%; background: #e8efff; border: 2px solid #cfe0ff; display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 900; color: #1d4ed8; }
    .profile-grid { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 12px; }
    .profile-field { background: #fff; border: 1px solid #e6edf5; border-radius: 12px; padding: 10px 12px; }
    .profile-field p { margin: 0 0 4px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #60758a; }
    .profile-field strong { margin: 0; display: block; font-size: 15px; color: #0b1b2b; line-height: 1.35; overflow-wrap: anywhere; }

    @media (max-width: 920px) {
      .doc-item { grid-template-columns: 1fr; }
      .doc-actions { justify-content: flex-start; }
      .profile-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .doc-item { grid-template-columns: 1fr; }
      .profile-wrap { grid-template-columns: 1fr; }
      .profile-avatar, .profile-initial { margin: 0 auto; }
    }
  </style>
</head>
<body class="app-shell">

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
      <a class="active" href="dashboard.php">Dashboard</a>
      <a href="perfil.php">Mi perfil</a>
      <a href="../index.php">Web pública</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <div class="app-page-head">
      <div>
        <div class="kicker">Área cliente</div>
        <h1>Panel de seguimiento</h1>
        <p class="muted">Consulta trámites, revisa documentos y envía nuevas solicitudes desde un espacio privado.</p>
      </div>
    </div>

    <?php if ($flashErr): ?>
      <div class="alert err"><?php echo htmlspecialchars($flashErr); ?></div>
    <?php endif; ?>
    <?php if ($flashOk): ?>
      <div class="alert ok"><?php echo htmlspecialchars($flashOk); ?></div>
    <?php endif; ?>

    <section class="hero" style="background:
      linear-gradient(135deg, rgba(7,26,43,.90), rgba(11,42,74,.62)),
      url('../assets/img/cliente.jpg');
      background-size:cover;background-position:center;">
      <div class="content">
        <div>
          <div class="kicker">Área privada</div>
          <h1>Hola, <?php echo htmlspecialchars($nombre); ?> 👋</h1>
          <p>Desde aquí puedes consultar el estado de tus trámites, revisar tus documentos y enviar solicitudes.</p>
          <div class="actions">
            <a class="btn primary" href="crear-solicitud.php">Enviar solicitud</a>
            <a class="btn soft" href="subir-documento.php">Subir documentos</a>
          </div>
        </div>

        <div class="logoCard">
          <img src="../assets/img/logo-leonario.png" alt="Logo Leonario">
        </div>
      </div>
    </section>

    <div style="height:14px"></div>

    <section class="grid grid3">
      <div class="card kpi-card">
        <h3><?php echo (int)$total; ?></h3>
        <p class="muted">Total expedientes</p>
      </div>
      <div class="card kpi-card">
        <h3><?php echo (int)$pend; ?></h3>
        <p class="muted">Pendientes</p>
      </div>
      <div class="card kpi-card">
        <h3><?php echo (int)$fin; ?></h3>
        <p class="muted">Finalizados</p>
      </div>
    </section>

    <div style="height:14px"></div>

    <section class="card app-tabs" style="padding:10px;">
      <div class="nav" style="gap:6px;">
        <a class="<?php echo $tab==='expedientes'?'active':''; ?>" href="dashboard.php?tab=expedientes">Mis expedientes</a>
        <a class="<?php echo $tab==='docs'?'active':''; ?>" href="dashboard.php?tab=docs">Mis documentos</a>
        <a class="<?php echo $tab==='perfil'?'active':''; ?>" href="dashboard.php?tab=perfil">Mi perfil</a>
      </div>
    </section>

    <div style="height:14px"></div>

    <?php if ($tab === 'expedientes'): ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Mis expedientes</h2>

        <table class="table">
          <thead>
            <tr>
              <th>Título</th>
              <th>Estado</th>
              <th>Asesor</th>
              <th>Fecha</th>
              <th style="text-align: right;">Acción</th> </tr>
          </thead>
          <tbody>
            <?php
              // Añadimos respuesta_asesor a la consulta para saber si hay mensaje
              $q_exp = "
                SELECT e.id, e.titulo, e.estado, e.created_at, e.respuesta_asesor, e.archivo_resolucion, u.nombre AS asesor_nombre
                FROM expedientes e
                LEFT JOIN usuarios u ON u.id = e.asesor_id
                WHERE e.cliente_id = ?
                ORDER BY e.created_at DESC
              ";
              $stmt_exp = mysqli_prepare($conexion, $q_exp);
              mysqli_stmt_bind_param($stmt_exp, "i", $uid);
              mysqli_stmt_execute($stmt_exp);
              $res_exp = mysqli_stmt_get_result($stmt_exp);

              if (mysqli_num_rows($res_exp) === 0) {
                echo '<tr><td colspan="5">Todavía no tienes expedientes.</td></tr>';
              } else {
                while ($r = mysqli_fetch_assoc($res_exp)) {
                  $st = $r['estado'] ?? '';
                  $cls = estadoClass($st);
                  
                  // COMPROBAMOS SI EL ASESOR HA RESPONDIDO
                  $tiene_respuesta = (!empty($r['respuesta_asesor']) || !empty($r['archivo_resolucion']));

                  echo '<tr>';
                  echo '<td>';
                  echo '<strong>' . htmlspecialchars($r['titulo']) . '</strong>';
                  // SI HAY RESPUESTA, MOSTRAMOS LA ETIQUETA AQUÍ
                  if ($tiene_respuesta) {
                      echo '<br><span class="badge-nueva-res">✨ Respuesta del Asesor</span>';
                  }
                  echo '</td>';
                  echo '<td><span class="status '.$cls.'">'.htmlspecialchars($st).'</span></td>';
                  echo '<td>'.htmlspecialchars($r['asesor_nombre'] ?? 'Sin asignar').'</td>';
                  echo '<td>'.htmlspecialchars(date('Y-m-d', strtotime($r['created_at']))).'</td>';
                  
                  // BOTÓN PARA ENTRAR AL EXPEDIENTE Y VER EL MENSAJE
                  echo '<td style="text-align: right;">';
                  echo '<a class="btn" href="ver-expediente.php?id='.(int)$r['id'].'">Ver trámite</a>';
                  echo '</td>';
                  echo '</tr>';
                }
              }
              mysqli_stmt_close($stmt_exp);
            ?>
          </tbody>
        </table>
      </section>

    <?php elseif ($tab === 'docs'): ?>
      <section class="card">
        <h2 style="margin:0 0 10px;font-weight:980;">Documentos subidos</h2>
        <p class="muted" style="margin:0 0 14px;">Tus documentos organizados por expediente.</p>

        <?php
          // Consulta: solo documentos del cliente autenticado
          $q_docs = "
            SELECT d.id, d.archivo, d.nombre_original, d.fecha, e.id AS expediente_id, e.titulo
            FROM documentos d
            INNER JOIN expedientes e ON e.id = d.expediente_id
            WHERE e.cliente_id = ? AND d.subido_por = ?
            ORDER BY e.titulo ASC, d.fecha DESC, d.id DESC
          ";
          $stmt_docs = mysqli_prepare($conexion, $q_docs);
          mysqli_stmt_bind_param($stmt_docs, "ii", $uid, $uid);
          mysqli_stmt_execute($stmt_docs);
          $res_docs = mysqli_stmt_get_result($stmt_docs);

          // Agrupamos los resultados por expediente para una vista más ordenada
          $docsPorExpediente = [];
          while ($rowDoc = mysqli_fetch_assoc($res_docs)) {
            $expId = (int)($rowDoc['expediente_id'] ?? 0);
            if (!isset($docsPorExpediente[$expId])) {
              $docsPorExpediente[$expId] = [
                'titulo' => $rowDoc['titulo'] ?? 'Expediente',
                'docs' => [],
              ];
            }
            $docsPorExpediente[$expId]['docs'][] = $rowDoc;
          }
        ?>

        <?php if (!$docsPorExpediente): ?>
          <div class="alert">Aún no hay documentos en tus expedientes.</div>
        <?php else: ?>
          <div class="exp-groups">
            <?php foreach ($docsPorExpediente as $grupo): ?>
              <section class="exp-group">
                <div>
                  <div class="exp-head">
                    <h3><?php echo htmlspecialchars($grupo['titulo']); ?></h3>
                    <span class="exp-count"><?php echo count($grupo['docs']); ?> archivos</span>
                  </div>
                  <div class="exp-body">
                    <?php foreach ($grupo['docs'] as $r):
                      // Preparación de datos para render y visor
                      $nombreVisible = !empty($r['nombre_original']) ? $r['nombre_original'] : $r['archivo'];
                      $ext = strtolower(pathinfo((string)$r['archivo'], PATHINFO_EXTENSION));
                      $esImagen = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
                      $esPDF = ($ext === 'pdf');
                      $puedeVer = $esImagen || $esPDF;
                    ?>
                    <article class="doc-item">
                      <div>
                        <p class="doc-title"><?php echo htmlspecialchars($nombreVisible); ?></p>
                        <p class="doc-meta">Subido el <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['fecha']))); ?></p>
                      </div>
                      <div class="doc-actions">
                        <?php if ($puedeVer): ?>
                          <a class="btn" href="../files/download.php?id=<?php echo (int)$r['id']; ?>&inline=1" target="_blank">Ver</a>
                        <?php endif; ?>
                        <a class="btn" href="../files/download.php?id=<?php echo (int)$r['id']; ?>">Descargar</a>
                      </div>
                    </article>
                    <?php endforeach; ?>
                  </div>
                </div>
              </section>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php mysqli_stmt_close($stmt_docs); ?>

      </section>

    <?php else:
      // Cargar datos del perfil
      $q_perfil = mysqli_prepare($conexion, "SELECT nombre, email, telefono, dni, direccion, avatar FROM usuarios WHERE id = ? LIMIT 1");
      mysqli_stmt_bind_param($q_perfil, "i", $uid);
      mysqli_stmt_execute($q_perfil);
      $perfil = mysqli_fetch_assoc(mysqli_stmt_get_result($q_perfil));
      mysqli_stmt_close($q_perfil);
    ?>
      <section class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
          <h2 style="margin:0;font-weight:980;">Mi perfil</h2>
          <a class="btn primary" href="perfil.php">Editar perfil</a>
        </div>

        <div class="profile-wrap">
          <?php if (!empty($perfil['avatar'])):
            $avatarFile = basename((string)$perfil['avatar']);
            $avatarSrc = is_file(__DIR__ . '/../uploads/avatars/' . $avatarFile)
              ? '../uploads/avatars/' . rawurlencode($avatarFile)
              : '../uploads/' . rawurlencode($avatarFile);
          ?>
            <img src="<?php echo htmlspecialchars($avatarSrc); ?>" class="profile-avatar" alt="Foto">
          <?php else: ?>
            <div class="profile-initial"><?php echo htmlspecialchars(mb_substr($perfil['nombre'] ?? 'C', 0, 1)); ?></div>
          <?php endif; ?>

          <div class="profile-grid">
            <div class="profile-field"><p>Nombre</p><strong><?php echo htmlspecialchars($perfil['nombre'] ?? '—'); ?></strong></div>
            <div class="profile-field"><p>Email</p><strong><?php echo htmlspecialchars($perfil['email'] ?? '—'); ?></strong></div>
            <div class="profile-field"><p>Teléfono</p><strong><?php echo htmlspecialchars($perfil['telefono'] ?: '—'); ?></strong></div>
            <div class="profile-field"><p>DNI / CIF</p><strong><?php echo htmlspecialchars($perfil['dni'] ?: '—'); ?></strong></div>
            <?php if (!empty($perfil['direccion'])): ?>
            <div class="profile-field" style="grid-column:1/-1;"><p>Dirección</p><strong><?php echo htmlspecialchars($perfil['direccion']); ?></strong></div>
            <?php endif; ?>
          </div>
        </div>
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
