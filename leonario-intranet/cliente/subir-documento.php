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
$expedienteSeleccionado = (int)($_POST['expediente_id'] ?? ($_GET['exp'] ?? 0));

// Lista de expedientes del cliente (para elegir)
$expedientes = [];
$stmtExp = mysqli_prepare($conexion, "SELECT id, titulo, estado FROM expedientes WHERE cliente_id = ? ORDER BY created_at DESC");
if ($stmtExp) {
  mysqli_stmt_bind_param($stmtExp, "i", $uid);
  mysqli_stmt_execute($stmtExp);
  $resExp = mysqli_stmt_get_result($stmtExp);
  $expedientes = mysqli_fetch_all($resExp, MYSQLI_ASSOC);
  mysqli_stmt_close($stmtExp);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $expediente_id = $expedienteSeleccionado;
  $expedienteValido = false;
  $expedienteFinalizado = false;

  // validar que ese expediente pertenece al cliente
  $check = mysqli_prepare($conexion, "SELECT id, estado FROM expedientes WHERE id = ? AND cliente_id = ? LIMIT 1");
  if (!$check) {
    $error = "No se pudo validar el expediente seleccionado.";
  } else {
    mysqli_stmt_bind_param($check, "ii", $expediente_id, $uid);
    mysqli_stmt_execute($check);
    $resCheck = mysqli_stmt_get_result($check);
    $expedienteCheck = mysqli_fetch_assoc($resCheck);
    $expedienteValido = (bool)$expedienteCheck;
    $expedienteFinalizado = (($expedienteCheck['estado'] ?? '') === 'Finalizado');
    mysqli_stmt_close($check);
  }

  if ($error === '' && !$expedienteValido) {
    $error = "Expediente no válido.";
  } elseif ($error === '' && $expedienteFinalizado) {
    $error = "Este expediente está finalizado y ya no admite nueva documentación.";
  } elseif ($error === '') {
    if (
      !isset($_FILES['archivos']) ||
      !isset($_FILES['archivos']['name']) ||
      !is_array($_FILES['archivos']['name'])
    ) {
      $error = "Debes seleccionar al menos un archivo válido.";
    } else {
      $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
      $dir = __DIR__ . '/../uploads/docs/';

      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }

      $stmtIns = mysqli_prepare($conexion, "INSERT INTO documentos (expediente_id, archivo, nombre_original, subido_por) VALUES (?, ?, ?, ?)");
      $stmtLog = mysqli_prepare($conexion, "INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");

      if (!$stmtIns || !$stmtLog) {
        if ($stmtIns) {
          mysqli_stmt_close($stmtIns);
        }
        if ($stmtLog) {
          mysqli_stmt_close($stmtLog);
        }

        $error = "No se pudo guardar la subida en la base de datos.";
      } else {
        $subidos = [];
        $errores = [];
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
            $errores[] = $nombreOriginal . ': no se pudo subir correctamente.';
            continue;
          }

          $ext = strtolower(pathinfo($nombreOriginalLimpio, PATHINFO_EXTENSION));
          if (!in_array($ext, $allowed, true)) {
            $errores[] = $nombreOriginal . ': formato no permitido.';
            continue;
          }

          if ($size > 8 * 1024 * 1024) {
            $errores[] = $nombreOriginal . ': supera el límite de 8MB.';
            continue;
          }

          $safeName = 'doc_' . $uid . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $dest = $dir . $safeName;

          if (!move_uploaded_file($tmpName, $dest)) {
            $errores[] = $nombreOriginal . ': no se pudo guardar en el servidor.';
            continue;
          }

          mysqli_stmt_bind_param($stmtIns, "issi", $expediente_id, $safeName, $nombreOriginalLimpio, $uid);
          if (!mysqli_stmt_execute($stmtIns)) {
            @unlink($dest);
            $errores[] = $nombreOriginal . ': no se pudo registrar en la base de datos.';
            continue;
          }

          $accion = "Cliente subió documento $safeName al expediente $expediente_id";
          mysqli_stmt_bind_param($stmtLog, "is", $uid, $accion);
          mysqli_stmt_execute($stmtLog);

          $subidos[] = $nombreOriginal;
        }

        mysqli_stmt_close($stmtIns);
        mysqli_stmt_close($stmtLog);

        if (!$subidos && !$errores) {
          $error = "Debes seleccionar al menos un archivo válido.";
        } else {
          if ($subidos) {
            $ok = count($subidos) === 1
              ? "Se subió 1 documento correctamente."
              : "Se subieron " . count($subidos) . " documentos correctamente.";
          }

          if ($errores) {
            $error = "Algunos archivos no pudieron subirse: " . implode(' ', $errores);
          }

          if ($ok !== '') {
            $_SESSION['flash_ok'] = $ok;
            if ($error !== '') {
              $_SESSION['flash_err'] = $error;
            }
            header('Location: dashboard.php?tab=docs');
            exit;
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Subir documento | Cliente</title>
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
        <div class="tag">Subir documentación</div>
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
    <section class="card upload-workbench">
      <div class="upload-intro">
        <div>
          <div class="kicker">Area cliente</div>
          <h1>Subir documentación</h1>
          <p class="muted">Adjunta archivos a un expediente abierto para mantener el tramite actualizado.</p>
          <p class="muted upload-copy">Hola, <?php echo htmlspecialchars($nombre); ?>. Los expedientes finalizados quedan cerrados para nueva documentación.</p>
          <div class="actions upload-actions">
            <button class="btn primary" type="submit" form="uploadDocsForm">Subir archivos</button>
            <a class="btn" href="dashboard.php?tab=docs">Ver mis documentos</a>
          </div>
        </div>
        <div class="upload-hint">
          <strong>PDF · JPG · PNG</strong>
          <span>Maximo 8MB por archivo</span>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form id="uploadDocsForm" method="post" enctype="multipart/form-data" class="formCard upload-form" style="box-shadow:none;">
        <div class="field">
          <label>Expediente</label>
          <select name="expediente_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($expedientes as $e): ?>
              <?php $finalizado = (($e['estado'] ?? '') === 'Finalizado'); ?>
              <option value="<?php echo (int)$e['id']; ?>" <?php echo $expedienteSeleccionado === (int)$e['id'] ? 'selected' : ''; ?> <?php echo $finalizado ? 'disabled' : ''; ?>>
                #<?php echo (int)$e['id']; ?> — <?php echo htmlspecialchars($e['titulo']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="muted">Solo puedes subir documentación a expedientes que no estén finalizados.</small>
        </div>

        <div class="field">
          <label>Archivos</label>
          <input type="file" name="archivos[]" accept=".pdf,.jpg,.jpeg,.png" multiple required>
          <small class="muted">Puedes seleccionar varios archivos a la vez. Permitidos: PDF, JPG, PNG. Máx 8MB por archivo.</small>
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
