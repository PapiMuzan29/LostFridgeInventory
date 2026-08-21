<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloInventario {
    private $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia(); 
    }

    /**
     * 📦 MÓDULO PRODUCTOS: Obtención y Gestión
     */
    public function getProducts($textoBusqueda = '', $estado = '', $pagina = 1) {
        $porPagina = 4;
        $offset = ($pagina - 1) * $porPagina;

        $query = "SELECT p.idProducto, p.codigoProducto, p.nombreProducto, p.activo, 
                         IFNULL(p.totalCajas, 0) as totalCajas,
                         IFNULL(p.totalPeso, 0) as totalPeso,
                         COALESCE(c.nombreCategoria, 'Sin categoría') as nombreCategoria, 
                         COALESCE(prov.nombreProveedor, 'Sin proveedor') as nombreProveedor 
                  FROM producto p
                  LEFT JOIN categoria c ON p.idCategoria = c.idCategoria
                  LEFT JOIN proveedor prov ON p.idProveedor = prov.idProveedor
                  WHERE 1 = 1";
                  
        $params = [];
        if (!empty($textoBusqueda)) {
            $query .= " AND (p.nombreProducto LIKE ? OR p.codigoProducto LIKE ?)";
            $params[] = "%$textoBusqueda%";
            $params[] = "%$textoBusqueda%";
        }
        if ($estado !== '' && $estado !== null) {
            $query .= " AND p.activo = ?";
            $params[] = (int)$estado;
        }

        $query .= " ORDER BY p.idProducto DESC LIMIT $porPagina OFFSET $offset";

        try {
            $stmt = $this->db->consulta($query, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en getProducts: " . $e->getMessage());
            return [];
        }
    }

    public function agregarProducto(array $data): int {
        $query = "INSERT INTO producto (codigoProducto, nombreProducto, idCategoria, activo, idProveedor) VALUES (?, ?, ?, 1, ?)";
        $params = [$data['codigoProducto'], $data['nombreProducto'], (int)$data['idCategoria'], (int)$data['idProveedor']];
        try {
            return $this->db->insert($query, $params);
        } catch (Exception $e) {
            throw new Exception("Error al insertar: " . $e->getMessage());
        }
    }

    public function actualizarProducto(int $id, array $data): int {
        $query = "UPDATE producto SET codigoProducto = ?, nombreProducto = ?, idCategoria = ?, idProveedor = ? WHERE idProducto = ?";
        $params = [$data['codigoProducto'], $data['nombreProducto'], (int)$data['idCategoria'], (int)$data['idProveedor'], $id];
        try {
            return $this->db->update($query, $params);
        } catch (Exception $e) {
            throw new Exception("Error al actualizar: " . $e->getMessage());
        }
    }

    public function eliminarProducto(int $id): int {
        $query = "DELETE FROM producto WHERE idProducto = ?";
        try {
            return $this->db->delete($query, [$id]);
        } catch (Exception $e) {
            throw new Exception("Error al eliminar: " . $e->getMessage());
        }
    }

    /**
     * 🚚 MÓDULO PROVEEDORES
     */
    public function getAllProviders(string $busqueda = '', string $estado = '', int $pagina = 1): array {
        $registrosPorPagina = 4;
        $offset = ((int)$pagina - 1) * $registrosPorPagina;

        $query = "SELECT idProveedor, codigoProveedor, nombreProveedor, rfc, direccion, colonia, codigoPostal, estadoRepublica, status,
                         codigoBarrasProductosPosicion, codigoBarrasProductosLongitud, 
                         codigoBarrasEnterosPosicion, codigoBarrasEnterosLongitud, 
                         codigoBarrasDecimalesPosicion, codigoBarrasDecimalesLongitud 
                  FROM proveedor WHERE 1 = 1";
        $params = [];

        if (!empty($busqueda)) {
            $query .= " AND (nombreProveedor LIKE ? OR rfc LIKE ? OR codigoProveedor LIKE ?)";
            $search = "%{$busqueda}%";
            $params = [$search, $search, $search];
        }
        if ($estado !== '') {
            $query .= " AND status = ?";
            $params[] = (int)$estado;
        }

        $query .= " ORDER BY idProveedor DESC LIMIT " . (int)$registrosPorPagina . " OFFSET " . (int)$offset;
        return $this->db->select($query, $params);
    }

    public function agregarProveedor(array $data): int {
        $query = "INSERT INTO proveedor (
                    codigoProveedor, nombreProveedor, rfc, direccion, colonia, codigoPostal, estadoRepublica, status,
                    codigoBarrasProductosPosicion, codigoBarrasProductosLongitud, 
                    codigoBarrasEnterosPosicion, codigoBarrasEnterosLongitud, 
                    codigoBarrasDecimalesPosicion, codigoBarrasDecimalesLongitud
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['codigoProveedor'], 
            $data['nombreProveedor'], 
            $data['rfc'], 
            $data['direccion'], 
            $data['colonia'], 
            $data['codigoPostal'], 
            $data['estadoRepublica'],
            (int)($data['codigoBarrasProductosPosicion'] ?? 29),
            (int)($data['codigoBarrasProductosLongitud'] ?? 4),
            (int)($data['codigoBarrasEnterosPosicion'] ?? 5),
            (int)($data['codigoBarrasEnterosLongitud'] ?? 5),
            (int)($data['codigoBarrasDecimalesPosicion'] ?? 10),
            (int)($data['codigoBarrasDecimalesLongitud'] ?? 2)
        ];
        return $this->db->insert($query, $params);
    }

    public function actualizarProveedor(int $id, array $data): int {
        $query = "UPDATE proveedor SET 
                    codigoProveedor = ?, 
                    nombreProveedor = ?, 
                    rfc = ?, 
                    direccion = ?, 
                    colonia = ?, 
                    codigoPostal = ?, 
                    estadoRepublica = ?,
                    codigoBarrasProductosPosicion = ?, 
                    codigoBarrasProductosLongitud = ?, 
                    codigoBarrasEnterosPosicion = ?, 
                    codigoBarrasEnterosLongitud = ?, 
                    codigoBarrasDecimalesPosicion = ?, 
                    codigoBarrasDecimalesLongitud = ? 
                  WHERE idProveedor = ?";
                  
        $params = [
            $data['codigoProveedor'], 
            $data['nombreProveedor'], 
            $data['rfc'], 
            $data['direccion'], 
            $data['colonia'], 
            $data['codigoPostal'], 
            $data['estadoRepublica'], 
            (int)($data['codigoBarrasProductosPosicion'] ?? 29),
            (int)($data['codigoBarrasProductosLongitud'] ?? 4),
            (int)($data['codigoBarrasEnterosPosicion'] ?? 5),
            (int)($data['codigoBarrasEnterosLongitud'] ?? 5),
            (int)($data['codigoBarrasDecimalesPosicion'] ?? 10),
            (int)($data['codigoBarrasDecimalesLongitud'] ?? 2),
            $id
        ];
        
        try {
            return $this->db->update($query, $params);
        } catch (Exception $e) {
            throw new Exception("Error al actualizar proveedor: " . $e->getMessage());
        }
    }

    public function getProveedores(): array {
        return $this->db->consulta("SELECT idProveedor, nombreProveedor FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getCategorias(): array {
        return $this->db->consulta("SELECT idCategoria, nombreCategoria FROM categoria ORDER BY nombreCategoria ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getKpiStats(): array {
        $sql = "SELECT COUNT(idProducto) as productos, SUM(IF(activo = 1, IFNULL(totalCajas, 0), 0)) as cajas FROM producto";
        $res = $this->db->select($sql);
        return ['productos' => (int)($res[0]['productos'] ?? 0), 'cajas' => (int)($res[0]['cajas'] ?? 0)];
    }
}
?>