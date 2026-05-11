<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'asesor') {
    header('Location: ../auth/login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$doc_id = (int)($_GET['id'] ?? 0); // Documento a eliminar
$exp_id = (int)($_GET['exp_id'] ?? 0); // Expediente al que pertenece

if ($doc_id <= 0 || $exp_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Regla de seguridad: el asesor solo borra documentos suyos y de expedientes asignados a él
$sqlDoc = "
    SELECT d.id, d.archivo, d.expediente_id
    FROM documentos d
    INNER JOIN expedientes e ON e.id = d.expediente_id
    WHERE d.id = ? AND d.expediente_id = ? AND d.subido_por = ? AND e.asesor_id = ?
    LIMIT 1
";
$stmtDoc = mysqli_prepare($conexion, $sqlDoc);
if ($stmtDoc) {
    mysqli_stmt_bind_param($stmtDoc, "iiii", $doc_id, $exp_id, $uid, $uid);
    mysqli_stmt_execute($stmtDoc);
    $doc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtDoc));
    mysqli_stmt_close($stmtDoc);

    if ($doc) {
        // 1) Borrado físico del archivo en disco (carpetas posibles)
        $nombreArchivo = basename((string)$doc['archivo']);
        $rutas = [
            __DIR__ . '/../files/' . $nombreArchivo,
            __DIR__ . '/../uploads/docs/' . $nombreArchivo,
        ];

        foreach ($rutas as $ruta) {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        // 2) Borrado lógico del registro en base de datos
        $stmtDelete = mysqli_prepare($conexion, "DELETE FROM documentos WHERE id = ? LIMIT 1");
        if ($stmtDelete) {
            mysqli_stmt_bind_param($stmtDelete, "i", $doc_id);
            mysqli_stmt_execute($stmtDelete);
            mysqli_stmt_close($stmtDelete);
        }

        // 3) Recalcular último documento del asesor para mantener coherente archivo_resolucion
        $ultimoArchivo = null;
        $sqlUltimo = "
            SELECT d.archivo
            FROM documentos d
            INNER JOIN usuarios u ON u.id = d.subido_por
            WHERE d.expediente_id = ? AND u.rol = 'asesor'
            ORDER BY d.fecha DESC, d.id DESC
            LIMIT 1
        ";
        $stmtUltimo = mysqli_prepare($conexion, $sqlUltimo);
        if ($stmtUltimo) {
            mysqli_stmt_bind_param($stmtUltimo, "i", $exp_id);
            mysqli_stmt_execute($stmtUltimo);
            $rowUltimo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUltimo));
            mysqli_stmt_close($stmtUltimo);
            if ($rowUltimo && !empty($rowUltimo['archivo'])) {
                $ultimoArchivo = $rowUltimo['archivo'];
            }
        }

        // Si no quedan documentos del asesor, archivo_resolucion quedará en NULL
        $stmtUpdate = mysqli_prepare($conexion, "UPDATE expedientes SET archivo_resolucion = ? WHERE id = ? AND asesor_id = ?");
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, "sii", $ultimoArchivo, $exp_id, $uid);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }
    }
}

header("Location: ver-expediente.php?id=$exp_id");
exit;
