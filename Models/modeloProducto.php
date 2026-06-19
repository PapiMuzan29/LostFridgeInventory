<?php


class modeloProducto {
    private BD $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia();
    }

    public function getAllUsers(string $busqueda = '', string $estado = '', int $pagina = 1): array {
        $registrosPorPagina = 4;
        $offset = ($pagina - 1) * $registrosPorPagina;

        $query ="SELECT 
                    Producto.codigoProducto, 
                    Producto.idProveedor, 
                    p.descripcion_producto, 
                    p.precio_producto, 
                    p.stock_producto, 
                    c.nombre_categoria, 
                    m.nombre_marca, 
                    e.nombre_estado
                WHERE 1 = 1";
    }
}

?>