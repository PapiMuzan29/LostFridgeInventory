<?php
// Controllers/EncargadoController.php
require_once '../Models/modeloEncargado.php';

header('Content-Type: application/json; charset=utf-8'); 
$action = $_GET['action'] ?? '';

try {
    $modelo = new modeloEncargado();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit;
}

switch ($action) {
    case 'listar':
        try {
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            if ($pagina < 1) $pagina = 1;
            
            $busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
            $limite = 3;
            $offset = ($pagina - 1) * $limite;

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
        $factura = $_POST['factura'] ?? null;

        if (!$idProducto || $porPiezas === null || $factura === null) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        try {
            $resultado = $modelo->actualizarEstadoProducto($idProducto, $porPiezas, $factura);
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'listarRevision':
        try {
            $busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
            $notas = $modelo->obtenerNotasPorEstado('COBRADO', $busqueda);
            echo json_encode(['success' => true, 'notas' => $notas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verificarFactura': 
        $idNota = $_GET['id_nota'] ?? null;
        if ($idNota) {
            try {
                $requiere = $modelo->requiereFactura($idNota);
                echo json_encode(['success' => true, 'requiere_factura' => $requiere]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Falta ID de nota.']);
        }
        break;

    case 'aprobar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $idNota = $_POST['id_nota'] ?? null;
        $folios = $_POST['folios'] ?? [];

        // Filtramos elementos vacíos enviando únicamente cadenas válidas
        $foliosFiltrados = array_filter(array_map('trim', (array)$folios), function($val) {
            return $val !== '';
        });

        if (!$idNota || empty($foliosFiltrados)) {
            echo json_encode(['success' => false, 'message' => 'Debe ingresar al menos un folio válido.']);
            exit;
        }

        try {
            $resultado = $modelo->aprobarNotaConFolios($idNota, $foliosFiltrados);
            echo json_encode(['success' => true, 'message' => 'Nota aprobada exitosamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verificarNuevasNotas':
        try {
            $sql = "SELECT COUNT(*) as total FROM notas WHERE estado = 'COBRADO'";
            
            $totalNotas = $modelo->contarNotasPorEstado('COBRADO'); 

            echo json_encode(['success' => true, 'total' => $totalNotas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}