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
            $usuarioResponsable = $_SESSION['apodoUsuario'] ?? $usuario;
            
            $movimientos->registrarMovimiento(
                'usuario', 
                $usuarioResponsable, 
                'Inicio de sesión exitoso en el sistema', 
                'Autenticación'
            );

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