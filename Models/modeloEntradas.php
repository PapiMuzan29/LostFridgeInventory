<?php
// Nota: Se removió session_start() de este archivo para evitar conflictos de sesiones múltiples.

require_once __DIR__ . '/../Config/BD.php'; 

class modeloEntradas {
    private $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia(); 
    }

    public function obtenerTodosProveedores() {
        $query = "SELECT idProveedor, nombreProveedor FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en modeloEntradas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerCodigoDeProducto(string $columna = '', string $valor = '', string $CodigoBarras = ''): array {
        if ($CodigoBarras === '') return [];

        $longitudTotal = mb_strlen($CodigoBarras, 'UTF-8');
        
        $parteFinal = mb_substr($CodigoBarras, -2, 2, 'UTF-8'); 
        $codigoProducto = ltrim($parteFinal, '0');
        if (empty($codigoProducto)) {
            $codigoProducto = ltrim($CodigoBarras, '0');
        }

        $partePeso = mb_substr($CodigoBarras, 2, 4, 'UTF-8'); 
        $enteros = mb_substr($partePeso, 0, 2, 'UTF-8');     
        $decimales = mb_substr($partePeso, 2, 2, 'UTF-8');   

        $pesoCalculado = floatval($enteros . '.' . $decimales);

        return [
            'codigoProducto'  => $codigoProducto, 
            'codigoEnteros'   => (string)$pesoCalculado, 
            'codigoDecimales' => $decimales
        ];
    }

    public function obtenerProducto(string $codigoBarras = '', string $codigoProveedor = '', string $nombreProveedor = '', string $rfc = ''): array {
        if ($codigoBarras === '') return [];

        $query = "SELECT codigoProducto, nombreProducto FROM producto WHERE codigoProducto = ? OR TRIM(codigoProducto) = ? LIMIT 1";
        
        try {
            $stmt = $this->db->consulta($query, [$codigoBarras, trim($codigoBarras)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'nombreProducto'  => $result['nombreProducto'], 
                    'codigoEnteros'   => '1', 
                    'codigoDecimales' => '00',
                    'codigoProducto'  => $result['codigoProducto']
                ];
            }

            return [];
        } catch (Exception $e) {
            error_log("Error en obtenerProducto: " . $e->getMessage());
            return [];
        }
    }

    public function buscarProductoPorCodigoYProveedor($codigo, $idProveedor) {
        if (empty($codigo)) return null;

        $query = "SELECT codigoProducto, nombreProducto FROM producto WHERE codigoProducto = ? LIMIT 1";
        try {
            $stmt = $this->db->consulta($query, [$codigo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? [
                'codigoProducto' => $result['codigoProducto'],
                'nombreProducto' => $result['nombreProducto']
            ] : null;
        } catch (Exception $e) {
            error_log("Error en buscarProductoPorCodigoYProveedor: " . $e->getMessage());
            return null;
        }
    }

    public function registrarEntradaTransaccion($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->db->consulta("START TRANSACTION");

            $stmtFolio = $this->db->consulta("SELECT IFNULL(MAX(folio), 0) + 1 AS siguiente_folio FROM entradas");
            $siguienteFolio = $stmtFolio->fetch(PDO::FETCH_ASSOC)['siguiente_folio'];

            $idAlmacen = $datos['id_almacen'] ?? 1;
            $totalKgs = $datos['total_kgs'] ?? 0;
            $totalCajas = $datos['cantidad_cajas'] ?? ($datos['total_cajas'] ?? count($datos['detalle']));

            $queryEntrada = "INSERT INTO entradas (folio, id_almacen, id_proveedor, id_usuario, totalPeso, status) VALUES (?, ?, ?, ?, ?, 'A')";
            $this->db->consulta($queryEntrada, [
                $siguienteFolio, 
                $idAlmacen, 
                $datos['id_proveedor'], 
                $idUsuario, 
                $totalKgs
            ]);
            
            $idEntrada = $this->db->consulta("SELECT LAST_INSERT_ID() as id")->fetch(PDO::FETCH_ASSOC)['id'];

            foreach ($datos['detalle'] as $item) {
                $stmtProd = $this->db->consulta("SELECT idProducto FROM producto WHERE codigoProducto = ? LIMIT 1", [$item['codigo_producto']]);
                $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                $idProducto = $productoBD ? $productoBD['idProducto'] : 1; 

                $cantidadItem = $item['cantidad'] ?? 1;
                $kgsItem = $item['kgs'] ?? 0;

                $queryDetalle = "INSERT INTO entradas_detalle (id_entrada, partida, id_producto, cantidad, kgs) VALUES (?, ?, ?, ?, ?)";
                $this->db->consulta($queryDetalle, [$idEntrada, $item['partida'], $idProducto, $cantidadItem, $kgsItem]);
                
                $idEntradaDetalle = $this->db->consulta("SELECT LAST_INSERT_ID() as id")->fetch(PDO::FETCH_ASSOC)['id'];

                $codigoLoteGenerado = "LOT-" . $siguienteFolio . "-" . $item['partida'];
                $queryLote = "INSERT INTO lote (idProducto, idUbicacion, idEntradaDetalle, idRack, codigoLote, fechaIngreso, pesoActual, estadoCalidad, activo) 
                              VALUES (?, ?, ?, ?, ?, NOW(), ?, 'Aprobado', 1)";
                
                $this->db->consulta($queryLote, [
                    $idProducto, 
                    $idAlmacen, 
                    $idEntradaDetalle, 
                    1, 
                    $codigoLoteGenerado, 
                    $kgsItem
                ]);

                $this->db->consulta("UPDATE producto SET totalCajas = IFNULL(totalCajas, 0) + ?, totalPeso = IFNULL(totalPeso, 0) + ? WHERE idProducto = ?", 
                    [$cantidadItem, $kgsItem, $idProducto]);
            }

            $this->db->consulta("INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('entrada', ?, ?, 'Entradas', CURDATE(), CURTIME())", 
                [$apodoUsuario, "Se registró la entrada Folio {$siguienteFolio} con {$totalCajas} cajas ({$totalKgs} Kgs)."]);

            $this->db->consulta("COMMIT");
            return ["success" => true, "folio" => $siguienteFolio];

        } catch (Exception $e) {
            $this->db->consulta("ROLLBACK");
            error_log("Error en registrarEntradaTransaccion: " . $e->getMessage());
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    public function registrarEntradaCombosTransaccion($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->db->consulta("START TRANSACTION");

            $stmtFolio = $this->db->consulta("SELECT IFNULL(MAX(folio), 0) + 1 AS siguiente_folio FROM entradas");
            $siguienteFolio = $stmtFolio->fetch(PDO::FETCH_ASSOC)['siguiente_folio'];

            $idAlmacen = $datos['id_almacen'] ?? 1;
            $idProveedor = $datos['id_proveedor'] ?? $datos['idProveedor'] ?? 0;
            $detalle = $datos['detalle'] ?? [];
            $totalKgs = $datos['total_kgs'] ?? 0;
            
            $cantidadCombosNuevos = count($detalle);
            
            $tipoCombo = strtolower($datos['tipo_combo'] ?? $datos['modo_captura'] ?? 'pierna');
            $nombreFiltro = strpos($tipoCombo, 'codillo') !== false ? '%CODILLO%' : '%PIERNA%';

            $queryEntrada = "INSERT INTO entradas (folio, id_almacen, id_proveedor, id_usuario, totalPeso, status) VALUES (?, ?, ?, ?, ?, 'A')";
            $this->db->consulta($queryEntrada, [
                $siguienteFolio, 
                $idAlmacen, 
                $idProveedor, 
                $idUsuario, 
                $totalKgs
            ]);
            
            $idEntrada = $this->db->consulta("SELECT LAST_INSERT_ID() as id")->fetch(PDO::FETCH_ASSOC)['id'];

            $stmtProd = $this->db->consulta("SELECT idProducto FROM producto WHERE nombreProducto LIKE ? LIMIT 1", [$nombreFiltro]);
            $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if ($productoBD) {
                $idProducto = $productoBD['idProducto'];
            } else {
                $nombreProdCrear = strpos($tipoCombo, 'codillo') !== false ? 'Combo de Codillo de Cerdo' : 'Combo de Pierna de Cerdo';
                $codigoProdCrear = strpos($tipoCombo, 'codillo') !== false ? 'CMB-COD' : 'CMB-PIE';
                
                $this->db->consulta("INSERT INTO producto (codigoProducto, nombreProducto, activo, totalCajas, totalPeso) VALUES (?, ?, 1, 0, 0)", 
                    [$codigoProdCrear, $nombreProdCrear]);
                $idProducto = $this->db->consulta("SELECT LAST_INSERT_ID() as id")->fetch(PDO::FETCH_ASSOC)['id'];
            }

            // Obtenemos las siglas del proveedor (Ej: TITAN -> TI)
            $stmtProv = $this->db->consulta("SELECT nombreProveedor FROM proveedor WHERE idProveedor = ? LIMIT 1", [$idProveedor]);
            $datosProv = $stmtProv->fetch(PDO::FETCH_ASSOC);
            $nombreProveedorText = $datosProv ? strtoupper($datosProv['nombreProveedor']) : 'GEN';
            
            $siglasProveedor = preg_replace('/[^A-Z]/', '', $nombreProveedorText);
            $siglasProveedor = substr($siglasProveedor, 0, 2);
            if (empty($siglasProveedor)) $siglasProveedor = 'XX';

            // Formato de fecha MMDDYYYY (Ej: 08272026)
            $fechaFormato = date('mdY');

            foreach ($detalle as $item) {
                $pesoNetoItem = floatval($item['peso_neto'] ?? $item['pesoNeto'] ?? 0);
                if ($pesoNetoItem <= 0) continue; 

                $queryDetalle = "INSERT INTO entradas_detalle (id_entrada, partida, id_producto, cantidad, kgs) VALUES (?, ?, ?, 0, ?)";
                $this->db->consulta($queryDetalle, [$idEntrada, $item['partida'], $idProducto, $pesoNetoItem]);
                
                $idEntradaDetalle = $this->db->consulta("SELECT LAST_INSERT_ID() as id")->fetch(PDO::FETCH_ASSOC)['id'];

                // Construcción basada en la partida del combo (Ej: partida 3 -> 03 + TI + 08272026 = 03TI08272026)
                $numeroPartidaStr = str_pad($item['partida'] ?? 1, 2, '0', STR_PAD_LEFT);
                $codigoLoteGenerado = "{$numeroPartidaStr}{$siglasProveedor}{$fechaFormato}";
                
                $queryLote = "INSERT INTO lote (idProducto, idUbicacion, idEntradaDetalle, idRack, codigoLote, fechaIngreso, pesoActual, estadoCalidad, activo) 
                              VALUES (?, ?, ?, ?, ?, NOW(), ?, 'Aprobado', 1)";
                
                $this->db->consulta($queryLote, [
                    $idProducto, 
                    $idAlmacen, 
                    $idEntradaDetalle, 
                    1, 
                    $codigoLoteGenerado, 
                    $pesoNetoItem
                ]);
            }

            // Acumulamos el conteo de combos y el peso en la tabla producto
            $this->db->consulta("UPDATE producto SET totalCajas = IFNULL(totalCajas, 0) + ?, totalPeso = IFNULL(totalPeso, 0) + ? WHERE idProducto = ?", 
                [$cantidadCombosNuevos, $totalKgs, $idProducto]);

            $this->db->consulta("INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('entrada', ?, ?, 'Entradas Combos', CURDATE(), CURTIME())", 
                [$apodoUsuario, "Se registró la entrada de combos ({$tipoCombo}) Folio {$siguienteFolio} con {$cantidadCombosNuevos} combo(s) y {$totalKgs} Kgs."]);

            $this->db->consulta("COMMIT");
            return ["success" => true, "status" => "success", "folio" => $siguienteFolio, "message" => "Entrada de combos registrada con éxito"];

        } catch (Exception $e) {
            $this->db->consulta("ROLLBACK");
            error_log("Error en registrarEntradaCombosTransaccion: " . $e->getMessage());
            return ["success" => false, "status" => "error", "error" => $e->getMessage(), "message" => $e->getMessage()];
        }
    }
}
?>