<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: dashboard.php'); exit; }

// 1. OBTENER DATOS DEL CLIENTE
$q_exp = "
  SELECT e.id, e.titulo, e.descripcion, e.estado, e.respuesta_asesor, e.notas_privadas,
         u.nombre AS cliente_nombre, 
         u.email AS cliente_email,
         u.telefono AS cliente_telefono,
         u.dni AS cliente_dni,
         u.direccion AS cliente_direccion,
         u.avatar AS cliente_avatar
  FROM expedientes e
  INNER JOIN usuarios u ON u.id = e.cliente_id
  WHERE e.id = ? AND e.asesor_id = ?
  LIMIT 1
";
$stmt_exp = mysqli_prepare($conexion, $q_exp);
mysqli_stmt_bind_param($stmt_exp, "ii", $id, $uid);
mysqli_stmt_execute($stmt_exp);
$exp = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_exp));
mysqli_stmt_close($stmt_exp);

if (!$exp) die("No tienes permisos para ver este expediente.");

// 2. OBTENER DOCUMENTOS
$q_docs = "SELECT d.id, d.archivo, d.nombre_original, d.fecha, u.rol AS subido_por_rol FROM documentos d LEFT JOIN usuarios u ON u.id = d.subido_por WHERE d.expediente_id = ? ORDER BY d.fecha DESC";
$stmt_docs = mysqli_prepare($conexion, $q_docs);
mysqli_stmt_bind_param($stmt_docs, "i", $id);
mysqli_stmt_execute($stmt_docs);
$docs = mysqli_fetch_all(mysqli_stmt_get_result($stmt_docs), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_docs);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestionar Expediente #<?php echo (int)$id; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
  <style>
    /* Diseño Base */
    body { background-color: #f9fafb; margin: 0; padding: 0; }
    
    /* Layout a dos columnas para el formulario CRM */
    .crm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px; }
    @media(max-width: 768px) { .crm-grid { grid-template-columns: 1fr; gap: 20px; } }
    
    .panel-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
    .panel-box-blue { background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; }
    
    .panel-header { font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
    .header-blue { color: #1e40af; border-bottom-color: #bfdbfe; }
    .header-gray { color: #374151; }

    .form-label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 14px; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    
    .btn-blue { background: #2563eb; color: white; padding: 12px 24px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; width: 100%; font-size: 15px; }
    .btn-blue:hover { background: #1d4ed8; }
    .btn-outline { border: 1px solid #d1d5db; background: white; color: #111827; padding: 6px 16px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 13px; display: inline-block; white-space: nowrap; }
    .btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #f87171; padding: 6px 16px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 13px; display: inline-block; white-space: nowrap; }
    
    .table th { text-transform: uppercase; font-size: 12px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; text-align: left;}
    .table td { padding: 15px 0; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }

    /* Tarjeta del Cliente */
    .client-profile-wrapper { display: flex; gap: 25px; align-items: center; margin-bottom: 30px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .client-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 900; color: #374151; flex-shrink: 0; }
    .client-data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; width: 100%; border-left: 1px solid #f3f4f6; padding-left: 25px; }
    .client-data-item p { margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: bold; }
    .client-data-item strong { display: block; font-size: 15px; color: #111827; margin-top: 4px; }

    /* Visualizador de documentos */
    .doc-viewer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9000; align-items:center; justify-content:center; }
    .doc-viewer-overlay.active { display:flex; }
    .doc-viewer-box { background:#fff; border-radius:10px; overflow:hidden; width:90vw; max-width:960px; height:88vh; display:flex; flex-direction:column; }
    .doc-viewer-header { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#f9fafb; border-bottom:1px solid #e5e7eb; flex-shrink:0; }
    .doc-viewer-header span { font-weight:700; font-size:14px; color:#111827; }
    .doc-viewer-header .viewer-actions { display:flex; gap:8px; align-items:center; }
    .doc-viewer-close { background:none; border:none; cursor:pointer; font-size:22px; color:#6b7280; line-height:1; padding:0 4px; }
    .doc-viewer-body { flex:1; overflow:hidden; }
    .doc-viewer-body iframe { width:100%; height:100%; border:none; }
    .doc-viewer-body .img-viewer { display:flex; align-items:center; justify-content:center; height:100%; background:#111; }
    .doc-viewer-body .img-viewer img { max-width:100%; max-height:100%; object-fit:contain; }
    .btn-dl { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; padding:6px 14px; border-radius:6px; font-weight:bold; text-decoration:none; font-size:13px; display:inline-block; white-space:nowrap; }
    .doc-actions { display:flex; justify-content:flex-end; align-items:center; gap:6px; flex-wrap:wrap; }
  </style>
</head>
<body class="app-shell">

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div><div class="name">Leonario Asesores</div><div class="tag">Expediente #<?php echo (int)$id; ?></div></div>
    </a>
    <nav class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="ver-expediente.php?id=<?php echo (int)$id; ?>">Gestionar expediente</a><a class="cta" href="../auth/logout.php">Cerrar sesión</a></nav>
  </div>
</header>

<main class="container" style="max-width: 1000px; margin: 30px auto 50px;">
  <div class="app-page-head">
    <div>
      <div class="kicker">Panel asesor</div>
      <h1>Gestionar expediente</h1>
      <p class="muted">Actualiza estado, notas internas, respuesta visible y documentos de resolucion.</p>
    </div>
  </div>
  <section class="card case-workspace" style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    
    <div class="case-summary">
      <div>
        <div class="kicker">Expediente asignado</div>
        <h2><?php echo htmlspecialchars($exp['titulo']); ?></h2>
      </div>
      <span class="status <?php echo $exp['estado']==='Finalizado' ? 'st-finalizado' : ($exp['estado']==='Pendiente' ? 'st-pendiente' : 'st-revision'); ?>"><?php echo htmlspecialchars($exp['estado']); ?></span>
    </div>
    <?php if (!empty(trim($exp['descripcion'] ?? ''))): ?>
      <p style="margin:0 0 22px; color:#4b5563; font-size:15px; background:#f9fafb; border-left:3px solid #2563eb; border-radius:4px; padding:10px 16px;"><?php echo nl2br(htmlspecialchars($exp['descripcion'])); ?></p>
    <?php else: ?>
      <div style="margin-bottom:22px;"></div>
    <?php endif; ?>

    <div class="client-profile-wrapper">
      <?php if (!empty($exp['cliente_avatar'])):
        $avatarFile = basename((string)$exp['cliente_avatar']);
        $avatarSrc = is_file(__DIR__ . '/../uploads/avatars/' . $avatarFile)
          ? '../uploads/avatars/' . rawurlencode($avatarFile)
          : '../uploads/' . rawurlencode($avatarFile);
      ?>
        <img src="<?php echo htmlspecialchars($avatarSrc); ?>" class="client-avatar" alt="Foto">
      <?php else: ?>
        <div class="client-avatar"><?php echo substr(htmlspecialchars($exp['cliente_nombre']), 0, 1); ?></div>
      <?php endif; ?>

      <div class="client-data-grid">
        <div class="client-data-item"><p>Cliente / Razón Social</p><strong><?php echo htmlspecialchars($exp['cliente_nombre']); ?></strong></div>
        <div class="client-data-item"><p>DNI / CIF</p><strong><?php echo htmlspecialchars($exp['cliente_dni'] ?: '-'); ?></strong></div>
        <div class="client-data-item"><p>Teléfono</p><strong><?php echo htmlspecialchars($exp['cliente_telefono'] ?: '-'); ?></strong></div>
        <div class="client-data-item"><p>Email</p><strong><?php echo htmlspecialchars($exp['cliente_email']); ?></strong></div>
      </div>
    </div>

    <form method="post" action="actualizar-expediente.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
      
      <div class="crm-grid">
        
        <div class="panel-box">
          <div class="panel-header header-gray">Control Interno (Privado)</div>
          
          <div style="margin-bottom: 15px;">
            <label class="form-label">Estado del Trámite</label>
            <select name="estado" class="form-control" required>
              <option value="Pendiente" <?php echo $exp['estado']==='Pendiente'?'selected':''; ?>>Pendiente</option>
              <option value="En Revisión" <?php echo $exp['estado']==='En Revisión'?'selected':''; ?>>En Revisión</option>
              <option value="Finalizado" <?php echo $exp['estado']==='Finalizado'?'selected':''; ?>>Finalizado</option>
            </select>
          </div>

          <div>
            <label class="form-label">Notas Privadas (No visibles para el cliente)</label>
            <textarea name="notas_privadas" class="form-control" rows="5" placeholder="Apunta aquí recordatorios, falta de documentos, etc..."><?php echo htmlspecialchars($exp['notas_privadas'] ?? ''); ?></textarea>
          </div>
        </div>

        <div class="panel-box-blue">
          <div class="panel-header header-blue">Comunicación con el Cliente</div>
          
          <div style="margin-bottom: 15px;">
            <label class="form-label" style="color: #1e40af;">Mensaje o Resolución Visible</label>
            <textarea name="respuesta_asesor" class="form-control" rows="3" style="border-color: #bfdbfe;" placeholder="Texto que leerá el cliente en su panel..."><?php echo htmlspecialchars($exp['respuesta_asesor'] ?? ''); ?></textarea>
          </div>

          <div>
            <label class="form-label" style="color: #1e40af;">Adjuntar Documento de Resolución</label>
            <input type="file" name="archivos_res[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" style="font-size: 13px; color: #4b5563; width: 100%; padding: 8px; background: white; border: 1px dashed #93c5fd; border-radius: 6px;">
            <p style="margin: 6px 0 0; font-size: 11px; color: #60a5fa;">Puedes adjuntar uno o varios archivos. Se marcarán como "RESPUESTA ASESOR" en el panel del cliente.</p>
          </div>
        </div>

      </div>

      <div style="text-align: right; margin-bottom: 40px;">
        <button class="btn-blue" type="submit" style="width: auto; padding: 12px 40px;">Guardar Cambios</button>
      </div>
    </form>

    <h3 style="margin:0 0 15px; font-weight:900; font-size: 16px; text-transform: uppercase; color: #111827; letter-spacing: 0.5px;">Historial de Documentos</h3>
    <table class="table" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr><th>ARCHIVO</th><th style="width: 150px;">ORIGEN</th><th style="text-align: right; width: 180px;">ACCIÓN</th></tr>
      </thead>
      <tbody>
        <?php if (!$docs): ?>
          <tr><td colspan="3" style="padding: 20px 0; color: #6b7280; text-align: center; font-size: 14px;">No hay documentos en este expediente.</td></tr>
        <?php else: ?>
          <?php foreach ($docs as $d):
            $es_asesor = ($d['subido_por_rol'] === 'asesor');
            $nombreVis = htmlspecialchars(!empty($d['nombre_original']) ? $d['nombre_original'] : $d['archivo']);
            $ext = strtolower(pathinfo($d['archivo'], PATHINFO_EXTENSION));
            $esImagen = in_array($ext, ['jpg','jpeg','png','gif','webp']);
            $esPDF   = ($ext === 'pdf');
            $puedeVer = $esImagen || $esPDF;
          ?>
            <tr>
              <td><strong style="color: #111827; font-size: 14px;"><?php echo $nombreVis; ?></strong><div style="font-size: 12px; color: #6b7280; margin-top: 4px;"><?php echo date('d/m/Y H:i', strtotime($d['fecha'])); ?></div></td>
              <td><?php if ($es_asesor): ?><span class="origin-badge origin-asesor">Asesor</span><?php else: ?><span class="origin-badge origin-cliente">Cliente</span><?php endif; ?></td>
              <td style="text-align: right;">
                <div class="doc-actions">
                  <?php if ($puedeVer): ?>
                    <a class="btn-outline" href="../files/download.php?id=<?php echo (int)$d['id']; ?>&inline=1" target="_blank">Ver</a>
                  <?php endif; ?>
                  <a class="btn-dl" href="../files/download.php?id=<?php echo (int)$d['id']; ?>">Descargar</a>
                  <?php if ($es_asesor): ?><a class="btn-danger" href="borrar-documento.php?id=<?php echo $d['id']; ?>&exp_id=<?php echo $id; ?>">Borrar</a><?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</main>

</body>
</html>
