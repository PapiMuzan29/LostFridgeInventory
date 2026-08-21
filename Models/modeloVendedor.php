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

    public function obtenerProductosActivos(): array {
        return $this->db->select("SELECT idProducto AS id_producto, nombreProducto FROM producto WHERE activo = 1 ORDER BY nombreProducto ASC");
    }

    public function obtenerEstibadores(): array {
        return $this->db->select("
            SELECT idCuenta AS id_usuario, CONCAT(nombreUsuario, ' ', apellidoPaternoUsuario) AS nombre 
            FROM cuenta 
            WHERE estado = 1 AND idRol = 4 
            ORDER BY nombreUsuario ASC
        ");
    }

    public function guardarNota(string $folio, string $cliente, int $idCuenta, array $productos, array $estibadores): bool {
        // Se cambia idCuenta por id_vendedor en la consulta SQL
        $sqlNota = "INSERT INTO notas (folio, nombre_cliente, id_vendedor, estado, fecha_creacion) VALUES (?, ?, ?, 'PENDIENTE', NOW())";
        
        $idNota = $this->db->insert($sqlNota, [$folio, $cliente, $idCuenta]);

        if (!$idNota) {
            return false;
        }

        // Insertar detalle de productos
        foreach ($productos as $prod) {
            $idProd = $prod['id_producto'] ?? null;
            $kilos = !empty($prod['kilos']) ? floatval($prod['kilos']) : 0.00;
            $piezas = !empty($prod['piezas']) ? intval($prod['piezas']) : 0;

            if ($idProd) {
                $sqlDetalle = "INSERT INTO detalle_notas (id_nota, idProducto, kilos, piezas) VALUES (?, ?, ?, ?)";
                $this->db->insert($sqlDetalle, [$idNota, $idProd, $kilos, $piezas]);
            }
        }

        // Insertar estibadores
        foreach ($estibadores as $idEstibador) {
            if (!empty($idEstibador)) {
                $sqlEstibador = "INSERT INTO nota_estibadores (id_nota, id_estibador) VALUES (?, ?)";
                $this->db->insert($sqlEstibador, [$idNota, $idEstibador]);
            }
        }

        return true;
    }
}