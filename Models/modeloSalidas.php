<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloSalidas {
    private $db;

    public function __construct() {
        $this->db = new BD(); 
    }

    // Obtener lista para el select (cambia 'cliente' por tu tabla real si es distinta)
    public function obtenerTodosClientes() {
        $query = "SELECT idCliente, nombreCliente FROM cliente WHERE status = 1 ORDER BY nombreCliente ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            // Fallback por si tu tabla se llama diferente o usas proveedores en salidas
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

    // Transacción que guarda la salida y RESTA el inventario
    public function registrarSalidaTransaccion($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->db->consulta("START TRANSACTION");

            $querySalida = "INSERT INTO salidas (id_almacen, id_cliente, id_usuario, totalKgs, status) VALUES (?, ?, ?, ?, 'A')";
            $this->db->consulta($querySalida, [
                $datos['id_almacen'], 
                $datos['id_cliente'], 
                $idUsuario, 
                $datos['total_kgs']
            ]);
            
            $stmtLastId = $this->db->consulta("SELECT LAST_INSERT_ID() as id");
            $idSalida = $stmtLastId->fetch(PDO::FETCH_ASSOC)['id'];

            foreach ($datos['detalle'] as $item) {
                $queryProd = "SELECT idProducto FROM producto WHERE codigoProducto = ? LIMIT 1";
                $stmtProd = $this->db->consulta($queryProd, [$item['codigo_producto']]);
                $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                $idProducto = $productoBD ? $productoBD['idProducto'] : 1; 

                $queryDetalle = "INSERT INTO salidas_detalle (id_salida, partida, id_producto, cantidad, kgs) VALUES (?, ?, ?, ?, ?)";
                $this->db->consulta($queryDetalle, [
                    $idSalida, 
                    $item['partida'], 
                    $idProducto, 
                    $item['cantidad_cajas'], 
                    $item['kgs']
                ]);

                // 🔥 RESTAR EL INVENTARIO EN LA TABLA PRODUCTO
                $queryUpdateStock = "UPDATE producto 
                                     SET totalCajas = IFNULL(totalCajas, 0) - ?, 
                                         totalPeso = IFNULL(totalPeso, 0) - ? 
                                     WHERE idProducto = ?";
                $this->db->consulta($queryUpdateStock, [
                    $item['cantidad_cajas'],
                    $item['kgs'],
                    $idProducto
                ]);
            }

            $desc = "Se registró la salida Folio {$idSalida} con {$datos['total_cajas']} cajas ({$datos['total_kgs']} Kgs).";
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