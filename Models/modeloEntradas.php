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

    public function obtenerCodigoDeProducto(
        string $columna = '',
        string $valor = '',
        string $CodigoBarras = ''
    ): array {

        if ($CodigoBarras === '' || $columna === '' || $valor === '') {
            return [];
        }

        // Medida de seguridad: Validar que la columna sea correcta y evitar inyección
        $columnasValidas = ['codigoProveedor', 'nombreProveedor', 'rfcProveedor'];
        if (!in_array($columna, $columnasValidas)) {
            return [];
        }

        $query = "SELECT 
                    codigoBarrasProductosPosicion,
                    codigoBarrasProductosLongitud,
                    codigoBarrasEnterosPosicion,
                    codigoBarrasEnterosLongitud,
                    codigoBarrasDecimalesPosicion,
                    codigoBarrasDecimalesLongitud 
                  FROM Proveedor 
                  WHERE {$columna} = ? 
                  AND activo = 1";
    
        // CORRECCIÓN: Debe ser un arreglo
        $params = [$valor]; 

        try {
            $stmt = $this->db->consulta($query, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return []; 
            }
        } catch (Exception $e) {
            error_log("Error en modeloEntradas: " . $e->getMessage());
            return [];
        }

        $codigoBarrasProductosPosicion = $result['codigoBarrasProductosPosicion'] ?? 0;
        $codigoBarrasProductosLongitud = $result['codigoBarrasProductosLongitud'] ?? 0;
        $codigoBarrasEnterosPosicion   = $result['codigoBarrasEnterosPosicion'] ?? 0;
        $codigoBarrasEnterosLongitud   = $result['codigoBarrasEnterosLongitud'] ?? 0;
        $codigoBarrasDecimalesPosicion = $result['codigoBarrasDecimalesPosicion'] ?? 0;
        $codigoBarrasDecimalesLongitud = $result['codigoBarrasDecimalesLongitud'] ?? 0;

        $codigoProducto = mb_substr($CodigoBarras, $codigoBarrasProductosPosicion, $codigoBarrasProductosLongitud, 'UTF-8');
        $codigoEnteros = mb_substr($CodigoBarras, $codigoBarrasEnterosPosicion, $codigoBarrasEnterosLongitud, 'UTF-8');
        $codigoDecimales = mb_substr($CodigoBarras, $codigoBarrasDecimalesPosicion, $codigoBarrasDecimalesLongitud, 'UTF-8');

        return [
            'codigoProducto' => $codigoProducto,
            'codigoEnteros' => $codigoEnteros,
            'codigoDecimales' => $codigoDecimales
        ];
    }

    public function obtenerProducto(
        string $codigoBarras = '',
        string $codigoProveedor = '',
        string $nombreProveedor = '',
        string $rfc = ''
    ): array {

        if ($codigoBarras === '') {
            return [];
        }

        $columna = '';
        $valor = '';

        if ($codigoProveedor !== '') {
            $columna = 'codigoProveedor';
            $valor = $codigoProveedor;
        } else if ($nombreProveedor !== '') {
            $columna = 'nombreProveedor';
            $valor = $nombreProveedor;
        } else if ($rfc !== '') {
            $columna = 'rfcProveedor';
            $valor = $rfc;
        } else {
            return [];
        }
        
        $Producto = $this->obtenerCodigoDeProducto($columna, $valor, $codigoBarras);

        if (empty($Producto)) {
            return [];
        }

        $query = "SELECT 
                    nombreProducto
                  FROM Producto 
                  WHERE codigoBarrasProducto = ?";
                  
        $params = [$Producto['codigoProducto']]; 

        try {
            $stmt = $this->db->consulta($query, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return []; 
            }

            return [
                'nombreProducto' => $result['nombreProducto'] ?? '',
                'codigoEnteros' => $Producto['codigoEnteros'] ?? '',
                'codigoDecimales' => $Producto['codigoDecimales'] ?? ''
            ];
        } catch (Exception $e) {
            error_log("Error en modeloEntradas: " . $e->getMessage());
            return [];
        }
    }
}