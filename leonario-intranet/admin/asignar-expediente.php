<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$error = '';
$ok = '';

// 1. OBTENER EXPEDIENTES (Procedimental)
$q_exp = "
  SELECT e.id, e.titulo, e.estado, e.asesor_id,
         c.nombre AS cliente_nombre,
         a.nombre AS asesor_nombre
  FROM expedientes e
  INNER JOIN usuarios c ON c.id = e.cliente_id
  LEFT JOIN usuarios a ON a.id = e.asesor_id
  ORDER BY e.created_at DESC
";
$res_exp = mysqli_query($conexion, $q_exp);
$expedientes = $res_exp ? mysqli_fetch_all($res_exp, MYSQLI_ASSOC) : [];

// 2. OBTENER ASESORES (Procedimental)
$q_ase = "SELECT id, nombre, email FROM usuarios WHERE rol='asesor' ORDER BY nombre ASC";
$res_ase = mysqli_query($conexion, $q_ase);
$asesores = $res_ase ? mysqli_fetch_all($res_ase, MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $expediente_id = (int)($_POST['expediente_id'] ?? 0);
  $asesor_id = (int)($_POST['asesor_id'] ?? 0);

  if ($expediente_id <= 0 || $asesor_id <= 0) {
    $error = "Selecciona expediente y asesor.";
  } else {
    // 3. VALIDAR EXPEDIENTE EXISTE
    $q_chk_e = "SELECT id FROM expedientes WHERE id = ? LIMIT 1";
    $stmt_e = mysqli_prepare($conexion, $q_chk_e);
    mysqli_stmt_bind_param($stmt_e, "i", $expediente_id);
    mysqli_stmt_execute($stmt_e);
    $exists_e = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_e));
    mysqli_stmt_close($stmt_e);

    if (!$exists_e) {
      $error = "Expediente no válido.";
    } else {
      // 4. VALIDAR ASESOR EXISTE
      $q_chk_a = "SELECT id FROM usuarios WHERE id=? AND rol='asesor' LIMIT 1";
      $stmt_a = mysqli_prepare($conexion, $q_chk_a);
      mysqli_stmt_bind_param($stmt_a, "i", $asesor_id);
      mysqli_stmt_execute($stmt_a);
      $exists_a = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_a));
      mysqli_stmt_close($stmt_a);

      if (!$exists_a) {
        $error = "Asesor no válido.";
      } else {
        // 5. ACTUALIZAR ASESOR
        $q_up = "UPDATE expedientes SET asesor_id = ? WHERE id = ?";
        $stmt_up = mysqli_prepare($conexion, $q_up);
        mysqli_stmt_bind_param($stmt_up, "ii", $asesor_id, $expediente_id);
        
        if (mysqli_stmt_execute($stmt_up)) {
            // 6. LOG
            $q_log = "INSERT INTO logs (usuario_id, accion) VALUES (?, ?)";
            $stmt_log = mysqli_prepare($conexion, $q_log);
            $msg_log = "Admin asignó asesor $asesor_id al expediente #$expediente_id";
            mysqli_stmt_bind_param($stmt_log, "is", $uid, $msg_log);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);

            mysqli_stmt_close($stmt_up);
            $_SESSION['flash_ok'] = "Asesor asignado correctamente.";
            header("Location: dashboard.php?tab=expedientes");
            exit;
        } else {
            $error = "Error al actualizar el expediente.";
        }
      }
    }
  }
}

if (isset($_SESSION['flash_ok'])) {
  $ok = (string)$_SESSION['flash_ok'];
  unset($_SESSION['flash_ok']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asignar expediente | Admin</title>
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
        <div class="tag">Asignar expediente</div>
      </div>
    </a>

    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="crear-expediente.php">Crear expediente</a>
      <a class="active" href="asignar-expediente.php">Asignar asesor</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">
    <div class="app-page-head">
      <div>
        <div class="kicker">Administracion</div>
        <h1>Asignar expediente</h1>
        <p class="muted">Relaciona expedientes existentes con asesores para ordenar el flujo de trabajo.</p>
      </div>
    </div>

    <section class="card">
      <h1 style="margin:0 0 10px;font-weight:980;">Asignar expediente</h1>
      <p class="muted">Asigna un asesor a un expediente existente.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" class="formCard" style="box-shadow:none;">
        <div class="field">
          <label>Expediente</label>
          <select name="expediente_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($expedientes as $e): ?>
              <option value="<?php echo (int)$e['id']; ?>">
                #<?php echo (int)$e['id']; ?> — <?php echo htmlspecialchars($e['titulo']); ?>
                · Cliente: <?php echo htmlspecialchars($e['cliente_nombre']); ?>
                · Asesor: <?php echo htmlspecialchars($e['asesor_nombre'] ?? 'Sin asignar'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Asesor</label>
          <select name="asesor_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($asesores as $a): ?>
              <option value="<?php echo (int)$a['id']; ?>">
                <?php echo htmlspecialchars($a['nombre']); ?> (<?php echo htmlspecialchars($a['email']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button class="btn primary" type="submit">Asignar</button>
        <a class="btn" href="dashboard.php">Volver</a>
      </form>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container muted">© <?php echo date('Y'); ?> Leonario Asesores</div>
</footer>

</body>
</html>
