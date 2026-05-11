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
  <title>Contacto | Leonario Asesores</title>
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
      <a href="equipo.php">Equipo</a>
      <a class="active" href="contacto.php">Contacto</a>
      <a class="nav-access" href="<?php echo htmlspecialchars($accesoUrl); ?>"><?php echo htmlspecialchars($accesoTexto); ?></a>
    </nav>
  </div>
</header>

<main>
  <section class="public-section page-intro">
    <div class="public-container narrow">
      <p class="eyebrow">Contacto</p>
      <h1>Cuéntalo una vez y lo convertimos en un expediente ordenado</h1>
      <p class="lead">Indica el tipo de consulta, los plazos y la documentación principal para que podamos orientarte con precisión desde el primer contacto.</p>
    </div>
  </section>

  <section class="public-section compact">
    <div class="public-container contact-grid">
      <article class="contact-panel">
        <h2>Datos de contacto</h2>
        <dl class="contact-list">
          <div><dt>Teléfono</dt><dd><a href="tel:+34600000000">+34 600 000 000</a></dd></div>
          <div><dt>Email</dt><dd><a href="mailto:contacto@leonarioasesores.com">contacto@leonarioasesores.com</a></dd></div>
          <div><dt>Horario</dt><dd>Lunes a viernes, 09:00-14:00 y 16:00-19:00</dd></div>
        </dl>
      </article>
      <article class="contact-panel">
        <h2>Antes de escribirnos</h2>
        <ul class="check-list compact-list">
          <li>Nombre, teléfono y correo de contacto.</li>
          <li>Tipo de consulta: fiscal, laboral, contable o administrativa.</li>
          <li>Fecha límite, notificación recibida o plazo relevante.</li>
          <li>Documentos principales relacionados con el trámite.</li>
        </ul>
        <div class="public-actions">
          <a class="public-btn primary" href="mailto:contacto@leonarioasesores.com">Enviar email</a>
          <a class="public-btn secondary" href="<?php echo htmlspecialchars($accesoUrl); ?>"><?php echo htmlspecialchars($accesoTexto); ?></a>
        </div>
      </article>
    </div>
  </section>

  <section class="public-section">
    <div class="public-container narrow">
      <div class="section-heading">
        <p class="eyebrow">FAQ</p>
        <h2>Preguntas frecuentes</h2>
      </div>
      <div class="faq-list">
        <details>
          <summary>Ya soy cliente, ¿dónde envío documentos?</summary>
          <p>Desde tu panel privado, vinculados al expediente correspondiente para que el asesor los revise con contexto.</p>
        </details>
        <details>
          <summary>¿Puedo pedir una revisión puntual?</summary>
          <p>Sí. Puedes escribirnos con el tipo de consulta y la fecha relevante para valorar el alcance antes de abrir expediente.</p>
        </details>
        <details>
          <summary>¿Qué pasa si falta información?</summary>
          <p>El asesor indicará qué documento o dato falta y el expediente permanecerá identificado como pendiente o en revisión.</p>
        </details>
        <details>
          <summary>¿La intranet sustituye al contacto personal?</summary>
          <p>No. La intranet ordena documentos, estados e historial; el trato profesional sigue siendo directo cuando el caso lo requiere.</p>
        </details>
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
