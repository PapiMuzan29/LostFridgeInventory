<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/BD.php';

class User {

    private BD $db;

    public function __construct() {

        $this->db = BD::obtenerInstancia();
    }

    public function findByUsername(string $username): ?array {

        $query = "SELECT cuenta.idCuenta,cuenta.apodoUsuario,cuenta.contrasenaUsuario,cuenta.estado,rol.nombreRol FROM cuenta INNER JOIN rol ON cuenta.idRol = rol.idRol WHERE cuenta.apodoUsuario = ? LIMIT 1";
        $result = $this->db->select($query, [$username]);

        return !empty($result) ? $result[0] : null;
    }
}

?>