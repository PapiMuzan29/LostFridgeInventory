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

            if ($kilos <= 0 && $piezas <= 0) {
                return ['exito' => false, 'mensaje' => "Debes ingresar una cantidad mayor a cero."];
            }

            // Reemplazar la consulta para traer cantidadPiezas
            $sql = "SELECT i.cantidadPeso, i.cantidadPiezas, p.nombreProducto, p.porPiezas 
                    FROM InventarioTemporalSalida i
                    INNER JOIN producto p ON i.idProducto = p.idProducto
                    WHERE i.idProducto = ?";
            $res = $this->db->select($sql, [$idProd]);
            
            if (empty($res)) return ['exito' => false, 'mensaje' => "Un producto ya no está disponible."];
            
            // Extraer las piezas en lugar de cajas
            $dbPiezas = intval($res[0]['cantidadPiezas']);
            $dbKilos = floatval($res[0]['cantidadPeso']);
            $esPorPieza = intval($res[0]['porPiezas']) === 1;
            $nombre = $res[0]['nombreProducto'];

            if ($esPorPieza) {
                if ($piezas <= 0) return ['exito' => false, 'mensaje' => "La cantidad en piezas para $nombre debe ser mayor a 0."];
                if ($piezas > $dbPiezas) return ['exito' => false, 'mensaje' => "Solo hay $dbPiezas piezas de $nombre."];
            } else {
                if ($kilos <= 0) return ['exito' => false, 'mensaje' => "Los kilos para $nombre deben ser mayores a 0."];
                $limiteMax = $dbKilos + 0.20;
                if ($kilos > $limiteMax) return ['exito' => false, 'mensaje' => "Límite excedido para $nombre. Hay $dbKilos kg (Max: $limiteMax kg)."];
            }
        }
        return ['exito' => true];
    }

    public function guardarNota(string $folio, string $cliente, int $idCuenta, array $productos, array $estibadores): bool {
        $sqlNota = "INSERT INTO notas (folio, nombre_cliente, id_vendedor, estado, fecha_creacion) VALUES (?, ?, ?, 'PENDIENTE', NOW())";
        $idNota = $this->db->insert($sqlNota, [$folio, $cliente, $idCuenta]);

        if (!$idNota) return false;

        foreach ($productos as $prod) {
            $idProd = $prod['id_producto'] ?? null;
            $kilos = !empty($prod['kilos']) ? floatval($prod['kilos']) : 0;
            $piezas = !empty($prod['piezas']) ? intval($prod['piezas']) : 0;

            if ($idProd) {
                $sqlDetalle = "INSERT INTO detalle_notas (id_nota, idProducto, kilos, piezas) VALUES (?, ?, ?, ?)";
                $this->db->insert($sqlDetalle, [$idNota, $idProd, $kilos, $piezas]);

                // Gestión de Inventario Temporal (Ramificación)
                // Gestión de Inventario Temporal (Ramificación)
                $sqlInv = "SELECT idSalidaTemporal, cantidadPeso, cantidadPiezas, p.porPiezas 
                           FROM InventarioTemporalSalida i
                           INNER JOIN producto p ON i.idProducto = p.idProducto
                           WHERE i.idProducto = ?";
                $resInv = $this->db->select($sqlInv, [$idProd]);
                
                if (!empty($resInv)) {
                    $idTemp = $resInv[0]['idSalidaTemporal'];
                    $esPorPieza = intval($resInv[0]['porPiezas']) === 1;
                    
                    if ($esPorPieza) {
                        // Restar piezas y actualizar la columna correcta
                        $nuevoPiezas = intval($resInv[0]['cantidadPiezas']) - $piezas;
                        if ($nuevoPiezas <= 0) {
                            $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTemp]);
                        } else {
                            $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPiezas = ? WHERE idSalidaTemporal = ?", [$nuevoPiezas, $idTemp]);
                        }
                    } else {
                        // Kilos se mantiene igual
                        $nuevoPeso = floatval($resInv[0]['cantidadPeso']) - $kilos;
                        if ($nuevoPeso <= 0) {
                            $this->db->delete("DELETE FROM InventarioTemporalSalida WHERE idSalidaTemporal = ?", [$idTemp]);
                        } else {
                            $this->db->update("UPDATE InventarioTemporalSalida SET cantidadPeso = ? WHERE idSalidaTemporal = ?", [$nuevoPeso, $idTemp]);
                        }
                    }
                }
            }
        }

        foreach ($estibadores as $idEstibador) {
            if (!empty($idEstibador)) {
                $this->db->insert("INSERT INTO nota_estibadores (id_nota, id_estibador) VALUES (?, ?)", [$idNota, $idEstibador]);
            }
        }
        return true;
    }
}