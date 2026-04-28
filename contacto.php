<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Contacto | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="inner container">
    <a class="brand" href="index.php">
      <img src="assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Fiscal · Laboral · Contable</div>
      </div>
    </a>

    <nav class="nav">
      <a href="index.php">Inicio</a>
      <a href="quienes-somos.php">Quiénes somos</a>
      <a href="servicios.php">Servicios</a>
      <a class="active" href="contacto.php">Contacto</a>
      <a class="cta" href="auth/login.php">Acceso</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero hero--office">
      <div class="content">
        <div>
          <div class="kicker">Atención profesional</div>
          <h1>Contacto</h1>
          <p>Puede contactar con nosotros para solicitar una cita o resolver cualquier duda.</p>
          <div class="actions">
            <a class="btn primary" href="auth/login.php">Acceso intranet</a>
            <a class="btn soft" href="servicios.php">Ver servicios</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="grid grid3">
        <div class="card">
          <h3>Teléfono</h3>
          <p class="muted">+34 600 000 000</p>
        </div>
        <div class="card">
          <h3>Email</h3>
          <p class="muted">contacto@leonarioasesores.com</p>
        </div>
        <div class="card">
          <h3>Horario</h3>
          <p class="muted">L–V: 09:00 a 14:00 · 16:00 a 19:00</p>
        </div>
      </div>
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
