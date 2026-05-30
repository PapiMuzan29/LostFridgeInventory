<?php

session_start();

if (!isset($_SESSION['apodoUsuario'])) {

    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/../Services/usuariosServicio.php';

$service = new usuariosServicio();

$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';

$usuarios = $service->getUsers($busqueda, $estado);
$stats = $service->getStats();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>ubicaciones</title>
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/usuarios.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
</head>

<body>
    <?php include 'assets/barraNavegacion.php';?>
</body>
</html>