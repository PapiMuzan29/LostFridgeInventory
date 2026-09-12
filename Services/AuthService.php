<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/User.php';

class AuthService {

    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Procesa la autenticación del usuario y registra las variables de sesión
     */
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
            isset($user['contrasenaUsuario']) &&
            isset($user['apodoUsuario']) &&
            isset($user['nombreRol']) &&
            isset($user['idRol']) &&
            isset($user['estado']) &&
            (int)$user['estado'] === 1 &&
            $password === $user['contrasenaUsuario']
        ) {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Regenera el ID de la sesión para prevenir Session Hijacking
            session_regenerate_id(true);

            // 🔑 AQUÍ GUARDAMOS EL ID EN LA SESIÓN (Solución al error de llaves foráneas)
            $_SESSION['idCuenta'] = $user['idCuenta']; 
            
            // REGISTROS DE SESIÓN CON DATOS Y ROLES DE USUARIO
            $_SESSION['idCuenta']     = $user['idCuenta'];
            $_SESSION['idRol']        = (int)$user['idRol'];
            $_SESSION['apodoUsuario'] = $user['apodoUsuario'];
            $_SESSION['nombreRol']   = $user['nombreRol'];

            return true;
        }

        return false;
    }

    /**
     * Cierra la sesión y destruye la cookie de la app
     */
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

    /**
     * Comprueba si hay una sesión válida iniciada
     */
    public function isAuthenticated(): bool {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['idCuenta']) || isset($_SESSION['apodoUsuario']);
    }

    /**
     * Obtiene el ID del rol de la sesión actual
     */
    public function getRoleId(): ?int {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['idRol']) ? (int)$_SESSION['idRol'] : null;
    }

    /**
     * Valida si el usuario tiene acceso a un módulo específico según su rol
     */
    public function hasRole(int ...$allowedRoles): bool {

        $userRole = $this->getRoleId();

        if ($userRole === null) {
            return false;
        }

        return in_array($userRole, $allowedRoles, true);
    }
}

?>