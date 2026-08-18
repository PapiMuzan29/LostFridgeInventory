<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_Usuario'])) {
    header("Location: /LostFridgeInventory/Views/login.php");
    exit();
}

$rolUsuario = $_SESSION['nombreRol'] ?? '';

$archivoActual = basename($_SERVER['PHP_SELF']);

$paginasSoloAdmin = [
    //LostFridge
    'configuracion.php',
    'inicio.php',
    'movimientos.php',
    'reportes.php',
    'ubicaciones.php',
    'usuarios.php',
];

switch ($rolUsuario) {
    case 'Administrador':
        header("Location: /LostFridgeInventory/Views/notasSystem.php");
        exit();
        break;
    case 'Vendedor':
        header("Location: /LostFridgeInventory/Views/notasSystem.php");
        exit();
        break;
           
    default:
        # code...
        break;
}

?>