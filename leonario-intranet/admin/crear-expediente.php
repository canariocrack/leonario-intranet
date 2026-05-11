<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Control de acceso para administradores
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];
$error = '';
$ok = '';

$tituloForm = trim($_POST['titulo'] ?? '');
$clienteIdForm = (int)($_POST['cliente_id'] ?? 0);
$asesorIdForm = (int)($_POST['asesor_id'] ?? 0);
$estadoForm = $_POST['estado'] ?? 'Pendiente';

// ==========================================
// Cargar listas para los <select> (Sin objetos)
// ==========================================

// 1. Obtener lista de clientes
$cq = mysqli_query($conexion, "SELECT id, nombre, email FROM usuarios WHERE rol='cliente' ORDER BY nombre ASC");
$clientes = $cq ? mysqli_fetch_all($cq, MYSQLI_ASSOC) : [];

// 2. Obtener lista de asesores
$aq = mysqli_query($conexion, "SELECT id, nombre, email FROM usuarios WHERE rol='asesor' ORDER BY nombre ASC");
$asesores = $aq ? mysqli_fetch_all($aq, MYSQLI_ASSOC) : [];


// ==========================================
// Procesar el formulario
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = $tituloForm;
  $cliente_id = $clienteIdForm;
  $asesor_id = $asesorIdForm;
  $estado = $estadoForm;

  $allowed = ['Pendiente','En Revisión','Finalizado'];

  if ($titulo === '' || $cliente_id <= 0) {
    $error = "Título y cliente son obligatorios.";
  } elseif (!in_array($estado, $allowed, true)) {
    $error = "Estado no válido.";
  } else {
    // 3. Comprobar que el cliente existe realmente en la BD
    $q_chk_cliente = "SELECT id FROM usuarios WHERE id=? AND rol='cliente' LIMIT 1";
    $ch = mysqli_prepare($conexion, $q_chk_cliente);
    mysqli_stmt_bind_param($ch, "i", $cliente_id);
    mysqli_stmt_execute($ch);
    $res_ch = mysqli_stmt_get_result($ch);
    $cliente_existe = mysqli_fetch_assoc($res_ch);
    mysqli_stmt_close($ch);

    if (!$cliente_existe) {
      $error = "Cliente no válido.";
    } else {
      
      // 4. Comprobar asesor opcional
      if ($asesor_id > 0) {
        $q_chk_asesor = "SELECT id FROM usuarios WHERE id=? AND rol='asesor' LIMIT 1";
        $ah = mysqli_prepare($conexion, $q_chk_asesor);
        mysqli_stmt_bind_param($ah, "i", $asesor_id);
        mysqli_stmt_execute($ah);
        $res_ah = mysqli_stmt_get_result($ah);
        $asesor_existe = mysqli_fetch_assoc($res_ah);
        mysqli_stmt_close($ah);
        
        if (!$asesor_existe) {
          $error = "Asesor no válido.";
        }
      }

      // 5. Si no hay errores, insertamos el expediente
      if (!$error) {
        $q_ins = "INSERT INTO expedientes (titulo, estado, asesor_id, cliente_id) VALUES (?,?,?,?)";
        $ins = mysqli_prepare($conexion, $q_ins);
        
        // El asesor_id puede ser NULL si viene como 0, necesitamos manejar eso
        $asesor_final = ($asesor_id > 0) ? $asesor_id : null;
        
        if ($ins) {
            // "ssii" -> String (titulo), String (estado), Integer (asesor_final), Integer (cliente_id)
            mysqli_stmt_bind_param($ins, "ssii", $titulo, $estado, $asesor_final, $cliente_id);
            mysqli_stmt_execute($ins);
            
            // Obtenemos el ID del expediente recién creado usando la función de mysqli
            $eid = (int)mysqli_insert_id($conexion);
            mysqli_stmt_close($ins);

            // 6. Guardamos el registro (Log) de la acción
            $q_log = "INSERT INTO logs (usuario_id, accion) VALUES (?, ?)";
            $log = mysqli_prepare($conexion, $q_log);
            
            if ($log) {
                $texto_log = "Admin creó expediente #$eid para cliente $cliente_id (asesor=".($asesor_final ? $asesor_final : 'NULL').")";
                mysqli_stmt_bind_param($log, "is", $uid, $texto_log);
                mysqli_stmt_execute($log);
                mysqli_stmt_close($log);
            }

            $_SESSION['flash_ok'] = "Expediente creado correctamente (#$eid).";
            header('Location: dashboard.php?tab=expedientes');
            exit;
        } else {
            $error = "Error al intentar crear el expediente en la base de datos.";
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
  <title>Crear expediente | Admin</title>
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
        <div class="tag">Crear expediente</div>
      </div>
    </a>

    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a class="active" href="crear-expediente.php">Crear expediente</a>
      <a href="asignar-expediente.php">Asignar asesor</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">
    <div class="app-page-head">
      <div>
        <div class="kicker">Administracion</div>
        <h1>Crear expediente</h1>
        <p class="muted">Alta manual de expedientes con cliente, estado inicial y asesor opcional.</p>
      </div>
    </div>

    <section class="card">
      <h1 style="margin:0 0 10px;font-weight:980;">Crear expediente</h1>
      <p class="muted">Genera expedientes y (si quieres) asigna asesor desde el inicio.</p>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post" class="formCard" style="box-shadow:none;">
        <div class="field">
          <label>Título</label>
          <input type="text" name="titulo" required placeholder="Ej: Alta autónomo, IVA trimestral, nóminas..." value="<?php echo htmlspecialchars($tituloForm); ?>">
        </div>

        <div class="field">
          <label>Cliente</label>
          <select name="cliente_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>" <?php echo $clienteIdForm === (int)$c['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['email']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Asesor (opcional)</label>
          <select name="asesor_id">
            <option value="0">Sin asignar</option>
            <?php foreach ($asesores as $a): ?>
              <option value="<?php echo (int)$a['id']; ?>" <?php echo $asesorIdForm === (int)$a['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($a['nombre']); ?> (<?php echo htmlspecialchars($a['email']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Estado</label>
          <select name="estado">
            <option value="Pendiente" <?php echo $estadoForm === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
            <option value="En Revisión" <?php echo $estadoForm === 'En Revisión' ? 'selected' : ''; ?>>En Revisión</option>
            <option value="Finalizado" <?php echo $estadoForm === 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
          </select>
        </div>

        <button class="btn primary" type="submit">Crear expediente</button>
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
