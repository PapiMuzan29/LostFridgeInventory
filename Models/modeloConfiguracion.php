<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/BD.php';

class modeloConfiguracion {

    private BD $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia();
    }

    /**
     * Obtiene los datos del usuario logueado usando su apodo de sesión
     */
    public function obtenerUsuarioPorApodo(string $apodo): ?array {
        $query = "SELECT 
                    apodoUsuario,
                    nombreUsuario,
                    apellidoPaternoUsuario,
                    apellidoMaternoUsuario,
                    estado
                  FROM cuenta 
                  WHERE apodoUsuario = ? 
                  LIMIT 1";

        $result = $this->db->select($query, [$apodo]);
        return !empty($result) ? $result[0] : null;
    }
}