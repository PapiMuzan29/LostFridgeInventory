<?php
// salidasController.php
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

    // BUSCAR PRODUCTO PARA SALIDA (VALIDADO POR CLIENTE Y PESO EXACTO)
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
        } elseif (strlen($codigoTrama) >= 6) {
            $enteros = substr($codigoTrama, 2, 2); 
            $decimales = substr($codigoTrama, 4, 2);
        }

        $producto = $service->obtenerProductoPorCodigo($codigoProductoExtraido, $idCliente);
        
        if (!$producto) {
            $producto = $service->obtenerProductoPorCodigo($codigoTrama, $idCliente);
        }

        if (!$producto) {
            echo json_encode(['error' => 'Producto no encontrado o no pertenece a este proveedor. (Código buscado: ' . $codigoProductoExtraido . ')']);
            exit;
        }

        echo json_encode([
            'codigoProducto'  => $producto['codigoProducto'] ?? $producto['codigo'] ?? $codigoProductoExtraido,
            'nombreProducto'  => $producto['nombreProducto'] ?? $producto['nombre'] ?? 'Producto Desconocido',
            'codigoEnteros'   => ltrim($enteros, '0') ?: '0',
            'codigoDecimales' => $decimales
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

        // Capturamos el concepto real que manda la interfaz (ej. "Venta", "Traspaso", etc.)
        $conceptoSalida = $datos['concepto'] ?? 'Salida General';

        try {
            $db = BD::obtenerInstancia();
            $db->beginTransaction();

            foreach ($datos['detalle'] as $item) {
                $idProducto = intval($item['id_producto'] ?? ($item['idProducto'] ?? ($item['id'] ?? 0)));
                
                // 🎯 Captura súper robusta del idLote (busca en todas las variantes posibles del JSON)
                $idLote = intval($item['id_lote'] ?? ($item['idLote'] ?? ($item['lote'] ?? 0)));
                
                $cantidadPiezas = intval($item['cantidad'] ?? ($item['cajas'] ?? ($item['cantidadCajas'] ?? ($item['piezas'] ?? 1))));
                $cantidadCajas = intval($item['cajas'] ?? ($item['cantidad'] ?? 0));
                $cantidadPeso = floatval($item['kgs'] ?? ($item['peso'] ?? 0.00));
                
                // Búsqueda de producto por código si viene vacío
                if ($idProducto <= 0 && (!empty($item['codigo_producto']) || !empty($item['codigoProducto']))) {
                    $codigoBusqueda = trim($item['codigo_producto'] ?? $item['codigoProducto']);
                    $stmtBuscaProd = $db->prepare("SELECT idProducto FROM producto WHERE TRIM(codigoProducto) = ? LIMIT 1");
                    $stmtBuscaProd->execute([$codigoBusqueda]);
                    $prodEncontrado = $stmtBuscaProd->fetch(PDO::FETCH_ASSOC);
                    if ($prodEncontrado) {
                        $idProducto = intval($prodEncontrado['idProducto']);
                    }
                }

                // Rescatar el idProducto desde la tabla lote si viene en 0 y tenemos un idLote válido
                if ($idProducto <= 0 && $idLote > 0) {
                    $stmtLoteProd = $db->prepare("SELECT idProducto FROM lote WHERE idLote = ? LIMIT 1");
                    $stmtLoteProd->execute([$idLote]);
                    $loteData = $stmtLoteProd->fetch(PDO::FETCH_ASSOC);
                    if ($loteData) {
                        $idProducto = intval($loteData['idProducto']);
                    }
                }

                // Observaciones limpias usando el concepto real seleccionado por el usuario
                $observaciones = $conceptoSalida . " - Cliente ID: " . ($datos['id_cliente'] ?? 'General');

                // 1. Insertar en la tabla temporal guardando el idLote de forma explícita
                $queryTemp = "INSERT INTO inventariotemporalsalida 
                              (idProducto, idLote, cantidadCajas, cantidadPeso, cantidadPiezas, observaciones) 
                              VALUES (:idProducto, :idLote, :cantidadCajas, :cantidadPeso, :cantidadPiezas, :observaciones)";
                
                $db->consulta($queryTemp, [
                    ':idProducto'     => $idProducto > 0 ? $idProducto : null,
                    ':idLote'         => $idLote > 0 ? $idLote : null,
                    ':cantidadCajas'  => $cantidadCajas,
                    ':cantidadPeso'   => $cantidadPeso,
                    ':cantidadPiezas' => $cantidadPiezas,
                    ':observaciones'  => $observaciones
                ]);

                // 2. Descontar stock general de la tabla producto
                if ($idProducto > 0) {
                    $queryUpdateProd = "UPDATE producto 
                                        SET totalCajas = GREATEST(0, IFNULL(totalCajas, 0) - ?), 
                                            totalPeso = GREATEST(0, IFNULL(totalPeso, 0) - ?) 
                                        WHERE idProducto = ?";
                    $db->consulta($queryUpdateProd, [$cantidadCajas, $cantidadPeso, $idProducto]);
                }

                // 3. Descontar peso y actualizar estado del lote (tabla lote)
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
                'mensaje' => 'Salida procesada correctamente con su lote y stock actualizados.'
            ]);
            exit;

        } catch (Exception $ex) {
            if (isset($db) && method_exists($db, 'inTransaction') && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['error' => 'Error al procesar la salida: ' . $ex->getMessage()]);
            exit;
        }
    }

    echo json_encode(['error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    error_log("Error en salidasController: " . $e->getMessage());
    echo json_encode(['error' => 'Falla interna del servidor: ' . $e->getMessage()]);
    exit;
}
?>