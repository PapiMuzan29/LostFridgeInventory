<?php

session_start();
require_once __DIR__ . '/../Services/AuthService.php';
// 🔔 Importamos el servicio de movimientos para auditar el acceso
require_once __DIR__ . '/../Services/movimientosServicio.php';

$auth = new AuthService();
$movimientos = new movimientosServicio();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['user'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($usuario && $password) {
        if ($auth->login($usuario, $password)) {
            // 🔔 Login correcto → Registramos la auditoría de acceso antes de redirigir
            // Usamos $_SESSION['apodoUsuario'] o el parámetro recibido según tu AuthService
            $usuarioResponsable = $_SESSION['apodoUsuario'] ?? $usuario;
            
            $movimientos->registrarMovimiento(
                'usuario', 
                $usuarioResponsable, 
                'Inicio de sesión exitoso en el sistema', 
                'Autenticación'
            );

            // Redirige al dashboard
            header("Location: ../Views/inicio.php");
            exit;
        } else {
            // Login incorrecto → regresa al login con mensaje
            header("Location: ../Views/login.php?error=1");
            exit;
        }
    } else {
        // Si no se mandaron datos
        header("Location: ../Views/login.php?error=2");
        exit;
    }
}

?>