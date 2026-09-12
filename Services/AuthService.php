<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/User.php';

class AuthService {

    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login(string $username, string $password): bool {

        $user = $this->userModel->findByUsername($username);

        if (
            $user !== null &&
            isset($user['idCuenta']) &&  // 👈 Validamos que el ID exista en la base de datos
            isset($user['contrasenaUsuario']) &&
            isset($user['apodoUsuario']) &&
            isset($user['nombreRol']) &&
            isset($user['estado']) &&
            $user['estado'] == 1 &&
            $password === $user['contrasenaUsuario']
        ) {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            session_regenerate_id(true);

            // 🔑 AQUÍ GUARDAMOS EL ID EN LA SESIÓN (Solución al error de llaves foráneas)
            $_SESSION['idCuenta'] = $user['idCuenta']; 
            
            $_SESSION['apodoUsuario'] = $user['apodoUsuario'];
            $_SESSION['nombreRol'] = $user['nombreRol'];

            return true;
        }

        return false;
    }

    public function logout(): void {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    public function isAuthenticated(): bool {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['apodoUsuario']);
    }
}

?>