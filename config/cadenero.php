<?php
// seguridad_roles.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Si a la vista se le olvidó definir la variable de roles, bloqueamos por seguridad
if (!isset($rolesPermitidos) || !is_array($rolesPermitidos)) {
    header("Location: /LostFridgeInventory/Controllers/LoginController.php?action=logout");
    exit;
}

// 2. Verificamos si existe un usuario logueado con un rol
$rolUsuario = (int)($_SESSION['idRol'] ?? 0); 

// 3. Si no tiene el rol correcto o no está logueado, lo expulsamos
if (!in_array($rolUsuario, $rolesPermitidos, true)) {
    header("Location: /LostFridgeInventory/Controllers/LoginController.php?action=logout");
    exit;
}
?>