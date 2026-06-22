<?php
require_once __DIR__ . '/../Models/modeloEntradas.php';

class entradasServicio {
    private $modelo;

    public function __construct() {
        $this->modelo = new modeloEntradas();
    }

    public function listarProveedores() {
        return $this->modelo->obtenerTodosProveedores();
    }

    public function crearEntrada(array $data) {
        return $this->modelo->crearEntrada($data);
    }
}