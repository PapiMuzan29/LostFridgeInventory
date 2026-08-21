<?php
// Controllers/EncargadoController.php
require_once '../Models/modeloEncargado.php';

header('Content-Type: application/json'); 
$action = $_GET['action'] ?? '';
$modelo = new modeloEncargado();

switch ($action) {
    case 'listar':
        try {
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            if ($pagina < 1) $pagina = 1;
            
            // Recibimos el texto de búsqueda
            $busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

            $limite = 3;
            $offset = ($pagina - 1) * $limite;

            // Le pasamos la búsqueda a ambas funciones
            $totalProductos = $modelo->obtenerTotalProductos($busqueda);
            $totalPaginas = ceil($totalProductos / $limite);

            $productos = $modelo->obtenerPaginados($limite, $offset, $busqueda);

            echo json_encode([
                'success' => true, 
                'productos' => $productos,
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizarEstado':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $idProducto = $_POST['id_producto'] ?? null;
        $porPiezas = $_POST['por_piezas'] ?? null;

        if (!$idProducto || $porPiezas === null) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
            exit;
        }

        try {
            $resultado = $modelo->actualizarEstadoContable($idProducto, $porPiezas);
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar o no hubo cambios.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'listarRevision':
        try {
            // Recibimos el texto de búsqueda (si existe)
            $busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
            
            // Buscamos las que están en estado COBRADO, pasándole la búsqueda
            $notas = $modelo->obtenerNotasPorEstado('COBRADO', $busqueda);
            
            echo json_encode(['success' => true, 'notas' => $notas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    break;

    // CASO 2: Aprobar una nota
    case 'aprobar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $idNota = $_POST['id_nota'] ?? null;

        if (!$idNota) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID de la nota.']);
            exit;
        }

        try {
            $resultado = $modelo->actualizarEstadoNota($idNota, 'APROBADO');
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Nota aprobada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo aprobar la nota o ya estaba aprobada.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}