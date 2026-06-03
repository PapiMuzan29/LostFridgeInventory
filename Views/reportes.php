<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Reportes - LFI</title>
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/reportes.css"> 
</head>
<body>
    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="contenedor">
        <div class="header">
            <h1>Gestión de Reportes</h1>
        </div>

        <div class="panel-controles-reportes">
            <form class="filtros-reportes-izquierda" id="formFiltrosReportes">
                <div class="grupo-input-reporte">
                    <label for="tipoReporte">Tipo de reporte</label>
                    <select id="tipoReporte" name="tipoReporte">
                        <option value="">Todos los reportes</option>
                        <option value="movimiento_inventario">Movimientos De Inventarios</option>
                        <option value="inventario_actual">Inventario Actual</option>
                        <option value="vencimiento">Vencimientos</option>
                        <option value="movimientos_usuario">Movimientos Por Usuario</option>
                        <option value="utilizacion_ubicaciones">Utilización De Ubicaciones</option>
                    </select>
                </div>

                <div class="grupo-input-reporte">
                    <label for="fechaInicio">Fecha inicio</label>
                    <input type="date" id="fechaInicio" name="fechaInicio">
                </div>

                <div class="grupo-input-reporte">
                    <label for="fechaFin">Fecha fin</label>
                    <input type="date" id="fechaFin" name="fechaFin">
                </div>

                <button type="button" class="btnAplicar" id="btnFiltrar">
                    <i class="icono-filtro"></i> Filtrar
                </button>
                <button type="button" class="btnLimpiar" id="btnLimpiarFiltros">
                    <i class="icono-limpiar"></i> Limpiar
                </button>
            </form>

            <div class="acciones-principales">
                <button type="button" class="btnNuevo">
                    <i class="icono-mas"></i> Generar Nuevo Reporte
                </button>
            </div>
        </div>

        <div class="tabla-reportes-contenedor">
            <table class="tabla-reportes-modulo">
                <thead>
                    <tr>
                        <th>Reporte</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Generado por</th>
                        <th>Fecha Creación</th>
                        <th>Periodo / Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-documentos-tbody">
                    </tbody>
            </table>
        </div>
        
        <div class="botones-paginacion">

            <!-- BOTON ANTERIOR -->
            <button
                type="button"
                class="btn-pagina"
                id="btnAnterior"
            >

                <i class="fa-solid fa-chevron-left"></i>
                Anterior

            </button>

            <!-- NUMEROS -->
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

            <!-- BOTON SIGUIENTE -->
            <button
                type="button"
                class="btn-pagina"
                id="btnSiguiente"
            >

                Siguiente
                <i class="fa-solid fa-chevron-right"></i>

            </button>
        </div>

    </div>
    <script src="../Views/js/funcionesReportes.js"></script>
    


</body>
</html>