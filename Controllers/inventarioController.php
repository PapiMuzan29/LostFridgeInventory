<?php
session_start();
// Control de seguridad: Validar si la sesión existe antes de escupir datos privados
if (!isset($_SESSION['apodoUsuario'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'datos' => [],
        'error' => 'Acceso denegado. Sesión no iniciada.'
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../Services/inventarioServicio.php';
    $service = new inventarioServicio();
    
    $action = $_GET['action'] ?? '';

    if ($action === 'busqueda') {
        $textoBusqueda = $_GET['busqueda'] ?? '';
        $estado        = $_GET['estado'] ?? '';
        $tipo          = $_GET['tipo_busqueda'] ?? 'producto'; 
        $pagina        = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

        $listaData = [];

        if ($tipo === 'proveedor') {
            $listaData = $service->getAllProviders($textoBusqueda, $estado, $pagina);
        } else {
            $listaData = $service->getProducts($textoBusqueda, $estado, $pagina);
        }

        if (!is_array($listaData)) {
            $listaData = [];
        }

        echo json_encode([
            'datos'        => $listaData,
            'totalPaginas' => 1, // Se gestiona progresivamente desde el JS ciego
            'pagina'       => $pagina
        ]);
        exit;
    }

    // 🛠️ CORREGIDO: Cambiado de 'agregarProveedor' a 'crearProveedor' para coincidir con tu JS
    if ($action === 'crearProveedor') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'codigoProveedor'               => $_POST['codigoProveedor'] ?? '',
                'nombreProveedor'               => $_POST['nombreProveedor'] ?? '',
                'rfc'                           => $_POST['rfc'] ?? '',
                'direccion'                     => $_POST['direccion'] ?? '',
                'colonia'                       => $_POST['colonia'] ?? '',
                'codigoPostal'                  => $_POST['codigoPostal'] ?? '',
                'estadoRepublica'               => $_POST['estadoRepublica'] ?? '',
                'codigoBarrasProductosPosicion' => $_POST['codigoBarrasProductosPosicion'] ?? 0,
                'codigoBarrasProductosLongitud' => $_POST['codigoBarrasProductosLongitud'] ?? 0,
                'codigoBarrasEnterosPosicion'   => $_POST['codigoBarrasEnterosPosicion'] ?? 0,
                'codigoBarrasEnterosLongitud'   => $_POST['codigoBarrasEnterosLongitud'] ?? 0,
                'codigoBarrasDecimalesPosicion' => $_POST['codigoBarrasDecimalesPosicion'] ?? 0,
                'codigoBarrasDecimalesLongitud' => $_POST['codigoBarrasDecimalesLongitud'] ?? 0,
            ];

            $service->agregarProveedor($data);

            // 🛠️ CORREGIDO: Devolvemos JSON de éxito en lugar de redireccionar
            echo json_encode([
                'status'  => 'success',
                'message' => 'Proveedor registrado correctamente.'
            ]);
            exit;
        }
    }

    if ($action === 'editarProveedor') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'codigoProveedor'               => $_POST['codigoProveedor'] ?? '',
                'nombreProveedor'               => $_POST['nombreProveedor'] ?? '',
                'rfc'                           => $_POST['rfc'] ?? '',
                'direccion'                     => $_POST['direccion'] ?? '',
                'colonia'                       => $_POST['colonia'] ?? '',
                'codigoPostal'                  => $_POST['codigoPostal'] ?? '',
                'estadoRepublica'               => $_POST['estadoRepublica'] ?? '',
                'codigoBarrasProductosPosicion' => $_POST['codigoBarrasProductosPosicion'] ?? 0,
                'codigoBarrasProductosLongitud' => $_POST['codigoBarrasProductosLongitud'] ?? 0,
                'codigoBarrasEnterosPosicion'   => $_POST['codigoBarrasEnterosPosicion'] ?? 0,
                'codigoBarrasEnterosLongitud'   => $_POST['codigoBarrasEnterosLongitud'] ?? 0,
                'codigoBarrasDecimalesPosicion' => $_POST['codigoBarrasDecimalesPosicion'] ?? 0,
                'codigoBarrasDecimalesLongitud' => $_POST['codigoBarrasDecimalesLongitud'] ?? 0,
            ];

            $service->editarProveedor($data);

            // 🛠️ CORREGIDO: Devolvemos JSON de éxito en lugar de redireccionar
            echo json_encode([
                'status'  => 'success',
                'message' => 'Proveedor actualizado correctamente.'
            ]);
            exit;   
        }
    }

    if ($action === 'crearProducto') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'codigoProducto' => $_POST['codigoProducto'] ?? '',
                'nombreProducto' => $_POST['nombreProducto'] ?? '',
                'idProveedor'    => $_POST['idProveedor'] ?? '',
                'idCategoria'    => $_POST['idCategoria'] ?? '',
                'totalCajas'     => 0, 
                'pesoProductive' => 0.00,
                'activo'         => 1
            ];

            $service->agregarProducto($data); 

            echo json_encode([
                'status'  => 'success',
                'message' => 'Producto procesado correctamente.'
            ]);
            exit;
        }
    }

    echo json_encode([
        'datos'        => [],
        'totalPaginas' => 1,
        'error'        => 'Acción no válida o no especificada.'
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'datos'        => [],
        'totalPaginas' => 1,
        'status'       => 'error',
        'error'        => 'Error en Servidor: ' . $e->getMessage(),
        'message'      => $e->getMessage()
    ]);
    exit;
}