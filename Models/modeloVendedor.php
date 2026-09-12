<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/BD.php';

class modeloVendedor {

    private BD $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia();
    }

    public function obtenerSiguienteFolio(): string {
        $stmtNext = $this->db->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notas'");
        $nextId = $stmtNext->fetchColumn() ?: 1;
        return "FOL-" . str_pad((string)$nextId, 5, "0", STR_PAD_LEFT);
    }

    public function obtenerEstibadores(): array {
        return $this->db->select("
            SELECT idCuenta AS id_usuario, CONCAT(nombreUsuario, ' ', apellidoPaternoUsuario) AS nombre 
            FROM cuenta 
            WHERE estado = 1 AND idRol = 4 
            ORDER BY nombreUsuario ASC
        ");
    }

    public function obtenerProductosActivos(): array {
        $sql = "SELECT 
                    MIN(i.idSalidaTemporal) AS idSalidaTemporal,
                    i.idProducto AS id_producto,
                    COALESCE(l.codigoLote, l_alt.codigoLote, p.nombreProducto) AS nombreProducto,
                    p.porPiezas,
                    p.precio,
                    SUM(i.cantidadCajas) AS cantidadCajas,
                    COALESCE(MAX(l.pesoActual), MAX(l_alt.pesoActual), SUM(i.cantidadPeso)) AS cantidadPeso,
                    SUM(i.cantidadPiezas) AS cantidadPiezas,
                    MAX(i.observaciones) AS observaciones
                FROM InventarioTemporalSalida i
                INNER JOIN Producto p ON i.idProducto = p.idProducto
                LEFT JOIN lote l ON i.idLote = l.idLote
                LEFT JOIN lote l_alt ON i.idProducto = l_alt.idProducto AND l_alt.activo = 1 AND l_alt.pesoActual > 0
                GROUP BY COALESCE(l.codigoLote, l_alt.codigoLote, p.nombreProducto), i.idProducto
                ORDER BY idSalidaTemporal ASC";

        return $this->db->select($sql);
    }

    public function verificarDisponibilidad(array $productos): array {
        foreach ($productos as $prod) {
            $idProd = $prod['id_producto'] ?? null;
            $kilos = !empty($prod['kilos']) ? floatval($prod['kilos']) : 0;
            $piezas = !empty($prod['piezas']) ? intval($prod['piezas']) : 0;

            if (!$idProd) continue;

            $sql = "SELECT i.cantidadPeso, i.cantidadCajas, i.cantidadPiezas, p.nombreProducto, p.porPiezas 
                    FROM InventarioTemporalSalida i
                    INNER JOIN Producto p ON i.idProducto = p.idProducto
                    WHERE i.idProducto = ?";
            $res = $this->db->select($sql, [$idProd]);
            
            if (empty($res)) return ['exito' => false, 'mensaje' => "Un producto ya no está disponible."];
            
            $dbCajas = intval($res[0]['cantidadCajas']);
            $dbPiezas = intval($res[0]['cantidadPiezas']);
            $dbKilos = floatval($res[0]['cantidadPeso']);
            $nombre = $res[0]['nombreProducto'];
            $nombreMinus = strtolower($nombre);
            
            $esPorPiezas = intval($res[0]['porPiezas']) === 1; 
            $esCajaPechos = ($nombreMinus === 'caja pechos');
            $esSal = (strpos($nombreMinus, 'sal') !== false); 

            $limiteUnidades = $dbCajas > 0 ? $dbCajas : $dbPiezas;

            if ($esCajaPechos) {
                if ($piezas <= 0) return ['exito' => false, 'mensaje' => "Debes ingresar cuántas cajas vendes de $nombre."];
                if ($piezas > $limiteUnidades) return ['exito' => false, 'mensaje' => "Solo hay $limiteUnidades cajas de $nombre en sistema."];
                if ($kilos <= 0) return ['exito' => false, 'mensaje' => "Debes ingresar el peso total de las cajas de $nombre."];
            
            } elseif ($esSal) {
                $totalKilosDisponibles = ($dbCajas * 10) + $dbKilos;
                
                if ($kilos < 1) return ['exito' => false, 'mensaje' => "El mínimo de venta para $nombre es 1 kg."];
                if ($kilos > $totalKilosDisponibles) return ['exito' => false, 'mensaje' => "Solo hay " . number_format($totalKilosDisponibles, 2) . " kg disponibles de $nombre (equivale a $dbCajas bultos)."];
                
            } elseif ($esPorPiezas) {
                if ($piezas <= 0) return ['exito' => false, 'mensaje' => "Debes ingresar la cantidad de piezas para $nombre."];
                if ($piezas > $limiteUnidades) return ['exito' => false, 'mensaje' => "Solo hay $limiteUnidades piezas de $nombre."];
            
            } else {
                if ($kilos <= 0) return ['exito' => false, 'mensaje' => "Los kilos para $nombre deben ser mayores a 0."];
                
                $limiteMaxKilos = $dbKilos + 0.20; 
                if ($kilos > $limiteMaxKilos) return ['exito' => false, 'mensaje' => "Límite excedido para $nombre. (Max: $limiteMaxKilos kg)."];
            }
        }
        return ['exito' => true];
    }

    public function guardarNotaConEstado(string $folio, string $cliente, int $idCuenta, array $productos, array $estibadores, string $estadoInicial = 'GUARDADA', string $observacion = ''): bool|int {
        $sqlNota = "INSERT INTO notas (folio, nombre_cliente, id_vendedor, estado, fecha_creacion, observacion_especial) VALUES (?, ?, ?, ?, NOW(), ?)";
        $idNota = $this->db->insert($sqlNota, [$folio, $cliente, $idCuenta, $estadoInicial, $observacion]);
        if (!$idNota) return false;

        foreach ($productos as $prod) {
            $idProd = $prod['id_producto'] ?? null;
            $kilos = !empty($prod['kilos']) ? floatval($prod['kilos']) : 0;
            $piezas = !empty($prod['piezas']) ? intval($prod['piezas']) : 0;

            if ($idProd) {
                $sqlDetalle = "INSERT INTO detalle_notas (id_nota, idProducto, kilos, piezas) VALUES (?, ?, ?, ?)";
                $this->db->insert($sqlDetalle, [$idNota, $idProd, $kilos, $piezas]);

                $sqlInv = "SELECT idSalidaTemporal, cantidadPeso, cantidadPiezas, cantidadCajas, p.porPiezas 
                           FROM InventarioTemporalSalida i
                           INNER JOIN Producto p ON i.idProducto = p.idProducto
                           WHERE i.idProducto = ?";
                $resInv = $this->db->select($sqlInv, [$idProd]);
                
                if (!empty($resInv)) {
                    $idTemp = $resInv[0]['idSalidaTemporal'];
                    $esPorPieza = intval($resInv[0]['porPiezas']) === 1;
                    
                    $cajasActuales = intval($resInv[0]['cantidadCajas']);
                    $piezasActuales = intval($resInv[0]['cantidadPiezas']);
                    $pesoActual = floatval($resInv[0]['cantidadPeso']);

                    if ($esPorPieza) {
                        if ($cajasActuales > 0) {
                            $cajasActuales -= $piezas;
                        } else {
                            $piezasActuales -= $piezas;
                        }
                    } else {
                        $pesoActual -= $kilos;
                        $piezasActuales -= $piezas; 
                    }

                    $cajasActuales = max(0, $cajasActuales);
                    $piezasActuales = max(0, $piezasActuales);
                    $pesoActual = max(0, $pesoActual);

                    if ($cajasActuales <= 0 && $piezasActuales <= 0 && $pesoActual <= 0) {
                        $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTemp]);
                    } else {
                        $this->db->update("UPDATE InventarioTemporalSalida SET cantidadCajas = ?, cantidadPiezas = ?, cantidadPeso = ? WHERE idSalidaTemporal = ?", [$cajasActuales, $piezasActuales, $pesoActual, $idTemp]);
                    }
                }
            }
        }

        foreach ($estibadores as $idEstibador) {
            if (!empty($idEstibador)) {
                $this->db->insert("INSERT INTO nota_estibadores (id_nota, id_estibador) VALUES (?, ?)", [$idNota, $idEstibador]);
            }
        }
        return (int)$idNota;
    }

    public function reversarInventarioNota(int $idNota): bool {
        $sqlDetalles = "SELECT idProducto, kilos, piezas FROM detalle_notas WHERE id_nota = ?";
        $detalles = $this->db->select($sqlDetalles, [$idNota]);

        if (empty($detalles)) return false;

        foreach ($detalles as $det) {
            $idProd = (int)$det['idProducto'];
            $kilosDev = floatval($det['kilos']);
            $piezasDev = intval($det['piezas']);

            $sqlProd = "SELECT porPiezas FROM Producto WHERE idProducto = ?";
            $resProd = $this->db->select($sqlProd, [$idProd]);
            if (empty($resProd)) continue;

            $esPorPieza = intval($resProd[0]['porPiezas']) === 1;

            $sqlTemp = "SELECT idSalidaTemporal, cantidadPeso, cantidadPiezas, cantidadCajas FROM InventarioTemporalSalida WHERE idProducto = ?";
            $resTemp = $this->db->select($sqlTemp, [$idProd]);

            if (!empty($resTemp)) {
                $idTemp = $resTemp[0]['idSalidaTemporal'];
                if ($esPorPieza) {
                    $cajasActuales = intval($resTemp[0]['cantidadCajas']);
                    if ($cajasActuales > 0 || $piezasDev > 0) {
                        if ($cajasActuales > 0) {
                            $this->db->update("UPDATE InventarioTemporalSalida SET cantidadCajas = cantidadCajas + ? WHERE idSalidaTemporal = ?", [$piezasDev, $idTemp]);
                        } else {
                            $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPiezas = cantidadPiezas + ? WHERE idSalidaTemporal = ?", [$piezasDev, $idTemp]);
                        }
                    }
                } else {
                    $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPeso = cantidadPeso + ? WHERE idSalidaTemporal = ?", [$kilosDev, $idTemp]);
                }
            } else {
                $cajasIns = $esPorPieza ? $piezasDev : 0;
                $piezasIns = 0;
                $pesoIns = !$esPorPieza ? $kilosDev : 0.00;
                $this->db->insert("INSERT INTO InventarioTemporalSalida (idProducto, cantidadCajas, cantidadPiezas, cantidadPeso) VALUES (?, ?, ?, ?)", [$idProd, $cajasIns, $piezasIns, $pesoIns]);
            }
        }
        return true;
    }

    public function obtenerNotasDelVendedor(int $idVendedor): array {
        $sql = "SELECT n.id_nota, n.folio, n.nombre_cliente, n.estado, n.fecha_creacion,
                       GROUP_CONCAT(CONCAT(d.kilos, 'kg / ', d.piezas, 'pzas - ', p.nombreProducto) SEPARATOR ' | ') AS resumen_productos
                FROM notas n
                LEFT JOIN detalle_notas d ON n.id_nota = d.id_nota
                LEFT JOIN Producto p ON d.idProducto = p.idProducto
                WHERE n.id_vendedor = ? AND n.estado IN ('GUARDADA', 'RECHAZADA')
                GROUP BY n.id_nota
                ORDER BY n.fecha_creacion DESC";
        return $this->db->select($sql, [$idVendedor]);
    }

    public function obtenerDatosNotaParaEditar(int $idNota): array {
        $sqlNota = "SELECT * FROM notas WHERE id_nota = ?";
        $nota = $this->db->select($sqlNota, [$idNota]);
        if (empty($nota)) return [];

        $sqlDetalles = "SELECT d.idProducto AS id_producto, p.nombreProducto AS nombre_producto, d.kilos, d.piezas 
                        FROM detalle_notas d
                        INNER JOIN Producto p ON d.idProducto = p.idProducto
                        WHERE d.id_nota = ?";
        $productos = $this->db->select($sqlDetalles, [$idNota]);

        $sqlEstibadores = "SELECT id_estibador FROM nota_estibadores WHERE id_nota = ?";
        $estibadoresRes = $this->db->select($sqlEstibadores, [$idNota]);
        $estibadores = array_column($estibadoresRes, 'id_estibador');

        return [
            'nombre_cliente' => $nota[0]['nombre_cliente'],
            'estibadores' => $estibadores,
            'productos' => $productos
        ];
    }

    public function eliminarNota(int $idNota): bool {
        $this->db->delete("DELETE FROM nota_estibadores WHERE id_nota = ?", [$idNota]);
        $this->db->delete("DELETE FROM detalle_notas WHERE id_nota = ?", [$idNota]);
        $this->db->delete("DELETE FROM notas WHERE id_nota = ?", [$idNota]);
        return true;
    }

    public function abrirCajaConvertirAKilos(int $idCaja, int $idPechoSuelto, float $pesoKilos): bool {
        $sqlCaja = "SELECT idSalidaTemporal, cantidadCajas, cantidadPiezas FROM InventarioTemporalSalida WHERE idProducto = ?";
        $resCaja = $this->db->select($sqlCaja, [$idCaja]);
        
        if (empty($resCaja)) {
            return false; 
        }
        
        $idTempCaja = $resCaja[0]['idSalidaTemporal'];
        $cajas = intval($resCaja[0]['cantidadCajas']);
        $piezas = intval($resCaja[0]['cantidadPiezas']);
        
        if ($cajas > 0) {
            $nuevasCajas = $cajas - 1;
            if ($nuevasCajas <= 0 && $piezas <= 0) {
                $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTempCaja]);
            } else {
                $this->db->update("UPDATE InventarioTemporalSalida SET cantidadCajas = ? WHERE idSalidaTemporal = ?", [$nuevasCajas, $idTempCaja]);
            }
        } elseif ($piezas > 0) {
            $nuevasPiezas = $piezas - 1;
            if ($nuevasPiezas <= 0 && $cajas <= 0) {
                $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTempCaja]);
            } else {
                $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPiezas = ? WHERE idSalidaTemporal = ?", [$nuevasPiezas, $idTempCaja]);
            }
        } else {
            return false; 
        }
        
        $sqlPecho = "SELECT idSalidaTemporal, cantidadPeso FROM InventarioTemporalSalida WHERE idProducto = ?";
        $resPecho = $this->db->select($sqlPecho, [$idPechoSuelto]);
        
        if (!empty($resPecho)) {
            $idTempPecho = $resPecho[0]['idSalidaTemporal'];
            $nuevoPeso = floatval($resPecho[0]['cantidadPeso']) + $pesoKilos;
            $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPeso = ? WHERE idSalidaTemporal = ?", [$nuevoPeso, $idTempPecho]);
        } else {
            $sqlInsert = "INSERT INTO InventarioTemporalSalida (idProducto, cantidadCajas, cantidadPiezas, cantidadPeso) VALUES (?, 0, 0, ?)";
            $this->db->insert($sqlInsert, [$idPechoSuelto, $pesoKilos]);
        }
        
        return true;
    }

    public function obtenerIdProductoPorNombre(string $nombreLike): int {
        $sql = "SELECT idProducto FROM Producto WHERE nombreProducto LIKE ? LIMIT 1";
        $res = $this->db->select($sql, ['%' . $nombreLike . '%']);
        
        return !empty($res) ? (int)$res[0]['idProducto'] : 0;
    }
}