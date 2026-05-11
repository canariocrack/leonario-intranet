<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php');
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: dashboard.php'); exit; }

// Expediente + cliente + asesor
$q_exp = "
  SELECT e.id, e.titulo, e.descripcion, e.estado, e.created_at,
         e.respuesta_asesor, e.notas_privadas,
         u.id AS cliente_id, u.nombre AS cliente_nombre,
         u.email AS cliente_email, u.telefono AS cliente_telefono,
         u.dni AS cliente_dni, u.direccion AS cliente_direccion, u.avatar AS cliente_avatar,
         a.nombre AS asesor_nombre, a.email AS asesor_email
  FROM expedientes e
  INNER JOIN usuarios u ON u.id = e.cliente_id
  LEFT JOIN usuarios a ON a.id = e.asesor_id
  WHERE e.id = ?
  LIMIT 1
";
$stmt_exp = mysqli_prepare($conexion, $q_exp);
mysqli_stmt_bind_param($stmt_exp, "i", $id);
mysqli_stmt_execute($stmt_exp);
$exp = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_exp));
mysqli_stmt_close($stmt_exp);

if (!$exp) { header('Location: dashboard.php'); exit; }

// Documentos
$q_docs = "SELECT d.id, d.archivo, d.nombre_original, d.fecha, u.rol AS subido_por_rol
           FROM documentos d LEFT JOIN usuarios u ON u.id = d.subido_por
           WHERE d.expediente_id = ? ORDER BY d.fecha DESC";
$stmt_docs = mysqli_prepare($conexion, $q_docs);
mysqli_stmt_bind_param($stmt_docs, "i", $id);
mysqli_stmt_execute($stmt_docs);
$docs = mysqli_fetch_all(mysqli_stmt_get_result($stmt_docs), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_docs);

function estadoBadge($s) {
  if ($s === 'Pendiente') return 'background:#fef9c3;color:#854d0e;border:1px solid #fde68a;';
  if ($s === 'Finalizado') return 'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;';
  return 'background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Expediente #<?php echo $id; ?> | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
  <style>
    body { background: #f9fafb; }
    .page-wrap { max-width: 1000px; margin: 30px auto 60px; padding: 0 16px; }

    /* Tarjeta cliente */
    .client-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px 28px; display:flex; gap:24px; align-items:center; margin-bottom:24px; box-shadow:0 1px 2px rgba(0,0,0,.05); }
    .client-avatar { width:72px; height:72px; border-radius:50%; object-fit:cover; flex-shrink:0; background:#e5e7eb; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:900; color:#374151; }
    .client-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; flex:1; border-left:1px solid #f3f4f6; padding-left:24px; }
    .ci p { margin:0; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
    .ci strong { display:block; font-size:14px; color:#111827; margin-top:3px; }

    /* Tarjeta expediente */
    .exp-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px 28px; margin-bottom:24px; box-shadow:0 1px 2px rgba(0,0,0,.05); }
    .exp-meta { display:flex; flex-wrap:wrap; gap:20px; margin-top:16px; }
    .exp-meta-item p { margin:0; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
    .exp-meta-item strong { font-size:14px; color:#111827; }

    .desc-box { margin-top:16px; background:#f0f7ff; border-left:3px solid #2563eb; border-radius:4px; padding:12px 16px; color:#374151; font-size:14px; line-height:1.6; }

    /* Tabla documentos */
    .docs-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px 28px; box-shadow:0 1px 2px rgba(0,0,0,.05); }
    .table { width:100%; border-collapse:collapse; }
    .table th { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; padding-bottom:10px; border-bottom:1px solid #e5e7eb; text-align:left; }
    .table td { padding:13px 0; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
    .btn-sm { padding:5px 13px; border-radius:6px; font-size:12px; font-weight:700; text-decoration:none; display:inline-block; cursor:pointer; border:none; white-space:nowrap; }
    .btn-view { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; }
    .btn-dl { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
    .doc-actions { display:flex; justify-content:flex-end; align-items:center; gap:6px; flex-wrap:wrap; }
    .badge-rol { padding:3px 9px; border-radius:4px; font-size:11px; font-weight:700; text-transform:uppercase; }

    /* Visualizador */
    .doc-viewer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9000; align-items:center; justify-content:center; }
    .doc-viewer-overlay.active { display:flex; }
    .doc-viewer-box { background:#fff; border-radius:10px; overflow:hidden; width:90vw; max-width:960px; height:88vh; display:flex; flex-direction:column; }
    .doc-viewer-header { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#f9fafb; border-bottom:1px solid #e5e7eb; flex-shrink:0; }
    .doc-viewer-header span { font-weight:700; font-size:14px; color:#111827; }
    .viewer-actions { display:flex; gap:8px; align-items:center; }
    .doc-viewer-close { background:none; border:none; cursor:pointer; font-size:22px; color:#6b7280; line-height:1; padding:0 4px; }
    .doc-viewer-body { flex:1; overflow:hidden; }
    .doc-viewer-body iframe { width:100%; height:100%; border:none; }
    .doc-viewer-body .img-viewer { display:flex; align-items:center; justify-content:center; height:100%; background:#111; overflow:auto; }
    .doc-viewer-body .img-viewer img { max-width:100%; max-height:100%; object-fit:contain; }
  </style>
</head>
<body class="app-shell">

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Admin · Expediente #<?php echo $id; ?></div>
      </div>
    </a>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="asignar-expediente.php">Asignar asesor</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="page-wrap">
  <div class="app-page-head">
    <div>
      <div class="kicker">Administracion</div>
      <h1>Detalle de expediente</h1>
      <p class="muted">Vista completa de cliente, asesor asignado, estado y documentos asociados.</p>
    </div>
  </div>

  <!-- Perfil del cliente -->
  <div class="client-card">
    <?php if (!empty($exp['cliente_avatar'])):
      $avatarFile = basename((string)$exp['cliente_avatar']);
      $avatarSrc = is_file(__DIR__ . '/../uploads/avatars/' . $avatarFile)
        ? '../uploads/avatars/' . rawurlencode($avatarFile)
        : '../uploads/' . rawurlencode($avatarFile);
    ?>
      <img src="<?php echo htmlspecialchars($avatarSrc); ?>" class="client-avatar" alt="Foto">
    <?php else: ?>
      <div class="client-avatar"><?php echo htmlspecialchars(substr($exp['cliente_nombre'], 0, 1)); ?></div>
    <?php endif; ?>
    <div class="client-grid">
      <div class="ci"><p>Cliente / Razón Social</p><strong><?php echo htmlspecialchars($exp['cliente_nombre']); ?></strong></div>
      <div class="ci"><p>DNI / CIF</p><strong><?php echo htmlspecialchars($exp['cliente_dni'] ?: '—'); ?></strong></div>
      <div class="ci"><p>Teléfono</p><strong><?php echo htmlspecialchars($exp['cliente_telefono'] ?: '—'); ?></strong></div>
      <div class="ci"><p>Email</p><strong><?php echo htmlspecialchars($exp['cliente_email']); ?></strong></div>
      <?php if (!empty($exp['cliente_direccion'])): ?>
        <div class="ci"><p>Dirección</p><strong><?php echo htmlspecialchars($exp['cliente_direccion']); ?></strong></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Datos del expediente -->
  <div class="exp-card">
    <h1 style="margin:0;font-size:22px;font-weight:900;color:#111827;"><?php echo htmlspecialchars($exp['titulo']); ?></h1>

    <?php if (!empty(trim($exp['descripcion'] ?? ''))): ?>
      <div class="desc-box"><?php echo nl2br(htmlspecialchars($exp['descripcion'])); ?></div>
    <?php endif; ?>

    <div class="exp-meta">
      <div class="exp-meta-item">
        <p>Estado</p>
        <span style="display:inline-block;margin-top:4px;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;<?php echo estadoBadge($exp['estado']); ?>"><?php echo htmlspecialchars($exp['estado']); ?></span>
      </div>
      <div class="exp-meta-item">
        <p>Asesor asignado</p>
        <strong><?php echo htmlspecialchars($exp['asesor_nombre'] ?? '— Sin asignar'); ?></strong>
        <?php if (!empty($exp['asesor_email'])): ?>
          <div style="font-size:12px;color:#6b7280;"><?php echo htmlspecialchars($exp['asesor_email']); ?></div>
        <?php endif; ?>
      </div>
      <div class="exp-meta-item">
        <p>Fecha de alta</p>
        <strong><?php echo date('d/m/Y H:i', strtotime($exp['created_at'])); ?></strong>
      </div>
    </div>

    <?php if (!empty(trim($exp['respuesta_asesor'] ?? ''))): ?>
      <div style="margin-top:20px;background:#f0fff4;border-left:3px solid #22c55e;border-radius:4px;padding:12px 16px;">
        <div style="font-size:11px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Respuesta del asesor al cliente</div>
        <div style="font-size:14px;color:#374151;"><?php echo nl2br(htmlspecialchars($exp['respuesta_asesor'])); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty(trim($exp['notas_privadas'] ?? ''))): ?>
      <div style="margin-top:14px;background:#fefce8;border-left:3px solid #eab308;border-radius:4px;padding:12px 16px;">
        <div style="font-size:11px;font-weight:700;color:#854d0e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Notas privadas (solo admin/asesor)</div>
        <div style="font-size:14px;color:#374151;"><?php echo nl2br(htmlspecialchars($exp['notas_privadas'])); ?></div>
      </div>
    <?php endif; ?>

    <div style="margin-top:20px;display:flex;gap:10px;">
      <a href="asignar-expediente.php" class="btn soft">Asignar / cambiar asesor</a>
    </div>
  </div>

  <!-- Documentos -->
  <div class="docs-card">
    <h2 style="margin:0 0 16px;font-size:16px;font-weight:900;text-transform:uppercase;letter-spacing:.5px;color:#111827;">Historial de documentos</h2>
    <?php if (!$docs): ?>
      <p style="color:#6b7280;font-size:14px;">No hay documentos en este expediente.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Archivo</th>
            <th style="width:110px;">Origen</th>
            <th style="text-align:right;width:200px;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($docs as $d):
            $es_asesor = ($d['subido_por_rol'] === 'asesor');
            $nombreVis = htmlspecialchars(!empty($d['nombre_original']) ? $d['nombre_original'] : $d['archivo']);
            $ext = strtolower(pathinfo($d['archivo'], PATHINFO_EXTENSION));
            $esImagen = in_array($ext, ['jpg','jpeg','png','gif','webp']);
            $esPDF = ($ext === 'pdf');
            $puedeVer = $esImagen || $esPDF;
          ?>
          <tr>
            <td>
              <strong style="font-size:14px;color:#111827;"><?php echo $nombreVis; ?></strong>
              <div style="font-size:12px;color:#6b7280;margin-top:3px;"><?php echo date('d/m/Y H:i', strtotime($d['fecha'])); ?></div>
            </td>
            <td>
              <?php if ($es_asesor): ?>
                <span class="origin-badge origin-asesor">Asesor</span>
              <?php else: ?>
                <span class="origin-badge origin-cliente">Cliente</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right;">
              <div class="doc-actions">
                <?php if ($puedeVer): ?>
                  <a class="btn-sm btn-view" href="../files/download.php?id=<?php echo (int)$d['id']; ?>&inline=1" target="_blank">Ver</a>
                <?php endif; ?>
                <a class="btn-sm btn-dl" href="../files/download.php?id=<?php echo (int)$d['id']; ?>">Descargar</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</main>

<footer class="footer">
  <div class="container muted">© <?php echo date('Y'); ?> Leonario Asesores · Administración</div>
</footer>

</body>
</html>
