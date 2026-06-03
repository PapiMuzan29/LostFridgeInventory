<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ModeloReportes.php';

class ReportesServicio {
    private ModeloReportes $reportesModel;

    public function __construct() {
      $this->reportesModel = new ModeloReportes();
    }

    public function obtenerReportesBusqueda(string $tipoReporte = '', string $fechaInicio = '', string $fechaFin = '', int $pagina = 1): array {
        return $this->reportesModel->obtenerReportes($tipoReporte, $fechaInicio, $fechaFin, $pagina);
    }
}
?>