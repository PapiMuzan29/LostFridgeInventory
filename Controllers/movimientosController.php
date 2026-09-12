<?php
// Controllers/movimientosController.php
session_start();
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['apodoUsuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../Services/movimientosServicio.php';
$movService = new movimientosServicio();

$action = $_GET['action'] ?? 'buscar';
$fecha = $_GET['fecha_filtro'] ?? date('Y-m-d');
$tipo = $_GET['tipo_movimiento'] ?? 'todos';
$usuario = $_GET['busqueda_usuario'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($action === 'buscar' || $action === 'listar') {
    try {
        // 1. Obtener listado de movimientos y paginación
        $resultado = $movService->consultarBitacora($tipo, $usuario, $fecha, $pagina);
        
        // 2. Obtener contadores reales para las tarjetas superiores
        $stats = $movService->obtenerResumenTarjetas($fecha);

        echo json_encode([
            'success' => true,
            'datos' => $resultado['datos'] ?? [],
            'total' => $resultado['total'] ?? 0,
            'paginas' => $resultado['paginas'] ?? 1,
            'contadores' => [
                'movimientosDia' => $stats['movimientosDia'] ?? 0,
                'cajasIngresadas' => $stats['cajasIngresadas'] ?? 0,
                'cajasDespachadas' => $stats['cajasDespachadas'] ?? 0
            ]
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

echo json_encode(['success' => false, 'error' => 'Acción no válida']);
exit();