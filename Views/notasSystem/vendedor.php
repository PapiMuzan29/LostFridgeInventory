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
</head>
<body>

    <header class="app-header">
        <h1>Grupo Cárnico América</h1>
        <p id="header-subtitle">Módulo de Captura de Pedidos</p>
    </header>

    <main class="app-content">

        <!-- VISTA 1: CAPTURA DE INICIO / NOTAS -->
        <div id="tab-inicio" class="tab-content active">

            <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                <div class="alert alert-success" style="background-color: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 16px; text-align: center; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> ¡Nota enviada a caja con éxito!
                </div>
            <?php endif; ?>

            <form action="../../Controllers/guardar_nota.php" method="POST" id="form-nota">
                
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

                    <div id="products-container" style="margin-top: 15px;">
                        
                        <!-- Fila de Producto -->
                        <div class="product-item card-inner">
                            <div class="product-item-header">
                                <span class="product-number">Producto #1</span>
                                <button type="button" class="btn-delete" onclick="removeProduct(this)" title="Eliminar producto">&times;</button>
                            </div>

                            <div class="form-group">
                                <label>Producto *</label>
                                <select name="productos[0][id_producto]" class="form-control producto-select" required>
                                    <option value="">Selecciona un producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= htmlspecialchars((string)$producto['id_producto']) ?>">
                                            <?= htmlspecialchars((string)$producto['nombre_producto']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                <div class="form-group">
                    <label>Modo de Conexión</label>
                    <select class="form-control" disabled>
                        <option>En Línea (BD LFI Principal)</option>
                    </select>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Sistema</h2>
                <p style="font-size: 0.9rem; color: #64748b;"><strong>LFI Ventas Móvil:</strong> v1.0</p>
                <p style="font-size: 0.9rem; color: #64748b; margin-top: 5px;">Desarrollado para Grupo Cárnico América</p>
                
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

        // Reordena títulos e índices de productos
        function actualizarNumeracionYNombres() {
            const items = document.querySelectorAll('.product-item');
            items.forEach((item, index) => {
                item.querySelector('.product-number').textContent = `Producto #${index + 1}`;

                const selectProducto = item.querySelector('.producto-select');
                const inputKilos = item.querySelector('.input-kilos');
                const inputPiezas = item.querySelector('.input-piezas');

                selectProducto.name = `productos[${index}][id_producto]`;
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

            newItem.querySelector('.producto-select').selectedIndex = 0;
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