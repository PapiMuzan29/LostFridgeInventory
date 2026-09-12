<?php
// inventarioController.php
require_once __DIR__ . '/../Models/modeloInventario.php';

$service = new modeloInventario();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

// Función auxiliar para obtener datos ya sea por $_POST o JSON (fetch)
function obtenerDatosEntrada() {
    if (!empty($_POST)) {
        return $_POST;
    }
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

switch ($action) {
    case 'busqueda':
        $tipoBusqueda = $_GET['tipo_busqueda'] ?? 'producto'; // 'producto' o 'proveedor'
        $tipoInventario = $_GET['tipo_inventario'] ?? 'cajas'; // 'cajas', 'pierna', 'codillo'
        $busqueda = $_GET['busqueda'] ?? '';
        $estado = $_GET['estado'] ?? '';
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

        if ($tipoBusqueda === 'proveedor') {
            $datos = $service->getAllProviders($busqueda, $estado, $pagina);
        } else {
            // Pasamos el tipo de inventario (cajas, pierna, codillo) al modelo
            if (method_exists($service, 'getProductsByType')) {
                $datos = $service->getProductsByType($tipoInventario, $busqueda, $estado, $pagina);
            } else {
                $datos = $service->getProducts($busqueda, $estado, $pagina);
            }
        }
        
        echo json_encode(['datos' => $datos, 'totalPaginas' => 1, 'pagina' => $pagina]);
        break;

    case 'crearProducto':
        try {
            $datos = obtenerDatosEntrada();
            if (empty($datos)) {
                throw new Exception("No se recibieron datos para crear el producto.");
            }

            $service->agregarProducto($datos);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizarProducto':
        try {
            $datos = obtenerDatosEntrada();
            $id = (int)($datos['idProducto'] ?? $_POST['idProducto'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception("ID de producto no válido para actualizar.");
            }

            $service->actualizarProducto($id, $datos);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminarProducto':
        try {
            $id = (int)($_GET['id'] ?? 0);
            $service->eliminarProducto($id);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'agregarProveedor':
        try {
            $datos = obtenerDatosEntrada();
            if (empty($datos)) {
                throw new Exception("No se recibieron datos para el proveedor.");
            }

            $service->agregarProveedor($datos);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizarProveedor':
        try {
            $datos = obtenerDatosEntrada();
            $id = (int)($datos['idProveedor'] ?? $_POST['idProveedor'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception("ID de proveedor no válido para actualizar.");
            }

            $service->actualizarProveedor($id, $datos);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminarProveedor':
        try {
            $id = (int)($_GET['id'] ?? 0);
            $service->eliminarProveedor($id);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}