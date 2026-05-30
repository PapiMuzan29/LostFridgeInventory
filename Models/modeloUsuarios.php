<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/BD.php';

class modeloUsuarios {

    private BD $db;

    public function __construct() {
        $this->db = BD::obtenerInstancia();
    }

    public function getAllUsers(
        string $busqueda = '',
        string $estado = '',
        int $pagina = 1
    ): array {
        $registrosPorPagina = 4;
        $offset = ($pagina - 1) * $registrosPorPagina;

        $query = "SELECT 
                    cuenta.idCuenta,
                    cuenta.idRol,
                    cuenta.apodoUsuario,
                    cuenta.nombreUsuario,
                    cuenta.apellidoPaternoUsuario,
                    cuenta.apellidoMaternoUsuario,
                    cuenta.contrasenaUsuario,
                    cuenta.estado,
                    rol.nombreRol
                FROM cuenta
                INNER JOIN rol
                ON cuenta.idRol = rol.idRol
                WHERE 1 = 1";

        $params = [];

        if (!empty($busqueda)) {
            $query .= " AND (
                        cuenta.apodoUsuario LIKE ?
                        OR CONCAT_WS(' ', cuenta.nombreUsuario, cuenta.apellidoPaternoUsuario, cuenta.apellidoMaternoUsuario) LIKE ?
                    )";

            $search = "%{$busqueda}%";
            
            // Ahora solo pasamos el parámetro 2 veces (uno para el apodo, uno para el bloque de nombre completo)
            $params[] = $search;
            $params[] = $search;
        }

        if ($estado !== '') {
            $query .= " AND cuenta.estado = ?";
            $params[] = $estado;
        }

        $query .= "
            ORDER BY cuenta.idCuenta DESC
            LIMIT $registrosPorPagina
            OFFSET $offset
        ";

        return $this->db->select($query, $params);
    }

    function createUser(array $data): int {
        $query = "
            INSERT INTO cuenta (
                idRol,
                apodoUsuario,
                nombreUsuario,
                apellidoPaternoUsuario,
                apellidoMaternoUsuario,
                contrasenaUsuario,
                estado
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?
            )
        ";

        return $this->db->insert($query, [
            $data['idRol'],
            $data['apodoUsuario'],
            $data['nombreUsuario'],
            $data['apellidoPaternoUsuario'],
            $data['apellidoMaternoUsuario'],
            $data['contrasena'],
            $data['estado']
        ]);
    }

    public function updateUser(int $id, array $data): int {
        $query = "
            UPDATE cuenta
            SET
                idRol = ?,
                apodoUsuario = ?,
                nombreUsuario = ?,
                apellidoPaternoUsuario = ?,
                apellidoMaternoUsuario = ?,
                estado = ?
            WHERE idCuenta = ?
        ";

        return $this->db->update($query, [
            $data['idRol'],
            $data['apodoUsuario'],
            $data['nombreUsuario'],
            $data['apellidoPaternoUsuario'],
            $data['apellidoMaternoUsuario'],
            $data['estado'],
            $id
        ]);
    }

    public function deleteUser(int $id): int {
        $query = "UPDATE cuenta
                SET estado = 0
                WHERE idCuenta = ?";

        return $this->db->update($query, [$id]);
    }   

    public function getUserById(int $id): ?array {
        $query = "SELECT * FROM cuenta WHERE idCuenta = ? LIMIT 1";
        $result = $this->db->select($query, [$id]);

        return !empty($result) ? $result[0] : null;
    }

    public function countUsers(): array {
        $query = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) AS inactivos
                  FROM cuenta";

        $result = $this->db->select($query);
        return $result[0];
    }
}
?>