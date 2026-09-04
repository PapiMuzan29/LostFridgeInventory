<?php
// chuleta.php
session_start();

$nombreUsuario = $_SESSION['apodoUsuario'] ?? $_SESSION['nombreUsuario'] ?? 'Usuario';
$fechaActual = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Módulo Chuleta Ahumada - Grupo Cárnico América</title>
    
    <!-- CSS del proyecto -->
    <link rel="stylesheet" href="../notasSystem/CSS/chuleta.css">
    <link rel="stylesheet" href="../notasSystem/CSS/encargado.css">
    
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>

    <style>
        /* Ocultar el input tipo date sin afectar el flujo */
        .input-date-hidden {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <!-- HEADER ESTILO CHULETA -->
    <header class="chuleta-header">
        <button type="button" class="btn-volver" onclick="volverPantallaAnterior()">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </button>
        <h1 class="header-title">
            <i class="fa-solid fa-drumstick-bite"></i> Chuleta Ahumada
        </h1>
        <div class="user-badge">
            <i class="fa-solid fa-user"></i>
            <span><?= htmlspecialchars($nombreUsuario) ?></span>
        </div>
    </header>

    <main class="container">

        <!-- SELECTOR DE FECHA FLOTANTE (ESTILO MANTECA EXACTO) -->
        <div class="date-picker-card">
            <button type="button" class="btn-date-arrow" onclick="cambiarFecha(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="date-display" onclick="abrirCalendario()">
                <i class="fa-regular fa-calendar-days"></i>
                <span id="label-fecha-seleccionada">Cargando fecha...</span>
                <input type="date" id="input-fecha-chuleta" class="input-date-hidden" value="<?= $fechaActual ?>" onchange="alSeleccionarFecha(this.value)">
            </div>

            <button type="button" class="btn-date-arrow" onclick="cambiarFecha(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- TOTAL DEL DÍA EN TARJETA SUPERIOR -->
        <div class="card-total-dia">
            <div class="info">
                <h3>TOTAL DEL DÍA</h3>
                <div class="val-piezas" id="total-banner-superior">0 piezas (0.00 kg)</div>
            </div>
            <i class="fa-solid fa-drumstick-bite icon"></i>
        </div>

        <!-- REGISTROS Y VENTAS DEL DÍA -->
        <div class="section-title">
            <i class="fa-solid fa-list-check"></i> VENTAS CHULETA AHUMADA
        </div>
        <div id="contenedor-ventas-chuleta" class="ventas-list"></div>

    </main>

    <script>
        let fechaSeleccionadaObj = new Date();

        document.addEventListener('DOMContentLoaded', () => {
            const inputFecha = document.getElementById('input-fecha-chuleta');
            if (inputFecha && inputFecha.value) {
                const partes = inputFecha.value.split('-');
                fechaSeleccionadaObj = new Date(partes[0], partes[1] - 1, partes[2]);
            }
            actualizarInterfazFecha();
            cargarDatosChuletaPorFecha(inputFecha.value);
        });

        function abrirCalendario() {
            const input = document.getElementById('input-fecha-chuleta');
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
            document.getElementById('input-fecha-chuleta').value = fechaString;
            alSeleccionarFecha(fechaString);
        }

        function alSeleccionarFecha(fechaString) {
            const partes = fechaString.split('-');
            fechaSeleccionadaObj = new Date(partes[0], partes[1] - 1, partes[2]);
            actualizarInterfazFecha();
            cargarDatosChuletaPorFecha(fechaString);
        }

        function actualizarInterfazFecha() {
            const opciones = { day: 'numeric', month: 'short' };
            const fechaFormateada = fechaSeleccionadaObj.toLocaleDateString('es-ES', opciones);
            document.getElementById('label-fecha-seleccionada').textContent = fechaFormateada;
        }

        // CONSULTA DE DATOS AL CONTROLADOR
        function cargarDatosChuletaPorFecha(fecha) {
            const rutaControlador = `../../Controllers/chuletaController.php?accion=consultarPorFecha&fecha=${fecha}`;

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

                    const contenedor = document.getElementById('contenedor-ventas-chuleta');

                    if (!data.ventas || data.ventas.length === 0) {
                        contenedor.innerHTML = `
                            <div class="empty-state-card" style="text-align: center; padding: 20px; color: #94a3b8;">
                                <i class="fa-solid fa-inbox fa-2x"></i>
                                <p style="margin-top: 8px;">Sin ventas registradas en Chuleta Ahumada</p>
                            </div>`;
                    } else {
                        contenedor.innerHTML = data.ventas.map(v => `
                            <div class="venta-item">
                                <div class="venta-info">
                                    <div class="cliente-nombre">
                                        <i class="fa-solid fa-user-tag"></i> Cliente: ${v.cliente}
                                    </div>
                                    <div class="venta-sub">
                                        <i class="fa-solid fa-receipt"></i> Ticket: ${v.ticket} • ${parseFloat(v.kilos).toFixed(2)} kg
                                    </div>
                                </div>
                                <div class="badge-cantidad">
                                    <span class="val">${v.piezas}</span>
                                    <span class="unit">pzs</span>
                                </div>
                            </div>
                        `).join('');
                    }

                    // Actualización del Total Superior
                    const totPiezas = data.total_general_piezas || 0;
                    const totKilos = parseFloat(data.total_general_kilos || 0).toFixed(2);
                    document.getElementById('total-banner-superior').textContent = `${totPiezas} piezas (${totKilos} kg)`;
                })
                .catch(err => console.error("Error al obtener datos de chuleta:", err));
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