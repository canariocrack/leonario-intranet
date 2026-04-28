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

// Lista de expedientes del cliente (para elegir)
$exp = $pdo->prepare("SELECT id, titulo FROM expedientes WHERE cliente_id = ? ORDER BY created_at DESC");
$exp->execute([$uid]);
$expedientes = $exp->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $expediente_id = (int)($_POST['expediente_id'] ?? 0);

  // validar que ese expediente pertenece al cliente
  $check = $pdo->prepare("SELECT id FROM expedientes WHERE id = ? AND cliente_id = ? LIMIT 1");
  $check->execute([$expediente_id, $uid]);
  if (!$check->fetch()) {
    $error = "Expediente no válido.";
  } else {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
      $error = "Debes seleccionar un archivo válido.";
    } else {
      $file = $_FILES['archivo'];

      $allowed = ['pdf','jpg','jpeg','png'];
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

      if (!in_array($ext, $allowed)) {
        $error = "Formato no permitido. Usa PDF/JPG/PNG.";
      } elseif ($file['size'] > 8 * 1024 * 1024) {
        $error = "El archivo es demasiado grande (máx 8MB).";
      } else {
        $dir = __DIR__ . '/../uploads/docs/';
        if (!is_dir($dir)) {
          mkdir($dir, 0777, true);
        }

        $safeName = 'doc_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
          $error = "No se pudo subir el archivo.";
        } else {
          $ins = $pdo->prepare("INSERT INTO documentos (expediente_id, archivo, subido_por) VALUES (?,?,?)");
          $ins->execute([$expediente_id, $safeName, $uid]);

          $log = $pdo->prepare("INSERT INTO logs (usuario_id, accion) VALUES (?, ?)");
          $log->execute([$uid, "Cliente subió documento $safeName al expediente $expediente_id"]);

          $ok = "Documento subido correctamente.";
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
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

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
      <a href="dashboard.php">Volver</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="card">
      <h1 class="section-title">Subir documentación</h1>
      <p class="muted">Hola, <?php echo htmlspecialchars($nombre); ?>. Selecciona el expediente y sube un PDF o imagen.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="formCard flat">
        <div class="field">
          <label>Expediente</label>
          <select name="expediente_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($expedientes as $e): ?>
              <option value="<?php echo (int)$e['id']; ?>">
                #<?php echo (int)$e['id']; ?> — <?php echo htmlspecialchars($e['titulo']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Archivo</label>
          <input type="file" name="archivo" required>
          <small class="muted">Permitidos: PDF, JPG, PNG. Máx 8MB.</small>
        </div>

        <button class="btn primary" type="submit">Subir</button>
        <a class="btn" href="dashboard.php?tab=docs">Ver mis documentos</a>
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
