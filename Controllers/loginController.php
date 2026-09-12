<?php
session_start();
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/movimientosServicio.php';

$auth = new AuthService();
$movimientos = new movimientosServicio();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['user'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($usuario && $password) {
        if ($auth->login($usuario, $password)) {
            
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