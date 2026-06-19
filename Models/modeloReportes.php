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
        $paramsCount = [];

        $whereClause = " WHERE 1 = 1";

        if ($tipoReporte !== '') {
            $whereClause .= " AND d.tipoDocumento = ?";
            $params[]      = $tipoReporte;
            $paramsCount[] = $tipoReporte;
        }

        if ($fechaInicio !== '' && $fechaFin !== '') {
            $whereClause .= " AND d.fechaCreacion >= ? AND d.fechaCreacion <= ?";
            $params[]      = $fechaInicio;
            $params[]      = $fechaFin;
            $paramsCount[] = $fechaInicio;
            $paramsCount[] = $fechaFin;
        }

        $queryCount  = "SELECT COUNT(*) AS total FROM Documentos d INNER JOIN Cuenta c ON c.idCuenta = d.idCuenta" . $whereClause;
        $resultCount = $this->db->select($queryCount, $paramsCount);
        $total       = (int)($resultCount[0]['total'] ?? 0);

        $query = "SELECT 
                    d.idDocumento,
                    d.nombreOriginal,
                    d.rutaArchivo,
                    d.tipoDocumento,
                    d.fechaCreacion,
                    d.fechaFinalizacion,
                    CONCAT_WS(' ', c.nombreUsuario, c.apellidoPaternoUsuario, c.apellidoMaternoUsuario) AS nombreCompletoResponsable
                FROM Documentos d
                INNER JOIN Cuenta c ON c.idCuenta = d.idCuenta"
                . $whereClause .
                " ORDER BY d.fechaCreacion DESC LIMIT $registrosPorPagina OFFSET $offset";

        $registros = $this->db->select($query, $params);

        return [
            'datos'              => $registros,
            'total'              => $total,
            'pagina'             => $pagina,
            'registrosPorPagina' => $registrosPorPagina,
            'totalPaginas'       => (int)ceil($total / $registrosPorPagina),
        ];
    }
}
?>