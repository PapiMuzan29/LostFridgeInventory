<?php
session_start();

require_once __DIR__ . '/../Models/modeloVendedor.php';

$idCuenta = $_SESSION['idCuenta'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;

if (!$idCuenta) {
    header("Location: ../Views/login.php");
    exit;
}

$modelo = new modeloVendedor();

// =========================================================
// 1. PROCESAR GUARDADO (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cliente = $_POST['nombre_cliente'] ?? '';
        $productos = $_POST['productos'] ?? []; 
        $estibadores = $_POST['estibadores'] ?? [];

        if (empty($cliente) || empty($productos)) {
            $_SESSION['alerta_error'] = 'El nombre del cliente y los productos son obligatorios.';
            session_write_close();
            header("Location: vendedorController.php");
            exit;
        }

        $folio = $modelo->obtenerSiguienteFolio();
        $exito = $modelo->guardarNota($folio, $cliente, (int)$idCuenta, $productos, $estibadores);

        if ($exito) {
            $_SESSION['alerta_exito'] = '¡Nota enviada a caja con éxito!';
        } else {
            $_SESSION['alerta_error'] = 'Ocurrió un problema al registrar la nota en la base de datos.';
        }
        
        // Cierre explícito de sesión y redirección limpia
        session_write_close();
        header("Location: vendedorController.php");
        exit;

    } catch (Exception $e) {
        die("Error al guardar la nota: " . $e->getMessage());
    }
}

// =========================================================
// 2. CARGAR VISTA (GET)
// =========================================================
try {
    $productos = $modelo->obtenerProductosActivos();
    $estibadores = $modelo->obtenerEstibadores();

    require_once __DIR__ . '/../Views/notasSystem/vendedor.php';

} catch (Exception $e) {
    die("Error al cargar los datos de la vista: " . $e->getMessage());
}