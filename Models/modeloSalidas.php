<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloSalidas {
    private $db;

    public function __construct() {
        $this->db = new BD(); 
    }

    // Obtener lista de clientes
    public function obtenerTodosClientes() {
        $query = "SELECT idCliente, nombreCliente FROM cliente WHERE status = 1 ORDER BY nombreCliente ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            try {
                $queryAlt = "SELECT idProveedor as idCliente, nombreProveedor as nombreCliente FROM proveedor WHERE status = 1";
                $stmtAlt = $this->db->consulta($queryAlt);
                return $stmtAlt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $ex) {
                return [];
            }
        }
    }

    public function buscarProductoSimple($codigo) {
        $query = "SELECT idProducto, nombreProducto, codigoProducto FROM producto WHERE TRIM(codigoProducto) = ? LIMIT 1";
        try {
            $stmt = $this->db->consulta($query, [trim($codigo)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    // 🎯 Obtener lotes disponibles (combos o cajas) con su código de lote exacto para las salidas
    public function obtenerLotesDisponibles($busqueda = '') {
        $query = "SELECT l.idLote, l.codigoLote, l.pesoActual, p.idProducto, p.nombreProducto, p.codigoProducto 
                  FROM lote l
                  JOIN producto p ON l.idProducto = p.idProducto
                  WHERE l.activo = 1 AND l.pesoActual > 0";
        
        $params = [];
        if (!empty($busqueda)) {
            $query .= " AND (l.codigoLote LIKE ? OR p.nombreProducto LIKE ? OR p.codigoProducto LIKE ?)";
            $search = "%{$busqueda}%";
            $params = [$search, $search, $search];
        }

        $query .= " ORDER BY l.idLote DESC LIMIT 20";

        try {
            $stmt = $this->db->consulta($query, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en obtenerLotesDisponibles: " . $e->getMessage());
            return [];
        }
    }

    // Transacción que guarda la salida, vincula el lote específico y RESTA el inventario y peso del lote
    public function registrarSalidaTransaccion($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->db->consulta("START TRANSACTION");

            $idAlmacen = $datos['id_almacen'] ?? 1;
            $totalKgs = $datos['total_kgs'] ?? 0;
            $totalCajas = $datos['cantidad_cajas'] ?? ($datos['total_cajas'] ?? count($datos['detalle']));

            $querySalida = "INSERT INTO salidas (id_almacen, id_cliente, id_usuario, totalKgs, status) VALUES (?, ?, ?, ?, 'A')";
            $this->db->consulta($querySalida, [
                $idAlmacen, 
                $datos['id_cliente'], 
                $idUsuario, 
                $totalKgs
            ]);
            
            $stmtLastId = $this->db->consulta("SELECT LAST_INSERT_ID() as id");
            $idSalida = $stmtLastId->fetch(PDO::FETCH_ASSOC)['id'];

            foreach ($datos['detalle'] as $item) {
                // 1. Buscar ID real del producto por su código
                $stmtProd = $this->db->consulta("SELECT idProducto FROM producto WHERE codigoProducto = ? LIMIT 1", [$item['codigo_producto']]);
                $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                $idProducto = $productoBD ? $productoBD['idProducto'] : 1; 

                // 🔥 Capturamos el lote específico que se manda desde la vista o buscamos uno por defecto
                $idLote = intval($item['id_lote'] ?? 0);
                $cantidadItem = $item['cantidad'] ?? ($item['cantidad_cajas'] ?? 1);
                $kgsItem = floatval($item['kgs'] ?? 0);

                if ($idLote <= 0) {
                    // Si no viene un lote específico, buscamos uno activo disponible de ese producto
                    $stmtLote = $this->db->consulta("SELECT idLote FROM lote WHERE idProducto = ? AND activo = 1 AND pesoActual > 0 LIMIT 1", [$idProducto]);
                    $loteBD = $stmtLote->fetch(PDO::FETCH_ASSOC);
                    
                    if ($loteBD) {
                        $idLote = $loteBD['idLote'];
                    } else {
                        // Si no hay lotes activos, buscamos cualquier lote disponible en el inventario
                        $stmtLoteAlt = $this->db->consulta("SELECT idLote FROM lote WHERE activo = 1 AND pesoActual > 0 ORDER BY idLote ASC LIMIT 1");
                        $loteAltBD = $stmtLoteAlt->fetch(PDO::FETCH_ASSOC);

                        if ($loteAltBD) {
                            $idLote = $loteAltBD['idLote'];
                        } else {
                            // Lote de emergencia si de plano no hay existencias en lotes
                            $queryEmergencia = "INSERT INTO lote (idProducto, idUbicacion, idRack, codigoLote, fechaIngreso, pesoActual, estadoCalidad, activo) 
                                                VALUES (?, 1, 1, ?, NOW(), ?, 'Aprobado', 1)";
                            $this->db->consulta($queryEmergencia, [$idProducto, "LOT-EMG-" . time(), $kgsItem]);
                            $idLote = $this->db->consulta("SELECT LAST_INSERT_ID() as id")->fetch(PDO::FETCH_ASSOC)['id'];
                        }
                    }
                }

                // 2. Insertar en salidas_detalle vinculando el lote exacto
                $queryDetalle = "INSERT INTO salidas_detalle (id_salida, partida, id_producto, id_lote, cantidad, kgs) VALUES (?, ?, ?, ?, ?, ?)";
                $this->db->consulta($queryDetalle, [
                    $idSalida, 
                    $item['partida'], 
                    $idProducto, 
                    $idLote, 
                    $cantidadItem, 
                    $kgsItem
                ]);

                // 3. Descontar peso y actualizar estado del lote específico que salió
                if ($idLote > 0) {
                    $this->db->consulta("UPDATE lote SET pesoActual = pesoActual - ?, activo = IF(pesoActual - ? <= 0, 0, 1) WHERE idLote = ?", [
                        $kgsItem, $kgsItem, $idLote
                    ]);
                }

                // 4. Restar inventario general en la tabla producto
                $queryUpdateStock = "UPDATE producto 
                                     SET totalCajas = IFNULL(totalCajas, 0) - ?, 
                                         totalPeso = IFNULL(totalPeso, 0) - ? 
                                     WHERE idProducto = ?";
                $this->db->consulta($queryUpdateStock, [
                    $cantidadItem,
                    $kgsItem,
                    $idProducto
                ]);
            }

            $desc = "Se registró la salida Folio {$idSalida} con {$totalCajas} cajas ({$totalKgs} Kgs).";
            $queryBitacora = "INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('salida', ?, ?, 'Salidas', CURDATE(), CURTIME())";
            $this->db->consulta($queryBitacora, [$apodoUsuario, $desc]);

            $this->db->consulta("COMMIT");
            return ["success" => true, "folio" => $idSalida];

        } catch (Exception $e) {
            $this->db->consulta("ROLLBACK");
            return ["success" => false, "error" => "Error de base de datos: " . $e->getMessage()];
        }
    }
}
?>