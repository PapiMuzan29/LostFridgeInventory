<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../Services/salidasServicio.php';
    $service = new salidasServicio();
    
    $action = $_GET['action'] ?? '';

    // OBTENER CLIENTES (O PROVEEDORES SEGÚN TU VISTA)
    if ($action === 'obtenerClientes' || $action === 'obtenerProveedores') {
        $datos = $service->listarClientesParaSelect(); // O tu método correspondiente
        echo json_encode($datos);
        exit;
    }
    // OBTENER CLIENTES O PROVEEDORES PARA EL SELECT
    if ($action === 'obtenerClientes' || $action === 'obtenerProveedores') {
        require_once __DIR__ . '/../Services/salidasServicio.php';
        $service = new salidasServicio();
        $datos = $service->listarClientesParaSelect();
        echo json_encode($datos);
        exit;
    }

    // BUSCAR PRODUCTO PARA SALIDA
    if ($action === 'buscarProducto') {
        $codigo = $_GET['codigo'] ?? '';
        if (empty($codigo)) {
            echo json_encode(['error' => 'Código vacío.']);
            exit;
        }
        $producto = $service->obtenerProductoPorCodigo($codigo);
        echo json_encode($producto ?: ['error' => 'No encontrado']);
        exit;
    }

    // GUARDAR SALIDA Y RESTAR INVENTARIO
    if ($action === 'guardarSalida') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido.']);
            exit;
        }

        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);

        if (!$datos || empty($datos['detalle'])) {
            echo json_encode(['error' => 'No se recibieron datos válidos.']);
            exit;
        }

        $idUsuario = $_SESSION['idCuenta'] ?? 2; // Ajusta según tu ID de usuario de prueba
        $resultado = $service->registrarSalidaCompleta($datos, $idUsuario, $_SESSION['apodoUsuario'] ?? 'Usuario');
        
        echo json_encode($resultado);
        exit;
    }

    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    error_log("Error en salidasController: " . $e->getMessage());
    echo json_encode(['error' => 'Falla interna del servidor.']);
    exit;
}