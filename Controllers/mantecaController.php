<?php
header('Content-Type: application/json; charset=utf-8');

try {
    // Rutas exactas con tus nombres de archivo y carpeta
    require_once __DIR__ . '/../Config/BD.php'; 
    require_once __DIR__ . '/../Models/modeloManteca.php';

    // Obtener la instancia Singleton de tu clase BD
    $db = BD::obtenerInstancia();

    if (!$db || !$db->conexionActiva()) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    $modelo = new modeloManteca($db);
    $accion = $_GET['accion'] ?? '';

    if ($accion === 'consultarPorFecha') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        $datos = $modelo->obtenerVentasMantecaPorFecha($fecha);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['error' => true, 'mensaje' => 'Acción no válida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>