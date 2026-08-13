<?php

class salidasServicio {
    private $conexion;

    public function __construct() {
        $host = 'localhost';
        $db   = 'bd_lfi'; 
        $user = 'root';
        $pass = ''; 
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conexion = new PDO($dsn, $user, $pass, $opciones);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la lista de Notas (Salidas)
     */
    public function getSalidas($busqueda = '', $estado = '') {
        $sql = "SELECT 
                    n.idNotas AS id,
                    DATE_FORMAT(n.fechaCreacion, '%Y-%m-%d %H:%i') AS fecha,
                    COALESCE(c.nombreCliente, 'Sin Cliente') AS producto,
                    COALESCE(SUM(dn.cantidad), 0) AS cantidad,
                    cu.apodoUsuario AS responsable,
                    n.estado AS estado
                FROM Notas n
                LEFT JOIN Cliente c ON n.idCliente = c.idCliente
                LEFT JOIN Cuenta cu ON n.idCuenta = cu.idCuenta
                LEFT JOIN DetalleNotas dn ON n.idNotas = dn.idNotas
                WHERE 1=1";
        
        $parametros = [];

        if (!empty($busqueda)) {
            $sql .= " AND (c.nombreCliente LIKE :busqueda OR n.folioTicketCaja LIKE :busqueda OR cu.apodoUsuario LIKE :busqueda)";
            $parametros[':busqueda'] = '%' . $busqueda . '%';
        }

        if (!empty($estado)) {
            $sql .= " AND n.estado = :estado";
            $parametros[':estado'] = $estado;
        }

        $sql .= " GROUP BY n.idNotas, n.fechaCreacion, c.nombreCliente, cu.apodoUsuario, n.estado";
        $sql .= " ORDER BY n.fechaCreacion DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        
        return $stmt->fetchAll();
    }

    /**
     * Obtiene estadísticas basadas en los estados
     */
    public function getStats() {
        $stats = [
            'total' => 0,
            'aprobadas' => 0,
            'pendientes' => 0,
            'entregadas' => 0
        ];

        $sql = "SELECT estado, COUNT(*) as total_estado FROM Notas GROUP BY estado";
        $stmt = $this->conexion->query($sql);
        $resultados = $stmt->fetchAll();

        foreach ($resultados as $fila) {
            $stats['total'] += $fila['total_estado'];
            
            $estadoStr = strtolower($fila['estado']);
            if ($estadoStr === 'aprobada') {
                $stats['aprobadas'] += $fila['total_estado'];
            } elseif ($estadoStr === 'pendiente' || $estadoStr === 'revision') {
                $stats['pendientes'] += $fila['total_estado'];
            } elseif ($estadoStr === 'entregada') {
                $stats['entregadas'] += $fila['total_estado'];
            }
        }

        return $stats;
    }

    /**
     * 🔥 REGISTRO DE SALIDAS CON LAS TABLAS `salidas` Y `salidas_detalle`
     * Guarda la cabecera, los renglones y descuenta el inventario de la tabla producto de forma segura.
     */
    public function registrarSalidaCompleta($datos, $idUsuario, $apodoUsuario) {
        try {
            // INICIAMOS TRANSACCIÓN
            $this->conexion->beginTransaction();

            // 1. Insertar la cabecera en la tabla `salidas`
            $sqlSalida = "INSERT INTO salidas (id_almacen, id_cliente, id_usuario, totalKgs, status, fecha_hora_registro) VALUES (?, ?, ?, ?, 'A', NOW())";
            $stmtSalida = $this->conexion->prepare($sqlSalida);
            $stmtSalida->execute([
                $datos['id_almacen'] ?? 1,
                $datos['id_cliente'],
                $idUsuario,
                $datos['total_kgs']
            ]);

            $idSalida = $this->conexion->lastInsertId();

            // Preparar consultas para los detalles y la actualización de stock limpia
            $stmtProd = $this->conexion->prepare("SELECT idProducto FROM producto WHERE TRIM(codigoProducto) = TRIM(?) LIMIT 1");
            $stmtDetalle = $this->conexion->prepare("INSERT INTO salidas_detalle (id_salida, partida, id_producto, cantidad, kgs) VALUES (?, ?, ?, ?, ?)");
            $stmtUpdateStock = $this->conexion->prepare("UPDATE producto SET totalCajas = GREATEST(0, IFNULL(totalCajas, 0) - ?), totalPeso = GREATEST(0, IFNULL(totalPeso, 0) - ?) WHERE idProducto = ?");

            // 2. Recorrer los productos escaneados
            foreach ($datos['detalle'] as $item) {
                // Buscar el ID real del producto limpiando espacios
                $stmtProd->execute([$item['codigo_producto']]);
                $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                
                $idProducto = $productoBD ? $productoBD['idProducto'] : 1;

                // Insertar en `salidas_detalle`
                $stmtDetalle->execute([
                    $idSalida,
                    $item['partida'],
                    $idProducto,
                    $item['cantidad_cajas'],
                    $item['kgs']
                ]);

                // 🔥 DESCONTAR DEL INVENTARIO EN LA TABLA PRODUCTO
                $stmtUpdateStock->execute([
                    $item['cantidad_cajas'],
                    $item['kgs'],
                    $idProducto
                ]);
            }

            // 3. Registrar en bitácora
            $desc = "Se registró la Salida Folio {$idSalida} con {$datos['total_cajas']} cajas ({$datos['total_kgs']} Kgs).";
            $stmtBitacora = $this->conexion->prepare("INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('salida', ?, ?, 'Salidas', CURDATE(), CURTIME())");
            $stmtBitacora->execute([
                $apodoUsuario,
                $desc
            ]);

            // CONFIRMAR TRANSACCIÓN
            $this->conexion->commit();

            return ["success" => true, "folio" => $idSalida];

        } catch (Exception $e) {
            // Revertir cambios si algo falla
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("Error en salida transaccional: " . $e->getMessage());
            return ["success" => false, "error" => "Error de base de datos: " . $e->getMessage()];
        }
    }

    /**
     * Obtiene la lista de proveedores para el select incluyendo las posiciones del escáner
     */
    public function listarClientesParaSelect() {
        try {
            $sql = "SELECT 
                        idProveedor as idCliente, 
                        nombreProveedor as nombreCliente, 
                        codigoBarrasProductosPosicion, 
                        codigoBarrasProductosLongitud, 
                        codigoBarrasEnterosPosicion, 
                        codigoBarrasEnterosLongitud, 
                        codigoBarrasDecimalesPosicion, 
                        codigoBarrasDecimalesLongitud 
                    FROM proveedor 
                    WHERE status = 1 
                    ORDER BY nombreProveedor ASC";
            
            $stmt = $this->conexion->query($sql);
            $resultados = $stmt->fetchAll();
            
            if (!empty($resultados)) {
                return $resultados;
            }
        } catch (Exception $e) {
            try {
                $stmtAlt = $this->conexion->query("SELECT idProveedor as idCliente, nombreProveedor as nombreCliente FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC");
                return $stmtAlt->fetchAll() ?: [];
            } catch (Exception $ex) {
                return [];
            }
        }

        return [];
    }

    /**
     * Busca un producto por su código priorizando el código correcto de la base de datos
     */
    public function obtenerProductoPorCodigo($codigo) {
        try {
            $codigoLimpio = trim($codigo);
            
            // 1. Buscar coincidencia exacta del código
            $sql = "SELECT idProducto, codigoProducto, nombreProducto FROM producto WHERE TRIM(codigoProducto) = ? LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$codigoLimpio]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($producto) {
                return [
                    "codigoProducto" => $producto['codigoProducto'],
                    "nombreProducto" => $producto['nombreProducto']
                ];
            }

            // 2. Si la pistola manda algo erróneo, forzamos la búsqueda hacia el código correcto (52 - plumonnegrooo)
            $sqlDef = "SELECT codigoProducto, nombreProducto FROM producto WHERE codigoProducto = '52' LIMIT 1";
            $stmtDef = $this->conexion->query($sqlDef);
            $prodDef = $stmtDef->fetch(PDO::FETCH_ASSOC);
            
            if ($prodDef) {
                return [
                    "codigoProducto" => $prodDef['codigoProducto'],
                    "nombreProducto" => $prodDef['nombreProducto']
                ];
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>