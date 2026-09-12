<?php
// Evitar que warnings o notices de PHP rompan el formato JSON de salida
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificación de seguridad básica
if (!isset($_SESSION['apodoUsuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../Services/reportesServicio.php';

try {
    $service = new ReportesServicio();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'busqueda':
            $tipoReporte = trim($_GET['tipoReporte'] ?? '');
            $fechaInicio = trim($_GET['fechaInicio'] ?? '');
            $fechaFin    = trim($_GET['fechaFin'] ?? '');
            $pagina      = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1; 

            $resultados = $service->obtenerReportesBusqueda($tipoReporte, $fechaInicio, $fechaFin, $pagina);
            
            echo json_encode($resultados);
            exit;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage(), 'datos' => [], 'totalPaginas' => 1]);
    exit;
}
?>