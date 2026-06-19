<?php
session_start();

// Control de seguridad
if (!isset($_SESSION['apodoUsuario'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../Services/entradasServicio.php';
    $service = new entradasServicio();
    
    $action = $_GET['action'] ?? '';

    if ($action === 'obtenerProveedores') {
        $proveedores = $service->listarProveedores();
        echo json_encode($proveedores);
        exit;
    }

    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}