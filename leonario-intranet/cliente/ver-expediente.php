<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: dashboard.php'); exit; }

// 1. Expediente (solo del cliente en sesión)
$stmt_exp = mysqli_prepare($conexion,
  "SELECT e.id, e.titulo, e.descripcion, e.estado, e.created_at,
          e.respuesta_asesor, e.archivo_resolucion,
          a.nombre AS asesor_nombre
   FROM expedientes e
   LEFT JOIN usuarios a ON a.id = e.asesor_id
   WHERE e.id = ? AND e.cliente_id = ?
   LIMIT 1"
);
mysqli_stmt_bind_param($stmt_exp, "ii", $id, $uid);
mysqli_stmt_execute($stmt_exp);
$exp = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_exp));
mysqli_stmt_close($stmt_exp);

if (!$exp) { header('Location: dashboard.php'); exit; }

// 2. Documentos
$stmt_docs = mysqli_prepare($conexion,
  "SELECT d.id, d.archivo, d.nombre_original, d.fecha, u.rol AS subido_por_rol
   FROM documentos d
   LEFT JOIN usuarios u ON u.id = d.subido_por
   WHERE d.expediente_id = ?
   ORDER BY d.fecha DESC"
);
mysqli_stmt_bind_param($stmt_docs, "i", $id);
mysqli_stmt_execute($stmt_docs);
$docs = mysqli_fetch_all(mysqli_stmt_get_result($stmt_docs), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_docs);

function estadoStyle($s) {
  if ($s === 'Pendiente')  return 'background:#fffaeb;border-color:#fedf89;color:#b54708';
  if ($s === 'Finalizado') return 'background:#ecfdf3;border-color:#abefc6;color:#067647';
  return 'background:#eff8ff;border-color:#b2ddff;color:#175cd3';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($exp['titulo']); ?> | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
  <style>
    .doc-viewer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9000; align-items:center; justify-content:center; }
    .doc-viewer-overlay.active { display:flex; }
    .doc-viewer-box { background:#fff; border-radius:10px; overflow:hidden; width:90vw; max-width:960px; height:88vh; display:flex; flex-direction:column; }
    .doc-viewer-header { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#f9fafb; border-bottom:1px solid #e5e7eb; flex-shrink:0; }
    .doc-viewer-header span { font-weight:700; font-size:14px; color:#111827; }
    .viewer-actions { display:flex; gap:8px; align-items:center; }
    .doc-viewer-close { background:none; border:none; cursor:pointer; font-size:22px; color:#6b7280; line-height:1; padding:0 4px; }
    .doc-viewer-body { flex:1; overflow:hidden; }
    .doc-viewer-body iframe { width:100%; height:100%; border:none; }
    .doc-viewer-body .img-viewer { display:flex; align-items:center; justify-content:center; height:100%; background:#111; }
    .doc-viewer-body .img-viewer img { max-width:100%; max-height:100%; object-fit:contain; }
    .btn-dl { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; padding:7px 14px; border-radius:10px; font-weight:700; text-decoration:none; font-size:13px; display:inline-block; }
  </style>
</head>
<body class="app-shell">

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Mis trámites</div>
      </div>
    </a>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="dashboard.php?tab=docs">Documentos</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">
    <div class="app-page-head">
      <div>
        <div class="kicker">Detalle de tramite</div>
        <h1><?php echo htmlspecialchars($exp['titulo']); ?></h1>
        <p class="muted">Consulta estado, respuesta del asesor y documentos asociados.</p>
      </div>
    </div>

    <!-- Encabezado -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
      <div>
        <p class="muted" style="margin:0 0 4px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Expediente</p>
        <h2 style="margin:0;font-size:22px;font-weight:980;color:#0b1b2b;"><?php echo htmlspecialchars($exp['titulo']); ?></h2>
      </div>
      <span style="padding:7px 16px;border-radius:999px;font-weight:900;font-size:13px;border:1px solid;align-self:center;<?php echo estadoStyle($exp['estado']); ?>"><?php echo htmlspecialchars($exp['estado']); ?></span>
    </div>

    <!-- Descripción -->
    <?php if (!empty(trim($exp['descripcion'] ?? ''))): ?>
      <section class="card" style="margin-bottom:14px;border-left:3px solid var(--brand);">
        <p style="margin:0;color:#374151;font-size:15px;line-height:1.65;"><?php echo nl2br(htmlspecialchars($exp['descripcion'])); ?></p>
      </section>
    <?php endif; ?>

    <!-- Metadatos -->
    <section class="card" style="margin-bottom:14px;">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;">
        <div>
          <p class="muted" style="margin:0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Asesor</p>
          <strong style="font-size:14px;"><?php echo htmlspecialchars($exp['asesor_nombre'] ?? '— Sin asignar'); ?></strong>
        </div>
        <div>
          <p class="muted" style="margin:0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Fecha de alta</p>
          <strong style="font-size:14px;"><?php echo date('d/m/Y', strtotime($exp['created_at'])); ?></strong>
        </div>
        <div>
          <p class="muted" style="margin:0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Estado</p>
          <strong style="font-size:14px;"><?php echo htmlspecialchars($exp['estado']); ?></strong>
        </div>
      </div>
    </section>

    <!-- Respuesta del asesor -->
    <?php if (!empty(trim($exp['respuesta_asesor'] ?? ''))): ?>
      <section class="card" style="margin-bottom:14px;background:#f0fff4;border:1px solid #abefc6;">
        <div style="margin-bottom:8px;">
          <span style="background:#067647;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;">✓ Respuesta de tu asesor</span>
        </div>
        <p style="margin:0;color:#0b1b2b;white-space:pre-wrap;font-size:15px;line-height:1.65;"><?php echo htmlspecialchars($exp['respuesta_asesor']); ?></p>
      </section>
    <?php endif; ?>

    <!-- Documentos -->
    <section class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <h2 style="margin:0;font-weight:980;font-size:17px;">Documentos del trámite</h2>
        <?php if (($exp['estado'] ?? '') === 'Finalizado'): ?>
          <span class="closed-notice">Expediente finalizado: no admite más documentación</span>
        <?php else: ?>
          <a class="btn primary" href="subir-documento.php?exp=<?php echo $id; ?>">+ Subir documentos</a>
        <?php endif; ?>
      </div>

      <?php if (!$docs): ?>
        <p class="muted" style="text-align:center;padding:20px 0;">No se han adjuntado documentos aún.</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Origen</th>
              <th>Archivo</th>
              <th>Fecha</th>
              <th style="text-align:right;">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($docs as $d):
              $es_asesor = ($d['subido_por_rol'] === 'asesor');
              $nombreVis = htmlspecialchars(!empty($d['nombre_original']) ? $d['nombre_original'] : $d['archivo']);
              $ext = strtolower(pathinfo($d['archivo'], PATHINFO_EXTENSION));
              $esImagen = in_array($ext, ['jpg','jpeg','png','gif','webp']);
              $esPDF    = ($ext === 'pdf');
              $puedeVer = $esImagen || $esPDF;
            ?>
              <tr>
                <td>
                  <?php if ($es_asesor): ?>
                    <span class="origin-badge origin-asesor">Asesor</span>
                  <?php else: ?>
                    <span class="origin-badge origin-cliente">Mi envio</span>
                  <?php endif; ?>
                </td>
                <td><strong><?php echo $nombreVis; ?></strong></td>
                <td style="color:#6b7280;font-size:13px;"><?php echo date('d/m/Y H:i', strtotime($d['fecha'])); ?></td>
                <td style="text-align:right;">
                  <?php if ($puedeVer): ?>
                    <a class="btn" href="../files/download.php?id=<?php echo (int)$d['id']; ?>&inline=1" target="_blank" style="font-size:13px;padding:7px 13px;">Ver</a>
                  <?php endif; ?>
                  <a class="btn-dl" href="../files/download.php?id=<?php echo (int)$d['id']; ?>" style="margin-left:6px;">Descargar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores · Área Cliente
  </div>
</footer>

</body>
</html>

