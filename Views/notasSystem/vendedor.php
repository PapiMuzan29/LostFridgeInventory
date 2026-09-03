<?php
// Validar que la vista reciba los datos desde VendedorController.php
if (!isset($productos) || !isset($estibadores)) {
    header("Location: ../../Controllers/vendedorController.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Captura de Venta - Grupo Cárnico América</title>
    <link rel="stylesheet" href="../Views/notasSystem/CSS/vendedor.css">
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>
    <script>
        // Cargar preferencia guardada antes de renderizar para evitar parpadeos
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <style>
        /* ESTILOS DEL BOTÓN Y WIDGET DE CHAT INTERNO */
        .chat-widget-container {
            position: fixed;
            bottom: 75px; /* Por encima de la barra de navegación inferior */
            right: 15px;
            z-index: 1000;
        }

        .chat-toggle-btn {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background-color: var(--primary-color, #1a365d);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .chat-toggle-btn:active {
            transform: scale(0.92);
        }

        .chat-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: #e53e3e;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            border: 2px solid #ffffff;
        }

        /* Ventana flotante de Chat */
        .chat-box {
            display: none;
            position: fixed;
            bottom: 140px;
            right: 15px;
            width: calc(100vw - 30px);
            max-width: 360px;
            height: 420px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            z-index: 1001;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .chat-box.open {
            display: flex;
        }

        .chat-header {
            background-color: var(--primary-color, #1a365d);
            color: #ffffff;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chat-close-btn {
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.1rem;
            cursor: pointer;
            opacity: 0.8;
        }

        .chat-close-btn:hover {
            opacity: 1;
        }

        .chat-body {
            flex: 1;
            padding: 12px;
            overflow-y: auto;
            background-color: #f7fafc;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-message {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            line-height: 1.3;
        }

        .chat-message.received {
            background-color: #edf2f7;
            color: #2d3748;
            align-self: flex-start;
        }

        .chat-message.sent {
            background-color: var(--primary-color, #1a365d);
            color: #ffffff;
            align-self: flex-end;
        }

        .chat-footer {
            padding: 10px;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
        }

        .chat-footer input {
            flex: 1;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.85rem;
            outline: none;
        }

        .chat-footer button {
            background-color: var(--primary-color, #1a365d);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            cursor: pointer;
        }

        /* Estilos en Modo Oscuro para el Chat */
        .dark-mode .chat-box {
            background-color: #1a202c;
            border-color: #2d3748;
        }

        .dark-mode .chat-body {
            background-color: #141923;
        }

        .dark-mode .chat-message.received {
            background-color: #2d3748;
            color: #e2e8f0;
        }

        .dark-mode .chat-footer {
            background-color: #1a202c;
            border-color: #2d3748;
        }

        .dark-mode .chat-footer input {
            background-color: #2d3748;
            border-color: #4a5568;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
            <div>
                <h1 style="margin: 0; line-height: 1.2; font-size: 1.25rem;">Grupo Cárnico<br>América</h1>
                <p id="header-subtitle" style="margin-top: 4px; font-size: 0.85rem; opacity: 0.8;">Módulo de Captura de Pedidos</p>
            </div>

            <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 14px; border-radius: 20px; display: flex; align-items: center; gap: 8px; color: #ffffff; font-weight: 600; font-size: 0.9rem; white-space: nowrap;">
                <i class="fa-solid fa-circle-user" style="font-size: 1.1rem;"></i>
                <span><?= htmlspecialchars($_SESSION['apodoUsuario'] ?? 'Vendedor') ?></span>
            </div>
        </div>
    </header>

    <main class="app-content">

        <!-- VISTA 1: CAPTURA DE INICIO / NOTAS -->
        <div id="tab-inicio" class="tab-content active">

            <!-- ALERTAS BASADAS EN SESIÓN CON AUTODESTRUCCIÓN -->
            <?php if (!empty($_SESSION['alerta_exito'])): ?>
                <div id="alerta-flash" class="alert alert-success" style="background-color: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 16px; text-align: center; font-weight: bold; transition: opacity 0.5s ease;">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['alerta_exito']) ?>
                </div>
                <?php unset($_SESSION['alerta_exito']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['alerta_error'])): ?>
                <div id="alerta-flash" class="alert alert-danger" style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 16px; text-align: center; font-weight: bold; transition: opacity 0.5s ease;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['alerta_error']) ?>
                </div>
                <?php unset($_SESSION['alerta_error']); ?>
            <?php endif; ?>

            <form action="../Controllers/vendedorController.php" method="POST" id="formVendedor">
                
                <!-- Datos del Cliente -->
                <section class="card">
                    <h2 class="card-title"><i class="fa-solid fa-id-card"></i> 1. Información General</h2>
                    
                    <div class="form-group">
                        <label for="cliente">Nombre del Cliente *</label>
                        <input type="text" id="cliente" name="nombre_cliente" class="form-control" placeholder="Ej. Taquería El Paisa / Juan Pérez" required>
                    </div>

                    <div class="form-group">
                        <label for="estibadores">Estibador(es) * <small>(Selecciona uno o varios)</small></label>
                        <select id="estibadores" name="estibadores[]" class="form-control" multiple required style="height: 90px;">
                            <?php foreach ($estibadores as $estibador): ?>
                                <option value="<?= htmlspecialchars((string)$estibador['id_usuario']) ?>">
                                    <?= htmlspecialchars((string)$estibador['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <!-- Detalle de Productos -->
                <section class="card">
                    <div class="card-header-flex">
                        <h2 class="card-title" style="margin-bottom:0;"><i class="fa-solid fa-boxes-packing"></i> 2. Productos</h2>
                        <button type="button" class="btn-secondary-sm" id="btn-add-product">
                            <i class="fa-solid fa-plus"></i> Agregar
                        </button>
                    </div>

                    <!-- Datalist compartido con el catálogo completo de productos -->
                    <datalist id="lista-productos">
    <?php foreach ($productos as $producto): ?>
        <option 
            value="<?= htmlspecialchars((string)$producto['nombreProducto']) ?>" 
            label="<?= htmlspecialchars((string)$producto['nombreProducto']) ?>" 
            data-id="<?= htmlspecialchars((string)($producto['id_producto'] ?? '')) ?>"
            data-por-piezas="<?= htmlspecialchars((string)($producto['porPiezas'] ?? '0')) ?>"
            data-stock-piezas="<?= htmlspecialchars((string)($producto['cantidadPiezas'] ?? '0')) ?>"
            data-stock-peso="<?= htmlspecialchars((string)($producto['cantidadPeso'] ?? '0')) ?>"
        ></option>
    <?php endforeach; ?>
</datalist>

                    <div id="products-container" style="margin-top: 15px;">
                        
                        <!-- Fila de Producto -->
                        <div class="product-item card-inner">
                            <div class="product-item-header">
                                <span class="product-number">Producto #1</span>
                                <button type="button" class="btn-delete" onclick="removeProduct(this)" title="Eliminar producto">&times;</button>
                            </div>

                            <div class="form-group">
                                <label>Buscar Producto *</label>
                                <input type="text" list="lista-productos" class="form-control producto-search" placeholder="Escribe para buscar..." onchange="capturarIdProducto(this)" autocomplete="off" required>
                                <input type="hidden" name="productos[0][id_producto]" class="producto-id-hidden">
                            </div>

                            <div class="form-row">
    <div class="form-group col">
        <label>Kilos *</label>
        <input type="number" step="0.01" name="productos[0][kilos]" class="form-control input-kilos" placeholder="0.00" required>
    </div>

    <div class="form-group col">
        <label>Piezas <small>(Opcional)</small></label>
        <input type="number" name="productos[0][piezas]" class="form-control input-piezas" placeholder="0">
    </div>
</div>

<!-- NUEVO: Botón oculto para abrir caja -->
<div class="form-group contenedor-abrir-caja" style="display: none; margin-top: 10px;">
    <button type="button" class="btn-abrir-caja" style="background-color: #d97706; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%;" onclick="procesarAperturaCaja(this)">
        <i class="fa-solid fa-box-open"></i> Abrir Caja y Convertir a Kilos
    </button>
</div>
                        </div>

                    </div>

                    <!-- Botón Enviar Formulario -->
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-paper-plane"></i> Enviar a Caja
                        </button>
                    </div>
                </section>

            </form>
        </div>

        <!-- VISTA 2: CONFIGURACIÓN -->
        <div id="tab-config" class="tab-content">
            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-user-gear"></i> Perfil de Usuario</h2>
                <div class="user-info-box">
                    <p><strong>Usuario Activo:</strong> <?= htmlspecialchars($_SESSION['apodoUsuario'] ?? 'Vendedor') ?></p>
                    <p><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['nombreRol'] ?? 'Venta Móvil') ?></p>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-sliders"></i> Ajustes de Captura</h2>
                
                <!-- TOGGLE DE MODO OSCURO -->
                <div class="toggle-control" style="margin-bottom: 16px;">
                    <label for="toggle-dark-mode" style="margin: 0; cursor: pointer;">
                        <i class="fa-solid fa-moon"></i> Modo Oscuro
                    </label>
                    <label class="switch">
                        <input type="checkbox" id="toggle-dark-mode" onchange="toggleDarkMode(this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Modo de Conexión</label>
                    <select class="form-control" disabled>
                        <option>En Línea (BD LFI Principal)</option>
                    </select>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Sistema</h2>
                <p style="font-size: 0.9rem; color: var(--text-muted);"><strong>LFI Ventas Móvil:</strong> v1.0</p>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 5px;">Desarrollado para Grupo Cárnico América</p>
                
                <div style="margin-top: 20px;">
                    <a href="../Config/Logouth.php" class="btn-danger-block">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                    </a>
                </div>
            </section>
        </div>

        <div class="spacer"></div>

    </main>

    <!-- COMPONENTE FLOTANTE DE CHAT INTERNO -->
    <div class="chat-widget-container">
        <button class="chat-toggle-btn" onclick="toggleChatWindow()" title="Chat Interno corporativo">
            <i class="fa-solid fa-comments"></i>
            <span class="chat-badge">1</span>
        </button>
    </div>

    <!-- VENTANA POPUP DEL CHAT -->
    <div class="chat-box" id="chatBox">
        <div class="chat-header">
            <h3><i class="fa-solid fa-user-shield"></i> Chat Interno (Personal)</h3>
            <button class="chat-close-btn" onclick="toggleChatWindow()">&times;</button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-message received">
                <strong>Soporte / Caja:</strong><br>
                Hola <?= htmlspecialchars($_SESSION['apodoUsuario'] ?? 'Compañero') ?>, recuerda verificar los kilos antes de enviar la nota.
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Escribe un mensaje interno..." onkeypress="handleChatKeyPress(event)">
            <button type="button" onclick="enviarMensajeChat()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- BARRA NAVEGACIÓN INFERIOR TIPO APP -->
    <nav class="bottom-nav">
        <button type="button" class="nav-item active" onclick="switchTab('inicio', this)">
            <i class="fa-solid fa-house"></i>
            <span>Inicio</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('config', this)">
            <i class="fa-solid fa-gear"></i>
            <span>Ajustes</span>
        </button>
    </nav>

    <script>
        // Gestor de Modo Oscuro con LocalStorage
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

        document.addEventListener('DOMContentLoaded', () => {
            // Sincronizar el checkbox según el estado actual
            const currentTheme = localStorage.getItem('theme');
            const toggleInput = document.getElementById('toggle-dark-mode');
            
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (toggleInput) toggleInput.checked = true;
            }

            // Ocultar la alerta de pantalla tras 3 segundos
            const alerta = document.getElementById('alerta-flash');
            if (alerta) {
                setTimeout(() => {
                    alerta.style.opacity = '0';
                    setTimeout(() => alerta.remove(), 500);
                }, 3000);
            }
        });

        // Navegación entre Pestañas
        function switchTab(tabName, btnElement) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
            });

            if (tabName === 'inicio') {
                document.getElementById('tab-inicio').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Módulo de Captura de Pedidos';
            } else if (tabName === 'config') {
                document.getElementById('tab-config').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Configuración del Sistema';
            }

            btnElement.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Mapea el idProducto, valida banderas y bloquea campos
        function capturarIdProducto(inputSearch) {
    const val = inputSearch.value.trim().toLowerCase();
    const options = document.querySelectorAll('#lista-productos option');
    const row = inputSearch.closest('.product-item');
    const hiddenInput = row.querySelector('.producto-id-hidden');
    const inputKilos = row.querySelector('.input-kilos');
    const inputPiezas = row.querySelector('.input-piezas');
    
    // NUEVO: Capturar el contenedor del botón
    const contenedorBotonCaja = row.querySelector('.contenedor-abrir-caja');
    
    hiddenInput.value = '';
    inputKilos.disabled = false;
    inputPiezas.disabled = false;
    inputKilos.value = '';
    inputPiezas.value = '';
    
    // NUEVO: Ocultar botón por defecto
    if (contenedorBotonCaja) contenedorBotonCaja.style.display = 'none';

    let matchFound = false;

    options.forEach(opt => {
        const optVal = opt.value.trim().toLowerCase();
        
        if (optVal === val) {
            matchFound = true;
            const idProducto = opt.getAttribute('data-id');
            hiddenInput.value = idProducto;
            
            const esPorPieza = (opt.getAttribute('data-por-piezas') === "1");
            const stockKilos = opt.getAttribute('data-stock-peso') || '0';
            const stockPiezas = opt.getAttribute('data-stock-piezas') || '0';
            
            if (esPorPieza) {
                inputKilos.disabled = true;
                inputKilos.placeholder = 'N/A';
                inputKilos.required = false;
                
                inputPiezas.required = true;
                inputPiezas.placeholder = `Max: ${stockPiezas}`;
                
                // VALIDACIÓN POR NOMBRE: Más seguro que usar el ID de la base de datos
                if (optVal === "caja pechos" && contenedorBotonCaja) {
                    contenedorBotonCaja.style.display = 'block';
                    inputPiezas.disabled = true; 
                    inputPiezas.placeholder = 'Caja para abrir';
                    inputPiezas.required = false;
                }
            } else {
                inputPiezas.disabled = true;
                inputPiezas.placeholder = 'N/A';
                
                inputKilos.required = true;
                inputKilos.placeholder = `Max: ${stockKilos} kg`;
            }
        }
    });

    if (!matchFound && inputSearch.value !== '') {
        inputSearch.value = '';
        alert('Por favor selecciona un producto válido de la lista.');
    }
}

// Reordena títulos e índices de productos para el POST
function actualizarNumeracionYNombres() {
    const items = document.querySelectorAll('.product-item');
    items.forEach((item, index) => {
        // Actualiza el texto visual (Producto #1, Producto #2, etc.)
        const titulo = item.querySelector('.product-number');
        if (titulo) titulo.textContent = `Producto #${index + 1}`;

        // Actualiza los índices de los inputs (productos[0], productos[1], etc.)
        const inputHidden = item.querySelector('.producto-id-hidden');
        const inputKilos = item.querySelector('.input-kilos');
        const inputPiezas = item.querySelector('.input-piezas');

        if (inputHidden) inputHidden.name = `productos[${index}][id_producto]`;
        if (inputKilos) inputKilos.name = `productos[${index}][kilos]`;
        if (inputPiezas) inputPiezas.name = `productos[${index}][piezas]`;
    });
}

// Agregar nuevo producto con validación
document.getElementById('btn-add-product').addEventListener('click', function() {
    const container = document.getElementById('products-container');
    const items = container.querySelectorAll('.product-item');
    
    // VALIDACIÓN: Verificar si el último producto agregado ya fue llenado
    const ultimoItem = items[items.length - 1];
    const ultimoId = ultimoItem.querySelector('.producto-id-hidden').value;
    
    if (ultimoId === '') {
       
        return; 
    }

    // Clonar siempre la primera fila como plantilla
    const firstItem = items[0];
    const newItem = firstItem.cloneNode(true);

    newItem.classList.remove('removing');
    newItem.querySelector('.producto-search').value = '';
    newItem.querySelector('.producto-id-hidden').value = '';
    
    // Resetear los inputs de kilos y piezas
    const nKilos = newItem.querySelector('.input-kilos');
    const nPiezas = newItem.querySelector('.input-piezas');
    
    if (nKilos) {
        nKilos.value = '';
        nKilos.disabled = false;
        nKilos.placeholder = '0.00';
    }
    
    if (nPiezas) {
        nPiezas.value = '';
        nPiezas.disabled = false;
        nPiezas.placeholder = '0';
    }

    container.appendChild(newItem);
    actualizarNumeracionYNombres();
});



        // Eliminar producto con animación y reordenar
        function removeProduct(btn) {
            const items = document.querySelectorAll('.product-item');
            if (items.length > 1) {
                const itemToRemove = btn.closest('.product-item');
                
                itemToRemove.classList.add('removing');

                setTimeout(() => {
                    itemToRemove.remove();
                    actualizarNumeracionYNombres();
                }, 300);

            } else {
                alert('Debe haber al menos un producto en la nota.');
            }
        }

        // LÓGICA DE CONTROL DEL CHAT INTERNO
        function toggleChatWindow() {
            const chatBox = document.getElementById('chatBox');
            chatBox.classList.toggle('open');
            
            // Ocultar notificación de badge al abrir por primera vez
            const badge = document.querySelector('.chat-badge');
            if (badge && chatBox.classList.contains('open')) {
                badge.style.display = 'none';
            }
        }

        function handleChatKeyPress(e) {
            if (e.key === 'Enter') {
                enviarMensajeChat();
            }
        }

        function enviarMensajeChat() {
            const input = document.getElementById('chatInput');
            const texto = input.value.trim();

            if (texto !== '') {
                const chatBody = document.getElementById('chatBody');
                
                // Mensaje enviado por el usuario
                const msgDiv = document.createElement('div');
                msgDiv.className = 'chat-message sent';
                msgDiv.textContent = texto;
                chatBody.appendChild(msgDiv);

                input.value = '';
                chatBody.scrollTop = chatBody.scrollHeight;

                /*
                  AQUÍ PUEDES INTEGRAR TU SERVICIO O CONTROLADOR EN PHP VÍA AJAX:
                  fetch('../Controllers/chatController.php', {
                      method: 'POST',
                      body: JSON.stringify({ mensaje: texto })
                  });
                */
            }
        }

        function procesarAperturaCaja(btn) {
    const kilosConfirmados = prompt("📦 APERTURA DE CAJA\n\nIngresa los kilos EXACTOS que arrojó la báscula al abrir esta caja de pechos:");
    
    if (kilosConfirmados === null) {
        return; // El usuario canceló
    }
    
    const peso = parseFloat(kilosConfirmados);
    
    if (isNaN(peso) || peso <= 0) {
        alert("❌ Error: Debes ingresar un peso válido mayor a 0.");
        return;
    }
    
    if (confirm(`¿Confirmas que la caja pesó exactamente ${peso} kg? \nEsto descontará 1 caja del inventario y sumará los kilos al producto a granel.`)) {
        // Redirigir al controlador con parámetros GET para procesar la apertura
        // Nota: Asegúrate de que la ruta al controlador sea la correcta
        window.location.href = `../Controllers/vendedorController.php?accion=abrir_caja&id_caja=234&peso=${peso}`;
    }
}
    </script>
</body>
</html>