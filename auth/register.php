<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $pass2 = $_POST['password2'] ?? '';

  if ($nombre === '' || $email === '' || $pass === '' || $pass2 === '') {
    $error = "Rellena todos los campos.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email no válido.";
  } elseif (strlen($pass) < 6) {
    $error = "La contraseña debe tener mínimo 6 caracteres.";
  } elseif ($pass !== $pass2) {
    $error = "Las contraseñas no coinciden.";
  } else {
    $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    if ($st->fetch()) {
      $error = "Ese email ya está registrado.";
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $ins = $pdo->prepare("INSERT INTO usuarios (nombre,email,password,rol) VALUES (?,?,?,'cliente')");
      $ins->execute([$nombre,$email,$hash]);
      $ok = "Cuenta creada correctamente. Ya puedes iniciar sesión.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro | Leonario Asesores</title>
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
        <div class="tag">Registro</div>
      </div>
    </a>

    <nav class="nav">
      <a href="../index.php">Inicio</a>
      <a href="../quienes-somos.php">Quiénes somos</a>
      <a href="../servicios.php">Servicios</a>
      <a href="../contacto.php">Contacto</a>
      <a class="cta" href="login.php">Iniciar sesión</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero hero--office">
      <div class="content">
        <div>
          <div class="kicker">Alta de usuario</div>
          <h1>Crear cuenta</h1>
          <p>Regístrese para acceder a la intranet (rol cliente por defecto).</p>
        </div>
      </div>
    </section>

    <div class="spacer-14"></div>

    <div class="formCard">
      <h2 class="section-title sm">Registro</h2>

      <?php if ($error): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="field">
          <label>Nombre</label>
          <input type="text" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
        </div>

        <div class="field">
          <label>Email</label>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="field">
          <label>Contraseña</label>
          <input type="password" name="password" required>
          <small class="muted">Mínimo 6 caracteres.</small>
        </div>

        <div class="field">
          <label>Repetir contraseña</label>
          <input type="password" name="password2" required>
        </div>

        <button class="btn primary" type="submit">Crear cuenta</button>

        <p class="muted mt10">
          ¿Ya tienes cuenta? <a href="login.php" class="inline-link">Iniciar sesión</a>
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
