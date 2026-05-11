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
  <title>Inicio | Leonario Asesores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/public.css">
</head>
<body class="public-site">

<header class="public-topbar">
  <div class="public-container public-topbar-inner">
    <a class="public-brand" href="index.php">
      <img src="assets/img/logo-leonario.png" alt="Leonario Asesores">
      <span>
        <strong>Leonario Asesores</strong>
        <small>Fiscal · Laboral · Contable</small>
      </span>
    </a>
    <nav class="public-nav" aria-label="Navegacion principal">
      <a class="active" href="index.php">Inicio</a>
      <a href="servicios.php">Servicios</a>
      <a href="equipo.php">Equipo</a>
      <a href="contacto.php">Contacto</a>
      <a class="nav-access" href="<?php echo htmlspecialchars($accesoUrl); ?>"><?php echo htmlspecialchars($accesoTexto); ?></a>
    </nav>
  </div>
</header>

<main>
  <section class="public-section hero-section">
    <div class="public-container hero-grid">
      <div class="hero-copy">
        <p class="eyebrow">Asesoria con gestion digital</p>
        <h1>Gestión fiscal, laboral y contable con orden y seguimiento digital</h1>
        <p class="lead">Leonario Asesores acompaña a negocios que necesitan cumplir obligaciones, entender números y resolver trámites sin perder información.</p>
        <div class="public-actions">
          <a class="public-btn primary" href="contacto.php">Solicitar una consulta</a>
          <a class="public-btn secondary" href="<?php echo htmlspecialchars($accesoUrl); ?>">Acceso intranet</a>
        </div>
      </div>
      <aside class="summary-panel" aria-label="Resumen de areas">
        <article>
          <span>Fiscal</span>
          <p>Impuestos, modelos, planificación y requerimientos.</p>
        </article>
        <article>
          <span>Laboral</span>
          <p>Contratos, nóminas, altas, bajas y asesoría recurrente.</p>
        </article>
        <article>
          <span>Contable</span>
          <p>Contabilidad, cierres, informes y control financiero.</p>
        </article>
        <article>
          <span>Intranet privada</span>
          <p>Expedientes, documentos, mensajes y estados reunidos en un único panel.</p>
        </article>
      </aside>
    </div>
  </section>

  <section class="public-section compact">
    <div class="public-container stats-strip">
      <div><strong>3</strong><span>áreas coordinadas</span></div>
      <div><strong>24/7</strong><span>consulta de expedientes</span></div>
      <div><strong>1</strong><span>repositorio documental</span></div>
      <div><strong>100%</strong><span>historial trazable</span></div>
    </div>
  </section>

  <section class="public-section">
    <div class="public-container">
      <div class="section-heading">
        <p class="eyebrow">Qué resolvemos</p>
        <h2>Gestión diaria con menos incertidumbre</h2>
      </div>
      <div class="card-grid four">
        <article class="public-card"><h3>Obligaciones periódicas</h3><p>Calendario fiscal, laboral y contable ordenado para trabajar con margen.</p></article>
        <article class="public-card"><h3>Consultas y decisiones</h3><p>Respuestas comprensibles para decidir con contexto y criterio técnico.</p></article>
        <article class="public-card"><h3>Documentación ordenada</h3><p>Archivos vinculados a expedientes y disponibles para futuras consultas.</p></article>
        <article class="public-card"><h3>Comunicación sencilla</h3><p>Estados claros, avisos y mensajes reunidos en el panel privado.</p></article>
      </div>
    </div>
  </section>

  <section class="public-section soft-band">
    <div class="public-container two-column">
      <div class="section-heading">
        <p class="eyebrow">Método Leonario</p>
        <h2>Un proceso visible desde la entrada hasta el archivo</h2>
      </div>
      <ol class="timeline">
        <li><strong>Entrada del caso</strong><span>Recogemos la solicitud, el plazo y la documentación inicial.</span></li>
        <li><strong>Revisión técnica</strong><span>El asesor analiza el expediente y pide lo imprescindible.</span></li>
        <li><strong>Resolución</strong><span>Se entrega respuesta, trámite o documento con explicación clara.</span></li>
        <li><strong>Archivo y seguimiento</strong><span>El historial queda trazado para futuras revisiones.</span></li>
      </ol>
    </div>
  </section>

  <section class="public-section">
    <div class="public-container">
      <div class="section-heading">
        <p class="eyebrow">Para quién trabajamos</p>
        <h2>Acompañamiento para estructuras reales de negocio</h2>
      </div>
      <div class="card-grid three">
        <article class="public-card accent"><h3>Autónomos</h3><p>Obligaciones, altas, facturación y decisiones fiscales recurrentes.</p></article>
        <article class="public-card accent"><h3>Pymes</h3><p>Gestión fiscal, laboral y contable coordinada en un mismo flujo.</p></article>
        <article class="public-card accent"><h3>Empresas en crecimiento</h3><p>Información financiera, procesos y seguimiento documental para escalar con orden.</p></article>
      </div>
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
