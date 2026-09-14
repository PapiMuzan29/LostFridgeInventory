<?php
require_once __DIR__ . '/../Models/modeloMovimientos.php';

class movimientosServicio {
    private $modelo;

    public function __construct() {
        $this->modelo = new modeloMovimientos();
    }

    public function registrarMovimiento(string $tipo, string $usuario, string $descripcion, string $modulo, ?array $detalles = null) {
        return $this->modelo->registrar($tipo, $usuario, $descripcion, $modulo, $detalles);
    }

    public function consultarBitacora(string $tipo, string $usuario, string $fecha, int $pagina) {
        $limite = 4; 
        return $this->modelo->listarMovimientos($tipo, $usuario, $fecha, $pagina, $limite);
    }

    public function obtenerResumenTarjetas(string $fecha) {
        return $this->modelo->obtenerContadoresDia($fecha);
    }
}