<?php
session_start();

require_once __DIR__ . '/../Models/modeloCajero.php';

$idCuenta = $_SESSION['idCuenta'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;

if (!$idCuenta) {
    header("Location: ../Views/login.php");
    exit;
}

$modelo = new modeloCajero();

// =========================================================
// 1. PROCESAR ACCIÓN DE PAGO (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'pagar') {
    $idNota = isset($_POST['id_nota']) ? (int)$_POST['id_nota'] : 0;

    if ($idNota > 0) {
        // Se envía el ID de la nota y el ID del cajero capturado en sesión
        $exito = $modelo->cambiarEstadoPagado($idNota, (int)$idCuenta);
        
        if ($exito) {
            $_SESSION['alerta_exito'] = "La nota fue cobrada correctamente.";
        } else {
            $_SESSION['alerta_error'] = "No se pudo actualizar el estado de la nota.";
        }
    } else {
        $_SESSION['alerta_error'] = "Identificador de nota no válido.";
    }

    session_write_close();
    header("Location: cajeroController.php");
    exit;
}

// =========================================================
// 2. CARGAR VISTA (GET)
// =========================================================
try {
    $notasPendientes = $modelo->obtenerNotasPendientes();

    require_once __DIR__ . '/../Views/notasSystem/cajero.php';

} catch (Exception $e) {
    die("Error en CajeroController: " . $e->getMessage());
}