<?php
// pecho.php
session_start();

$rolesPermitidos = [1, 5];
require_once __DIR__ . '/../../Config/cadenero.php';

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
    <title>Módulo Pecho - Grupo Cárnico América</title>
    
    <!-- Hojas de Estilos -->
    <link rel="stylesheet" href="../notasSystem/CSS/cajero.css">
    <link rel="stylesheet" href="../notasSystem/CSS/pecho.css?v=<?= filemtime('../notasSystem/CSS/pecho.css') ?>">
    <link rel="stylesheet" href="CSS/encargado.css">
    
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>

    <!-- DETECCIÓN RÁPIDA DE TEMA EN EL HEAD (EVITA PARPADEO BLANCO) -->
    <script>
        (function() {
            const temaGuardado = localStorage.getItem("theme_mode");
            const prefiereOscuro = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
            if (temaGuardado === "dark" || (!temaGuardado && prefiereOscuro)) {
                document.documentElement.classList.add("dark-mode");
            }
        })();
    </script>

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
            background-color: var(--pecho-bg, #f8fafc);
            padding: 10px 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 15px;
            transition: background-color 0.3s ease;
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

        .empty-state-card {
            text-align: center;
            padding: 20px;
            color: var(--pecho-text-muted, #64748b);
            font-size: 0.9rem;
            background: var(--pecho-card-bg, #ffffff);
            border-radius: 8px;
            border: 1px dashed var(--pecho-border, #fbcfe8);
        }

        /* CARD DE TOTAL GENERAL PEGADA ABAJO DEL TODO */
        .fixed-bottom-total-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: var(--pecho-card-bg, #ffffff);
            padding: 10px 15px 15px 15px;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: background-color 0.3s ease;
            border-top: 1px solid var(--pecho-border);
        }

        .fixed-bottom-total-container .total-general-card {
            margin: 0;
            text-align: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--pecho-text-muted);
        }

        .fixed-bottom-total-container .total-val {
            font-size: 1.4rem;
            font-weight: 800;
            margin-top: 2px;
            color: var(--pecho-text-main);
        }
    </style>
</head>
<body>

    <!-- HEADER UNIFICADO -->
    <header class="chuleta-header">
        <button type="button" class="btn-volver" onclick="volverPantallaAnterior()">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </button>
        <div class="header-title">
            <i class="fa-solid fa-drumstick-bite"></i> Pecho
        </div>
        <div class="user-badge">
            <i class="fa-solid fa-user"></i>
            <span><?= htmlspecialchars($nombreUsuario) ?></span>
        </div>
    </header>

    <!-- NAVEGADOR DE FECHA / CALENDARIO -->
    <div class="sticky-date-bar">
        <div class="date-picker-card" style="margin: 0;">
            <button type="button" class="btn-date-arrow" onclick="cambiarFecha(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="date-display" onclick="abrirCalendario()">
                <i class="fa-regular fa-calendar-days"></i>
                <span id="label-fecha-seleccionada">Cargando fecha...</span>
                <input type="date" id="input-fecha-pecho" value="<?= $fechaActual ?>" style="position:absolute; opacity:0; pointer-events:none;" onchange="alSeleccionarFecha(this.value)">
            </div>
            <button type="button" class="btn-date-arrow" onclick="cambiarFecha(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <main class="container">

        <!-- 1. SELECCIONAR PRESENTACIÓN -->
        <div class="section-title">SELECCIONA PRESENTACIÓN</div>
        <div class="presentation-tabs">
            <button type="button" class="btn-tab active" id="btn-suelto" onclick="seleccionarPresentacion('suelto')">
                <i class="fa-solid fa-cubes-stacked"></i> SUELTO
            </button>
            <button type="button" class="btn-tab" id="btn-cajas" onclick="seleccionarPresentacion('cajas')">
                <i class="fa-solid fa-box"></i> CAJAS
            </button>
        </div>

        <!-- 2. TOTAL DEL DÍA SEGÚN PRESENTACIÓN -->
        <div class="card-total-dia">
            <div class="info">
                <h3 id="label-total-presentacion">TOTAL DEL DÍA (SUELTO)</h3>
                <div class="val-piezas" id="val-total-presentacion">0 pzs (0.00 kg)</div>
            </div>
            <i class="fa-solid fa-boxes-stacked icon"></i>
        </div>

        <!-- 3. ÁREA DE TARJETAS DINÁMICAS POR PRESENTACIÓN -->
        <div class="content-cards-wrapper" style="margin-top: 15px;">

            <!-- SECCIÓN SUELTO -->
            <div id="sec-suelto" class="presentacion-section active">
                <div class="section-title"><i class="fa-solid fa-list-check"></i> Ventas Pecho Suelto</div>
                <div id="contenedor-ventas-suelto" class="ventas-list"></div>
            </div>

            <!-- SECCIÓN CAJAS -->
            <div id="sec-cajas" class="presentacion-section">
                <div class="section-title"><i class="fa-solid fa-list-check"></i> Ventas Pecho Cajas</div>
                <div id="contenedor-ventas-cajas" class="ventas-list"></div>
            </div>

        </div>

    </main>

    <!-- 4. TOTAL GENERAL SIEMPRE FIJO ABAJO DEL TODO -->
    <div class="fixed-bottom-total-container">
        <div class="total-general-card">
            TOTAL GENERAL ACUMULADO
            <div class="total-val" id="total-general">0 pzs (0.00 kg)</div>
        </div>
    </div>

    <script>
        let presentacionActual = 'suelto';
        let fechaSeleccionadaObj = new Date();
        let datosCargadosActuales = null;

        document.addEventListener('DOMContentLoaded', () => {
            // --- APLICAR MODO OSCURO SEGÚN ENCARGADO (theme_mode) ---
            const temaGuardado = localStorage.getItem("theme_mode");
            const prefiereOscuro = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;

            if (temaGuardado === "dark" || (!temaGuardado && prefiereOscuro)) {
                document.body.classList.add("dark-mode");
            } else {
                document.body.classList.remove("dark-mode");
            }

            // --- LÓGICA DE INICIALIZACIÓN ---
            const inputFecha = document.getElementById('input-fecha-pecho');
            if (inputFecha && inputFecha.value) {
                const partes = inputFecha.value.split('-');
                fechaSeleccionadaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            }
            actualizarInterfazFecha();
            cargarDatosPechoPorFecha(inputFecha.value);
        });

        function abrirCalendario() {
            const input = document.getElementById('input-fecha-pecho');
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
            document.getElementById('input-fecha-pecho').value = fechaString;
            alSeleccionarFecha(fechaString);
        }

        function alSeleccionarFecha(fechaString) {
            if (!fechaString) return;
            const partes = fechaString.split('-');
            fechaSeleccionadaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            actualizarInterfazFecha();
            cargarDatosPechoPorFecha(fechaString);
        }

        function actualizarInterfazFecha() {
            const opciones = { day: 'numeric', month: 'short' };
            const fechaFormateada = fechaSeleccionadaObj.toLocaleDateString('es-ES', opciones);
            document.getElementById('label-fecha-seleccionada').textContent = fechaFormateada;
        }

        // CARGA DE DATOS DESDE EL CONTROLADOR MVC
        function cargarDatosPechoPorFecha(fecha) {
            const rutaControlador = `../../Controllers/pechoController.php?accion=consultarPorFecha&fecha=${fecha}`;

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

                    // 1. Separamos las ventas usando Javascript según el nombre del producto
                    const ventasSuelto = [];
                    const ventasCajas = [];

                    (data.ventas || []).forEach(v => {
                        const nombre = v.producto.toLowerCase();
                        if (nombre.includes('caja')) {
                            ventasCajas.push(v);
                        } else {
                            ventasSuelto.push(v);
                        }
                    });

                    // 2. Renderizar SUELTO
                    const contSuelto = document.getElementById('contenedor-ventas-suelto');
                    if (ventasSuelto.length === 0) {
                        contSuelto.innerHTML = `
                            <div class="empty-state-card">
                                <i class="fa-solid fa-inbox fa-2x"></i>
                                <p style="margin-top:5px;">Sin ventas registradas en SUELTO</p>
                            </div>`;
                    } else {
                        contSuelto.innerHTML = ventasSuelto.map(v => `
                            <div class="venta-item">
                                <div class="venta-info">
                                    <div class="cliente-nombre"><i class="fa-solid fa-user-tag"></i> ${v.cliente}</div>
                                    <div class="venta-sub"><i class="fa-solid fa-receipt"></i> Ticket: ${v.ticket} • ${parseFloat(v.kilos).toFixed(2)} kg</div>
                                </div>
                                <div class="badge-cantidad">
                                    <span class="val">${v.piezas}</span>
                                    <span class="unit">pzs</span>
                                </div>
                            </div>
                        `).join('');
                    }

                    // 3. Renderizar CAJAS
                    const contCajas = document.getElementById('contenedor-ventas-cajas');
                    if (ventasCajas.length === 0) {
                        contCajas.innerHTML = `
                            <div class="empty-state-card">
                                <i class="fa-solid fa-inbox fa-2x"></i>
                                <p style="margin-top:5px;">Sin ventas registradas en CAJAS</p>
                            </div>`;
                    } else {
                        contCajas.innerHTML = ventasCajas.map(v => `
                            <div class="venta-item">
                                <div class="venta-info">
                                    <div class="cliente-nombre"><i class="fa-solid fa-user-tag"></i> ${v.cliente}</div>
                                    <div class="venta-sub"><i class="fa-solid fa-receipt"></i> Ticket: ${v.ticket} • ${parseFloat(v.kilos).toFixed(2)} kg</div>
                                </div>
                                <div class="badge-cantidad">
                                    <span class="val">${v.piezas}</span>
                                    <span class="unit">cajas</span>
                                </div>
                            </div>
                        `).join('');
                    }

                    // 4. Actualizar banners e indicadores de totales
                    actualizarBanners();
                })
                .catch(err => console.error("Error al obtener datos:", err));
        }

        function actualizarBanners() {
            if (!datosCargadosActuales) return;

            // Subtotal de la pestaña activa (suelto o cajas) leyendo las variables exactas del backend
            let subPzs = 0;
            let subKg = 0;
            
            if (presentacionActual === 'suelto') {
                subPzs = datosCargadosActuales.total_suelto_piezas || 0;
                subKg = parseFloat(datosCargadosActuales.total_suelto_kilos || 0).toFixed(2);
                document.getElementById('val-total-presentacion').textContent = `${subPzs} pzs (${subKg} kg)`;
            } else {
                subPzs = datosCargadosActuales.total_caja_piezas || 0;
                subKg = parseFloat(datosCargadosActuales.total_caja_kilos || 0).toFixed(2);
                document.getElementById('val-total-presentacion').textContent = `${subPzs} cajas (${subKg} kg)`;
            }

            // Total General Inferior (acumula ambas cosas)
            const totPiezas = datosCargadosActuales.total_general_piezas || 0;
            const totKilosVal = parseFloat(datosCargadosActuales.total_general_kilos || 0).toFixed(2);

            document.getElementById('total-general').textContent = `${totPiezas} pzs/cajas (${totKilosVal} kg)`;
        }

        function seleccionarPresentacion(tipo) {
            presentacionActual = tipo;

            // Cambiar botón activo
            document.getElementById('btn-suelto').classList.toggle('active', tipo === 'suelto');
            document.getElementById('btn-cajas').classList.toggle('active', tipo === 'cajas');

            // Cambiar título del subtotal del día
            document.getElementById('label-total-presentacion').textContent = `TOTAL DEL DÍA (${tipo.toUpperCase()})`;

            // Mostrar/Ocultar contenedores
            document.getElementById('sec-suelto').classList.toggle('active', tipo === 'suelto');
            document.getElementById('sec-cajas').classList.toggle('active', tipo === 'cajas');

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