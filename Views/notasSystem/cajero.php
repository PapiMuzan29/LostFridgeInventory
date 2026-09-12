<?php

$rolesPermitidos = [1, 6];
require_once __DIR__ . '/../../Config/cadenero.php';


if (!isset($notasPendientes)) {
    header("Location: ../../Controllers/cajeroController.php");
    exit;
}

$nombreUsuario = $_SESSION['apodoUsuario'] ?? $_SESSION['nombreUsuario'] ?? 'Cajero';


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Módulo Cajero - Grupo Cárnico América</title>
    
    <link rel="stylesheet" href="../Views/notasSystem/CSS/cajero.css">
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>
</head>
<body>
     
    <header class="app-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h1>Grupo Cárnico América</h1>
                <p id="header-subtitle">Módulo de Recepción y Cobro en Caja</p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.15); padding: 6px 12px; border-radius: 20px;">
                <i class="fa-solid fa-circle-user" style="font-size: 1.4rem;"></i>
                <span style="font-weight: bold; font-size: 0.95rem;"><?php echo htmlspecialchars($nombreUsuario); ?></span>
            </div>
        </div>
    </header>

    <main class="app-content">

        <!-- 1. PREPARAMOS EL MENSAJE DEL MODAL -->
        <?php 
            $mensajeModal = '';
            $tipoModal = '';
            if (!empty($_SESSION['alerta_exito'])) {
                $mensajeModal = htmlspecialchars($_SESSION['alerta_exito']);
                $tipoModal = 'exito';
                unset($_SESSION['alerta_exito']);
            } elseif (!empty($_SESSION['alerta_error'])) {
                $mensajeModal = htmlspecialchars($_SESSION['alerta_error']);
                $tipoModal = 'error';
                unset($_SESSION['alerta_error']);
            }
        ?>

        <?php if ($mensajeModal !== ''): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    mostrarAlerta('<?= $mensajeModal ?>', '<?= $tipoModal ?>');
                });
            </script>
        <?php endif; ?>

        <!-- VISTA 1: NOTAS RECIBIDAS -->
        <div id="tab-inicio" class="tab-content active">

            <div class="caja-toolbar">
                <h2 class="caja-toolbar-title" id="titulo-notas">
                    <i class="fa-solid fa-receipt"></i> Notas Pendientes (<?= count($notasPendientes ?? []) ?>)
                </h2>

                <button onclick="fetchNotas()" class="btn-secondary-sm" title="Actualizar manualmente">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </div>

            <section class="card sticky-search">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="buscarNota"><i class="fa-solid fa-magnifying-glass"></i> Buscar Nota</label>
                    <input type="text" id="buscarNota" class="form-control" placeholder="Buscar por folio o cliente..." onkeyup="filtrarNotas()">
                </div>
            </section>

            <!-- CONTENEDOR DINÁMICO -->
            <div id="contenedor-notas">
                <?php if (empty($notasPendientes)): ?>
                    <section class="card empty-state">
                        <i class="fa-solid fa-check-circle empty-state-icon" style="color: #16a34a;"></i>
                        <p style="margin: 0; font-weight: bold;">No hay notas pendientes por cobrar.</p>
                    </section>
                <?php else: ?>
                    <?php foreach ($notasPendientes as $nota): ?>
                        <section class="card nota-card" data-id="<?= (int)$nota['id_nota'] ?>">
                            <div class="nota-header">
                                <div>
                                    <strong class="nota-title">Nota #<?= htmlspecialchars((string)$nota['folio']) ?></strong>
                                    <small class="nota-date"><?= date('Y-m-d h:i A', strtotime($nota['fecha'])) ?></small>
                                </div>
                                <span class="badge-pending">PENDIENTE</span>
                            </div>

                            <p class="nota-info"><strong><i class="fa-solid fa-user"></i> Cliente:</strong> <?= htmlspecialchars((string)$nota['cliente']) ?></p>
                            <p class="nota-info"><strong><i class="fa-solid fa-user-tag"></i> Vendedor:</strong> <?= htmlspecialchars((string)$nota['vendedor']) ?></p>

                            <div class="detalles-list">
                                <strong><i class="fa-solid fa-cubes"></i> Productos Solicitados:</strong><br>
                                <?php if (!empty($nota['productos'])): ?>
                                    <?php foreach ($nota['productos'] as $prod): ?>
                                        • <?= number_format((float)$prod['kilos'], 2) ?> kg - <?= htmlspecialchars((string)$prod['nombre']) ?> (<?= (int)$prod['piezas'] ?> pzs)<br>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    • <em>Sin productos detallados</em><br>
                                <?php endif; ?>

                                <?php if (!empty($nota['estibadores'])): ?>
                                    <strong style="display:inline-block; margin-top:8px;"><i class="fa-solid fa-people-carry-box"></i> Estibadores:</strong><br>
                                    <?php foreach ($nota['estibadores'] as $est): ?>
                                        • <?= htmlspecialchars((string)$est['nombre']) ?><br>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form action="../Controllers/cajeroController.php" method="POST" style="margin: 0;" onsubmit="event.preventDefault(); const form = this; mostrarAlerta('¿Confirmar el cobro de la Nota <?= htmlspecialchars((string)$nota['folio']) ?>?', 'confirmacion', function(acepta) { if(acepta) form.submit(); });">
                                <input type="hidden" name="accion" value="pagar">
                                <input type="hidden" name="id_nota" value="<?= (int)$nota['id_nota'] ?>">
                                <button type="submit" class="btn-success">
                                    <i class="fa-solid fa-cash-register"></i> Procesar / Cobrar Nota
                                </button>
                            </form>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- VISTA 2: GESTIÓN Y RESUMEN DE PRODUCTOS -->
        <div id="tab-historial" class="tab-content">
            
           <section class="card sticky-search">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="buscarHistorial"><i class="fa-solid fa-magnifying-glass"></i> Buscar Ticket</label>
                    <input type="text" id="buscarHistorial" class="form-control" placeholder="Buscar por folio o cliente..." onkeyup="filtrarHistorial()">
                </div>
            </section>

            <!-- contenedor-historial -->
            <div id="contenedor-historial">
                <div style="text-align:center; padding:20px;">
                    <p style="color: var(--caja-text-muted);">Cargando historial...</p>
                </div>
            </div>

        </div>

        <!-- VISTA 3: CONFIGURACIÓN -->
        <div id="tab-config" class="tab-content">
            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-user-gear"></i> Perfil de Cajero</h2>
                <div class="user-info-box">
                    <p><strong>Cajero Activo:</strong> <?php echo htmlspecialchars($nombreUsuario); ?></p>
                    <p><strong>Rol:</strong> Caja / Recepción</p>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-sliders"></i> Ajustes del Sistema</h2>
                <div class="form-group">
                    <label>Modo de Operación</label>
                    <select class="form-control" disabled>
                        <option>Recepción y Cobro Directo</option>
                    </select>
                </div>

                <!-- SWITCH DE MODO OSCURO ESTRUCTURADO -->
                <div class="toggle-control">
                    <label for="toggle-dark-mode" style="margin: 0; cursor: pointer;">
                        <i class="fa-solid fa-moon"></i> Modo Oscuro
                    </label>
                    <label class="switch">
                        <input type="checkbox" id="toggle-dark-mode" onchange="toggleDarkMode(this.checked)">
                        <span class="slider"></span>
                    </label>
                </div> 
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Sistema</h2>
                <p class="subtext-muted"><strong>LFI Caja Móvil:</strong> v1.0</p>
                <p class="subtext-muted mt-5">Desarrollado para Grupo Cárnico América</p>
                
                <div class="mt-20">
                    <a href="../Controllers/LoginController.php?action=logout" class="btn-danger-block">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                    </a>
                </div>
            </section>
        </div>

        <div class="spacer"></div>

    </main>

    <!-- BARRA NAVEGACIÓN INFERIOR -->
    <nav class="bottom-nav">
        <button type="button" class="nav-item active" onclick="switchTab('inicio', this)">
            <i class="fa-solid fa-receipt"></i>
            <span>Notas</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('historial', this)">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Historial</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('config', this)">
            <i class="fa-solid fa-gear"></i>
            <span>Ajustes</span>
        </button>
    </nav>

    <script>
        // ==========================================
        // CONTROL Y PERSISTENCIA DE MODO OSCURO
        // ==========================================
        function toggleDarkMode(isDark) {
            if (isDark) {
                document.body.classList.add('dark-mode');
                document.documentElement.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                document.documentElement.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        }

        function obtenerIdsActuales() {
            const tarjetas = document.querySelectorAll('#contenedor-notas .nota-card');
            return Array.from(tarjetas).map(t => t.getAttribute('data-id')).filter(Boolean);
        }

        let idsNotasActuales = obtenerIdsActuales();

        async function fetchNotas() {
            try {
                const inputBusqueda = document.getElementById('buscarNota');
                const textoBusqueda = inputBusqueda ? inputBusqueda.value.trim() : '';

                const url = `../Controllers/obtenerNotasAjax.php?q=${encodeURIComponent(textoBusqueda)}`;
                
                const response = await fetch(url);
                if (!response.ok) return;

                const html = await response.text();
                const contenedor = document.getElementById('contenedor-notas');
                
                if (contenedor.innerHTML.trim() === html.trim()) return;

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                const nuevasTarjetas = tempDiv.querySelectorAll('.nota-card');
                const nuevosIds = Array.from(nuevasTarjetas).map(t => t.getAttribute('data-id')).filter(Boolean);

                const hayNotasNuevas = nuevosIds.some(id => !idsNotasActuales.includes(id));

                contenedor.innerHTML = html;

                if (hayNotasNuevas) {
                    nuevosIds.forEach(id => {
                        if (!idsNotasActuales.includes(id)) {
                            const tarjetaNueva = contenedor.querySelector(`.nota-card[data-id="${id}"]`);
                            if (tarjetaNueva) {
                                tarjetaNueva.classList.add('nota-nueva-alerta');
                            }
                        }
                    });
                }

                idsNotasActuales = nuevosIds;
                const titulo = document.getElementById('titulo-notas');
                if (titulo) {
                    titulo.innerHTML = `<i class="fa-solid fa-receipt"></i> Notas Pendientes (${nuevosIds.length})`;
                }

            } catch (err) {
                console.error("Error al sincronizar silenciosamente:", err);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Cargar preferencia del Tema Oscuro Guardado
            const savedTheme = localStorage.getItem('theme');
            const toggleInput = document.getElementById('toggle-dark-mode');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                document.documentElement.classList.add('dark-mode');
                if (toggleInput) toggleInput.checked = true;
            }

            // Mantenemos solo la recarga automática de notas
            setInterval(fetchNotas, 4000);
        });

        // ==========================================
        // FUNCIONES PARA MOSTRAR ALERTAS BONITAS EN MODAL
        // ==========================================
        function mostrarAlerta(mensaje, tipo = 'error', callbackAceptar = null) {
            const modal = document.getElementById('modal-alerta-global');
            const mensajeEl = document.getElementById('modal-alerta-mensaje');
            const tituloEl = document.getElementById('modal-alert-title');
            const iconEl = document.getElementById('modal-alert-icon-container');
            const footerEl = document.getElementById('modal-alert-footer-buttons');

            mensajeEl.textContent = mensaje;
            window._callbackAlertaAceptar = callbackAceptar;

            if (tipo === 'error') {
                tituloEl.textContent = 'Atención';
                iconEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #e53e3e;"></i>';
            } else if (tipo === 'exito') {
                tituloEl.textContent = '¡Éxito!';
                iconEl.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #38a169;"></i>';
            } else if (tipo === 'confirmacion') {
                tituloEl.textContent = 'Confirmación';
                iconEl.innerHTML = '<i class="fa-solid fa-circle-question" style="color: #d97706;"></i>';
                
                footerEl.innerHTML = `
                    <button type="button" class="btn-secondary-sm" style="flex:1;" onclick="cerrarAlertaGlobal(false)">Cancelar</button>
                    <button type="button" class="btn-success" style="flex:1;" onclick="cerrarAlertaGlobal(true)">
                        <i class="fa-solid fa-cash-register"></i> Sí, cobrar
                    </button>
                `;
                modal.style.display = 'flex';
                return;
            }

            footerEl.innerHTML = `<button type="button" class="btn-primary" style="width: 100%; padding: 10px;" onclick="cerrarAlertaGlobal(true)">Aceptar</button>`;
            modal.style.display = 'flex';
        }

        function cerrarAlertaGlobal(resultado) {
            const modal = document.getElementById('modal-alerta-global');
            modal.style.display = 'none';

            if (window._callbackAlertaAceptar && typeof window._callbackAlertaAceptar === 'function') {
                window._callbackAlertaAceptar(resultado);
                window._callbackAlertaAceptar = null;
            }
        }

        function switchTab(tabName, btnElement) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));

            if (tabName === 'inicio') {
                document.getElementById('tab-inicio').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Módulo de Recepción y Cobro en Caja';
            } else if (tabName === 'config') {
                document.getElementById('tab-config').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Configuración del Sistema';
            } else if (tabName === 'historial') {
                document.getElementById('tab-historial').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Tickets Cobrados';
                
                fetchHistorial();
            }

            btnElement.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        let timeoutBusquedaNotas;

        function filtrarNotas() {
            clearTimeout(timeoutBusquedaNotas);
            timeoutBusquedaNotas = setTimeout(() => {
                fetchNotas();
            }, 400);
        }

        async function fetchHistorial() {
            try {
                const inputBusqueda = document.getElementById('buscarHistorial');
                const textoBusqueda = inputBusqueda ? inputBusqueda.value.trim() : '';

                const url = `../Controllers/obtenerNotasAjax.php?q=${encodeURIComponent(textoBusqueda)}&tipo=historial`;
                
                const response = await fetch(url);
                if (!response.ok) return;

                const html = await response.text();
                const contenedor = document.getElementById('contenedor-historial');
                
                if (contenedor.innerHTML.trim() !== html.trim()) {
                    contenedor.innerHTML = html;
                }

            } catch (err) {
                console.error("Error al cargar historial:", err);
            }
        }

        let timeoutBusquedaHistorial;
        function filtrarHistorial() {
            clearTimeout(timeoutBusquedaHistorial);
            timeoutBusquedaHistorial = setTimeout(() => {
                fetchHistorial();
            }, 400);
        }
    </script>

    <!-- MODAL DE ALERTAS GENERALES -->
    <div id="modal-alerta-global" class="modal-alert-overlay" style="display: none;">
        <div class="modal-alert-content">
            <div class="modal-alert-header">
                <div id="modal-alert-icon-container" class="modal-alert-icon">⚠️</div>
                <h3 id="modal-alert-title" class="modal-alert-title">Aviso</h3>
            </div>
            <div id="modal-alerta-mensaje" class="modal-alert-body">
                Mensaje...
            </div>
            <div class="modal-alert-footer" id="modal-alert-footer-buttons">
                <button type="button" class="btn-primary" style="width: 100%; padding: 10px;" onclick="cerrarAlertaGlobal()">Aceptar</button>
            </div>
        </div>
    </div>
</body>
</html>