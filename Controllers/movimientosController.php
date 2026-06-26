<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

if (!isset($_SESSION['apodoUsuario'])) {
    echo json_encode(['error' => 'Acceso denegado. No se encontró una sesión activa.']);
    exit;
}

try {
    require_once __DIR__ . '/../Services/movimientosServicio.php';
    $service = new movimientosServicio();
    
    $action = $_GET['action'] ?? '';

    if ($action === 'buscar') {
        $tipo = $_GET['tipo_movimiento'] ?? 'todos';
        $usuario = $_GET['busqueda_usuario'] ?? '';
        $fecha = $_GET['fecha_filtro'] ?? date('Y-m-d');
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

        $resultado = $service->consultarBitacora($tipo, $usuario, $fecha, $pagina);
        $contadores = $service->obtenerResumenTarjetas($fecha);

        echo json_encode([
            'datos' => $resultado['datos'],
            'paginas' => $resultado['paginas'],
            'total' => $resultado['total'],
            'contadores' => $contadores
        ]);
        exit;
    }

    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}