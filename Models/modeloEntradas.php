<?php
// Usamos la misma lógica de inclusión que ya te funciona en los otros modelos
require_once __DIR__ . '/../Config/BD.php'; // O como se llame tu archivo principal de conexión si no es directo. 
// Si marcas error de ruta, recuerda que puedes usar require_once 'Conexion.php' si están en la misma carpeta.

class modeloEntradas {
    private $db;

    public function __construct() {
        // Inicializamos tu clase de base de datos habitual
        // En tu controlador anterior llamabas a $this->db->consulta(), por lo que asumimos que tienes un objeto global o una clase inyectada.
        // Si usas una clase llamada 'Conexion' o 'BD', instánciala aquí:
        $this->db = new BD(); 
    }

    /**
     * 👥 Trae los proveedores activos
     */
    public function obtenerTodosProveedores() {
        $query = "SELECT idProveedor, nombreProveedor FROM proveedor WHERE activo = 1 ORDER BY nombreProveedor ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en modeloEntradas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProducto(): array{
        string $busqueda = ''

        $query = "SELECT 
            idProducto, 
            nombreProducto 
            FROM producto 
            WHERE activo = 1 ORDER BY nombreProducto ASC";
        
    }
}