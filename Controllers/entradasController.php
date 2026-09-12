<?php
// Controllers/entradasController.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

try {
    require_once __DIR__ . '/../Services/entradasServicio.php';
    require_once __DIR__ . '/../Services/movimientosServicio.php'; // 👈 Importamos el servicio de movimientos
    
    $service = new entradasServicio();
    $movService = new movimientosServicio(); // 👈 Instanciamos el servicio
    
    $action = $_GET['action'] ?? '';

    // 1. OBTENER PROVEEDORES
    if ($action === 'obtenerProveedores') {
        header('Content-Type: application/json; charset=utf-8');
        $proveedores = $service->listarProveedoresParaSelect();
        echo json_encode($proveedores);
        exit;
    }

    // 2. BUSCAR PRODUCTO POR CÓDIGO
    if ($action === 'buscarProducto') {
        header('Content-Type: application/json; charset=utf-8');
        $codigo = $_GET['codigo'] ?? '';
        $idProveedor = $_GET['idProveedor'] ?? '';

        if (empty($codigo)) {
            echo json_encode(['error' => 'No se proporcionó ningún código de producto.']);
            exit;
        }

        try {
            $producto = $service->obtenerProductoPorCodigo($codigo, $idProveedor);

            if ($producto) {
                echo json_encode($producto);
            } else {
                echo json_encode(['error' => 'Producto no encontrado o configuración incompleta.']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error en servicio: ' . $e->getMessage()]);
        }
        exit;
    }

    // 3. GUARDAR ENTRADA (UNIFICADA E INTELIGENTE PARA CAJAS Y COMBOS)
    if ($action === 'guardarEntrada' || $action === 'guardarEntradaCombo' || $action === 'guardarEntradaCombos') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$datos || empty($datos['detalle'])) {
            echo json_encode(['success' => false, 'error' => 'Método no permitido o datos vacíos.']);
            exit;
        }

        $idUsuario = $_SESSION['idCuenta'] ?? 4; 
        $apodo = $_SESSION['apodoUsuario'] ?? 'operador';

        // 🎯 DETECCIÓN AUTOMÁTICA: Si el detalle trae campos de báscula/combo, se va por el carril de combos
        $primerItem = $datos['detalle'][0] ?? [];
        $esCombo = isset($primerItem['peso_bruto']) || isset($primerItem['pesoBruto']) || isset($primerItem['lb_tara']) || $action === 'guardarEntradaCombo' || $action === 'guardarEntradaCombos';

        if ($esCombo) {
            $resultado = $service->registrarEntradaCombosCompleta($datos, $idUsuario, $apodo);
            $tipoModo = 'Combos';
        } else {
            $resultado = $service->registrarEntradaCompleta($datos, $idUsuario, $apodo);
            $tipoModo = 'Estándar (Cajas)';
        }
        
        // Si se guardó correctamente, registramos el movimiento en la bitácora automáticamente
        if (isset($resultado['success']) && $resultado['success'] === true) {
            $folioGenerado = $resultado['folio'] ?? 'N/D';
            $totalKgs = $datos['total_kgs'] ?? 0;

            $movService->registrarMovimiento(
                'entrada',
                $apodo,
                "Se registró Entrada {$tipoModo} - Folio: {$folioGenerado} (Kgs: {$totalKgs})",
                'Entradas',
                ['folio' => $folioGenerado, 'tipo' => $tipoModo, 'total_kgs' => $totalKgs]
            );
        }

        echo json_encode($resultado);
        exit;
    }

    // 4. EXPORTAR COMBOS DE ENTRADA A EXCEL (.XLS) CON FÓRMULAS DIRECTAS
    if ($action === 'exportarExcelCombos') {
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);

        $proveedor = $datos['proveedor'] ?? 'GENERAL';
        $marca = $datos['marca'] ?? 'WHOLESTONE';
        $sello = $datos['sello'] ?? '';
        $remolque = $datos['remolque'] ?? '';
        $tractor = $datos['tractor'] ?? '';
        $fecha = $datos['fecha'] ?? date('d/m/Y');
        $combos = $datos['combos'] ?? [];

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Control_Combos_' . preg_replace('/[^a-zA-Z0-9]/', '_', $proveedor) . '_' . date('Y-m-d') . '.xls"');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="utf-8"><style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo '.banner-superior { background-color: #0f172a; color: #ffffff; font-size: 18pt; font-weight: bold; text-align: center; vertical-align: middle; height: 55px; }';
        echo '.info-header { font-size: 11pt; font-weight: bold; background-color: #f1f5f9; border: 1.5px solid #cbd5e1; padding: 10px; height: 30px; vertical-align: middle; }';
        echo '.tabla-header { background-color: #334155; color: #ffffff; font-size: 11pt; font-weight: bold; text-align: center; border: 1.5px solid #000000; height: 40px; vertical-align: middle; }';
        echo '.celda-dato { font-size: 11pt; border: 1px solid #94a3b8; padding: 8px; text-align: center; vertical-align: middle; height: 28px; }';
        echo '.fila-totales { background-color: #e2e8f0; font-weight: bold; font-size: 11pt; border: 1.5px solid #000000; height: 35px; text-align: center; vertical-align: middle; }';
        echo '</style></head><body>';
        echo '<table>';
        
        echo '<tr><td colspan="9" class="banner-superior">LFI - CONTROL DE ENTRADAS Y COMBOS DE CARNE</td></tr>';
        echo '<tr><td colspan="9" style="height: 15px;"></td></tr>';
        
        echo '<tr><td colspan="4" class="info-header">PROVEEDOR: ' . htmlspecialchars($proveedor) . '</td><td colspan="5" class="info-header">MARCA: ' . htmlspecialchars($marca) . '</td></tr>';
        echo '<tr><td colspan="3" class="info-header">PEDIMENTO: </td><td colspan="3" class="info-header">N° SELLO: ' . htmlspecialchars($sello)  . '</td><td colspan="3" class="info-header">N° REMOLQUE: ' . htmlspecialchars($remolque) . '</td></tr>';
        echo '<tr><td colspan="4" class="info-header">FECHA: ' . htmlspecialchars($fecha) . '</td><td colspan="5" class="info-header">N° TRACTO: ' . htmlspecialchars($tractor) . '</td></tr>';
        echo '<tr><td colspan="9" style="height: 15px;"></td></tr>';

        echo '<tr>';
        echo '<th class="tabla-header" style="width: 50px;">NO.</th>';
        echo '<th class="tabla-header" style="width: 110px;">PESO BRUTO</th>';
        echo '<th class="tabla-header" style="width: 90px;">LB TARA</th>';
        echo '<th class="tabla-header" style="width: 100px;">KG TARA</th>';
        echo '<th class="tabla-header" style="width: 110px;">PESO NETO</th>';
        echo '<th class="tabla-header" style="width: 110px;">PESO ORIGEN</th>';
        echo '<th class="tabla-header" style="width: 100px;">MERMA</th>';
        echo '<th class="tabla-header" style="width: 80px;">1%</th>';
        echo '<th class="tabla-header" style="width: 120px;">DIFERENCIA</th>';
        echo '</tr>';

        for ($i = 0; $i < 28; $i++) {
            $no = $i + 1;
            $combo = $combos[$i] ?? [];
            $pesoBruto = floatval($combo['pesoBruto'] ?? 0);
            $lbTara = floatval($combo['lbTara'] ?? 58); 
            $pesoOrigen = floatval($combo['pesoOrigen'] ?? 0);
            
            $numFilaExcel = 8 + $i; 

            $fKgTara = "=C{$numFilaExcel}*0.45359237";
            $fPesoNeto = "=B{$numFilaExcel}-D{$numFilaExcel}";
            $fMerma = "=E{$numFilaExcel}-F{$numFilaExcel}";
            $fPorcentaje = "=E{$numFilaExcel}*0.01";
            $fDiferencia = "=(E{$numFilaExcel}-F{$numFilaExcel})+H{$numFilaExcel}";

            echo '<tr>';
            echo '<td class="celda-dato">' . $no . '</td>';
            echo '<td class="celda-dato">' . ($pesoBruto > 0 ? $pesoBruto : '') . '</td>';
            echo '<td class="celda-dato">' . $lbTara . '</td>';
            
            if ($pesoBruto > 0) {
                echo '<td class="celda-dato">' . $fKgTara . '</td>';
                echo '<td class="celda-dato">' . $fPesoNeto . '</td>';
                echo '<td class="celda-dato">' . ($pesoOrigen > 0 ? $pesoOrigen : '') . '</td>';
                echo '<td class="celda-dato">' . $fMerma . '</td>';
                echo '<td class="celda-dato">' . $fPorcentaje . '</td>';
                echo '<td class="celda-dato">' . $fDiferencia . '</td>';
            } else {
                echo '<td class="celda-dato"></td>';
                echo '<td class="celda-dato"></td>';
                echo '<td class="celda-dato">' . ($pesoOrigen > 0 ? $pesoOrigen : '') . '</td>';
                echo '<td class="celda-dato"></td>';
                echo '<td class="celda-dato"></td>';
                echo '<td class="celda-dato"></td>';
            }
            echo '</tr>';
        }

        echo '<tr class="fila-totales">';
        echo '<td colspan="4" style="text-align: right; font-weight: bold; border: 1.5px solid #000; padding-right: 10px;">TOTALES:</td>';
        echo '<td class="celda-dato" style="font-weight: bold; border: 1.5px solid #000;">=SUMA(E8:E35)</td>';
        echo '<td class="celda-dato" style="font-weight: bold; border: 1.5px solid #000;">=SUMA(F8:F35)</td>';
        echo '<td class="celda-dato" style="font-weight: bold; border: 1.5px solid #000;">=SUMA(G8:G35)</td>';
        echo '<td class="celda-dato" style="font-weight: bold; border: 1.5px solid #000;">=SUMA(H8:H35)</td>';
        echo '<td class="celda-dato" style="font-weight: bold; border: 1.5px solid #000;">=SUMA(I8:I35)</td>';
        echo '</tr>';

        echo '</table></body></html>';
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Falla interna del servidor: ' . $e->getMessage()]);
    exit;
}
// 5. OBTENER PRODUCTOS POR PROVEEDOR (PARA AUTOCOMPLETAR MANTECA)
    if ($action === 'obtenerProductosPorProveedor') {
        header('Content-Type: application/json; charset=utf-8');
        $idProveedor = $_GET['idProveedor'] ?? '';

        if (empty($idProveedor)) {
            echo json_encode([]);
            exit;
        }

        try {
            // Llamamos a un método del servicio que lista los productos de ese proveedor específico
            $productos = $service->listarProductosPorProveedor($idProveedor);
            echo json_encode($productos);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
        }
        exit;
    }
?>