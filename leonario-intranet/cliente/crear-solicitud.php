<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$nombre = $_SESSION['nombre'] ?? 'Cliente';

$error = '';
$ok = '';
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($titulo === '') {
    $error = "Indica un título o motivo de la solicitud.";
  } else {
    // 1. CREAR EL EXPEDIENTE
    $q_ins = "INSERT INTO expedientes (titulo, descripcion, estado, asesor_id, cliente_id) VALUES (?, ?, 'Pendiente', NULL, ?)";
    $stmt_ins = mysqli_prepare($conexion, $q_ins);
    
    if ($stmt_ins) {
        mysqli_stmt_bind_param($stmt_ins, "ssi", $titulo, $descripcion, $uid);
        mysqli_stmt_execute($stmt_ins);
        
        $eid = (int)mysqli_insert_id($conexion); 
        mysqli_stmt_close($stmt_ins);

        // 2. CREAR EL LOG
        $q_log = "INSERT INTO logs (usuario_id, accion) VALUES (?, ?)";
        $stmt_log = mysqli_prepare($conexion, $q_log);
        if ($stmt_log) {
            $accion = "Cliente creó solicitud/expediente #$eid: $titulo";
            mysqli_stmt_bind_param($stmt_log, "is", $uid, $accion);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);
        }

        // 3. PROCESAR LOS ARCHIVOS SUBIDOS
        if (
          isset($_FILES['archivos']) &&
          isset($_FILES['archivos']['name']) &&
          is_array($_FILES['archivos']['name'])
        ) {
            $dir_subida = __DIR__ . '/../files/';
          $formatosPermitidos = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
          $archivosSubidos = 0;
          $erroresArchivos = [];
            
            if (!file_exists($dir_subida)) {
                mkdir($dir_subida, 0777, true);
            }

            $q_doc = "INSERT INTO documentos (expediente_id, archivo, nombre_original, subido_por) VALUES (?, ?, ?, ?)";
          $stmt_doc = mysqli_prepare($conexion, $q_doc);

          if ($stmt_doc) {
            $totalArchivos = count($_FILES['archivos']['name']);

            for ($i = 0; $i < $totalArchivos; $i++) {
                    $nombreOriginal = trim((string)($_FILES['archivos']['name'][$i] ?? ''));
                    $nombreOriginalLimpio = basename($nombreOriginal);
              $tmpName = $_FILES['archivos']['tmp_name'][$i] ?? '';
              $fileError = (int)($_FILES['archivos']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
              $size = (int)($_FILES['archivos']['size'][$i] ?? 0);

              if ($nombreOriginal === '' || $fileError === UPLOAD_ERR_NO_FILE) {
                continue;
              }

              if ($fileError !== UPLOAD_ERR_OK) {
                $erroresArchivos[] = $nombreOriginal . ': no se pudo subir correctamente.';
                continue;
              }

              $ext = strtolower(pathinfo($nombreOriginalLimpio, PATHINFO_EXTENSION));
              if (!in_array($ext, $formatosPermitidos, true)) {
                $erroresArchivos[] = $nombreOriginal . ': formato no permitido.';
                continue;
              }

              if ($size > 8 * 1024 * 1024) {
                $erroresArchivos[] = $nombreOriginal . ': supera el límite de 8MB.';
                continue;
              }

                    $nombre_archivo = time() . '_' . $i . '_' . $nombreOriginalLimpio;
              $ruta_final = $dir_subida . $nombre_archivo;

              if (!move_uploaded_file($tmpName, $ruta_final)) {
                $erroresArchivos[] = $nombreOriginal . ': no se pudo guardar en el servidor.';
                continue;
              }

                    mysqli_stmt_bind_param($stmt_doc, "issi", $eid, $nombre_archivo, $nombreOriginalLimpio, $uid);
              if (!mysqli_stmt_execute($stmt_doc)) {
                @unlink($ruta_final);
                $erroresArchivos[] = $nombreOriginal . ': no se pudo registrar en la base de datos.';
                continue;
              }

              $archivosSubidos++;
                }

            mysqli_stmt_close($stmt_doc);
          } else {
            $erroresArchivos[] = 'No se pudieron registrar los documentos adjuntos.';
            }

          if ($archivosSubidos > 0) {
            $ok = $archivosSubidos === 1
              ? "Solicitud enviada correctamente con 1 archivo adjunto."
              : "Solicitud enviada correctamente con " . $archivosSubidos . " archivos adjuntos.";
          }

          if ($erroresArchivos) {
            $error = "La solicitud se creó, pero algunos archivos fallaron: " . implode(' ', $erroresArchivos);
          }
        }

        if ($ok === '') {
          $ok = "Solicitud enviada correctamente. Se ha generado un expediente pendiente.";
        }

        if ($ok !== '') {
          $_SESSION['flash_ok'] = $ok;
          if ($error !== '') {
            $_SESSION['flash_err'] = $error;
          }
          header('Location: dashboard.php?tab=expedientes');
          exit;
        }
    } else {
        $error = "Error al intentar guardar la solicitud en la base de datos.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Enviar solicitud | Cliente</title>
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
        <div class="tag">Enviar solicitud</div>
      </div>
    </a>

    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="perfil.php">Mi perfil</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">
    <section class="card request-workbench">
      <div class="request-intro">
        <div>
          <div class="kicker">Area cliente</div>
          <h1>Nueva solicitud</h1>
          <p class="muted">Describe el tramite y adjunta documentos iniciales si los tienes preparados.</p>
          <p class="muted request-copy">Hola, <?php echo htmlspecialchars($nombre); ?>. Cuanto mas claro sea el motivo, mas rapido podra clasificarlo el equipo.</p>
          <div class="actions request-actions">
            <button class="btn primary" type="submit" form="requestForm">Enviar solicitud</button>
            <a class="btn" href="dashboard.php">Cancelar</a>
          </div>
        </div>
        <div class="request-hint">
          <strong>Solicitud guiada</strong>
          <span>Motivo, descripcion y adjuntos iniciales</span>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form id="requestForm" method="post" class="formCard request-form" style="box-shadow:none;" enctype="multipart/form-data">
        
        <div class="field">
          <label>Motivo / Título</label>
          <input type="text" name="titulo" placeholder="Ej: Revisión IRPF, alta autónomo, consulta laboral..." required value="<?php echo htmlspecialchars($titulo); ?>">
        </div>

        <div class="field">
          <label>Descripción de la solicitud <span class="muted" style="font-weight:400;">(Opcional)</span></label>
          <textarea name="descripcion" rows="4" placeholder="Explica con más detalle en qué consiste tu solicitud, fechas relevantes, documentación que adjuntas..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-family:inherit;font-size:14px;resize:vertical;box-sizing:border-box;"><?php echo htmlspecialchars($descripcion); ?></textarea>
        </div>

        <div class="field">
          <label>Adjuntar archivos (Opcional)</label>
          <input type="file" name="archivos[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
          <p class="muted" style="font-size: 12px; margin-top: 5px;">Puedes seleccionar varios archivos. Formatos permitidos: PDF, Word, JPG, PNG. Máx 8MB por archivo.</p>
        </div>

      </form>
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
