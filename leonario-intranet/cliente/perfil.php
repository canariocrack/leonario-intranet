<?php
// Inicia la sesión y carga la conexión con la base de datos.
session_start();
require_once __DIR__ . '/../config/database.php';

// Solo los usuarios con rol cliente pueden acceder a esta página.
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
  header('Location: ../auth/login.php');
  exit;
}

// Variables principales de trabajo.
$uid = (int)$_SESSION['user_id'];
$error = '';
$ok = '';
$user = null;

// 1. Obtener los datos actuales del usuario.
$q_user = "SELECT id, nombre, email, telefono, dni, direccion, avatar FROM usuarios WHERE id = ? LIMIT 1";
$stmt_user = mysqli_prepare($conexion, $q_user);
if ($stmt_user) {
  mysqli_stmt_bind_param($stmt_user, "i", $uid);
  mysqli_stmt_execute($stmt_user);
  $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
  mysqli_stmt_close($stmt_user);
}

// Si no se encuentra el usuario, se vuelve al panel.
if (!$user) {
  header('Location: dashboard.php');
  exit;
}

// 2. Recuperar mensajes guardados después de una redirección.
if (isset($_SESSION['flash_ok'])) {
  $ok = (string)$_SESSION['flash_ok'];
  unset($_SESSION['flash_ok']);
}
if (isset($_SESSION['flash_err'])) {
  $error = (string)$_SESSION['flash_err'];
  unset($_SESSION['flash_err']);
}

// 3. Procesar los formularios enviados desde esta página.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tipo_formulario = $_POST['tipo_formulario'] ?? '';

  // 3.1 Formulario de datos personales y foto de perfil.
  if ($tipo_formulario === 'datos') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') {
      $error = 'El nombre no puede estar vacío.';
    } else {
      // Actualizar los campos de texto del perfil.
      $q_up = "UPDATE usuarios SET nombre = ?, telefono = ?, dni = ?, direccion = ? WHERE id = ?";
      $stmt_up = mysqli_prepare($conexion, $q_up);
      if ($stmt_up) {
        mysqli_stmt_bind_param($stmt_up, "ssssi", $nombre, $telefono, $dni, $direccion, $uid);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        $_SESSION['nombre'] = $nombre;
        $user['nombre'] = $nombre;
        $user['telefono'] = $telefono;
        $user['dni'] = $dni;
        $user['direccion'] = $direccion;
        $ok = 'Perfil actualizado correctamente.';
      } else {
        $error = 'No se pudieron actualizar los datos del perfil.';
      }

      // Subir y guardar la foto de perfil si se ha seleccionado una imagen.
      if ($error === '' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
          $error = 'No se pudo recibir la imagen de perfil.';
        }
      }

      if ($error === '' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $infoImagen = @getimagesize($_FILES['avatar']['tmp_name']);
        $tamanoMaximo = 2 * 1024 * 1024;

        if (!in_array($ext, $permitidas, true) || $infoImagen === false) {
          $error = 'El formato de la imagen no es válido. Usa JPG, PNG o WEBP.';
        } elseif ($_FILES['avatar']['size'] > $tamanoMaximo) {
          $error = 'La imagen no puede superar los 2 MB.';
        } else {
          $nombre_foto = 'avatar_' . $uid . '_' . time() . '.' . $ext;
          $dir_avatar = __DIR__ . '/../uploads/avatars/';

          if (!is_dir($dir_avatar)) {
            mkdir($dir_avatar, 0775, true);
          }

          if (!is_dir($dir_avatar)) {
            $error = 'No se pudo preparar la carpeta de avatares.';
          } elseif (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir_avatar . $nombre_foto)) {
            $q_av = "UPDATE usuarios SET avatar = ? WHERE id = ?";
            $stmt_av = mysqli_prepare($conexion, $q_av);
            if ($stmt_av) {
              mysqli_stmt_bind_param($stmt_av, "si", $nombre_foto, $uid);
              mysqli_stmt_execute($stmt_av);
              mysqli_stmt_close($stmt_av);

              $user['avatar'] = $nombre_foto;
            } else {
              $error = 'La imagen se ha subido, pero no se pudo guardar en el perfil.';
            }
          } else {
            $error = 'No se pudo subir la imagen de perfil.';
          }
        }
      }

      if ($error === '') {
        $_SESSION['flash_ok'] = $ok;
        header('Location: dashboard.php?tab=perfil');
        exit;
      }
    }
  }

  // 3.2 Formulario de cambio de contraseña.
  if ($tipo_formulario === 'password') {
    $pass1 = $_POST['pass1'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';

    if (strlen($pass1) < 6) {
      $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass1 !== $pass2) {
      $error = 'Las contraseñas no coinciden.';
    } else {
      $hash = password_hash($pass1, PASSWORD_DEFAULT);
      $q_up = "UPDATE usuarios SET password = ? WHERE id = ?";
      $stmt_up = mysqli_prepare($conexion, $q_up);

      if ($stmt_up) {
        mysqli_stmt_bind_param($stmt_up, "si", $hash, $uid);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        $_SESSION['flash_ok'] = 'Contraseña actualizada correctamente.';
        header('Location: dashboard.php?tab=perfil');
        exit;
      }

      $error = 'No se pudo actualizar la contraseña.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi perfil | Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/style.css?v=20260510a">
  <style>
    /* Distribución responsive para campos dobles del formulario. */
    .grid-2col { display: grid; grid-template-columns: 1fr; gap: 15px; }
    @media(min-width: 600px) { .grid-2col { grid-template-columns: 1fr 1fr; } }

    /* Cabecera del perfil y avatar del cliente. */
    .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
    .avatar-circle { width: 80px; height: 80px; border-radius: 50%; background: #e5e7eb; color: #374151; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 900; text-transform: uppercase; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="app-shell">

<!-- Cabecera superior de navegación del cliente. -->
<header class="topbar">
  <div class="inner container">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo-leonario.png" alt="Leonario Asesores">
      <div>
        <div class="name">Leonario Asesores</div>
        <div class="tag">Mi perfil</div>
      </div>
    </a>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a class="active" href="perfil.php">Mi perfil</a>
      <a class="cta" href="../auth/logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<!-- Contenido principal de la página. -->
<main class="main">
  <div class="container" style="max-width: 800px;">
    <div class="app-page-head">
      <div>
        <div class="kicker">Área cliente</div>
        <h1>Perfil y seguridad</h1>
        <p class="muted">Actualiza tus datos personales, imagen y credenciales de acceso.</p>
      </div>
    </div>

    <!-- Mensajes de error o confirmación. -->
    <?php if ($error): ?>
      <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($ok): ?>
      <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
    <?php endif; ?>

    <!-- Bloque de datos personales del cliente. -->
    <section class="card" style="margin-bottom: 20px;">
      <div class="profile-header">
        <?php if (!empty($user['avatar'])):
          $avatarFile = basename((string)$user['avatar']);
          $avatarSrc = is_file(__DIR__ . '/../uploads/avatars/' . $avatarFile)
            ? '../uploads/avatars/' . rawurlencode($avatarFile)
            : '../uploads/' . rawurlencode($avatarFile);
        ?>
          <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Foto de perfil" class="avatar-circle">
        <?php else: ?>
          <div class="avatar-circle">
            <?php echo htmlspecialchars(substr((string)$user['nombre'], 0, 1)); ?>
          </div>
        <?php endif; ?>

        <div>
          <h1 style="margin:0 0 5px;font-weight:980; font-size: 22px;">Datos personales</h1>
          <p class="muted" style="margin:0;">Actualiza tu información y foto de perfil.</p>
        </div>
      </div>

      <!-- Formulario para actualizar datos personales y avatar. -->
      <form method="post" enctype="multipart/form-data" class="formCard" style="box-shadow:none; padding: 0;">
        <input type="hidden" name="tipo_formulario" value="datos">

        <div class="field" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 20px;">
          <label style="color: #475569;">Cambiar foto de perfil (JPG, PNG o WEBP)</label>
          <input type="file" name="avatar" accept="image/png, image/jpeg, image/webp" style="margin-top: 5px;">
        </div>

        <div class="grid-2col">
          <div class="field">
            <label>Nombre completo / Razón social</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>DNI / NIE / CIF</label>
            <input type="text" name="dni" value="<?php echo htmlspecialchars($user['dni'] ?? ''); ?>">
          </div>
        </div>

        <div class="grid-2col">
          <div class="field">
            <label>Teléfono de contacto</label>
            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Email</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled style="background-color: #f3f4f6;">
          </div>
        </div>

        <div class="field">
          <label>Dirección completa</label>
          <input type="text" name="direccion" value="<?php echo htmlspecialchars($user['direccion'] ?? ''); ?>">
        </div>

        <button class="btn primary" type="submit" style="margin-top: 10px;">Guardar cambios</button>
      </form>
    </section>

    <!-- Bloque de seguridad y cambio de contraseña. -->
    <section class="card">
      <h2 style="margin:0 0 5px;font-weight:980; font-size: 20px;">Seguridad</h2>
      <p class="muted" style="margin-top:0;">Protege tu cuenta actualizando tu contraseña periódicamente.</p>

      <form method="post" class="formCard" style="box-shadow:none; padding: 0; margin-top: 20px;">
        <input type="hidden" name="tipo_formulario" value="password">
        <div class="grid-2col">
          <div class="field">
            <label>Nueva contraseña</label>
            <input type="password" name="pass1" required>
          </div>
          <div class="field">
            <label>Repetir nueva contraseña</label>
            <input type="password" name="pass2" required>
          </div>
        </div>
        <button class="btn" type="submit" style="background: #374151; color: white; border: none; margin-top: 10px;">Actualizar contraseña</button>
      </form>
    </section>
  </div>
</main>
</body>
</html>
