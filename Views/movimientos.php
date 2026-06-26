<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: ../login.php');
    exit();
}

// 1. CAPTURA DE FILTROS EXISTENTES
$tipo_movimiento  = $_GET['tipo_movimiento'] ?? 'todos'; 
$busqueda_usuario = $_GET['busqueda_usuario'] ?? '';
$fecha_filtro     = $_GET['fecha_filtro'] ?? date('Y-m-d'); 

// 2. LÓGICA DE PAGINACIÓN OPERATIVA
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

$total_paginas = 1; 
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos del Sistema</title>
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
                <span id="cardMovimientosDia">0</span>
            </div>
            <div class="card success">
                <h3>Cajas Ingresadas</h3>
                <span id="cardCajasIngresadas">0</span>
            </div>
            <div class="card danger">
                <h3>Cajas Despachadas</h3>
                <span id="cardCajasDespachadas">0</span>
            </div>
        </div>

        <div class="barra-filtros">
            <form method="GET" action="movimientos.php" id="formFiltrosMovimientos" class="form-movimientos-filtros">
                
                <input type="hidden" name="pagina" id="inputPagina" value="<?php echo $pagina_actual; ?>">

                <div class="filtros-izquierda-movimientos">
                    <div class="tipo-movimiento-selector">
                        <label for="selectTipoMovimiento" style="font-weight: 600; color: #475569; margin-right: 8px;">Tipo:</label>
                        <select name="tipo_movimiento" id="selectTipoMovimiento" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #1e293b; font-weight: 600; cursor: pointer;">
                            <option value="todos" <?php echo $tipo_movimiento === 'todos' ? 'selected' : ''; ?>>Todos los movimientos</option>
                            <option value="usuario" <?php echo $tipo_movimiento === 'usuario' ? 'selected' : ''; ?>>Usuario (Accesos)</option>
                            <option value="entrada" <?php echo $tipo_movimiento === 'entrada' ? 'selected' : ''; ?>>Entradas</option>
                            <option value="salida" <?php echo $tipo_movimiento === 'salida' ? 'selected' : ''; ?>>Salidas</option>
                            <option value="inventario" <?php echo $tipo_movimiento === 'inventario' ? 'selected' : ''; ?>>Inventario</option>
                        </select>
                    </div>

                    <div class="buscador">
                        <input type="text" name="busqueda_usuario" id="inputBusquedaUsuario" value="<?php echo htmlspecialchars($busqueda_usuario); ?>" placeholder="Buscar por usuario responsable...">
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="filtros-derecha-movimientos">
                    <input type="date" name="fecha_filtro" id="inputFechaFiltro" value="<?php echo htmlspecialchars($fecha_filtro); ?>" class="input-fecha-bitacora">
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
            <tbody id="tablaMovimientosBody">
                </tbody>
        </table>

      <div class="botones-paginacion">
            <button type="button" class="btn-pagina" id="btnAnteriorMov">
                <i class="fa-solid fa-chevron-left"></i> Anterior
            </button>

            <button type="button" class="btn-pagina numero-pagina activa" id="numPagMov1">1</button>
            <button type="button" class="btn-pagina numero-pagina" id="numPagMov2">2</button>

            <button type="button" class="btn-pagina" id="btnSiguienteMov">
                Siguiente <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <script src="../Services/funcionesMovimientos.js"></script>
</body>
</html>