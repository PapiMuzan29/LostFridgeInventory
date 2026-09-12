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
// 1. PROCESAR ACCIONES DEL FORMULARIO (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $_SESSION['form_data'] = $_POST;

        // CASO A: Abrir Caja
        if (isset($_POST['accion_especial']) && $_POST['accion_especial'] === 'abrir_caja') {
            $idCaja = isset($_POST['id_caja']) ? (int)$_POST['id_caja'] : 0;
            $pesoKilos = isset($_POST['peso']) ? (float)$_POST['peso'] : 0.0;
            
            if ($idCaja > 0 && $pesoKilos > 0) {
                // NUEVO: Busca el ID dinámicamente usando LIKE '%Pecho%'
                $idPechoSuelto = $modelo->obtenerIdProductoPorNombre('Pecho');
                
                if ($idPechoSuelto > 0) {
                    $exito = $modelo->abrirCajaConvertirAKilos($idCaja, $idPechoSuelto, $pesoKilos);
                    
                    if ($exito) {
                        $_SESSION['alerta_exito'] = "¡Caja abierta exitosamente! Se sumaron {$pesoKilos} kg al inventario.";
                    } else {
                        $_SESSION['alerta_error'] = "Error: No se encontró inventario en la caja.";
                    }
                } else {
                    $_SESSION['alerta_error'] = "Error: No se encontró el producto 'Pecho' en el catálogo.";
                }
            } else {
                $_SESSION['alerta_error'] = 'Datos inválidos para abrir la caja.';
            }
            
            session_write_close();
            header("Location: /LostFridgeInventory/Controllers/vendedorController.php");
            exit;
        }

        // CASO B: Guardar Nota (Ya sea como GUARDADA o PENDIENTE para caja)
        $cliente = $_POST['nombre_cliente'] ?? '';
        $productos = $_POST['productos'] ?? []; 
        $estibadores = $_POST['estibadores'] ?? [];
        $accionBoton = $_POST['accion_boton'] ?? 'enviar_caja'; // 'guardar_espera' o 'enviar_caja'

        if (empty($cliente) || empty($productos)) {
            $_SESSION['alerta_error'] = 'El nombre del cliente y los productos son obligatorios.';
            session_write_close();
            header("Location: /LostFridgeInventory/Controllers/vendedorController.php");
            exit;
        }

        $validacion = $modelo->verificarDisponibilidad($productos);
        if (!$validacion['exito']) {
            $_SESSION['alerta_error'] = $validacion['mensaje'];
            session_write_close();
            header("Location: /LostFridgeInventory/Controllers/vendedorController.php");
            exit;
        }

        $folio = $modelo->obtenerSiguienteFolio();
        $observacion = trim($_POST['observacion_especial'] ?? '');
        
        // El estado se vuelve PREVAUTORIZAR solo si envían a caja y hay texto
        if ($accionBoton === 'guardar_espera') {
            $estadoInicial = 'GUARDADA';
        } else {
            $estadoInicial = empty($observacion) ? 'PENDIENTE' : 'PREVAUTORIZAR';
        }
        
        // Pasa la observación a la función (debes añadir este 7mo parámetro en tu modelo)
        $idNotaCreada = $modelo->guardarNotaConEstado($folio, $cliente, (int)$idCuenta, $productos, $estibadores, $estadoInicial, $observacion);
        if ($idNotaCreada) {
            if ($estadoInicial === 'GUARDADA') {
                $_SESSION['alerta_exito'] = '¡Nota previa guardada en espera correctamente!';
            } else {
                $_SESSION['alerta_exito'] = '¡Nota enviada a caja con éxito!';
            }
            unset($_SESSION['form_data']); 

            if (!empty($_POST['redirigir_a'])) {
                $urlDestino = $_POST['redirigir_a'];
                session_write_close();
                header("Location: " . $urlDestino);
                exit;
            }
        } else {
            $_SESSION['alerta_error'] = 'Ocurrió un problema al registrar la nota en la base de datos.';
        }
        
        session_write_close();
        header("Location: /LostFridgeInventory/Controllers/vendedorController.php");
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// =========================================================
// 2. PROCESAR ACCIONES RÁPIDAS (GET: EDITAR O CANCELAR NOTA GUARDADA)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion'])) {
    try {
        $accion = $_GET['accion'];
        $idNota = isset($_GET['id_nota']) ? (int)$_GET['id_nota'] : 0;

        if ($idNota > 0) {
            if ($accion === 'editar_nota') {
                // 1. Reversar inventario de la nota guardada
                $modelo->reversarInventarioNota($idNota);
                
                // 2. Extraer datos para cargarlos al formulario
                $datosNota = $modelo->obtenerDatosNotaParaEditar($idNota);
                if (!empty($datosNota)) {
                    $_SESSION['form_data'] = $datosNota;
                }

                // 3. Eliminar la nota vieja
                $modelo->eliminarNota($idNota);

                $_SESSION['alerta_exito'] = 'Nota lista para editarse en el formulario.';
            } 
            elseif ($accion === 'cancelar_nota') {
                // Devolver inventario y borrar nota
                $modelo->reversarInventarioNota($idNota);
                $modelo->eliminarNota($idNota);
                $_SESSION['alerta_exito'] = 'Nota cancelada y stock devuelto al inventario.';
            }
        }

        session_write_close();
        header("Location: /LostFridgeInventory/Controllers/vendedorController.php");
        exit;

    } catch (Exception $e) {
        die("Error en acción GET: " . $e->getMessage());
    }
}



// =========================================================
// 3. CARGAR VISTA (GET DEFAULT)
// =========================================================
try {
    $productos = $modelo->obtenerProductosActivos();
    $estibadores = $modelo->obtenerEstibadores();
    $notasVendedor = $modelo->obtenerNotasDelVendedor((int)$idCuenta);

    require_once __DIR__ . '/../Views/notasSystem/vendedor.php';

} catch (Exception $e) {
    die("Error al cargar los datos de la vista: " . $e->getMessage());
}