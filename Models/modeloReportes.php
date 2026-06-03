<?php
require_once __DIR__ . '/../Config/BD.php';

class ModeloReportes {
    private BD $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia();
    }

    public function obtenerReportes(string $tipoReporte, string $fechaInicio, string $fechaFin, int $pagina): array {
        $registrosPorPagina = 4;
        $offset = ($pagina - 1) * $registrosPorPagina;
        $params = [];

        $query = "SELECT 
                    d.idDocumento,
                    d.nombreOriginal,
                    d.rutaArchivo,
                    d.tipoDocumento,
                    d.fechaCreacion,
                    d.fechaFinalizacion,
                    CONCAT_WS(' ', c.nombreUsuario, c.apellidoPaternoUsuario, c.apellidoMaternoUsuario) AS nombreCompletoResponsable
                  FROM Documentos d
                  INNER JOIN Cuenta c ON c.idCuenta = d.idCuenta 
                  WHERE 1 = 1";

        if ($tipoReporte !== '') {
            $query .= " AND d.tipoDocumento = ?"; 
            $params[] = $tipoReporte;
        }

        // Filtro por rango de fechas
        if ($fechaInicio !== '' && $fechaFin !== '') {
            $query .= " AND d.fechaCreacion >= ? AND d.fechaCreacion <= ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        $query .= " ORDER BY d.fechaCreacion DESC LIMIT $registrosPorPagina OFFSET $offset";
       
        return $this->db->select($query, $params);
    }
}
?>