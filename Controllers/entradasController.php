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
            // 🎯 AGREGAMOS ESTO PARA VER EL DIAGNÓSTICO EN VIVO AUNQUE HAYA ÉXITO
            require_once __DIR__ . '/../Models/modeloEntradas.php';
            $modeloDebug = new modeloEntradas();
            $cortes = $modeloDebug->obtenerCodigoDeProducto('idProveedor', $idProveedor, $codigo);
            
            // Inyectamos los cortes en la respuesta exitosa
            $producto['codigoEnteros'] = $cortes['codigoEnteros'] ?? '0';
            $producto['codigoDecimales'] = $cortes['codigoDecimales'] ?? '00';
            $producto['codigoProducto'] = $cortes['codigoProducto'] ?? $codigo;
            
            echo json_encode($producto);
        } else {
            // ... (Tu bloque else de depuración se queda igual)
        }
        exit;
    }

    // Si mandan una acción desconocida
    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    // Captura cualquier falla interna y evita que muera el canal HTTP
    error_log("Error fatal en entradasController: " . $e->getMessage());
    echo json_encode(['error' => 'Falla interna del servidor.']);
    exit;
}