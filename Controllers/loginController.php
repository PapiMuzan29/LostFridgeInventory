<?php
session_start();
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/movimientosServicio.php';

$auth = new AuthService();
$movimientos = new movimientosServicio();

// =========================================================
// 1. MANEJO DE CIERRE DE SESIÓN (LOGOUT)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    
    // 🔔 (Opcional) Registrar la auditoría de salida antes de destruir la sesión
    if (isset($_SESSION['apodoUsuario']) || isset($_SESSION['user'])) {
        $usuarioResponsable = $_SESSION['apodoUsuario'] ?? $_SESSION['user'] ?? 'Usuario';
        $movimientos->registrarMovimiento(
            'usuario', 
            $usuarioResponsable, 
            'Cierre de sesión o expulsión del sistema', 
            'Autenticación'
        );
    }

    // Destruir todas las variables de sesión
    session_unset();
    // Destruir la sesión por completo
    session_destroy();

    // Redirigir directo a la vista de login
    header("Location: ../Views/login.php");
    exit;
}

// =========================================================
// 2. MANEJO DE INICIO DE SESIÓN (LOGIN)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['user'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($usuario && $password) {
        if ($auth->login($usuario, $password)) {
            
            // 🔔 Login correcto → Registramos la auditoría de acceso antes de redirigir
            $usuarioResponsable = $_SESSION['apodoUsuario'] ?? $usuario;
            
            // 🔍 CAPTURA DE ERRORES PARA DIAGNÓSTICO
            try {
                $resultado = $movimientos->registrarMovimiento(
                    'usuario', 
                    $usuarioResponsable, 
                    'Inicio de sesión exitoso en el sistema', 
                    'Autenticación'
                );
                
                // Si quieres ver un mensaje de éxito en pantalla antes de entrar, descomenta la línea de abajo:
                // echo "Registrado con éxito. Resultado: " . ($resultado ? 'TRUE' : 'FALSE'); exit();

            } catch (Throwable $e) {
                // 🛑 Si hay un error en la base de datos o en el servicio, lo imprimirá en pantalla
                echo "<h3>Error al registrar en la bitácora:</h3>";
                echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                exit();
            }

            // Redirige al dashboard principal
            header("Location: ../Views/inicio.php");
            // 🔀 REDIRECCIÓN SEGÚN EL ROL DE USUARIO
            $idRol = (int)($_SESSION['idRol'] ?? 0);

            switch ($idRol) {
                case 2: // Vendedor
                    header("Location: ../Views/notasSystem/vendedor.php");
                    break;

                case 5: // Encargado
                    header("Location: ../Views/notasSystem/encargado.php");
                    break;

                case 6: // Cajero
                    header("Location: ../Views/notasSystem/cajero.php");
                    break;

                case 1: // Administrador (Opcional: Por si inicia sesión un Admin)
                    header("Location: ../Views/inicio.php");
                    break;

                default:
                    // Si el rol no coincide o no tiene vista asignada, manda a inicio por defecto
                    header("Location: ../Views/inicio.php");
                    break;
            }
            exit;

        } else {
            header("Location: ../Views/login.php?error=1");
            exit;
        }
    } else {
        header("Location: ../Views/login.php?error=2");
        exit;
    }
}
?>