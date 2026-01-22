<?php

$password = "admin";
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Hashed Password: $hash\n";

?>
