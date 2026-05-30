<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/modeloUsuarios.php';

class usuariosServicio {

    private $model;

    public function __construct() {

        $this->model = new modeloUsuarios();
    }

    public function getUsers(
    string $busqueda = '',
    string $estado = '',
    int $pagina = 1
): array {

    return $this->model->getAllUsers(
        $busqueda,
        $estado,
        $pagina
    );
}

    public function create(array $data): int {

        return $this->model->createUser($data);
    }

    public function update(
        int $id,
        array $data
        ): int {

        return $this->model->updateUser(
            $id,
            $data
        );
    }

    public function delete(int $id): bool {

        return $this->model->deleteUser($id) > 0;
    }

    public function getById(int $id): ?array {

        return $this->model->getUserById($id);
    }

    public function getStats(): array {

        return $this->model->countUsers();
    }
}

?>