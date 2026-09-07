<?php
// manteca.php
session_start();

// 1. Forzar zona horaria correcta de México
date_default_timezone_set('America/Mexico_City');

$nombreUsuario = $_SESSION['apodoUsuario'] ?? $_SESSION['nombreUsuario'] ?? 'Usuario';
$fechaActual = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Módulo Manteca - Grupo Cárnico América</title>
    
    <!-- Hojas de Estilos -->
    <link rel="stylesheet" href="../notasSystem/CSS/cajero.css">
    <link rel="stylesheet" href="../notasSystem/CSS/manteca.css">
    <link rel="stylesheet" href="CSS/encargado.css">
    
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>

    <style>
        /* Ajustes globales */
        body {
            padding-bottom: 90px; /* Espacio para el Total Fijo inferior */
            margin: 0;
        }

        /* BARRA DEL CALENDARIO FIJA ARRIBA */
        .sticky-date-bar {
            position: sticky;
            top: 0;
            z-index: 999;
            background-color: #f8fafc;
            padding: 10px 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 15px;
        }

        /* Estilos de las secciones dinámicas */
        .presentacion-section {
            display: none;
        }

        .presentacion-section.active {
            display: block;
        }

        .contenedor-tarjetas-ventas {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .venta-card-item {
            background: #ffffff;
            border-radius: 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            border-left: 4px solid #f59e0b;
        }

        .venta-cliente {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .venta-ticket {
            font-size: 0.85rem;
            color: #64748b;
        }

        .venta-badge-pzs {
            background-color: #fef3c7;
            color: #b45309;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 1rem;
        }

        .empty-state-card {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 0.9rem;
            background: #ffffff;
            border-radius: 8px;
            border: 1px dashed #cbd5e1;
        }

        /* CARD DE TOTAL GENERAL PEGADA ABAJO DEL TODO */
        .fixed-bottom-total-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #ffffff;
            padding: 10px 15px 15px 15px;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .fixed-bottom-total-container .total-general-card {
            margin: 0;
        }
    </style>
</head>
<body>

    <!-- HEADER UNIFICADO -->
    <header class="manteca-header">
        <div class="manteca-header-left">
            <button type="button" class="btn-back" onclick="volverPantallaAnterior()">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </button>
            <h1><i class="fa-solid fa-box-open"></i> Manteca</h1>
        </div>
        <div class="date-badge">
            <i class="fa-solid fa-user"></i>
            <span><?= htmlspecialchars($nombreUsuario) ?></span>
        </div>
    </header>

    <!-- NAVEGADOR DE FECHA / CALENDARIO -->
    <div class="sticky-date-bar">
        <div class="date-picker-bar" style="margin: 0;">
            <button type="button" class="btn-date-nav" onclick="cambiarFecha(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="date-picker-display" onclick="abrirCalendario()">
                <i class="fa-regular fa-calendar-days"></i>
                <span id="label-fecha-seleccionada">Cargando fecha...</span>
                <input type="date" id="input-fecha-manteca" value="<?= $fechaActual ?>" style="position:absolute; opacity:0; pointer-events:none;" onchange="alSeleccionarFecha(this.value)">
            </div>
            <button type="button" class="btn-date-nav" onclick="cambiarFecha(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <main class="container">

        <!-- 1. SELECCIONAR PRESENTACIÓN -->
        <div class="section-title">Selecciona Presentación</div>
        <div class="grid-presentaciones">
            <button type="button" class="btn-presentacion active" id="btn-10" onclick="seleccionarPresentacion(10)">
                <i class="fa-solid fa-cube"></i> 10 KG
            </button>
            <button type="button" class="btn-presentacion" id="btn-15" onclick="seleccionarPresentacion(15)">
                <i class="fa-solid fa-cube"></i> 15 KG
            </button>
            <button type="button" class="btn-presentacion" id="btn-18" onclick="seleccionarPresentacion(18)">
                <i class="fa-solid fa-cube"></i> 18 KG
            </button>
        </div>

        <!-- 2. TOTAL DEL DÍA SEGÚN PRESENTACIÓN -->
        <div class="card-total-dia" style="margin-top: 10px;">
            <div class="info">
                <h3 id="label-total-presentacion">TOTAL DEL DÍA (10 KG)</h3>
                <div class="val" id="val-total-presentacion">0 unidades (0 kg)</div>
            </div>
            <i class="fa-solid fa-boxes-stacked icon"></i>
        </div>

        <!-- 3. ÁREA DE TARJETAS DINÁMICAS POR PRESENTACIÓN -->
        <div class="content-cards-wrapper" style="margin-top: 15px;">

            <!-- SECCIÓN 10 KG -->
            <div id="sec-10" class="presentacion-section active">
                <div class="section-title"><i class="fa-solid fa-bucket"></i> Ventas Manteca 10 KG</div>
                <div id="contenedor-ventas-10" class="contenedor-tarjetas-ventas"></div>
            </div>

            <!-- SECCIÓN 15 KG -->
            <div id="sec-15" class="presentacion-section">
                <div class="section-title"><i class="fa-solid fa-bucket"></i> Ventas Manteca 15 KG</div>
                <div id="contenedor-ventas-15" class="contenedor-tarjetas-ventas"></div>
            </div>

            <!-- SECCIÓN 18 KG -->
            <div id="sec-18" class="presentacion-section">
                <div class="section-title"><i class="fa-solid fa-bucket"></i> Ventas Manteca 18 KG</div>
                <div id="contenedor-ventas-18" class="contenedor-tarjetas-ventas"></div>
            </div>

        </div>

    </main>

    <!-- 4. TOTAL GENERAL SIEMPRE FIJO ABAJO DEL TODO -->
    <div class="fixed-bottom-total-container">
        <div class="total-general-card">
            TOTAL GENERAL
            <div style="font-size: 1.4rem; font-weight: 800; margin-top: 2px;" id="total-general">0 unidades (0 kg)</div>
        </div>
    </div>

    <script>
        let presentacionActual = 10;
        let fechaSeleccionadaObj = new Date();
        let datosCargadosActuales = null;

        document.addEventListener('DOMContentLoaded', () => {
            const inputFecha = document.getElementById('input-fecha-manteca');
            if (inputFecha && inputFecha.value) {
                const partes = inputFecha.value.split('-');
                // Parsear números estrictamente
                fechaSeleccionadaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            }
            actualizarInterfazFecha();
            cargarDatosMantecaPorFecha(inputFecha.value);
        });

        function abrirCalendario() {
            const input = document.getElementById('input-fecha-manteca');
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
            document.getElementById('input-fecha-manteca').value = fechaString;
            alSeleccionarFecha(fechaString);
        }

        function alSeleccionarFecha(fechaString) {
            if (!fechaString) return;
            const partes = fechaString.split('-');
            fechaSeleccionadaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            actualizarInterfazFecha();
            cargarDatosMantecaPorFecha(fechaString);
        }

        function actualizarInterfazFecha() {
            const opciones = { day: 'numeric', month: 'short' };
            const fechaFormateada = fechaSeleccionadaObj.toLocaleDateString('es-ES', opciones);
            document.getElementById('label-fecha-seleccionada').textContent = fechaFormateada;
        }

        // CARGA DE DATOS DESDE EL CONTROLADOR MVC
        function cargarDatosMantecaPorFecha(fecha) {
            const rutaControlador = `../../Controllers/mantecaController.php?accion=consultarPorFecha&fecha=${fecha}`;

            fetch(rutaControlador)
                .then(res => res.text())
                .then(texto => {
                    let data;
                    try {
                        data = JSON.parse(texto);
                    } catch (e) {
                        console.error("El servidor devolvió una respuesta no válida (no es JSON):", texto);
                        return;
                    }

                    datosCargadosActuales = data;

                    // 1. Renderizar tarjetas de cada tipo (10, 15, 18)
                    [10, 15, 18].forEach(kg => {
                        const contenedor = document.getElementById(`contenedor-ventas-${kg}`);
                        const tipo = data[kg];

                        if (!tipo || !tipo.ventas || tipo.ventas.length === 0) {
                            contenedor.innerHTML = `
                                <div class="empty-state-card">
                                    <i class="fa-solid fa-inbox fa-2x"></i>
                                    <p style="margin-top:5px;">Sin ventas registradas en ${kg} KG</p>
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

                    // 2. Actualizar banners e indicadores de totales
                    actualizarBanners();
                })
                .catch(err => console.error("Error al obtener datos:", err));
        }

        function actualizarBanners() {
            if (!datosCargadosActuales) return;

            // Subtotal de la pestaña activa (10, 15 u 18 KG)
            const subtotal = datosCargadosActuales[presentacionActual];
            if (subtotal) {
                const kilosVal = typeof subtotal.kilos === 'number' ? subtotal.kilos.toFixed(2) : parseFloat(subtotal.kilos || 0).toFixed(2);
                document.getElementById('val-total-presentacion').textContent = 
                    `${subtotal.piezas} unidades (${kilosVal} kg)`;
            }

            // Total General Inferior
            const totPiezas = datosCargadosActuales.total_general_piezas || 0;
            const totKilosVal = typeof datosCargadosActuales.total_general_kilos === 'number' 
                ? datosCargadosActuales.total_general_kilos.toFixed(2) 
                : parseFloat(datosCargadosActuales.total_general_kilos || 0).toFixed(2);

            document.getElementById('total-general').textContent = 
                `${totPiezas} unidades (${totKilosVal} kg)`;
        }

        function seleccionarPresentacion(kg) {
            presentacionActual = kg;

            // Cambiar botón activo
            document.getElementById('btn-10').classList.toggle('active', kg === 10);
            document.getElementById('btn-15').classList.toggle('active', kg === 15);
            document.getElementById('btn-18').classList.toggle('active', kg === 18);

            // Cambiar título del subtotal del día
            document.getElementById('label-total-presentacion').textContent = `TOTAL DEL DÍA (${kg} KG)`;

            // Mostrar/Ocultar contenedores
            document.getElementById('sec-10').classList.toggle('active', kg === 10);
            document.getElementById('sec-15').classList.toggle('active', kg === 15);
            document.getElementById('sec-18').classList.toggle('active', kg === 18);

            // Actualizar número del subtotal del día
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