<?php
// tools/seed_users.php
// Ejecuta una vez en el navegador y luego borra este archivo por seguridad.

require_once __DIR__ . '/../config/database.php';

$passPlano = 'Leonario123!';
$hash = password_hash($passPlano, PASSWORD_DEFAULT);

$users = [
  ['Administrador', 'admin@leonario.com', $hash, 'admin'],
  ['Asesor Principal', 'asesor@leonario.com', $hash, 'asesor'],
  ['Cliente Demo', 'cliente@leonario.com', $hash, 'cliente'],
];

echo "<h2>Seeder de usuarios</h2>";
echo "<p>Contraseña común: <b>" . htmlspecialchars($passPlano) . "</b></p>";

try {
  // Evita duplicados por email: si existe, actualiza password y rol
  $stmt = $pdo->prepare("
    INSERT INTO usuarios (nombre, email, password, rol)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      nombre = VALUES(nombre),
      password = VALUES(password),
      rol = VALUES(rol)
  ");

  foreach ($users as $u) {
    $stmt->execute([$u[0], $u[1], $u[2], $u[3]]);
    echo "<p>✅ Usuario listo: <b>" . htmlspecialchars($u[1]) . "</b> (" . htmlspecialchars($u[3]) . ")</p>";
  }

  echo "<hr>";
  echo "<p><b>Listo.</b> Ahora entra a login y prueba.</p>";
  echo "<p>Después, BORRA este archivo: <code>tools/seed_users.php</code></p>";

} catch (PDOException $e) {
  echo "<p style='color:red;'><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
  echo "<p>Si te da error de DUPLICATE KEY, asegúrate de que <b>email</b> es UNIQUE en la tabla usuarios.</p>";
}
