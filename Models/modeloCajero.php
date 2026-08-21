<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/BD.php';

class modeloCajero {

    private BD $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia();
    }

    /**
     * Obtiene las notas pendientes con sus productos y estibadores.
     */
    public function obtenerNotasPendientes(): array {
        $sqlNotas = "
            SELECT 
                n.id_nota,
                n.folio,
                n.nombre_cliente AS cliente,
                n.fecha_creacion AS fecha,
                COALESCE(CONCAT(v.nombreUsuario, ' ', v.apellidoPaternoUsuario), 'Vendedor General') AS vendedor
            FROM notas n
            LEFT JOIN cuenta v ON n.id_vendedor = v.idCuenta
            WHERE UPPER(n.estado) = 'PENDIENTE'
            ORDER BY n.fecha_creacion DESC
        ";
        
        $notasRaw = $this->db->select($sqlNotas);
        $notasCompleta = [];

        if (empty($notasRaw)) {
            return [];
        }

        foreach ($notasRaw as $nota) {
            $idNota = (int)$nota['id_nota'];

            $nota['productos'] = $this->obtenerProductosPorNota($idNota);
            $nota['estibadores'] = $this->obtenerEstibadoresPorNota($idNota);

            $notasCompleta[] = $nota;
        }

        return $notasCompleta;
    }

    public function obtenerProductosPorNota(int $idNota): array {
        $sql = "
            SELECT 
                COALESCE(p.nombreProducto, 'Producto Sin Nombre') AS nombre,
                dn.kilos,
                dn.piezas
            FROM detalle_notas dn
            LEFT JOIN producto p ON dn.idProducto = p.idProducto
            WHERE dn.id_nota = ?
        ";
        return $this->db->select($sql, [$idNota]);
    }

    public function obtenerEstibadoresPorNota(int $idNota): array {
        $sql = "
            SELECT 
                CONCAT(c.nombreUsuario, ' ', c.apellidoPaternoUsuario) AS nombre
            FROM nota_estibadores ne
            LEFT JOIN cuenta c ON ne.id_estibador = c.idCuenta
            WHERE ne.id_nota = ?
        ";
        return $this->db->select($sql, [$idNota]);
    }

    /**
     * Cambia el estado de la nota a PAGADO y asigna el id_cajero.
     */
    public function cambiarEstadoPagado(int $idNota, int $idCajero): bool {
        try {
            $sql = "UPDATE notas 
                    SET estado = 'COBRADO', 
                        id_cajero = ?, 
                        fecha_cobro = NOW() 
                    WHERE id_nota = ?";
            
            $filasAfectadas = $this->db->update($sql, [$idCajero, $idNota]);

            return $filasAfectadas >= 0;
        } catch (Exception $e) {
            error_log("Error en cambiarEstadoPagado: " . $e->getMessage());
            return false;
        }
    }
}