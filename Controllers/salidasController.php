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

    // OBTENER CLIENTES PARA EL SELECT
    if ($action === 'obtenerClientes') {
        $datos = $service->listarClientesParaSelect();
        echo json_encode($datos);
        exit;
    }

    // BUSCAR PRODUCTO PARA SALIDA (VALIDADO POR CLIENTE Y PESO EXACTO)
    if ($action === 'buscarProducto') {
        $codigoTrama = $_GET['codigo'] ?? '';
        $idCliente = $_GET['idCliente'] ?? '';

        if (empty($codigoTrama) || empty($idCliente)) {
            echo json_encode(['error' => 'Datos incompletos.']);
            exit;
        }
        
        // 1. Buscamos el producto filtrando por el cliente/proveedor
        // Asegúrate de que tu método obtenerProductoPorCodigo en el servicio
        // realice un: WHERE codigo = :codigo AND idCliente = :idCliente
        $producto = $service->obtenerProductoPorCodigo($codigoTrama, $idCliente);
        
        if (!$producto) {
            echo json_encode(['error' => 'Producto no encontrado o no pertenece a este proveedor.']);
            exit;
        }

        // 2. EXTRACCIÓN DE PESO EXACTA (Basado en trama 071641041752 -> 16.41)
        // Posiciones: Indice 2 y 3 para "16", Indice 4 y 5 para "41"
        $enteros = "0";
        $decimales = "00";

        if (strlen($codigoTrama) >= 6) {
            $enteros = substr($codigoTrama, 2, 2); 
            $decimales = substr($codigoTrama, 4, 2);
        }

        // 3. Respuesta final
        echo json_encode([
            'codigoProducto' => $producto['codigoProducto'] ?? $producto['codigo'] ?? $codigoTrama,
            'nombreProducto' => $producto['nombreProducto'] ?? $producto['nombre'] ?? 'Producto Desconocido',
            'codigoEnteros'  => (string)$enteros,
            'codigoDecimales' => (string)$decimales
        ]);
        exit;
    }

    // GUARDAR SALIDA
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

        $idUsuario = $_SESSION['idCuenta'] ?? 2;
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