<?php
require_once __DIR__ . '/../Config/BD.php';
class ModeloReportes{
    private BD $db;

    public function __construct() {

        $this->db = BD::obtenerInstancia();
    }

    public function obtenerReportes() {
        
    }
    
}
?>