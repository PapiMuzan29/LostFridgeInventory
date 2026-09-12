<?php
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['apodoUsuario'])) {
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

try {
    require_once __DIR__ . '/../Services/salidasServicio.php';
    require_once __DIR__ . '/../Config/BD.php'; 
    $service = new salidasServicio();
    
    $action = $_GET['action'] ?? '';

    // OBTENER CLIENTES PARA EL SELECT
    if ($action === 'obtenerClientes') {
        $datos = $service->listarClientesParaSelect();
        echo json_encode($datos);
        exit;
    }

    // 🎯 OBTENER COMBOS Y LOTES DISPONIBLES PARA EL SELECT DE SALIDAS
    if ($action === 'obtenerCombosDisponibles') {
        $datos = $service->listarCombosDisponiblesParaSalida();
        echo json_encode($datos);
        exit;
    }

    // 🛢️ OBTENER MANTECAS DISPONIBLES PARA EL SELECT DE SALIDAS (POR BOTE/PRODUCTO)
    if ($action === 'obtenerMantecaDisponibles') {
        $datos = $service->listarMantecaDisponiblesParaSalida();
        echo json_encode($datos);
        exit;
    }

    // BUSCAR PRODUCTO PARA SALIDA
    if ($action === 'buscarProducto') {
        $codigoTrama = $_GET['codigo'] ?? '';
        $idCliente = $_GET['idCliente'] ?? '';

        if (empty($codigoTrama) || empty($idCliente)) {
            echo json_encode(['error' => 'Datos incompletos.']);
            exit;
        }

        $longitudTotal = mb_strlen($codigoTrama, 'UTF-8');
        
        $inicioCodigo = $longitudTotal >= 4 ? $longitudTotal - 4 : 0;
        $codigoProductoExtraido = mb_substr($codigoTrama, $inicioCodigo, 4, 'UTF-8');

        $enteros = "0";
        $decimales = "00";

        if ($longitudTotal >= 12) {
            $enteros = mb_substr($codigoTrama, 5, 5, 'UTF-8');
            $decimales = mb_substr($codigoTrama, 10, 2, 'UTF-8');
        }

        $producto = $service->obtenerProductoPorCodigo($codigoProductoExtraido, $idCliente);
        
        if (!$producto) {
            echo json_encode(['error' => 'Producto no encontrado o no pertenece a este proveedor. (Código buscado: ' . $codigoProductoExtraido . ')']);
            exit;
        }

        echo json_encode([
            'codigoProducto'  => $producto['codigoProducto'] ?? $codigoProductoExtraido,
            'nombreProducto'  => $producto['nombreProducto'] ?? 'Producto Desconocido',
            'codigoEnteros'   => ltrim($enteros, '0') ?: '0',
            'codigoDecimales' => $decimales
        ]);
        exit;
    }

    // GUARDAR SALIDA O TRASPASO A MAYOREO
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

        $conceptoSalida = $datos['concepto'] ?? '';
        $tipoDespacho = $datos['tipo_despacho'] ?? '';

        // 🚨 PRUEBA DE FUEGO PARA DEPURAR: Descomenta la siguiente línea si quieres ver exactamente qué JSON llega a tu pantalla
        // echo json_encode(['error' => 'JSON RECIBIDO: ' . $json]); exit;

        // 📦 SI EL CONCEPTO ES TRASPASO A MAYOREO -> GUARDAR EN TEMPORAL Y RESTAR STOCK / LOTES
        if ($conceptoSalida === 'inventariotemporalsalida') {
            try {
                $db = BD::obtenerInstancia();
                $db->beginTransaction();

                foreach ($datos['detalle'] as $item) {
                    $idProducto = intval($item['id_producto'] ?? ($item['idProducto'] ?? ($item['id'] ?? ($item['id_lote'] ?? 0))));
                    $idLote = intval($item['id_lote'] ?? ($item['idLote'] ?? 0));
                    $cantidadCajas = intval($item['cantidad'] ?? ($item['cajas'] ?? ($item['cantidadCajas'] ?? 1)));
                    $cantidadPeso = floatval($item['kgs'] ?? ($item['peso'] ?? 0.00));
                    $cantidadPiezas = intval($item['piezas'] ?? 0);
                    
                    // Si el idProducto viene en 0 pero hay código, intentamos buscarlo
                    if ($idProducto <= 0 && (!empty($item['codigo_producto']) || !empty($item['codigoProducto']))) {
                        $codigoBusqueda = trim($item['codigo_producto'] ?? $item['codigoProducto']);
                        $stmtBuscaProd = $db->prepare("SELECT idProducto FROM producto WHERE TRIM(codigoProducto) = ? LIMIT 1");
                        $stmtBuscaProd->execute([$codigoBusqueda]);
                        $prodEncontrado = $stmtBuscaProd->fetch(PDO::FETCH_ASSOC);
                        if ($prodEncontrado) {
                            $idProducto = intval($prodEncontrado['idProducto']);
                        }
                    }

                    $observaciones = "Traspaso a mayoreo - Cliente ID: " . ($datos['id_cliente'] ?? 'General');

                    // 1. Insertar en la tabla temporal de salida
                    $queryTemp = "INSERT INTO inventariotemporalsalida 
                                  (idProducto, cantidadCajas, cantidadPeso, cantidadPiezas, observaciones) 
                                  VALUES (:idProducto, :cantidadCajas, :cantidadPeso, :cantidadPiezas, :observaciones)";
                    
                    $db->consulta($queryTemp, [
                        ':idProducto' => $idProducto > 0 ? $idProducto : null,
                        ':cantidadCajas' => $cantidadCajas,
                        ':cantidadPeso' => $cantidadPeso,
                        ':cantidadPiezas' => $cantidadPiezas,
                        ':observaciones' => $observaciones
                    ]);

                    // 2. Descontar stock general de la tabla producto
                    if ($idProducto > 0) {
                        $queryUpdateProd = "UPDATE producto 
                                            SET totalCajas = GREATEST(0, IFNULL(totalCajas, 0) - ?), 
                                                totalPeso = GREATEST(0, IFNULL(totalPeso, 0) - ?) 
                                            WHERE idProducto = ?";
                        $db->consulta($queryUpdateProd, [$cantidadCajas, $cantidadPeso, $idProducto]);
                    }

                    // 3. Descontar peso y desactivar el lote si aplica (tabla lote)
                    if ($idLote > 0) {
                        $queryUpdateLote = "UPDATE lote 
                                            SET pesoActual = GREATEST(0, pesoActual - ?), 
                                                activo = IF(pesoActual - ? <= 0, 0, 1) 
                                            WHERE idLote = ?";
                        $db->consulta($queryUpdateLote, [$cantidadPeso, $cantidadPeso, $idLote]);
                    }
                }

                $db->commit();
                echo json_encode([
                    'success' => true, 
                    'mensaje' => 'Traspaso a mayoreo almacenado y stock/lotes descontados correctamente.'
                ]);
                exit;

            } catch (Exception $ex) {
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                echo json_encode(['error' => 'Error al procesar el traspaso temporal: ' . $ex->getMessage()]);
                exit;
            }
        }

        // 🛢️ FLUJO NORMAL (Ventas, Mermas, Morelos, Combos y Manteca)
        $idUsuario = $_SESSION['idCuenta'] ?? 2;
        $apodoUsuario = $_SESSION['apodoUsuario'] ?? 'Usuario';
        
        $resultado = $service->registrarSalidaCompleta($datos, $idUsuario, $apodoUsuario);
        
        echo json_encode($resultado);
        exit;
    }

    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    error_log("Error en salidasController: " . $e->getMessage());
    echo json_encode(['error' => 'Falla interna del servidor: ' . $e->getMessage()]);
    exit;
}
?>