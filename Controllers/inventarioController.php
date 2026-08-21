<?php
// inventarioController.php
require_once __DIR__ . '/../Models/modeloInventario.php';

$service = new modeloInventario();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'busqueda':
        $tipo = $_GET['tipo_busqueda'] ?? 'producto';
        $busqueda = $_GET['busqueda'] ?? '';
        $estado = $_GET['estado'] ?? '';
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

        if ($tipo === 'proveedor') {
            $datos = $service->getAllProviders($busqueda, $estado, $pagina);
        } else {
            $datos = $service->getProducts($busqueda, $estado, $pagina);
        }
        
        echo json_encode(['datos' => $datos, 'totalPaginas' => 1, 'pagina' => $pagina]);
        break;

    case 'crearProducto':
        try {
            $service->agregarProducto($_POST);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizarProducto':
        try {
            $id = (int)$_POST['idProducto'];
            $service->actualizarProducto($id, $_POST);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminarProducto':
        try {
            $id = (int)$_GET['id'];
            $service->eliminarProducto($id);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'agregarProveedor':
        try {
            $service->agregarProveedor($_POST);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    // 🔥 AGREGAMOS ESTE BLOQUE NUEVO PARA ACTUALIZAR PROVEEDORES
    case 'actualizarProveedor':
        try {
            $id = (int)$_POST['idProveedor']; // Asegúrate de que el input hidden se llame idProveedor
            $service->actualizarProveedor($id, $_POST); // Asegúrate de tener este método en tu modeloInventario
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminarProveedor':
        try {
            $id = (int)$_GET['id'];
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