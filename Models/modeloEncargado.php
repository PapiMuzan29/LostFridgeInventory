<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloEncargado {
    private $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia(); 
    }

    public function obtenerTodos() {
        // 💡 Corrección: Producto -> producto
        $sql = "SELECT idProducto, nombreProducto FROM producto ORDER BY nombreProducto ASC";
        return $this->db->select($sql);
    }

    public function actualizarEstadoProducto($idProducto, $porPiezas, $factura) {
        // 💡 Corrección: Producto -> producto
        $sql = "UPDATE producto SET porPiezas = ?, factura = ? WHERE idProducto = ?";
        return $this->db->update($sql, [$porPiezas, $factura, $idProducto]);
    }
    
    public function obtenerTotalProductos($busqueda = '') {
        // 💡 Corrección: Producto -> producto
        $sql = "SELECT COUNT(*) as total FROM producto";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " WHERE nombreProducto LIKE ?";
            $params[] = "%" . $busqueda . "%";
        }
        
        $resultado = $this->db->select($sql, $params);
        return isset($resultado[0]['total']) ? (int)$resultado[0]['total'] : 0;
    }

    public function obtenerPaginados($limite, $offset, $busqueda = '') {
        // 💡 Corrección: Producto -> producto
        $sql = "SELECT idProducto, nombreProducto, porPiezas, factura FROM producto";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " WHERE nombreProducto LIKE ?";
            $params[] = "%" . $busqueda . "%";
        }
    
        $sql .= " ORDER BY nombreProducto ASC LIMIT $limite OFFSET $offset";
        return $this->db->select($sql, $params);
    }

    private function obtenerProductosPorNota($idNota) {
        // 💡 Corrección: Producto -> producto
        $sql = "SELECT 
                    dn.id_detalle,
                    dn.idProducto,
                    p.nombreProducto,
                    dn.kilos,
                    dn.piezas
                FROM detalle_notas dn
                JOIN producto p ON dn.idProducto = p.idProducto
                WHERE dn.id_nota = ?";
        
        return $this->db->select($sql, [$idNota]);
    }

    private function obtenerEstibadoresPorNota($idNota) {
        $sql = "SELECT 
                    e.id_estibador,
                    CONCAT(c.nombreUsuario, ' ', c.apellidoPaternoUsuario) AS nombre_estibador
                FROM nota_estibadores e
                JOIN cuenta c ON e.id_estibador = c.idCuenta
                WHERE e.id_nota = ?";
        
        return $this->db->select($sql, [$idNota]);
    }

    public function obtenerNotasPorEstado($estado, $busqueda = '') {
        $params = [$estado];
        
        $sql = "SELECT 
                    n.id_nota, 
                    n.folio, 
                    n.nombre_cliente, 
                    n.fecha_creacion,
                    CONCAT(v.nombreUsuario, ' ', v.apellidoPaternoUsuario, ' ', v.apellidoMaternoUsuario) AS nombre_vendedor
                FROM notas n
                LEFT JOIN cuenta v ON n.id_vendedor = v.idCuenta
                WHERE n.estado = ?";
                
        if (!empty($busqueda)) {
            $sql .= " AND (n.folio LIKE ? OR n.nombre_cliente LIKE ?)";
            $termino = "%" . $busqueda . "%";
            $params[] = $termino;
            $params[] = $termino;
        }
        
        $sql .= " ORDER BY n.fecha_creacion DESC";
                
        $notasBase = $this->db->select($sql, $params);
        $notasCompletas = [];
        
        if (!empty($notasBase)) {
            foreach ($notasBase as $nota) {
                $idNota = $nota['id_nota'];
                $nota['productos'] = $this->obtenerProductosPorNota($idNota);
                $nota['estibadores'] = $this->obtenerEstibadoresPorNota($idNota);
                $notasCompletas[] = $nota;
            }
        }
        
        return $notasCompletas;
    }

    public function contarNotasPorEstado($estado) {
        $sql = "SELECT COUNT(*) as total FROM notas WHERE estado = ?";
        
        $resultado = $this->db->select($sql, [$estado]);
        
        return isset($resultado[0]['total']) ? (int)$resultado[0]['total'] : 0;
    }

    public function requiereFactura($idNota) {
        // 💡 Corrección: Producto -> producto
        $sql = "SELECT COUNT(*) as total_facturables 
                FROM detalle_notas dn
                JOIN producto p ON dn.idProducto = p.idProducto
                WHERE dn.id_nota = ? AND p.factura = 1";
                
        $resultado = $this->db->select($sql, [$idNota]);
        return (isset($resultado[0]['total_facturables']) && (int)$resultado[0]['total_facturables'] > 0);
    }

    public function aprobarNotaConFolios($idNota, $folios) {
        try {
            if (method_exists($this->db, 'beginTransaction')) {
                $this->db->beginTransaction();
            }

            date_default_timezone_set('America/Mexico_City'); 
            $fechaMexico = date('Y-m-d H:i:s');
            $sqlNota = "UPDATE notas SET estado = 'APROBADO', fecha_salida = ? WHERE id_nota = ?";
            $this->db->update($sqlNota, [$fechaMexico, $idNota]);

            $sqlFolio = "INSERT INTO folios_tickets (id_nota, folio_ticket) VALUES (?, ?)";
            
            foreach ($folios as $folio) {
                $folioLimpio = trim($folio);
                if ($folioLimpio !== '') {
                    $this->db->insert($sqlFolio, [$idNota, $folioLimpio]);
                }
            }

            if (method_exists($this->db, 'commit')) {
                $this->db->commit();
            }
            return true;

        } catch (Exception $e) {
            if (method_exists($this->db, 'rollBack')) {
                $this->db->rollBack();
            }
            throw new Exception("Error en la transacción: " . $e->getMessage());
        }
    }
}