<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Servicios | Leonario Asesores</title>
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
      <a class="active" href="servicios.php">Servicios</a>
      <a href="contacto.php">Contacto</a>
      <a class="cta" href="auth/login.php">Acceso</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero hero--office">
      <div class="content">
        <div>
          <div class="kicker">Soluciones profesionales</div>
          <h1>Servicios</h1>
          <p>Soluciones fiscales, laborales y contables con seguimiento profesional y comunicación segura.</p>
          <div class="actions">
            <a class="btn primary" href="contacto.php">Solicitar información</a>
            <a class="btn soft" href="auth/login.php">Acceso intranet</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="grid grid3">
        <div class="card">
          <h3>Fiscal</h3>
          <p class="muted">
            Declaraciones, IVA/IRPF/IS, planificación fiscal y apoyo ante requerimientos.
          </p>
        </div>
        <div class="card">
          <h3>Laboral</h3>
          <p class="muted">
            Altas/bajas, contratos, nóminas, asesoramiento laboral y gestión integral.
          </p>
        </div>
        <div class="card">
          <h3>Contable</h3>
          <p class="muted">
            Contabilidad, cierres, reporting y control financiero para tomar decisiones con seguridad.
          </p>
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
