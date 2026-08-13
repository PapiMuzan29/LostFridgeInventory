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

    // 1. OBTENER PROVEEDORES
    if ($action === 'obtenerProveedores') {
        $proveedores = $service->listarProveedoresParaSelect();
        echo json_encode($proveedores);
        exit;
    }

    // 2. BUSCAR PRODUCTO POR CÓDIGO
    if ($action === 'buscarProducto') {
        $codigo = $_GET['codigo'] ?? '';
        $idProveedor = $_GET['idProveedor'] ?? '';

        if (empty($codigo)) {
            echo json_encode(['error' => 'No se proporcionó ningún código de producto.']);
            exit;
        }

        $producto = $service->obtenerProductoPorCodigo($codigo, $idProveedor);

        if ($producto) {
            require_once __DIR__ . '/../Models/modeloEntradas.php';
            $modeloDebug = new modeloEntradas();
            $cortes = $modeloDebug->obtenerCodigoDeProducto('idProveedor', $idProveedor, $codigo);
            
            $producto['codigoEnteros'] = $cortes['codigoEnteros'] ?? '0';
            $producto['codigoDecimales'] = $cortes['codigoDecimales'] ?? '00';
            $producto['codigoProducto'] = $cortes['codigoProducto'] ?? $codigo;
            
            echo json_encode($producto);
        } else {
            echo json_encode(['error' => 'Producto no encontrado.']);
        }
        exit;
    }

    // 3. GUARDAR ENTRADA (NUEVO BLOQUE)
    if ($action === 'guardarEntrada') {
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

        // Asumimos que guardas el id de cuenta en la sesión, si no, ajusta la variable
        $idUsuario = $_SESSION['idCuenta'] ?? 4; 
        
        // Llamamos al servicio para realizar la transacción SQL
        $resultado = $service->registrarEntradaCompleta($datos, $idUsuario, $_SESSION['apodoUsuario']);
        
        echo json_encode($resultado);
        exit;
    }

    // Si mandan una acción desconocida
    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    error_log("Error fatal en entradasController: " . $e->getMessage());
    echo json_encode(['error' => 'Falla interna del servidor.']);
    exit;
}