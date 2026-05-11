<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificamos seguridad
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
    die("Acceso denegado.");
}

$uid = (int)$_SESSION['user_id'];
$id = (int)$_POST['id']; // ID del expediente que se está actualizando

// Recogemos los datos del formulario
$estado = $_POST['estado'] ?? 'Pendiente';
$respuesta = $_POST['respuesta_asesor'] ?? '';
$notas_privadas = $_POST['notas_privadas'] ?? '';

$archivo_final = ""; // Guarda el último archivo de respuesta insertado (para archivo_resolucion)

// 1. SUBIDA DE ARCHIVO(S) (Si hay)
$archivosNuevos = [];

// Flujo principal: nuevo input múltiple "archivos_res[]"
if (isset($_FILES['archivos_res']) && isset($_FILES['archivos_res']['name']) && is_array($_FILES['archivos_res']['name'])) {
    $total = count($_FILES['archivos_res']['name']);
    for ($i = 0; $i < $total; $i++) {
        $nombreOriginal = trim((string)($_FILES['archivos_res']['name'][$i] ?? ''));
        $tmpName = $_FILES['archivos_res']['tmp_name'][$i] ?? '';
        $errorArchivo = (int)($_FILES['archivos_res']['error'][$i] ?? UPLOAD_ERR_NO_FILE);

        if ($nombreOriginal === '' || $errorArchivo === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($errorArchivo !== UPLOAD_ERR_OK) {
            continue;
        }

        $nombreOriginalRes = basename($nombreOriginal);
        $ext = strtolower(pathinfo($nombreOriginalRes, PATHINFO_EXTENSION));
        $safeName = 'RES_' . $id . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . ($ext !== '' ? '.' . $ext : '');

        if (move_uploaded_file($tmpName, "../files/" . $safeName)) {
            $archivosNuevos[] = [
                'archivo' => $safeName,
                'nombre_original' => $nombreOriginalRes,
            ];
        }
    }
// Flujo legado: compatibilidad con el input antiguo "archivo_res"
} elseif (isset($_FILES['archivo_res']) && ($_FILES['archivo_res']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    // Compatibilidad con el nombre anterior del input
    $nombreOriginalRes = basename((string)$_FILES['archivo_res']['name']);
    $ext = strtolower(pathinfo($nombreOriginalRes, PATHINFO_EXTENSION));
    $safeName = 'RES_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . ($ext !== '' ? '.' . $ext : '');
    if (move_uploaded_file($_FILES['archivo_res']['tmp_name'], "../files/" . $safeName)) {
        $archivosNuevos[] = [
            'archivo' => $safeName,
            'nombre_original' => $nombreOriginalRes,
        ];
    }
}

// Persistimos cada archivo subido como registro independiente en la tabla documentos
if ($archivosNuevos) {
    $ins_doc = "INSERT INTO documentos (expediente_id, archivo, nombre_original, subido_por) VALUES (?, ?, ?, ?)";
    $stmt_doc = mysqli_prepare($conexion, $ins_doc);
    if ($stmt_doc) {
        foreach ($archivosNuevos as $archivoData) {
            $nombre = $archivoData['archivo'];
            $nombreOriginalRes = $archivoData['nombre_original'];
            mysqli_stmt_bind_param($stmt_doc, "issi", $id, $nombre, $nombreOriginalRes, $uid);
            mysqli_stmt_execute($stmt_doc);
            $archivo_final = $nombre;
        }
        mysqli_stmt_close($stmt_doc);
    }
}

// 2. ACTUALIZAR EXPEDIENTE
// Si se adjuntó al menos un archivo, se actualiza también el campo archivo_resolucion
if ($archivo_final !== "") {
    $sql = "UPDATE expedientes SET estado = ?, respuesta_asesor = ?, notas_privadas = ?, archivo_resolucion = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $estado, $respuesta, $notas_privadas, $archivo_final, $id);
} else {
    $sql = "UPDATE expedientes SET estado = ?, respuesta_asesor = ?, notas_privadas = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $estado, $respuesta, $notas_privadas, $id);
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ==========================================
// 3. LA MAGIA: ENVÍO DE EMAIL AUTOMÁTICO
// ==========================================

// Primero, necesitamos saber el email y el nombre del cliente de este expediente
$q_email = "
    SELECT u.email, u.nombre, e.titulo 
    FROM expedientes e 
    INNER JOIN usuarios u ON u.id = e.cliente_id 
    WHERE e.id = ? LIMIT 1
";
$stmt_email = mysqli_prepare($conexion, $q_email);
mysqli_stmt_bind_param($stmt_email, "i", $id);
mysqli_stmt_execute($stmt_email);
$res_email = mysqli_stmt_get_result($stmt_email);
$datos_cliente = mysqli_fetch_assoc($res_email);
mysqli_stmt_close($stmt_email);

// Si encontramos al cliente, disparamos el email
if ($datos_cliente) {
    $destinatario = $datos_cliente['email'];
    $asunto = "Actualización en tu trámite: " . $datos_cliente['titulo'];
    
    // El cuerpo del mensaje
    $mensaje = "Hola " . $datos_cliente['nombre'] . ",\n\n";
    $mensaje .= "Te escribimos para informarte de que tu asesor ha actualizado el expediente: '" . $datos_cliente['titulo'] . "'.\n\n";
    $mensaje .= "El estado actual es: " . strtoupper($estado) . "\n\n";
    
    // Solo notificamos mensaje nuevo si el asesor escribió texto visible al cliente
    if ($respuesta !== '') {
        $mensaje .= "Tienes un nuevo mensaje de tu asesor. Por favor, entra en tu Área Privada para leerlo o descargar tu resolución.\n\n";
    }
    
    $mensaje .= "Atentamente,\nEl equipo de Leonario Asesores.";

    // Cabeceras para que no vaya a Spam y se lea bien
    $headers = "From: notificaciones@leonarioasesores.com\r\n";
    $headers .= "Reply-To: no-reply@leonarioasesores.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Enviamos el email usando la función nativa procedimental de PHP
    // (Nota: Esto funcionará perfectamente cuando la web esté subida a un hosting real con servicio de correo activo)
    @mail($destinatario, $asunto, $mensaje, $headers);
}
// ==========================================

// Redirigimos al dashboard del asesor
header("Location: dashboard.php");
exit;
