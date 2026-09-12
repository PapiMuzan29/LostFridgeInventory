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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <form class="filtros-reportes-izquierda" id="formFiltrosReportes" onsubmit="return false;">
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
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <button type="button" class="btnLimpiar" id="btnLimpiarFiltros">
                    <i class="fa-solid fa-rotate-right"></i> Limpiar
                </button>
            </form>

            <div class="acciones-principales">
                <button type="button" class="btnNuevo" onclick="alert('Funcionalidad de nuevo reporte en desarrollo');">
                    <i class="fa-solid fa-plus"></i> Generar Nuevo Reporte
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
                    <!-- Los datos se cargan dinámicamente mediante JavaScript -->
                </tbody>
            </table>
        </div>
        
        <div class="botones-paginacion">
            <!-- BOTON ANTERIOR -->
            <button type="button" class="btn-pagina" id="btnAnterior">
                <i class="fa-solid fa-chevron-left"></i> Anterior
            </button>

            <!-- BOTON SIGUIENTE -->
            <button type="button" class="btn-pagina" id="btnSiguiente">
                Siguiente <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- VENTANA MODAL (VISTA PREVIA EN LA MISMA PESTAÑA) -->
    <div id="modalVistaPrevia" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center;">
        <div style="background: white; width: 850px; max-height: 90vh; border-radius: 8px; overflow-y: auto; padding: 25px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
            
            <!-- Botón de Cerrar Modal -->
            <button onclick="cerrarVistaPrevia()" style="position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: bold;">✕ Cerrar</button>
            
            <!-- Contenedor donde se inyectará el comprobante -->
            <div id="contenidoComprobante">
                <p style="text-align: center; color: #64748b; padding: 20px;">Cargando comprobante...</p>
            </div>
        </div>
    </div>

    <script>
    // Función global para abrir la vista previa en la misma pestaña sin abrir ventanas nuevas
    function abrirComprobanteMismaPestana(idEntrada) {
        document.getElementById('modalVistaPrevia').style.display = 'flex';
        document.getElementById('contenidoComprobante').innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Cargando comprobante...</p>';

        // Petición AJAX (Fetch) para traer el HTML del controlador sin recargar la página
        fetch('exportarPdfController.php?id=' + idEntrada)
            .then(response => response.text())
            .then(html => {
                document.getElementById('contenidoComprobante').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('contenidoComprobante').innerHTML = '<p style="color: red; text-align: center;">Error al cargar el comprobante.</p>';
            });
    }

    function cerrarVistaPrevia() {
        document.getElementById('modalVistaPrevia').style.display = 'none';
    }
    </script>

    <script src="../Services/funcionesReportes.js"></script>
    <script src="../Services/tema.js"></script>
</body>
</html>