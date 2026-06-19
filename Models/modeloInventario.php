<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloInventario {
    private $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia(); 
    }

    /**
     * 📦 MÓDULO PRODUCTOS: Obtiene los productos con su proveedor y peso total
     */
   public function getProducts($textoBusqueda = '', $estado = '', $pagina = 1) {
        $porPagina = 4;
        $offset = ($pagina - 1) * $porPagina;

        // 🔥 CORRECCIÓN: Agregamos el LEFT JOIN prov para traer 'prov.nombreProveedor' hacia la tabla
        $query = "SELECT p.*, p.totalCajas as totalCajas, p.totalCajas as cajas, 
                         c.nombreCategoria, prov.nombreProveedor 
                  FROM producto p
                  LEFT JOIN categoria c ON p.idCategoria = c.idCategoria
                  LEFT JOIN proveedor prov ON p.idProveedor = prov.idProveedor
                  WHERE (p.nombreProducto LIKE ? OR p.codigoProducto LIKE ?)";
                  
        $params = ["%$textoBusqueda%", "%$textoBusqueda%"];

        if ($estado !== '') {
            $query .= " AND p.activo = ?";
            $params[] = $estado;
        }

        $query .= " ORDER BY p.nombreProducto ASC LIMIT $porPagina OFFSET $offset";

        try {
            $stmt = $this->db->consulta($query, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en getProducts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🚚 MÓDULO PROVEEDORES: Obtiene los proveedores con paginación limpia
     */
    public function getAllProviders(string $busqueda = '', string $estado = '', int $pagina = 1): array {
        $registrosPorPagina = 4;
        $offset = ((int)$pagina - 1) * $registrosPorPagina;

        $query = "SELECT idProveedor, codigoProveedor, nombreProveedor, rfc, direccion, colonia, codigoPostal, estadoRepublica, status 
                  FROM proveedor 
                  WHERE 1 = 1";
        $params = [];

        if (!empty($busqueda)) {
            $query .= " AND (nombreProveedor LIKE ? OR rfc LIKE ? OR codigoProveedor LIKE ?)";
            $search = "%{$busqueda}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if ($estado !== '') {
            $query .= " AND status = ?";
            $params[] = (int)$estado;
        }

        $query .= " ORDER BY idProveedor DESC LIMIT " . (int)$registrosPorPagina . " OFFSET " . (int)$offset;
        
        try {
            return $this->db->select($query, $params);
        } catch (Exception $e) {
            error_log("Error en getAllProviders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 📊 ESTADÍSTICAS KPIs (Optimizado en una única consulta)
     */
    public function getKpiStats(): array {
        $stats = [
            'productos' => 0,
            'cajas'     => 0,
            'proximos'  => 0,
            'vencidos'  => 0
        ];

        // 🔥 CORRECCIÓN: Se cambia el SUM de filas activas por el SUM real de tu columna 'totalCajas'
        $sql = "SELECT 
                    COUNT(idProducto) as productos,
                    SUM(IF(activo = 1, IFNULL(totalCajas, 0), 0)) as cajas
                FROM producto";
        try {
            $res = $this->db->select($sql);
            if (!empty($res)) {
                $stats['productos'] = (int)($res[0]['productos'] ?? 0);
                $stats['cajas']     = (int)($res[0]['cajas'] ?? 0);
            }
        } catch (Exception $e) {
            error_log("Error en getKpiStats: " . $e->getMessage());
        }

        return $stats;
    }

    // ===================================================================================
    // ACCIONES CRUD PROVEEDORES
    // ===================================================================================
    public function agregarProveedor(array $data): int {
        $query = "
            INSERT INTO proveedor (
                codigoProveedor,
                nombreProveedor,
                rfc,
                direccion,
                colonia, 
                codigoPostal, 
                estadoRepublica, 
                status,
                codigoBarrasProductosPosicion,
                codigoBarrasProductosLongitud,
                codigoBarrasEnterosPosicion,
                codigoBarrasEnterosLongitud,
                codigoBarrasDecimalesPosicion,
                codigoBarrasDecimalesLongitud
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $params = [
            $data['codigoProveedor'],
            $data['nombreProveedor'],
            $data['rfc'],
            $data['direccion'],
            $data['colonia'],
            $data['codigoPostal'],
            $data['estadoRepublica'],
            1,
            $data['codigoBarrasProductosPosicion'],
            $data['codigoBarrasProductosLongitud'],
            $data['codigoBarrasEnterosPosicion'],
            $data['codigoBarrasEnterosLongitud'],
            $data['codigoBarrasDecimalesPosicion'],
            $data['codigoBarrasDecimalesLongitud'],
        ];

        try {
            return $this->db->insert($query, $params);
        } catch (Exception $e) {
            error_log("Error en agregarProveedor: " . $e->getMessage());
            return false;
        }
    }

    public function editarProveedor(int $idProveedor, array $data): int {
        $query = "
            UPDATE proveedor SET 
                codigoProveedor = ?, 
                nombreProveedor = ?, 
                rfc = ?, 
                direccion = ?, 
                colonia = ?, 
                codigoPostal = ?, 
                estadoRepublica = ?, 
                status = ?,
                codigoBarrasProductosPosicion = ?,
                codigoBarrasProductosLongitud = ?,
                codigoBarrasEnterosPosicion = ?,
                codigoBarrasEnterosLongitud = ?,
                codigoBarrasDecimalesPosicion = ?,
                codigoBarrasDecimalesLongitud = ?
        ";
        $params = [
            $data['codigoProveedor'],
            $data['nombreProveedor'],
            $data['rfc'],
            $data['direccion'],
            $data['colonia'],
            $data['codigoPostal'],
            $data['estadoRepublica'],
            (int)$data['status'],
            $data['codigoBarrasProductosPosicion'],
            $data['codigoBarrasProductosLongitud'],
            $data['codigoBarrasEnterosPosicion'],
            $data['codigoBarrasEnterosLongitud'],
            $data['codigoBarrasDecimalesPosicion'],
            $data['codigoBarrasDecimalesLongitud'],
        ];

        $query .= " WHERE idProveedor = ?";
        $params[] = $idProveedor;

        try {
            return $this->db->update($query, $params);
        } catch (Exception $e) {
            error_log("Error en editarProveedor: " . $e->getMessage());
            return false;
        }
    }

    // ===================================================================================
    // 🔍 FUNCIÓNES COMPLEMENTARIAS PARA LOS SELECTS DEL MODAL (VERSIÓN BLINDADA)
    // ===================================================================================
    
    public function getProveedores(): array {
        $query = "SELECT idProveedor, nombreProveedor FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en getProveedores: " . $e->getMessage());
            return [];
        }
    }

    public function getCategorias(): array {
        $query = "SELECT idCategoria, nombreCategoria FROM categoria ORDER BY nombreCategoria ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en getCategorias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 📦 ACCIONES CRUD PRODUCTOS: Inserta un nuevo producto en la base de datos
     */
    public function agregarProducto(array $data): int {
        $query = "
            INSERT INTO producto (
                codigoProducto,
                nombreProducto,
                idCategoria,
                activo,
                idProveedor,
                totalPeso,
                totalCajas
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $data['codigoProducto'],
            $data['nombreProducto'],
            (int)$data['idCategoria'],
            (int)$data['activo'],
            (int)$data['idProveedor'],
            (float)$data['totalPeso'],
            (int)$data['totalCajas']
        ];

        try {
            // Ejecuta el insert usando la instancia de tu BD.php
            return $this->db->insert($query, $params);
        } catch (Exception $e) {
            error_log("Error en agregarProducto: " . $e->getMessage());
            return false;
        }
    }
    
} 
?>