<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if (isset($_SESSION['user_id'])) {
  $rol = $_SESSION['rol'] ?? 'cliente';
  if ($rol === 'admin') header('Location: ../admin/dashboard.php');
  elseif ($rol === 'asesor') header('Location: ../asesor/dashboard.php');
  else header('Location: ../cliente/dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Rellena email y contraseña.';
  } else {
    $st = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $u = $st->fetch();

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

    <section class="hero hero--office">
      <div class="content">
        <div>
          <div class="kicker">Área privada</div>
          <h1>Acceso a la intranet</h1>
          <p>Introduzca sus credenciales para consultar expedientes y gestionar documentación.</p>
        </div>
      </div>
    </section>

    <div class="spacer-14"></div>

    <div class="formCard">
      <h2 class="section-title sm">Iniciar sesión</h2>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
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

        <p class="muted mt10">
          ¿No tienes cuenta? <a href="register.php" class="inline-link">Crear cuenta</a>
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
