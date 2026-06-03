<?php
session_start();
require_once __DIR__ . '/../Services/reportesServicio.php';

// Verificación de seguridad básica
if (!isset($_SESSION['apodoUsuario'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$service = new ReportesServicio();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'busqueda':
        $tipoReporte = trim($_GET['tipoReporte'] ?? '');
        $fechaInicio = trim($_GET['fechaInicio'] ?? '');
        $fechaFin    = trim($_GET['fechaFin'] ?? '');
        $pagina      = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1; 

        $resultados = $service->obtenerReportesBusqueda($tipoReporte, $fechaInicio, $fechaFin, $pagina);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultados);

        exit;
        
    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Acción no válida']);
        exit;
}
?>