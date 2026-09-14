<?php
require_once __DIR__ . '/../Config/BD.php';

class modeloMovimientos {
    private $db;

    public function __construct() {
        $this->db = new BD();
    }

    /**
     * 📝 Registra cualquier tipo de evento en el sistema de manera global
     */
    public function registrar(string $tipo, string $usuario, string $descripcion, string $modulo, ?array $detalles = null): bool {
        $query = "INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) 
                  VALUES (?, ?, ?, ?, CURDATE(), CURTIME())";
        
        $params = [$tipo, $usuario, $descripcion, $modulo];

        try {
            $this->db->consulta($query, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error al registrar movimiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔍 Obtiene movimientos filtrados aplicando paginación dinámica
     */
    public function listarMovimientos(string $tipo, string $usuario, string $fecha, int $pagina, int $limite): array {
        $offset = ($pagina - 1) * $limite;
        $conditions = ["fecha = ?"];
        $params = [$fecha];

        if ($tipo !== 'todos') {
            $conditions[] = "tipo = ?";
            $params[] = $tipo;
        }

        if (!empty($usuario)) {
            $conditions[] = "usuarioResponsable LIKE ?";
            $params[] = "%" . $usuario . "%";
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        // Conteo general para paginado
        $queryCount = "SELECT COUNT(*) as total FROM bitacora_movimientos {$whereClause}";
        $stmtCount = $this->db->consulta($queryCount, $params);
        $totalRegistros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Carga de la página actual ordenando de forma descendente por la primera columna disponible
        $queryData = "SELECT * 
                      FROM bitacora_movimientos 
                      {$whereClause} 
                      ORDER BY 1 DESC 
                      LIMIT $limite OFFSET $offset";
                      
        $stmtData = $this->db->consulta($queryData, $params);
        $registros = $stmtData->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => $totalRegistros,
            'paginas' => ceil($totalRegistros / $limite),
            'datos' => $registros
        ];
    }

    /**
     * 📊 Obtiene el conteo de movimientos del día y las cajas ingresadas
     */
    public function obtenerContadoresDia(string $fecha): array {
        try {
            // 1. Total de movimientos en la bitácora del día
            $qMov = "SELECT COUNT(*) as total FROM bitacora_movimientos WHERE fecha = ?";
            $stmt = $this->db->consulta($qMov, [$fecha]);
            $totalMov = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // 2. Total de entradas del día
            $qCajas = "SELECT COUNT(*) as total FROM entradas WHERE DATE(fecha_captura) = ? AND status = 'A'";
            $stmtCajas = $this->db->consulta($qCajas, [$fecha]);
            $cajasIngresadas = $stmtCajas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            $cajasDespachadas = 0; 

            return [
                'movimientosDia' => $totalMov,
                'cajasIngresadas' => $cajasIngresadas,
                'cajasDespachadas' => $cajasDespachadas 
            ];
        } catch (Exception $e) {
            return ['movimientosDia' => 0, 'cajasIngresadas' => 0, 'cajasDespachadas' => 0];
        }
    }
}