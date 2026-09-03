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
    
    <!-- Hojas de Estilos coincidentes con Mazos y Manteca -->
    <link rel="stylesheet" href="../notasSystem/CSS/cajero.css">
    <link rel="stylesheet" href="../notasSystem/CSS/manteca.css">
    <link rel="stylesheet" href="../notasSystem/CSS/chuleta.css">
    <link rel="stylesheet" href="CSS/encargado.css">
    
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>

    <style>
        /* Estilos purpura específicos para el resumen de Chuleta Ahumada */
        .card-total-dia-chuleta {
            background-color: #f3e8ff;
            border-radius: 18px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-total-dia-chuleta .info h3 {
            margin: 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #6b21a8;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .card-total-dia-chuleta .info .val {
            font-size: 1.25rem;
            font-weight: 600;
            color: #3b0764;
            margin-top: 4px;
        }

        .card-total-dia-chuleta .icon {
            font-size: 2.2rem;
            color: #7e22ce;
        }

        .venta-card-item-chuleta {
            border-left: 4px solid #7e22ce;
        }

        .venta-badge-pzs-chuleta {
            background-color: #f3e8ff;
            color: #6b21a8;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.95rem;
        }

        /* Ocultar input date nativo para evitar solapamientos */
        .input-date-hidden-custom {
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            opacity: 0;
            pointer-events: none;
            border: none;
            padding: 0;
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
            <h1><i class="fa-solid fa-drumstick-bite"></i> Chuleta Ahumada</h1>
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
            <div class="date-picker-display" style="position: relative;" onclick="abrirCalendario()">
                <i class="fa-regular fa-calendar-days"></i>
                <span id="label-fecha-seleccionada">Cargando fecha...</span>
                <input type="date" id="input-fecha-chuleta" class="input-date-hidden-custom" value="<?= $fechaActual ?>" onchange="alSeleccionarFecha(this.value)">
            </div>
            <button type="button" class="btn-date-nav" onclick="cambiarFecha(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <main class="container">

        <!-- TOTAL DEL DÍA EN TARJETA SUPERIOR -->
        <div class="card-total-dia-chuleta">
            <div class="info">
                <h3>TOTAL DEL DÍA</h3>
                <div class="val" id="total-banner-superior">0 piezas (0.00 kg)</div>
            </div>
            <i class="fa-solid fa-drumstick-bite icon"></i>
        </div>

        <!-- LISTADO DE VENTAS -->
        <div class="section-title"><i class="fa-solid fa-list-check"></i> Ventas Chuleta Ahumada</div>
        <div id="contenedor-ventas-chuleta" class="contenedor-tarjetas-ventas"></div>

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

        // CARGA DE DATOS DESDE EL CONTROLADOR
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
                            <div class="empty-state-card">
                                <i class="fa-solid fa-inbox fa-2x"></i>
                                <p class="empty-state-text">Sin ventas registradas en Chuleta Ahumada</p>
                            </div>`;
                    } else {
                        contenedor.innerHTML = data.ventas.map(v => `
                            <div class="venta-card-item venta-card-item-chuleta">
                                <div class="venta-card-main">
                                    <div class="venta-cliente"><i class="fa-solid fa-user-tag"></i> Cliente: ${v.cliente}</div>
                                    <div class="venta-ticket"><i class="fa-solid fa-receipt"></i> Ticket: ${v.ticket} • ${parseFloat(v.kilos).toFixed(2)} kg</div>
                                </div>
                                <div class="venta-badge-pzs-chuleta">
                                    <span>${v.piezas}</span> <small>pzs</small>
                                </div>
                            </div>
                        `).join('');
                    }

                    // Actualizar Totales
                    const totPiezas = data.total_general_piezas || 0;
                    const totKilos = parseFloat(data.total_general_kilos || 0).toFixed(2);
                    const textoTotal = `${totPiezas} piezas (${totKilos} kg)`;

                    document.getElementById('total-banner-superior').textContent = textoTotal;
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