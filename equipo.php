<?php
session_start();

$accesoUrl = 'auth/login.php';
$accesoTexto = 'Acceso';
if (isset($_SESSION['user_id'])) {
  $rol = $_SESSION['rol'] ?? 'cliente';
  if ($rol === 'admin') {
    $accesoUrl = 'admin/dashboard.php';
  } elseif ($rol === 'asesor') {
    $accesoUrl = 'asesor/dashboard.php';
  } else {
    $accesoUrl = 'cliente/dashboard.php';
  }
  $accesoTexto = 'Mi panel';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Equipo | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/public.css">
</head>
<body class="public-site">

<header class="public-topbar">
  <div class="public-container public-topbar-inner">
    <a class="public-brand" href="index.php">
      <img src="assets/img/logo-leonario.png" alt="Leonario Asesores">
      <span><strong>Leonario Asesores</strong><small>Fiscal · Laboral · Contable</small></span>
    </a>
    <nav class="public-nav" aria-label="Navegacion principal">
      <a href="index.php">Inicio</a>
      <a href="servicios.php">Servicios</a>
      <a class="active" href="equipo.php">Equipo</a>
      <a href="contacto.php">Contacto</a>
      <a class="nav-access" href="<?php echo htmlspecialchars($accesoUrl); ?>"><?php echo htmlspecialchars($accesoTexto); ?></a>
    </nav>
  </div>
</header>

<main>
  <section class="public-section page-intro">
    <div class="public-container narrow">
      <p class="eyebrow">Equipo</p>
      <h1>Cercanía profesional, criterio técnico y procesos visibles</h1>
      <p class="lead">Un despacho organizado para explicar, registrar y anticipar lo que cada cliente necesita en materia fiscal, laboral y contable.</p>
    </div>
  </section>

  <section class="public-section compact">
    <div class="public-container two-column align-start">
      <div class="section-heading">
        <p class="eyebrow">Forma de trabajar</p>
        <h2>No basta con tramitar: hay que explicar, registrar y anticipar</h2>
      </div>
      <aside class="quote-panel">
        <p>“Una buena asesoría reduce incertidumbre: convierte obligaciones, documentos y plazos en un sistema comprensible.”</p>
      </aside>
    </div>
  </section>

  <section class="public-section">
    <div class="public-container">
      <div class="section-heading">
        <p class="eyebrow">Principios de trabajo</p>
        <h2>Un criterio común para todas las áreas</h2>
      </div>
      <div class="card-grid four">
        <article class="public-card"><h3>Claridad</h3><p>Explicaciones útiles, estados visibles y próximos pasos definidos.</p></article>
        <article class="public-card"><h3>Rigor</h3><p>Revisión técnica y documentación suficiente antes de cerrar cada expediente.</p></article>
        <article class="public-card"><h3>Continuidad</h3><p>Historial disponible para no empezar de cero en cada consulta.</p></article>
        <article class="public-card"><h3>Confianza</h3><p>Trato cercano con procesos profesionales y datos ordenados.</p></article>
      </div>
    </div>
  </section>

  <section class="public-section soft-band">
    <div class="public-container">
      <div class="section-heading">
        <p class="eyebrow">Áreas internas</p>
        <h2>Especialización coordinada</h2>
      </div>
      <div class="card-grid four">
        <article class="public-card accent"><h3>Fiscal</h3><p>Impuestos, vencimientos, planificación y requerimientos.</p></article>
        <article class="public-card accent"><h3>Laboral</h3><p>Contratación, nóminas, altas, bajas y consultas recurrentes.</p></article>
        <article class="public-card accent"><h3>Contable</h3><p>Registros, cierres, balances e informes de gestión.</p></article>
        <article class="public-card accent"><h3>Administración</h3><p>Coordinación, archivo, avisos y seguimiento operativo.</p></article>
      </div>
    </div>
  </section>

  <section class="public-section">
    <div class="public-container two-column">
      <div class="section-heading">
        <p class="eyebrow">Compromiso digital</p>
        <h2>La información importante debe poder encontrarse</h2>
      </div>
      <ul class="check-list">
        <li>Acceso diferenciado para cliente, asesor y administración.</li>
        <li>Documentos vinculados a cada expediente.</li>
        <li>Estados claros: pendiente, en revisión o finalizado.</li>
        <li>Respuestas del asesor disponibles para futuras consultas.</li>
      </ul>
    </div>
  </section>
</main>

<footer class="public-footer">
  <div class="public-container footer-grid">
    <div><h4>Leonario Asesores</h4><p>Asesoría fiscal, laboral y contable con intranet privada para clientes, asesores y administración.</p><p>© <?php echo date('Y'); ?> Leonario Asesores</p></div>
    <div><h4>Web pública</h4><a href="servicios.php">Servicios</a><a href="equipo.php">Equipo</a><a href="contacto.php">Contacto</a></div>
    <div><h4>Intranet</h4><a href="<?php echo htmlspecialchars($accesoUrl); ?>"><?php echo htmlspecialchars($accesoTexto); ?></a><a href="auth/register.php">Registro si aplica</a></div>
    <div><h4>Contacto</h4><a href="mailto:contacto@leonarioasesores.com">contacto@leonarioasesores.com</a><a href="tel:+34600000000">+34 600 000 000</a></div>
  </div>
</footer>

</body>
</html>
