<?php
session_start();
require_once __DIR__ . '/../Models/modeloSalidasPendientes.php';
require_once __DIR__ . '/../Services/movimientosServicio.php';

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: ../Views/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idInventario = $_POST['id_inventario'] ?? null;
    $codigo = $_POST['codigo'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $cantidad = $_POST['cantidad'] ?? null;
    $cliente = $_POST['cliente_destino'] ?? 'Público General';
    $usuario = $_SESSION['apodoUsuario'];

    if ($idInventario && $cantidad > 0) {
        $modeloPendientes = new modeloSalidasPendientes();
        $movimientos = new movimientosServicio();

        // 1. Registrar en la tabla de salidas pendientes
        $enviado = $modeloPendientes->enviarAPendientes(
            $idInventario, 
            $codigo, 
            $descripcion, 
            $cantidad, 
            $cliente, 
            $usuario
        );

        if ($enviado) {
            // 2. Registrar la acción en la bitácora general
            $movimientos->registrar(
                'salida', 
                $usuario, 
                "Producto enviado a pendientes de venta (Código: {$codigo}, Cantidad: {$cantidad})", 
                'Inventario'
            );

            header("Location: ../Views/inventario.php?envio=success");
            exit();
        }
    }

    header("Location: ../Views/inventario.php?envio=error");
    exit();
}
?>