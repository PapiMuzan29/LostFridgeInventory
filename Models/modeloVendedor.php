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
                    i.idSalidaTemporal,
                    i.idProducto AS id_producto,
                    p.nombreProducto,
                    p.porPiezas,
                    p.precio,
                    i.cantidadCajas,
                    i.cantidadPeso,
                    i.cantidadPiezas,
                    i.observaciones
                FROM InventarioTemporalSalida i
                INNER JOIN Producto p ON i.idProducto = p.idProducto
                ORDER BY i.idSalidaTemporal ASC";

        return $this->db->select($sql);
    }

    public function verificarDisponibilidad(array $productos): array {
        foreach ($productos as $prod) {
            $idProd = $prod['id_producto'] ?? null;
            $kilos = !empty($prod['kilos']) ? floatval($prod['kilos']) : 0;
            $piezas = !empty($prod['piezas']) ? intval($prod['piezas']) : 0;

            if (!$idProd) continue;

            // 1. Agregamos p.porPiezas a la consulta para traer tu configuración de BD
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

            // Identificar de dónde sacar las unidades
            $limiteUnidades = $dbCajas > 0 ? $dbCajas : $dbPiezas;

            if ($esCajaPechos) {
                // VENTA DE CAJA CERRADA
                if ($piezas <= 0) return ['exito' => false, 'mensaje' => "Debes ingresar cuántas cajas vendes de $nombre."];
                if ($piezas > $limiteUnidades) return ['exito' => false, 'mensaje' => "Solo hay $limiteUnidades cajas de $nombre en sistema."];
                if ($kilos <= 0) return ['exito' => false, 'mensaje' => "Debes ingresar el peso total de las cajas de $nombre."];
            
            } elseif ($esPorPiezas) {
                // PRODUCTOS POR PIEZA (Manteca, Mazos, etc.)
                if ($piezas <= 0) return ['exito' => false, 'mensaje' => "Debes ingresar la cantidad de piezas para $nombre."];
                if ($piezas > $limiteUnidades) return ['exito' => false, 'mensaje' => "Solo hay $limiteUnidades piezas de $nombre."];
            
            } else {
                // PRODUCTOS A GRANEL (porPiezas = 0 -> Pechos, Piernas, etc.)
                // Solo validamos estrictamente los kilos
                if ($kilos <= 0) return ['exito' => false, 'mensaje' => "Los kilos para $nombre deben ser mayores a 0."];
                
                $limiteMaxKilos = $dbKilos + 0.20; // Tolerancia de 200 gramos
                if ($kilos > $limiteMaxKilos) return ['exito' => false, 'mensaje' => "Límite excedido para $nombre. (Max: $limiteMaxKilos kg)."];
                
            }
        }
        return ['exito' => true];
    }

    // 1. Guardar nota en estado GUARDADA (En espera) o PENDIENTE (Enviada a caja)
    public function guardarNotaConEstado(string $folio, string $cliente, int $idCuenta, array $productos, array $estibadores, string $estadoInicial = 'GUARDADA'): bool|int {
        $sqlNota = "INSERT INTO notas (folio, nombre_cliente, id_vendedor, estado, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
        $idNota = $this->db->insert($sqlNota, [$folio, $cliente, $idCuenta, $estadoInicial]);

        if (!$idNota) return false;

        foreach ($productos as $prod) {
            $idProd = $prod['id_producto'] ?? null;
            $kilos = !empty($prod['kilos']) ? floatval($prod['kilos']) : 0;
            $piezas = !empty($prod['piezas']) ? intval($prod['piezas']) : 0;

            if ($idProd) {
                $sqlDetalle = "INSERT INTO detalle_notas (id_nota, idProducto, kilos, piezas) VALUES (?, ?, ?, ?)";
                $this->db->insert($sqlDetalle, [$idNota, $idProd, $kilos, $piezas]);

                // Descontar del inventario temporal (Reserva)
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

                    // 1. Restar lo correspondiente según el tipo
                    if ($esPorPieza) {
                        if ($cajasActuales > 0) {
                            $cajasActuales -= $piezas;
                        } else {
                            $piezasActuales -= $piezas;
                        }
                    } else {
                        $pesoActual -= $kilos;
                        $piezasActuales -= $piezas; // Descuenta piezas físicas si el vendedor las anotó
                    }

                    // 2. Prevenir números negativos en caso de decimales extraños
                    $cajasActuales = max(0, $cajasActuales);
                    $piezasActuales = max(0, $piezasActuales);
                    $pesoActual = max(0, $pesoActual);

                    // 3. REGLA UNIVERSAL DE ELIMINACIÓN
                    // Solo se borra si TODO el inventario de esa fila se agotó
                    if ($cajasActuales <= 0 && $piezasActuales <= 0 && $pesoActual <= 0) {
                        $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTemp]);
                    } else {
                        // Si sobró algo en cualquier columna, simplemente actualizamos
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

    // 2. Devolver inventario a la tabla temporal (Reversa al editar o cancelar)
    public function reversarInventarioNota(int $idNota): bool {
        $sqlDetalles = "SELECT idProducto, kilos, piezas FROM detalle_notas WHERE id_nota = ?";
        $detalles = $this->db->select($sqlDetalles, [$idNota]);

        if (empty($detalles)) return false;

        foreach ($detalles as $det) {
            $idProd = (int)$det['idProducto'];
            $kilosDev = floatval($det['kilos']);
            $piezasDev = intval($det['piezas']);

            // Verificar si el producto es por pieza o granel
            $sqlProd = "SELECT porPiezas FROM Producto WHERE idProducto = ?";
            $resProd = $this->db->select($sqlProd, [$idProd]);
            if (empty($resProd)) continue;

            $esPorPieza = intval($resProd[0]['porPiezas']) === 1;

            // Revisar si ya existe en InventarioTemporalSalida
            $sqlTemp = "SELECT idSalidaTemporal, cantidadPeso, cantidadPiezas, cantidadCajas FROM InventarioTemporalSalida WHERE idProducto = ?";
            $resTemp = $this->db->select($sqlTemp, [$idProd]);

            if (!empty($resTemp)) {
                $idTemp = $resTemp[0]['idSalidaTemporal'];
                if ($esPorPieza) {
                    // Si es producto por pieza, decidimos si sumamos a cajas o piezas basándonos en cómo venía
                    // Por seguridad de control, si manejaba cajas o piezas los devolvemos a donde corresponda
                    $cajasActuales = intval($resTemp[0]['cantidadCajas']);
                    if ($cajasActuales > 0 || $piezasDev > 0) {
                        // Si era de tipo caja o pieza genérica
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
                // Si ya no existe en la temporal, lo reinserción
                $cajasIns = $esPorPieza ? $piezasDev : 0;
                $piezasIns = 0;
                $pesoIns = !$esPorPieza ? $kilosDev : 0.00;
                $this->db->insert("INSERT INTO InventarioTemporalSalida (idProducto, cantidadCajas, cantidadPiezas, cantidadPeso) VALUES (?, ?, ?, ?)", [$idProd, $cajasIns, $piezasIns, $pesoIns]);
            }
        }
        return true;
    }

    // 3. Obtener notas del vendedor (Guardadas o Rechazadas para su bandeja)
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

    // 4. Obtener una nota completa con sus estibadores y productos para rellenar el form de edición
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

    // 5. Cancelar / Borrar nota del sistema
    public function eliminarNota(int $idNota): bool {
        $this->db->delete("DELETE FROM nota_estibadores WHERE id_nota = ?", [$idNota]);
        $this->db->delete("DELETE FROM detalle_notas WHERE id_nota = ?", [$idNota]);
        $this->db->delete("DELETE FROM notas WHERE id_nota = ?", [$idNota]);
        return true;
    }

    public function abrirCajaConvertirAKilos(int $idCaja, int $idPechoSuelto, float $pesoKilos): bool {
        // 1. Traer ambas columnas (Cajas y Piezas)
        $sqlCaja = "SELECT idSalidaTemporal, cantidadCajas, cantidadPiezas FROM InventarioTemporalSalida WHERE idProducto = ?";
        $resCaja = $this->db->select($sqlCaja, [$idCaja]);
        
        if (empty($resCaja)) {
            return false; 
        }
        
        $idTempCaja = $resCaja[0]['idSalidaTemporal'];
        $cajas = intval($resCaja[0]['cantidadCajas']);
        $piezas = intval($resCaja[0]['cantidadPiezas']);
        
        // 2. Determinar de dónde restar (priorizamos cajas, si no, piezas)
        if ($cajas > 0) {
            $nuevasCajas = $cajas - 1;
            // Solo borramos si cajas y piezas quedan en 0
            if ($nuevasCajas <= 0 && $piezas <= 0) {
                $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTempCaja]);
            } else {
                $this->db->update("UPDATE InventarioTemporalSalida SET cantidadCajas = ? WHERE idSalidaTemporal = ?", [$nuevasCajas, $idTempCaja]);
            }
        } elseif ($piezas > 0) {
            $nuevasPiezas = $piezas - 1;
            // Solo borramos si cajas y piezas quedan en 0
            if ($nuevasPiezas <= 0 && $cajas <= 0) {
                $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTempCaja]);
            } else {
                $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPiezas = ? WHERE idSalidaTemporal = ?", [$nuevasPiezas, $idTempCaja]);
            }
        } else {
            return false; // Ambos inventarios están en 0
        }
        
        // 3. Buscar si el pecho suelto ya está activo
        $sqlPecho = "SELECT idSalidaTemporal, cantidadPeso FROM InventarioTemporalSalida WHERE idProducto = ?";
        $resPecho = $this->db->select($sqlPecho, [$idPechoSuelto]);
        
        if (!empty($resPecho)) {
            // Si existe, sumamos los kilos
            $idTempPecho = $resPecho[0]['idSalidaTemporal'];
            $nuevoPeso = floatval($resPecho[0]['cantidadPeso']) + $pesoKilos;
            $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPeso = ? WHERE idSalidaTemporal = ?", [$nuevoPeso, $idTempPecho]);
        } else {
            // Si no existe, lo damos de alta en el inventario temporal
            $sqlInsert = "INSERT INTO InventarioTemporalSalida (idProducto, cantidadCajas, cantidadPiezas, cantidadPeso) VALUES (?, 0, 0, ?)";
            $this->db->insert($sqlInsert, [$idPechoSuelto, $pesoKilos]);
        }
        
        return true;
    }


}