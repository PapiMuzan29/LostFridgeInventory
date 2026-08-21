<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloEncargado {
    private $db;

    public function __construct() {
        // Usamos tu método Singleton para reutilizar la conexión y ahorrar memoria
        $this->db = BD::obtenerInstancia(); 
    }

    public function obtenerTodos() {
        $sql = "SELECT idProducto, nombreProducto FROM Producto ORDER BY nombreProducto ASC";
        return $this->db->select($sql);
    }

    public function actualizarEstadoContable($idProducto, $porPiezas) {
        $sql = "UPDATE Producto SET porPiezas = ? WHERE idProducto = ?";
        $params = [$porPiezas, $idProducto];
        $filasAfectadas = $this->db->update($sql, $params);
        return $filasAfectadas > 0;
    }

    public function obtenerTotalProductos($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM Producto";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " WHERE nombreProducto LIKE ?";
            $params[] = "%" . $busqueda . "%";
        }
        
        $resultado = $this->db->select($sql, $params);
        return isset($resultado[0]['total']) ? (int)$resultado[0]['total'] : 0;
    }

    public function obtenerPaginados($limite, $offset, $busqueda = '') {
        $sql = "SELECT idProducto, nombreProducto, porPiezas FROM Producto";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " WHERE nombreProducto LIKE ?";
            $params[] = "%" . $busqueda . "%";
        }
    
        $sql .= " ORDER BY nombreProducto ASC LIMIT $limite OFFSET $offset";
                
        return $this->db->select($sql, $params);
    }

    // ==========================================
    // FUNCIONES AUXILIARES PARA DETALLES
    // ==========================================
    
    // Traer los productos de una nota específica
    private function obtenerProductosPorNota($idNota) {
        $sql = "SELECT 
                    dn.id_detalle,
                    dn.idProducto,
                    p.nombreProducto,
                    dn.kilos,
                    dn.piezas
                FROM detalle_notas dn
                JOIN Producto p ON dn.idProducto = p.idProducto
                WHERE dn.id_nota = ?";
        
        return $this->db->select($sql, [$idNota]);
    }

    // Traer los estibadores de una nota específica
    private function obtenerEstibadoresPorNota($idNota) {
        $sql = "SELECT 
                    e.id_estibador,
                    CONCAT(c.nombreUsuario, ' ', c.apellidoPaternoUsuario) AS nombre_estibador
                FROM nota_estibadores e
                JOIN cuenta c ON e.id_estibador = c.idCuenta
                WHERE e.id_nota = ?";
        
        return $this->db->select($sql, [$idNota]);
    }

    // ==========================================
    // FUNCIÓN PRINCIPAL ACTUALIZADA
    // ==========================================
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
                
        // Obtenemos las notas base
        $notasBase = $this->db->select($sql, $params);
        
        // Arreglo donde guardaremos las notas con todos sus detalles
        $notasCompletas = [];
        
        // Recorremos cada nota para buscarle sus productos y estibadores
        foreach ($notasBase as $nota) {
            $idNota = $nota['id_nota'];
            
            // Le agregamos un nuevo campo (arreglo) con sus productos
            $nota['productos'] = $this->obtenerProductosPorNota($idNota);
            
            // Le agregamos un nuevo campo (arreglo) con sus estibadores
            $nota['estibadores'] = $this->obtenerEstibadoresPorNota($idNota);
            
            $notasCompletas[] = $nota;
        }
        
        return $notasCompletas;
    }

    public function actualizarEstadoNota($idNota, $nuevoEstado) {
        $sql = "UPDATE notas SET estado = ?, fecha_salida = ? WHERE id_nota = ?";
        
        $params = [$nuevoEstado, date('Y-m-d H:i:s'), $idNota];
        
        $filasAfectadas = $this->db->update($sql, $params);
        
        return $filasAfectadas > 0;
    }

}