<?php
require_once __DIR__ . '/../Models/modeloInventario.php';

class inventarioServicio {
    private $modelo;

    public function __construct() {
        $this->modelo = new modeloInventario();
    }

    /**
     * Puente para Productos
     */
    public function getProducts($busqueda = '', $estado = '', $pagina = 1) {
        $paginaValidada = max(1, (int)$pagina);
        return $this->modelo->getProducts($busqueda, $estado, $paginaValidada);
    }

    /**
     * Puente para Proveedores
     */
    public function getAllProviders($busqueda = '', $estado = '', $pagina = 1) {
        $paginaValidada = max(1, (int)$pagina);
        return $this->modelo->getAllProviders($busqueda, $estado, $paginaValidada);
    }

    public function agregarProveedor(array $data): int {
        return $this->modelo->agregarProveedor($data);
    }

    public function editarProveedor(int $id, array $data): int {
        return $this->modelo->editarProveedor($id, $data);
    }

    public function agregarProducto($data) {
    // Debe conectarse con el método que inserte en la tabla producto
    return $this->modelo->agregarProducto($data); 
}
}



