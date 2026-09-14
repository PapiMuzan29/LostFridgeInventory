<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/modeloConfiguracion.php';

class configuracionServicio {

    private modeloConfiguracion $modelo;

    public function __construct() {
        $this->modelo = new modeloConfiguracion();
    }

    public function obtenerDatosUsuario(string $apodo): ?array {
        return $this->modelo->obtenerUsuarioPorApodo($apodo);
    }
}