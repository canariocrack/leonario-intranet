<?php
// config/database.php

$host = 'localhost';
$db = 'leonario_intranet';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

if (!function_exists('leonario_db_run_multi_query')) {
    function leonario_db_run_multi_query($conexion, $sql, $errorPrefix)
    {
        if (!mysqli_multi_query($conexion, $sql)) {
            die($errorPrefix . ': ' . mysqli_error($conexion));
        }

        do {
            $resultado = mysqli_store_result($conexion);
            if ($resultado) {
                mysqli_free_result($resultado);
            }
        } while (mysqli_more_results($conexion) && mysqli_next_result($conexion));

        if (mysqli_errno($conexion)) {
            die($errorPrefix . ': ' . mysqli_error($conexion));
        }
    }
}

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = mysqli_connect($host, $user, $pass);

if (!$conexion) {
    die('Error crítico al conectar con MySQL: ' . mysqli_connect_error());
}

$sqlCreateDatabase = sprintf(
    "CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET %s COLLATE %s_general_ci",
    $db,
    $charset,
    $charset
);

if (!mysqli_query($conexion, $sqlCreateDatabase)) {
    die('No se pudo crear o verificar la base de datos: ' . mysqli_error($conexion));
}

if (!mysqli_select_db($conexion, $db)) {
    die('No se pudo seleccionar la base de datos: ' . mysqli_error($conexion));
}

if (!mysqli_set_charset($conexion, $charset)) {
    die('No se pudo establecer el juego de caracteres: ' . mysqli_error($conexion));
}

$schemaSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','asesor','cliente') NOT NULL DEFAULT 'cliente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `expedientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(160) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('Pendiente','En Revisión','Finalizado') NOT NULL DEFAULT 'Pendiente',
  `asesor_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `respuesta_asesor` text DEFAULT NULL,
  `notas_privadas` text DEFAULT NULL,
  `archivo_resolucion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_exp_asesor` (`asesor_id`),
  KEY `fk_exp_cliente` (`cliente_id`),
  CONSTRAINT `fk_exp_asesor` FOREIGN KEY (`asesor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_exp_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(255) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_logs_user` (`usuario_id`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expediente_id` int(11) NOT NULL,
  `archivo` varchar(255) NOT NULL,
    `nombre_original` varchar(255) DEFAULT NULL,
  `subido_por` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_doc_exp` (`expediente_id`),
  KEY `fk_doc_user` (`subido_por`),
  CONSTRAINT `fk_doc_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_user` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL;

leonario_db_run_multi_query($conexion, $schemaSql, 'No se pudo preparar la estructura de la base de datos');

// Auto-migración: descripcion en expedientes
$resColDesc = mysqli_query($conexion, "SHOW COLUMNS FROM expedientes LIKE 'descripcion'");
$existeDesc = $resColDesc && mysqli_num_rows($resColDesc) > 0;
if ($resColDesc) { mysqli_free_result($resColDesc); }
if (!$existeDesc) {
    mysqli_query($conexion, "ALTER TABLE expedientes ADD COLUMN descripcion text DEFAULT NULL AFTER titulo");
}

$resColumnaNombreOriginal = mysqli_query($conexion, "SHOW COLUMNS FROM documentos LIKE 'nombre_original'");
$existeNombreOriginal = $resColumnaNombreOriginal && mysqli_num_rows($resColumnaNombreOriginal) > 0;
if ($resColumnaNombreOriginal) {
    mysqli_free_result($resColumnaNombreOriginal);
}

if (!$existeNombreOriginal) {
    mysqli_query($conexion, "ALTER TABLE documentos ADD COLUMN nombre_original varchar(255) DEFAULT NULL AFTER archivo");
}

mysqli_query($conexion, "UPDATE documentos SET nombre_original = archivo WHERE (nombre_original IS NULL OR nombre_original = '')");

$resUsuarios = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios");
$filaUsuarios = $resUsuarios ? mysqli_fetch_assoc($resUsuarios) : null;
$totalUsuarios = (int)($filaUsuarios['total'] ?? 0);
if ($resUsuarios) {
    mysqli_free_result($resUsuarios);
}

if ($totalUsuarios === 0) {
    $seedSql = <<<'SQL'
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `dni`, `direccion`, `avatar`, `password`, `rol`, `created_at`) VALUES
(18, 'Antonio Guerrero Cano', 'canariocrack5555@gmail.com', '653859584', '29511912Z', 'Calle Cantabria Nº3', 'avatar_18_1772013617.jpg', '$2y$10$oAOU09WMGclZ/SMZXAou4.GANwtTEU8vTI0mdywuAMT2mW0reVqbm', 'cliente', '2026-01-21 07:43:24'),
(19, 'asesor', 'asesor@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$u.o1wPQK5SCozhCY6tLaJeZV7CCW9QRv7RUR9d2MhWvUxXRuEYloG', 'asesor', '2026-01-21 07:44:26'),
(20, 'admin', 'admin@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$AKnynBQm6qxkw3pBpLoBWOhM8G3lcpfNg3K9id5Ujcl8BOyazl3AC', 'admin', '2026-01-21 07:46:17');

INSERT INTO `expedientes` (`id`, `titulo`, `estado`, `asesor_id`, `cliente_id`, `created_at`, `respuesta_asesor`, `notas_privadas`, `archivo_resolucion`) VALUES
(9, 'IRPF', 'Pendiente', NULL, 18, '2026-02-25 08:29:37', NULL, NULL, NULL),
(10, 'IRPF2', 'Finalizado', 19, 18, '2026-02-25 08:46:17', 'Este es el mensaje', NULL, 'RES_1772009586_Capturapass.PNG'),
(11, 'Pendiente', 'Pendiente', NULL, 18, '2026-03-04 08:33:28', NULL, NULL, NULL),
(12, 'Finalizado', 'Pendiente', 19, 18, '2026-03-04 08:33:42', '', '', NULL),
(13, 'En proceso', 'En Revisión', 19, 18, '2026-03-04 08:33:52', 'Hola estamos revisando el documento', 'Entrega tarde', 'RES_1772613449_5.1_primera.png');

INSERT INTO `logs` (`id`, `usuario_id`, `accion`, `fecha`) VALUES
(34, 18, 'Cliente creó solicitud/expediente #9: IRPF', '2026-02-25 08:29:37'),
(35, 18, 'Cliente creó solicitud/expediente #10: IRPF2', '2026-02-25 08:46:17'),
(36, 20, 'Admin asignó asesor 19 al expediente #10', '2026-02-25 08:47:02'),
(37, 18, 'Cliente actualizó su perfil (nombre)', '2026-02-25 09:40:02'),
(43, 18, 'Cliente actualizó su perfil completo (datos personales)', '2026-02-25 09:46:08'),
(44, 18, 'Cliente creó solicitud/expediente #11: Pendiente', '2026-03-04 08:33:28'),
(45, 18, 'Cliente creó solicitud/expediente #12: Finalizado', '2026-03-04 08:33:42'),
(46, 18, 'Cliente creó solicitud/expediente #13: En proceso', '2026-03-04 08:33:52'),
(47, 20, 'Admin asignó asesor 19 al expediente #13', '2026-03-04 08:34:11'),
(48, 20, 'Admin asignó asesor 19 al expediente #12', '2026-03-04 08:34:14');

INSERT INTO `documentos` (`id`, `expediente_id`, `archivo`, `nombre_original`, `subido_por`, `fecha`) VALUES
(15, 9, '1772008177_Screenshot_1.png', 'Screenshot_1.png', 18, '2026-02-25 08:29:37'),
(16, 10, '1772009177_5.1_primera.png', '5.1_primera.png', 18, '2026-02-25 08:46:17'),
(17, 10, 'RES_1772009586_Capturapass.PNG', 'Capturapass.PNG', 19, '2026-02-25 08:53:06'),
(19, 13, 'RES_1772613449_5.1_primera.png', '5.1_primera.png', 19, '2026-03-04 08:37:29');
SQL;

    leonario_db_run_multi_query($conexion, $seedSql, 'No se pudieron cargar los datos iniciales');
}

?>
