<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Quiénes somos | Leonario Asesores</title>
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
      <a class="active" href="quienes-somos.php">Quiénes somos</a>
      <a href="servicios.php">Servicios</a>
      <a href="contacto.php">Contacto</a>
      <a class="cta" href="auth/login.php">Acceso</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero" style="background:
      linear-gradient(135deg, rgba(7,26,43,.90), rgba(11,42,74,.62)),
      url('assets/img/oficina.jpg');
      background-size:cover;background-position:center;">
      <div class="content">
        <div>
          <div class="kicker">Equipo y metodología</div>
          <h1>Quiénes somos</h1>
          <p>Un equipo orientado a generar confianza y tranquilidad, con rigor técnico y metodología transparente.</p>
          <div class="actions">
            <a class="btn primary" href="contacto.php">Contactar</a>
            <a class="btn soft" href="servicios.php">Ver servicios</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="grid grid3">
        <div class="card">
          <h3>Rigor técnico</h3>
          <p class="muted">Cumplimiento normativo, prevención de riesgos y documentación clara.</p>
        </div>
        <div class="card">
          <h3>Metodología</h3>
          <p class="muted">Procesos definidos, comunicación constante y trazabilidad de cada trámite.</p>
        </div>
        <div class="card">
          <h3>Cercanía profesional</h3>
          <p class="muted">Acompañamiento y explicación clara sin perder formalidad y precisión.</p>
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
