<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Leonario Asesores</title>
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
      <a class="active" href="index.php">Inicio</a>
      <a href="quienes-somos.php">Quiénes somos</a>
      <a href="servicios.php">Servicios</a>
      <a href="contacto.php">Contacto</a>
      <a class="cta" href="auth/login.php">Acceso</a>
    </nav>
  </div>
</header>

<main class="main">
  <div class="container">

    <section class="hero">
      <div class="content">
        <div>
          <div class="kicker">Asesoría corporativa</div>
          <h1>Orden, control y tranquilidad para su gestión empresarial</h1>
          <p>
            Fiscal, laboral y contable con metodología clara y seguimiento digital.
            Un entorno profesional para consultar expedientes, aportar documentación y mantener trazabilidad.
          </p>

          <div class="actions">
            <a class="btn primary" href="auth/login.php">Acceso clientes / asesores</a>
            <a class="btn soft" href="servicios.php">Ver servicios</a>
          </div>

          <p class="muted" style="margin-top:14px;color:rgba(255,255,255,.78)">
            Acceso seguro · Documentación centralizada · Estado de trámites actualizado
          </p>
        </div>

        <div class="logoCard">
          <img src="assets/img/logo-leonario.png" alt="Logo Leonario">
        </div>
      </div>
    </section>

    <section class="section">
      <h2 style="margin:0 0 10px;font-weight:980;">Por qué Leonario Asesores</h2>
      <div class="grid grid3">
        <div class="card">
          <h3>+10 años</h3>
          <p class="muted">Experiencia en asesoría fiscal, laboral y contable con visión preventiva.</p>
        </div>
        <div class="card">
          <h3>3 áreas</h3>
          <p class="muted">Fiscal · Laboral · Contable. Enfoque integral para empresas y autónomos.</p>
        </div>
        <div class="card">
          <h3>24/7</h3>
          <p class="muted">Acceso a expedientes y documentación desde la intranet, con trazabilidad.</p>
        </div>
      </div>
    </section>

    <section class="section">
      <h2 style="margin:0 0 10px;font-weight:980;">Servicios principales</h2>
      <div class="grid grid3">
        <div class="card">
          <h3>Asesoría Fiscal</h3>
          <p class="muted">Declaraciones, planificación y cumplimiento normativo con visión estratégica.</p>
        </div>
        <div class="card">
          <h3>Asesoría Laboral</h3>
          <p class="muted">Contratos, nóminas, altas/bajas y gestión laboral integral.</p>
        </div>
        <div class="card">
          <h3>Asesoría Contable</h3>
          <p class="muted">Contabilidad clara, reporting y control financiero para decisiones seguras.</p>
        </div>
      </div>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container muted">
    © <?php echo date('Y'); ?> Leonario Asesores · Web corporativa (TFG)
  </div>
</footer>

</body>
</html>
