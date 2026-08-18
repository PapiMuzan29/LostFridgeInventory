<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/BD.php';

class modeloVendedor {

    /**
     * Obtiene todos los productos activos de la base de datos
     */
    public static function obtenerProductos(): array {
        $db = BD::obtenerInstancia();
        
        $query = "SELECT 
                    idProducto AS id_producto, 
                    nombreProducto AS nombre_producto 
                  FROM producto 
                  WHERE activo = 1 
                  ORDER BY nombreProducto ASC";

        return $db->select($query);
    }

    /**
     * Obtiene el catálogo de estibadores/usuarios activos
     */
    public static function obtenerEstibadores(): array {
        $db = BD::obtenerInstancia();

        $query = "SELECT 
                    idCuenta AS id_usuario, 
                    apodoUsuario AS nombre 
                  FROM cuenta 
                  WHERE estado = 1 
                  ORDER BY apodoUsuario ASC";

        return $db->select($query);
    }
}