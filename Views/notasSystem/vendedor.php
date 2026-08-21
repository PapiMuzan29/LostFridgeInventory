<?php
// Validar que la vista reciba los datos desde VendedorController.php
if (!isset($productos) || !isset($estibadores)) {
    header("Location: ../../Controllers/VendedorController.php");
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
                            <option value="<?= htmlspecialchars((string)$producto['nombreProducto']) ?>" data-id="<?= htmlspecialchars((string)$producto['id_producto']) ?>"></option>
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
                                <input type="text" list="lista-productos" class="form-control producto-search" placeholder="Escribe para buscar..." onchange="capturarIdProducto(this)" required>
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
                    <a href="../../Controllers/LoginController.php?action=logout" class="btn-danger-block">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                    </a>
                </div>
            </section>
        </div>

        <div class="spacer"></div>

    </main>

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

        // Mapea el nombre escrito con el idProducto oculto
        function capturarIdProducto(inputSearch) {
            const val = inputSearch.value;
            const options = document.querySelectorAll('#lista-productos option');
            const hiddenInput = inputSearch.closest('.form-group').querySelector('.producto-id-hidden');
            
            hiddenInput.value = '';

            options.forEach(opt => {
                if (opt.value === val) {
                    hiddenInput.value = opt.getAttribute('data-id');
                }
            });

            if (!hiddenInput.value && val !== '') {
                inputSearch.value = '';
                alert('Por favor selecciona un producto válido de la lista.');
            }
        }

        // Reordena títulos e índices de productos para el POST
        function actualizarNumeracionYNombres() {
            const items = document.querySelectorAll('.product-item');
            items.forEach((item, index) => {
                item.querySelector('.product-number').textContent = `Producto #${index + 1}`;

                const inputHidden = item.querySelector('.producto-id-hidden');
                const inputKilos = item.querySelector('.input-kilos');
                const inputPiezas = item.querySelector('.input-piezas');

                inputHidden.name = `productos[${index}][id_producto]`;
                inputKilos.name = `productos[${index}][kilos]`;
                inputPiezas.name = `productos[${index}][piezas]`;
            });
        }

        // Agregar nuevo producto
        document.getElementById('btn-add-product').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const firstItem = container.querySelector('.product-item');
            const newItem = firstItem.cloneNode(true);

            newItem.classList.remove('removing');

            newItem.querySelector('.producto-search').value = '';
            newItem.querySelector('.producto-id-hidden').value = '';
            newItem.querySelector('.input-kilos').value = '';
            newItem.querySelector('.input-piezas').value = '';

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
    </script>
</body>
</html>