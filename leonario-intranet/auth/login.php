<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';
$ok = '';

if (isset($_SESSION['user_id'])) {
  $rol = $_SESSION['rol'] ?? 'cliente';
  if ($rol === 'admin') header('Location: ../admin/dashboard.php');
  elseif ($rol === 'asesor') header('Location: ../asesor/dashboard.php');
  else header('Location: ../cliente/dashboard.php');
  exit;
}

if (isset($_SESSION['flash_ok'])) {
  $ok = (string)$_SESSION['flash_ok'];
  unset($_SESSION['flash_ok']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Rellena email y contraseña.';
  } else {
    
    // --- INICIO DEL CÓDIGO PROCEDIMENTAL (SIN OBJETOS) ---
    
    // 1. Escribimos la consulta SQL con el hueco de seguridad (?)
    $query = "SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? LIMIT 1";
    
    // 2. Preparamos la consulta usando la variable $conexion (que viene de database.php)
    $stmt = mysqli_prepare($conexion, $query);

    // Verificamos si la preparación fue exitosa
    if ($stmt) {
        // 3. Vinculamos el dato. La "s" le dice al sistema que $email es un String (texto).
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        // 4. Ejecutamos la acción en la base de datos
        mysqli_stmt_execute($stmt);
        
        // 5. Obtenemos el resultado de la búsqueda
        $resultado = mysqli_stmt_get_result($stmt);
        
        // 6. Extraemos la información del usuario y la guardamos en el array $u
        $u = mysqli_fetch_assoc($resultado);
        
        // 7. Cerramos la consulta para no saturar la memoria del servidor
        mysqli_stmt_close($stmt);

        // --- FIN DEL CÓDIGO PROCEDIMENTAL ---

        // El resto de la validación de contraseñas sigue siendo exactamente igual
        if (!$u || !password_verify($password, $u['password'])) {
          $error = 'Credenciales incorrectas.';
        } else {
          session_regenerate_id(true);
          $_SESSION['user_id'] = (int)$u['id'];
          $_SESSION['nombre']  = $u['nombre'];
          $_SESSION['email']   = $u['email'];
          $_SESSION['rol']     = $u['rol'];

          if ($u['rol'] === 'admin') header('Location: ../admin/dashboard.php');
          elseif ($u['rol'] === 'asesor') header('Location: ../asesor/dashboard.php');
          else header('Location: ../cliente/dashboard.php');
          exit;
        }
    } else {
        $error = 'Error de conexión con la base de datos.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="../index.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Acceso a la intranet</div>
      </div>
    </a>

    <nav class="nav">
      <a href="../index.php">Inicio</a>
      <a href="../quienes-somos.php">Quiénes somos</a>
      <a href="../servicios.php">Servicios</a>
      <a href="../contacto.php">Contacto</a>
      <a class="cta active" href="login.php">Acceso</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero" style="background:
      linear-gradient(135deg, rgba(7,26,43,.90), rgba(11,42,74,.62)),
      url('../assets/img/oficina.jpg');
      background-size:cover;background-position:center;">
      <div class="content">
        <div>
          <div class="kicker">Área privada</div>
          <h1>Acceso a la intranet</h1>
          <p>Introduzca sus credenciales para consultar expedientes y gestionar documentación.</p>
        </div>
      </div>
    </section>

    <div style="height:14px"></div>

    <div class="formCard">
      <h2 style="margin:0 0 10px;font-weight:980;">Iniciar sesión</h2>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="field">
          <label>Contraseña</label>
          <input type="password" name="password" required>
        </div>

        <button class="btn primary" type="submit">Entrar</button>

        <p class="muted" style="margin-top:10px;">
          ¿No tienes cuenta? <a href="register.php" style="color:var(--brand);font-weight:900;">Crear cuenta</a>
        </p>
      </form>
    </div>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores
  </div>
</footer>

</body>
</html>