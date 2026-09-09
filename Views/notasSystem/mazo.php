<?php
// mazo.php
session_start();

// 1. Forzar la zona horaria correcta de México
date_default_timezone_set('America/Mexico_City');

$nombreUsuario = $_SESSION['apodoUsuario'] ?? $_SESSION['nombreUsuario'] ?? 'Usuario';
$fechaActual = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Módulo Mazos - Grupo Cárnico América</title>
    
    <!-- Hojas de Estilos -->
    <link rel="stylesheet" href="../notasSystem/CSS/cajero.css">
    <link rel="stylesheet" href="../notasSystem/CSS/manteca.css">
    <link rel="stylesheet" href="../notasSystem/CSS/mazo.css">
    <link rel="stylesheet" href="CSS/encargado.css">
    
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>
</head>
<body>

    <!-- HEADER UNIFICADO -->
    <header class="manteca-header">
        <div class="manteca-header-left">
            <button type="button" class="btn-back" onclick="volverPantallaAnterior()">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </button>
            <h1><i class="fa-solid fa-drumstick-bite"></i>Mazos</h1>
        </div>
        <div class="date-badge">
            <i class="fa-solid fa-user"></i>
            <span><?= htmlspecialchars($nombreUsuario) ?></span>
        </div>
    </header>

    <!-- NAVEGADOR DE FECHA / CALENDARIO -->
    <div class="sticky-date-bar">
        <div class="date-picker-bar date-picker-bar-inline">
            <button type="button" class="btn-date-nav" onclick="cambiarFecha(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="date-picker-display" onclick="abrirCalendario()">
                <i class="fa-regular fa-calendar-days"></i>
                <span id="label-fecha-seleccionada">Cargando fecha...</span>
                <input type="date" id="input-fecha-mazo" class="input-date-hidden" value="<?= $fechaActual ?>" onchange="alSeleccionarFecha(this.value)">
            </div>
            <button type="button" class="btn-date-nav" onclick="cambiarFecha(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <main class="container">

        <!-- 1. SELECCIONAR PRESENTACIÓN -->
        <div class="section-title">Selecciona Presentación</div>
        <div class="grid-presentaciones-mazo">
            <button type="button" class="btn-presentacion active" id="btn-nacional" onclick="seleccionarPresentacion('nacional')">
                <i class="fa-solid fa-flag"></i> NACIONAL
            </button>
            <button type="button" class="btn-presentacion" id="btn-importado" onclick="seleccionarPresentacion('importado')">
                <i class="fa-solid fa-globe"></i> IMPORTADO
            </button>
        </div>

        <!-- 2. TOTAL DEL DÍA SEGÚN PRESENTACIÓN -->
        <div class="card-total-dia card-total-dia-spacing">
            <div class="info">
                <h3 id="label-total-presentacion">TOTAL DEL DÍA (NACIONAL)</h3>
                <div class="val" id="val-total-presentacion">0 piezas</div>
            </div>
            <i class="fa-solid fa-boxes-stacked icon"></i>
        </div>

        <!-- 3. ÁREA DE TARJETAS DINÁMICAS POR PRESENTACIÓN -->
        <div class="content-cards-wrapper content-cards-wrapper-spacing">

            <!-- SECCIÓN NACIONAL -->
            <div id="sec-nacional" class="presentacion-section active">
                <div class="section-title"><i class="fa-solid fa-box"></i> Ventas Mazo Nacional</div>
                <div id="contenedor-ventas-nacional" class="contenedor-tarjetas-ventas"></div>
            </div>

            <!-- SECCIÓN IMPORTADO -->
            <div id="sec-importado" class="presentacion-section">
                <div class="section-title"><i class="fa-solid fa-box"></i> Ventas Mazo Importado</div>
                <div id="contenedor-ventas-importado" class="contenedor-tarjetas-ventas"></div>
            </div>

        </div>

    </main>

    <!-- 4. TOTAL GENERAL SIEMPRE FIJO ABAJO DEL TODO -->
    <div class="fixed-bottom-total-container">
        <div class="total-general-card">
            TOTAL GENERAL MAZOS
            <div class="total-general-val" id="total-general">0 piezas</div>
        </div>
    </div>

    <script>
        let presentacionActual = 'nacional';
        let fechaSeleccionadaObj = new Date();
        let datosCargadosActuales = null;

        document.addEventListener('DOMContentLoaded', () => {
            const inputFecha = document.getElementById('input-fecha-mazo');
            if (inputFecha && inputFecha.value) {
                const partes = inputFecha.value.split('-');
                // Parsear fecha exactamente como números enteros
                fechaSeleccionadaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            }
            actualizarInterfazFecha();
            cargarDatosMazoPorFecha(inputFecha.value);
        });

        function abrirCalendario() {
            const input = document.getElementById('input-fecha-mazo');
            if (input.showPicker) {
                input.showPicker();
            } else {
                input.click();
            }
        }

        function cambiarFecha(dias) {
            fechaSeleccionadaObj.setDate(fechaSeleccionadaObj.getDate() + dias);
            const yyyy = fechaSeleccionadaObj.getFullYear();
            const mm = String(fechaSeleccionadaObj.getMonth() + 1).padStart(2, '0');
            const dd = String(fechaSeleccionadaObj.getDate()).padStart(2, '0');
            
            const fechaString = `${yyyy}-${mm}-${dd}`;
            document.getElementById('input-fecha-mazo').value = fechaString;
            alSeleccionarFecha(fechaString);
        }

        function alSeleccionarFecha(fechaString) {
            if (!fechaString) return;
            const partes = fechaString.split('-');
            fechaSeleccionadaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            actualizarInterfazFecha();
            cargarDatosMazoPorFecha(fechaString);
        }

        function actualizarInterfazFecha() {
            const opciones = { day: 'numeric', month: 'short' };
            const fechaFormateada = fechaSeleccionadaObj.toLocaleDateString('es-ES', opciones);
            document.getElementById('label-fecha-seleccionada').textContent = fechaFormateada;
        }

        // CARGA DE DATOS DESDE EL CONTROLADOR MVC
        function cargarDatosMazoPorFecha(fecha) {
            const rutaControlador = `../../Controllers/mazoController.php?accion=consultarPorFecha&fecha=${fecha}`;

            fetch(rutaControlador)
                .then(res => res.text())
                .then(texto => {
                    let data;
                    try {
                        data = JSON.parse(texto);
                    } catch (e) {
                        console.error("Respuesta no válida del servidor:", texto);
                        return;
                    }

                    datosCargadosActuales = data;

                    // Renderizar las dos secciones: nacional e importado
                    ['nacional', 'importado'].forEach(tipoKey => {
                        const contenedor = document.getElementById(`contenedor-ventas-${tipoKey}`);
                        const tipo = data[tipoKey];

                        if (!tipo || !tipo.ventas || tipo.ventas.length === 0) {
                            contenedor.innerHTML = `
                                <div class="empty-state-card">
                                    <i class="fa-solid fa-inbox fa-2x"></i>
                                    <p class="empty-state-text">Sin ventas registradas en ${tipoKey.toUpperCase()}</p>
                                </div>`;
                        } else {
                            contenedor.innerHTML = tipo.ventas.map(v => `
                                <div class="venta-card-item">
                                    <div class="venta-card-main">
                                        <div class="venta-cliente"><i class="fa-solid fa-user-tag"></i> Cliente: ${v.cliente}</div>
                                        <div class="venta-ticket"><i class="fa-solid fa-receipt"></i> Ticket: ${v.ticket}</div>
                                    </div>
                                    <div class="venta-badge-pzs">
                                        <span>${v.piezas}</span> <small>pzs</small>
                                    </div>
                                </div>
                            `).join('');
                        }
                    });

                    actualizarBanners();
                })
                .catch(err => console.error("Error al obtener datos de mazos:", err));
        }

        function actualizarBanners() {
            if (!datosCargadosActuales) return;

            // Subtotal de la presentación seleccionada
            const subtotal = datosCargadosActuales[presentacionActual];
            if (subtotal) {
                const piezasVal = subtotal.piezas || 0;
                document.getElementById('val-total-presentacion').textContent = `${piezasVal} piezas`;
            }

            // Total General Inferior
            const totPiezas = datosCargadosActuales.total_general_piezas || 0;
            document.getElementById('total-general').textContent = `${totPiezas} piezas`;
        }

        function seleccionarPresentacion(tipo) {
            presentacionActual = tipo;

            // Cambiar botones activos
            document.getElementById('btn-nacional').classList.toggle('active', tipo === 'nacional');
            document.getElementById('btn-importado').classList.toggle('active', tipo === 'importado');

            // Cambiar título del subtotal
            document.getElementById('label-total-presentacion').textContent = `TOTAL DEL DÍA (${tipo.toUpperCase()})`;

            // Alternar vista de contenedores
            document.getElementById('sec-nacional').classList.toggle('active', tipo === 'nacional');
            document.getElementById('sec-importado').classList.toggle('active', tipo === 'importado');

            actualizarBanners();
        }

        function volverPantallaAnterior() {
            if (document.referrer) {
                window.location.href = document.referrer;
            } else {
                window.location.href = 'encargado.php';
            }
        }
    </script>
</body>
</html>