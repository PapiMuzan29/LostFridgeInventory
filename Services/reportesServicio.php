<?php
class ReportesServicio {
    private $conexion;

    public function __construct() {
        $host = 'localhost';
        $db   = 'bd_lfi'; 
        $user = 'root';
        $pass = ''; 
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conexion = new PDO($dsn, $user, $pass, $opciones);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la BD: " . $e->getMessage());
        }
    }

    public function obtenerReportesBusqueda($tipoReporte, $fechaInicio, $fechaFin, $pagina) {
        try {
            $porPagina = 10;
            $offset = ($pagina - 1) * $porPagina;

            // Tomamos el apodo del usuario activo de la sesión actual
            $usuarioActual = $_SESSION['apodoUsuario'] ?? 'Sistema';

            $sql = "SELECT 
                        id_entrada AS id,
                        'movimiento_inventario' AS tipoDocumento,
                        '$usuarioActual' AS nombreCompletoResponsable,
                        fecha_hora_registro AS fechaCreacion,
                        fecha_hora_registro AS fechaFinalizacion,
                        CONCAT('../Controllers/exportarPdfController.php?id=', id_entrada) AS rutaArchivo
                    FROM entradas
                    WHERE 1=1";
            
            $params = [];

            if (!empty($tipoReporte) && $tipoReporte !== 'Todos los reportes') {
                $sql .= " AND 'movimiento_inventario' = ?";
                $params[] = $tipoReporte;
            }

            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $sql .= " AND DATE(fecha_hora_registro) BETWEEN ? AND ?";
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
            }

            // Conteo total para paginación
            $sqlCount = "SELECT COUNT(*) as total FROM (" . $sql . ") AS t";
            $stmtCount = $this->conexion->prepare($sqlCount);
            $stmtCount->execute($params);
            $totalRegistros = $stmtCount->fetch()['total'] ?? 0;
            $totalPaginas = max(1, ceil($totalRegistros / $porPagina));

            // Consulta paginada
            $sql .= " ORDER BY fecha_hora_registro DESC LIMIT $porPagina OFFSET $offset";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return [
                "success" => true,
                "datos" => $datos,
                "total" => $totalRegistros,
                "pagina" => $pagina,
                "totalPaginas" => $totalPaginas
            ];

        } catch (Exception $e) {
            throw new Exception("Error en la consulta SQL: " . $e->getMessage());
        }
    }
}
?>