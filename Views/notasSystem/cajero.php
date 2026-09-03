<?php
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

    <!-- ESTILOS ADICIONALES PARA EL CHAT FLOTANTE -->
    <style>
        .chat-btn-float {
            position: fixed;
            bottom: 80px; /* Posicionado por encima de la barra de navegación inferior */
            right: 20px;
            width: 56px;
            height: 56px;
            background-color: #2563eb;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            cursor: pointer;
            z-index: 999;
            border: none;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .chat-btn-float:active {
            transform: scale(0.92);
        }

        .chat-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: #ef4444;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            border: 2px solid #ffffff;
            display: none;
        }

        /* MODAL DEL CHAT */
        .chat-modal {
            position: fixed;
            bottom: 145px;
            right: 20px;
            width: calc(100% - 40px);
            max-width: 360px;
            height: 420px;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .chat-modal.active {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .chat-header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chat-close-btn {
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .chat-body {
            flex: 1;
            padding: 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background-color: #f8fafc;
        }

        .chat-msg {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.88rem;
            line-height: 1.3;
            word-wrap: break-word;
        }

        .chat-msg-sent {
            align-self: flex-end;
            background-color: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .chat-msg-received {
            align-self: flex-start;
            background-color: #e2e8f0;
            color: #1e293b;
            border-bottom-left-radius: 2px;
        }

        .chat-msg-author {
            font-size: 0.7rem;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
            opacity: 0.8;
        }

        .chat-footer {
            padding: 10px;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
        }

        .chat-input {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            padding: 8px 14px;
            font-size: 0.9rem;
            outline: none;
        }

        .chat-input:focus {
            border-color: #2563eb;
        }

        .chat-send-btn {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    </style>
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

        <!-- ALERTAS DE SESIÓN -->
        <?php if (!empty($_SESSION['alerta_exito'])): ?>
            <div id="alerta-flash" class="alert alert-success" style="background-color: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin: 10px 0; text-align: center; font-weight: bold;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['alerta_exito']) ?>
            </div>
            <?php unset($_SESSION['alerta_exito']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['alerta_error'])): ?>
            <div id="alerta-flash" class="alert alert-danger" style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin: 10px 0; text-align: center; font-weight: bold;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['alerta_error']) ?>
            </div>
            <?php unset($_SESSION['alerta_error']); ?>
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

                            <form action="../Controllers/cajeroController.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Confirmar el cobro de la Nota <?= htmlspecialchars((string)$nota['folio']) ?>?');">
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
                    <p style="color: var(--text-muted);">Cargando historial...</p>
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

    <!-- BOTÓN Y MODAL DEL CHAT INTERNO -->
    <button class="chat-btn-float" id="btnChatToggle" onclick="toggleChatModal()">
        <i class="fa-solid fa-comments"></i>
        <span class="chat-badge" id="chatBadge">1</span>
    </button>

    <div class="chat-modal" id="chatModal">
        <div class="chat-header">
            <h3><i class="fa-solid fa-comments"></i> Chat Interno</h3>
            <button class="chat-close-btn" onclick="toggleChatModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-msg chat-msg-received">
                <span class="chat-msg-author">Sistema</span>
                ¡Hola, <?= htmlspecialchars($nombreUsuario) ?>! Canal de comunicación interna activo.
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" id="chatInput" class="chat-input" placeholder="Escribe un mensaje..." onkeypress="handleChatKeyPress(event)">
            <button class="chat-send-btn" onclick="enviarMensajeChat()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

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
            const alerta = document.getElementById('alerta-flash');
            if (alerta) {
                setTimeout(() => {
                    alerta.style.transition = 'opacity 0.5s ease';
                    alerta.style.opacity = '0';
                    setTimeout(() => alerta.remove(), 500);
                }, 3000);
            }

            setInterval(fetchNotas, 4000);
        });

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

        // ==========================================
        // LÓGICA DEL CHAT INTERNO
        // ==========================================
        function toggleChatModal() {
            const modal = document.getElementById('chatModal');
            const badge = document.getElementById('chatBadge');
            
            modal.classList.toggle('active');

            if (modal.classList.contains('active')) {
                badge.style.display = 'none';
                const body = document.getElementById('chatBody');
                body.scrollTop = body.scrollHeight;
            }
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                enviarMensajeChat();
            }
        }

        function enviarMensajeChat() {
            const input = document.getElementById('chatInput');
            const mensaje = input.value.trim();

            if (!mensaje) return;

            const chatBody = document.getElementById('chatBody');

            // Crear el mensaje enviado
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-msg chat-msg-sent';
            msgDiv.innerHTML = `<span class="chat-msg-author">Tú</span>${escaparHTML(mensaje)}`;
            
            chatBody.appendChild(msgDiv);
            input.value = '';
            chatBody.scrollTop = chatBody.scrollHeight;

            /* 
               AQUÍ PUEDES CONECTAR TU AJAX / WEBSOCKET PARA GUARDAR EL MENSAJE EN BASE DE DATOS:
               fetch('../Controllers/chatController.php', {
                   method: 'POST',
                   headers: { 'Content-Type': 'application/json' },
                   body: JSON.stringify({ mensaje: mensaje })
               });
            */
        }

        function escaparHTML(str) {
            return str.replace(/[&<>'"]/g, 
                tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
            );
        }
    </script>
</body>
</html>