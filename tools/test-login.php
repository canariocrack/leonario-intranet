<?php
require_once __DIR__ . '/../config/database.php';

$email = 'admin@leonario.com';
$pass  = 'Leonario123!';

$q = $pdo->prepare("SELECT * FROM usuarios WHERE email=?");
$q->execute([$email]);
$u = $q->fetch();

var_dump($u);
echo '<br>';

if ($u && password_verify($pass, $u['password'])) {
  echo 'PASSWORD OK';
} else {
  echo 'PASSWORD FAIL';
}
