<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../Services/usuariosServicio.php';
$service = new usuariosServicio();

// 1. CAPTURA DE FILTROS EXISTENTES
$tipo_movimiento  = $_GET['tipo_movimiento'] ?? 'todos'; 
$busqueda_usuario = $_GET['busqueda_usuario'] ?? '';
$fecha_filtro     = $_GET['fecha_filtro'] ?? date('Y-m-d'); 

// 2. LÓGICA DE PAGINACIÓN OPERATIVA
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

// Simulamos un total de páginas (En producción usarás: ceil($total_registros / $limite))
$total_paginas = 5; 

// Construimos los parámetros actuales para adjuntar a cada enlace de la paginación
$query_params = http_build_query([
    'tipo_movimiento'  => $tipo_movimiento,
    'busqueda_usuario' => $busqueda_usuario,
    'fecha_filtro'     => $fecha_filtro
]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos</title>
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/movimientos.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <?php include '../Views/assets/barraNavegacion.php'; ?>

    <div class="contenedor">
        
        <div class="header">
            <h1>Información de movimientos del sistema</h1>
        </div>

        <div class="cards">
            <div class="card general-mov">
                <h3>Movimientos del Día</h3>
                <span>45</span>
            </div>
            <div class="card success">
                <h3>Cajas Ingresadas</h3>
                <span>280</span>
            </div>
            <div class="card danger">
                <h3>Cajas Despachadas</h3>
                <span>115</span>
            </div>
        </div>

        <div class="barra-filtros">
            <form method="GET" action="movimientos.php" class="form-movimientos-filtros">
                
                <input type="hidden" name="pagina" value="1">

                <div class="filtros-izquierda-movimientos">
                    <div class="tipo-movimiento-selector">
                        <label class="radio-movimiento">
                            <input type="radio" name="tipo_movimiento" value="todos" <?php echo $tipo_movimiento === 'todos' ? 'checked' : ''; ?>>
                            <span>Todos</span>
                        </label>
                        <label class="radio-movimiento">
                            <input type="radio" name="tipo_movimiento" value="usuario" <?php echo $tipo_movimiento === 'usuario' ? 'checked' : ''; ?>>
                            <span>Usuario</span>
                        </label>
                        <label class="radio-movimiento">
                            <input type="radio" name="tipo_movimiento" value="entrada" <?php echo $tipo_movimiento === 'entrada' ? 'checked' : ''; ?>>
                            <span>Entrada</span>
                        </label>
                        <label class="radio-movimiento">
                            <input type="radio" name="tipo_movimiento" value="salida" <?php echo $tipo_movimiento === 'salida' ? 'checked' : ''; ?>>
                            <span>Salida</span>
                        </label>
                        <label class="radio-movimiento">
                            <input type="radio" name="tipo_movimiento" value="inventario" <?php echo $tipo_movimiento === 'inventario' ? 'checked' : ''; ?>>
                            <span>Inventario</span>
                        </label>
                    </div>

                    <div class="buscador">
                        <input type="text" name="busqueda_usuario" value="<?php echo htmlspecialchars($busqueda_usuario); ?>" placeholder="Buscar por usuario responsable...">
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="filtros-derecha-movimientos">
                    <input type="date" name="fecha_filtro" value="<?php echo htmlspecialchars($fecha_filtro); ?>" class="input-fecha-bitacora">
                    <button type="submit" class="btnAplicar"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="movimientos.php" class="btnLimpiar"><i class="fas fa-sync-alt"></i> Hoy</a>
                </div>
            </form>
        </div>

        <table class="tablaUsuarios">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Tipo</th>
                    <th>Usuario Responsable</th>
                    <th>Acción / Descripción</th>
                    <th>Módulo Afectado</th>
                    <th class="text-center">Detalles</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>09:17:20</strong></td>
                    <td><span class="badge-tipo badge-usr">Usuario</span></td>
                    <td class="usuarioCell"><i class="fas fa-user-circle"></i> <strong>maria_v</strong></td>
                    <td>Inicio de sesión exitoso en el sistema</td>
                    <td>Autenticación</td>
                    <td class="text-center"><button class="btn-info-bitacora"><i class="fas fa-info-circle"></i></button></td>
                </tr>
                <tr>
                    <td><strong>09:19:32</strong></td>
                    <td><span class="badge-tipo badge-ent">Entrada</span></td>
                    <td class="usuarioCell"><i class="fas fa-user-circle"></i> <strong>richi_op</strong></td>
                    <td>Ingreso de <span class="txt-verde font-600">150 cajas</span> de Costilla Res</td>
                    <td>Entradas (CAM01)</td>
                    <td class="text-center"><button class="btn-info-bitacora"><i class="fas fa-info-circle"></i></button></td>
                </tr>
                <tr>
                    <td><strong>10:02:11</strong></td>
                    <td><span class="badge-tipo badge-sal">Salida</span></td>
                    <td class="usuarioCell"><i class="fas fa-user-circle"></i> <strong>richi_op</strong></td>
                    <td>Salida FIFO de <span class="txt-rojo font-600">60 cajas</span> de Chuleta</td>
                    <td>Salidas</td>
                    <td class="text-center"><button class="btn-info-bitacora"><i class="fas fa-info-circle"></i></button></td>
                </tr>
                <tr>
                    <td><strong>11:45:00</strong></td>
                    <td><span class="badge-tipo badge-inv">Inventario</span></td>
                    <td class="usuarioCell"><i class="fas fa-user-circle"></i> <strong>rodrigo_tl</strong></td>
                    <td>Actualización de stock manual lote P004</td>
                    <td>Inventario</td>
                    <td class="text-center"><button class="btn-info-bitacora"><i class="fas fa-info-circle"></i></button></td>
                </tr>
            </tbody>
        </table>

        <div class="botones-paginacion">

            <button
                type="button"
                class="btn-pagina"
                id="btnAnteriorMov"
            >
                <i class="fa-solid fa-chevron-left"></i>
                Anterior
            </button>

            <div id="contenedorNumeros">
                <button
                    type="button"
                    class="btn-pagina numero-pagina activa"
                >
                    1
                </button>

                <button
                    type="button"
                    class="btn-pagina numero-pagina"
                >
                    2
                </button>
            </div>

            <button
                type="button"
                class="btn-pagina"
                id="btnSiguienteMov"
            >
                Siguiente
                <i class="fa-solid fa-chevron-right"></i>
            </button>

        </div>
    </div>

<script>
    // 1. Elementos del DOM actualizados para movimientos
        const formFiltros = document.getElementById('formFiltrosMovimientos');
        const inputBusqueda = document.getElementById('inputBusquedaUsuario');
        const inputFecha = document.getElementById('inputFechaFiltro');
        const radiosTipo = document.querySelectorAll('input[name="tipo_movimiento"]');
        const btnLimpiar = document.getElementById('btnLimpiarFiltros');
        
        // Captura específica de los botones dentro de la caja horizontal
        const botonesNumero = document.querySelectorAll('#contenedorNumeros .numero-pagina');
        const btnAnterior = document.getElementById('btnAnteriorMov');
        const btnSiguiente = document.getElementById('btnSiguienteMov');

        // 2. Estado de la paginación asíncrona local
        let paginaActual = 1;

        // 3. Función para actualizar la numeración visual de los botones en fila
        function actualizarNumeros() {
            botonesNumero.forEach((boton, index) => {
                boton.textContent = paginaActual + index;
            });
        }

        // Función para detonar la búsqueda en tiempo real
        function dispararConsulta() {
            inputBusqueda.dispatchEvent(new Event('input'));
        }

        // 4. Eventos de los botones Anterior y Siguiente con rebote visual
        btnSiguiente.addEventListener('click', () => {
            paginaActual++;
            if (typeof window.cambiarPaginaMovimientos === 'function') {
                window.cambiarPaginaMovimientos(paginaActual);
            }
            actualizarNumeros();
            dispararConsulta();
        });

        btnAnterior.addEventListener('click', () => {
            if (paginaActual > 1) {
                paginaActual--;
                if (typeof window.cambiarPaginaMovimientos === 'function') {
                    window.cambiarPaginaMovimientos(paginaActual);
                }
                actualizarNumeros();
                dispararConsulta();
            }
        });
</script>
        


</body>
</html>