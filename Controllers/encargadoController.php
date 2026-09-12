<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    case 'listarInvTemporal':
        try {
            $inventario = $modelo->obtenerInventarioTemporalSalMazo();
            echo json_encode(['success' => true, 'inventario' => $inventario]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizarInvTemporal':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }
        
        $idTemp = (int)($_POST['id_temporal'] ?? 0);
        $idProd = (int)($_POST['id_producto'] ?? 0); // <-- NUEVO
        $piezas = (int)($_POST['piezas'] ?? 0);
        $cajas = (int)($_POST['cajas'] ?? 0);
        $kilos = (float)($_POST['kilos'] ?? 0);

        // Ahora evaluamos si tenemos cualquiera de los dos IDs
        if ($idTemp > 0 || $idProd > 0) {
            try {
                $modelo->actualizarInventarioTemporal($idTemp, $idProd, $piezas, $cajas, $kilos);
                echo json_encode(['success' => true, 'message' => 'Inventario ajustado correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos de producto inválidos.']);
        }
        break;
    case 'verificarAutorizaciones':
        try {
            $total = $modelo->contarAutorizacionesPendientes();
            echo json_encode(['success' => true, 'total' => $total]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'listarAutorizaciones':
        try {
            $notas = $modelo->obtenerAutorizacionesPendientes();
            echo json_encode(['success' => true, 'notas' => $notas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

   case 'procesarAutorizacion':
        $idNota = (int)($_POST['id_nota'] ?? 0);
        $accionAuth = $_POST['accion_auth'] ?? ''; // 'aprobar' o 'denegar'

        if ($accionAuth === 'aprobar') {
            $pwd = $_POST['password'] ?? '';
            
            // 1. Obtenemos el ID del Encargado desde su propia sesión
            $idUsuarioActual = (int)($_SESSION['idCuenta'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
            
            if ($idUsuarioActual === 0) {
                echo json_encode(['success' => false, 'message' => 'Error de sesión: No se detectó tu usuario activo.']);
                exit;
            }

            // 2. Traemos la contraseña de la base de datos
            $hashBD = $modelo->obtenerHashEncargado($idUsuarioActual);
            
            if (empty($hashBD)) {
                echo json_encode(['success' => false, 'message' => 'Error BD: No se encontró la contraseña del usuario.']);
                exit;
            }

            // 3. Verificamos la contraseña (Soporta bcrypt, texto plano y MD5)
            if (password_verify($pwd, $hashBD) || $pwd === $hashBD || md5($pwd) === $hashBD) {
                $modelo->cambiarEstadoNota($idNota, 'PENDIENTE');
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta. Inténtalo de nuevo.']);
            }
            
        } elseif ($accionAuth === 'denegar') {
            $modelo->cambiarEstadoNota($idNota, 'CANCELADA');
            
            require_once __DIR__ . '/../Models/modeloVendedor.php';
            $mv = new modeloVendedor();
            $mv->reversarInventarioNota($idNota);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción desconocida.']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}