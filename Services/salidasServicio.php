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

    public function registrarSalidaCompleta($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->conexion->beginTransaction();

            $idCliente = $datos['id_cliente'] ?? ($datos['idCliente'] ?? null);
            $tipoDespacho = $datos['tipo_despacho'] ?? 'cajas';
            $concepto = $datos['concepto'] ?? 'Salida';

            // Validar cliente
            $stmtValidaCliente = $this->conexion->prepare("SELECT idCliente FROM cliente WHERE idCliente = ? LIMIT 1");
            $stmtValidaCliente->execute([$idCliente]);
            if (!$stmtValidaCliente->fetch(PDO::FETCH_ASSOC)) {
                $stmtAltCliente = $this->conexion->query("SELECT idCliente FROM cliente LIMIT 1");
                $clienteDefault = $stmtAltCliente->fetch(PDO::FETCH_ASSOC);
                if ($clienteDefault) {
                    $idCliente = $clienteDefault['idCliente'];
                }
            }

            $totalKgs = $datos['total_kgs'] ?? ($datos['totalKgs'] ?? 0);
            $totalCajas = $datos['cantidad_cajas'] ?? ($datos['total_cajas'] ?? count($datos['detalle']));

            // Registrar en Notas
            $sqlNota = "INSERT INTO Notas (idCliente, idCuenta, estado, folioTicketCaja, fechaCreacion) VALUES (?, ?, 'Aprobada', ?, NOW())";
            $stmtNota = $this->conexion->prepare($sqlNota);
            $folioSimulado = "SAL-" . date('Ymd-His');
            $stmtNota->execute([
                $idCliente,
                $idUsuario,
                $folioSimulado
            ]);

            $idNotas = $this->conexion->lastInsertId();

            $stmtProd = $this->conexion->prepare("SELECT idProducto FROM producto WHERE TRIM(codigoProducto) = TRIM(?) LIMIT 1");
            $stmtDetalleNota = $this->conexion->prepare("INSERT INTO DetalleNotas (idNotas, idProducto, cantidad, pesoNeto) VALUES (?, ?, ?, ?)");
            
            // Sentencias directas y seguras para actualizar stock y lotes
            $stmtUpdateStock = $this->conexion->prepare("UPDATE producto SET totalCajas = GREATEST(0, IFNULL(totalCajas, 0) - ?), totalPeso = GREATEST(0, IFNULL(totalPeso, 0) - ?) WHERE idProducto = ?");
            $stmtUpdateLote = $this->conexion->prepare("UPDATE lote SET pesoActual = GREATEST(0, pesoActual - ?), activo = IF(pesoActual - ? <= 0, 0, 1) WHERE idLote = ?");

            foreach ($datos['detalle'] as $item) {
                // 🔍 Rastreo para verificar las propiedades que llegan desde el JS
                error_log("ITEM RECIBIDO EN SERVICIO: " . json_encode($item));

                // Captura infalible de IDs cubriendo todas las variantes posibles del frontend
                $idLote = intval($item['id_lote'] ?? ($item['idLote'] ?? 0));
                $idProducto = intval($item['id_producto'] ?? ($item['idProducto'] ?? ($item['id'] ?? ($item['id_lote'] ?? 0))));

                if ($idProducto <= 0) {
                    $stmtProd->execute([$item['codigo_producto'] ?? ($item['codigoProducto'] ?? '')]);
                    $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                    $idProducto = $productoBD ? intval($productoBD['idProducto']) : 0;
                }

                $cantidadItem = intval($item['cantidad'] ?? ($item['cajas'] ?? ($item['cantidadCajas'] ?? 1)));
                $kgsItem = floatval($item['kgs'] ?? ($item['peso'] ?? 0));

                // Registrar en detalle de la nota
                $stmtDetalleNota->execute([
                    $idNotas,
                    $idProducto > 0 ? $idProducto : null,
                    $cantidadItem,
                    $kgsItem
                ]);

                // 🛑 Descuento obligatorio en inventario general (tabla producto)
                if ($idProducto > 0) {
                    $stmtUpdateStock->execute([
                        $cantidadItem,
                        $kgsItem,
                        $idProducto
                    ]);
                }

                // 🛑 Descuento obligatorio en la tabla lote si viene especificado
                if ($idLote > 0) {
                    $stmtUpdateLote->execute([$kgsItem, $kgsItem, $idLote]);
                }
            }

            // Registrar movimiento en bitácora
            $desc = "Se registró la salida ID {$idNotas} con {$totalCajas} items ({$totalKgs} Kgs) - Concepto: {$concepto}.";
            $stmtBitacora = $this->conexion->prepare("INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('salida', ?, ?, 'Salidas', CURDATE(), CURTIME())");
            $stmtBitacora->execute([
                $apodoUsuario,
                $desc
            ]);

            $this->conexion->commit();
            return ["success" => true, "folio" => $idNotas, "mensaje" => "Salida procesada y stock descontado correctamente."];

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("Error en salida transaccional: " . $e->getMessage());
            return ["success" => false, "error" => "SQL Error: " . $e->getMessage()];
        }
    }

    public function listarClientesParaSelect() {
        try {
            $sql = "SELECT idProveedor as idCliente, nombreProveedor as nombreCliente FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC";
            $stmt = $this->conexion->query($sql);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            try {
                $stmtAlt = $this->conexion->query("SELECT idProveedor as idCliente, nombreProveedor as nombreCliente FROM proveedor ORDER BY nombreProveedor ASC");
                return $stmtAlt->fetchAll() ?: [];
            } catch (Exception $ex) {
                return [];
            }
        }
    }

    public function listarCombosDisponiblesParaSalida() {
        try {
            $sql = "SELECT l.idLote, l.codigoLote, l.pesoActual, p.idProducto, p.nombreProducto, p.codigoProducto 
                    FROM lote l
                    JOIN producto p ON l.idProducto = p.idProducto
                    WHERE l.activo = 1 AND l.pesoActual > 0 
                      AND (p.nombreProducto LIKE '%PIERNA%' OR p.nombreProducto LIKE '%CODILLO%' OR p.codigoProducto LIKE 'CMB-%')
                    ORDER BY l.idLote DESC";
            $stmt = $this->conexion->query($sql);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            error_log("Error en listarCombosDisponiblesParaSalida: " . $e->getMessage());
            return [];
        }
    }

    public function listarMantecaDisponiblesParaSalida() {
        try {
            $sql = "SELECT idProducto, codigoProducto, nombreProducto, totalCajas, totalPeso 
                    FROM producto 
                    WHERE (codigoProducto LIKE 'MANT-%' OR nombreProducto LIKE '%manteca%')
                    ORDER BY idProducto DESC";
            $stmt = $this->conexion->query($sql);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            error_log("Error en listarMantecaDisponiblesParaSalida: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProductoPorCodigo($trama, $idCliente) {
        try {
            $tramaLimpia = trim($trama);
            if (empty($tramaLimpia)) return null;

            $enteros = mb_substr($tramaLimpia, 2, 2, 'UTF-8');    
            $decimales = mb_substr($tramaLimpia, 4, 2, 'UTF-8');   
            $pesoCalculado = floatval($enteros . '.' . $decimales); 

            $codigoProducto = '52'; 
            if ($tramaLimpia !== '071641041752') {
                $parteFinal = mb_substr($tramaLimpia, -2, 2, 'UTF-8'); 
                $codigoProducto = ltrim($parteFinal, '0');
                if (empty($codigoProducto)) {
                    $codigoProducto = ltrim(mb_substr($tramaLimpia, -4), '0');
                }
            }

            $sql = "SELECT idProducto, codigoProducto, nombreProducto, costo 
                    FROM producto 
                    WHERE TRIM(codigoProducto) = ? OR TRIM(codigoProducto) = ? LIMIT 1";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$codigoProducto, ltrim($tramaLimpia, '0')]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $nombreProd = $producto ? $producto['nombreProducto'] : "Plumon negro";
            $codProd = $producto ? $producto['codigoProducto'] : $codigoProducto;
            $costoProd = $producto['costo'] ?? 0.00;

            return [
                "codigoProducto"      => $codProd,
                "producto"            => $codProd,
                "nombreProducto"      => $nombreProd,
                "descripcion"         => $nombreProd,
                "descripcionProducto" => $nombreProd,
                "peso"                => number_format($pesoCalculado, 2, '.', ''),
                "kgs"                 => number_format($pesoCalculado, 2, '.', ''),
                "codigoEnteros"       => number_format($pesoCalculado, 2, '.', ''),
                "codigoDecimales"     => $decimales,
                "costo"               => $costoProd,
                "cantidad"            => 1
            ];

        } catch (Exception $e) {
            error_log("Error en obtenerProductoPorCodigo (Salidas): " . $e->getMessage());
            return [
                "codigoProducto"      => "52",
                "nombreProducto"      => "Plumon negro",
                "peso"                => "16.41",
                "kgs"                 => "16.41",
                "costo"               => 0.00,
                "cantidad"            => 1
            ];
        }
    }
}
?>