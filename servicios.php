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
  <title>Servicios | Leonario Asesores</title>
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
      <a class="active" href="servicios.php">Servicios</a>
      <a href="equipo.php">Equipo</a>
      <a href="contacto.php">Contacto</a>
      <a class="nav-access" href="<?php echo htmlspecialchars($accesoUrl); ?>"><?php echo htmlspecialchars($accesoTexto); ?></a>
    </nav>
  </div>
</header>

<main>
  <section class="public-section page-intro">
    <div class="public-container narrow">
      <p class="eyebrow">Servicios</p>
      <h1>Fiscal, laboral y contable con información clara y expediente digital</h1>
      <p class="lead">Organizamos obligaciones, consultas y documentos para que cada área trabaje con contexto, responsables y seguimiento.</p>
    </div>
  </section>

  <section class="public-section compact">
    <div class="public-container service-stack">
      <article class="service-block">
        <div>
          <span class="area-label">Fiscal</span>
          <h2>Impuestos, planificación y respuesta ante requerimientos</h2>
        </div>
        <div class="pill-list">
          <span>IVA e IRPF</span><span>Sociedades</span><span>Modelos trimestrales</span><span>Declaraciones anuales</span><span>Requerimientos</span><span>Planificación fiscal</span>
        </div>
      </article>
      <article class="service-block">
        <div>
          <span class="area-label">Laboral</span>
          <h2>Gestión de personas, contratos y obligaciones laborales</h2>
        </div>
        <div class="pill-list">
          <span>Altas y bajas</span><span>Contratos</span><span>Nóminas</span><span>Seguros sociales</span><span>Partes y certificados</span><span>Consultas laborales</span>
        </div>
      </article>
      <article class="service-block">
        <div>
          <span class="area-label">Contable</span>
          <h2>Contabilidad útil para cumplir y decidir mejor</h2>
        </div>
        <div class="pill-list">
          <span>Registro contable</span><span>Cierres</span><span>Balances</span><span>Cuenta de resultados</span><span>Control de gastos</span><span>Reporting</span>
        </div>
      </article>
    </div>
  </section>

  <section class="public-section">
    <div class="public-container">
      <div class="section-heading">
        <p class="eyebrow">Servicios complementarios</p>
        <h2>Apoyo técnico cuando el trámite requiere más contexto</h2>
      </div>
      <div class="card-grid three">
        <article class="public-card"><h3>Inicio de actividad</h3><p>Alta, encuadre fiscal y primeras obligaciones.</p></article>
        <article class="public-card"><h3>Cambios societarios</h3><p>Coordinación documental y revisión administrativa.</p></article>
        <article class="public-card"><h3>Inspecciones y requerimientos</h3><p>Preparación de respuesta, anexos y seguimiento.</p></article>
        <article class="public-card"><h3>Subvenciones y ayudas</h3><p>Revisión de bases, plazos y documentación necesaria.</p></article>
        <article class="public-card"><h3>Informes de gestión</h3><p>Datos contables transformados en lectura útil.</p></article>
        <article class="public-card"><h3>Digitalización documental</h3><p>Expedientes ordenados y archivos localizables.</p></article>
      </div>
    </div>
  </section>

  <section class="public-section soft-band">
    <div class="public-container two-column">
      <div class="section-heading">
        <p class="eyebrow">Cómo se trabaja</p>
        <h2>Un recorrido breve, claro y documentado</h2>
      </div>
      <ol class="timeline">
        <li><strong>Solicitud o aviso</strong><span>El cliente envía la consulta o se registra el vencimiento.</span></li>
        <li><strong>Revisión del asesor</strong><span>Se valida información, alcance, plazo y documentos pendientes.</span></li>
        <li><strong>Entrega del resultado</strong><span>La respuesta o trámite queda archivado en el expediente digital.</span></li>
      </ol>
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
