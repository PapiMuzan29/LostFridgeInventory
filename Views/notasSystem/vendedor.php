<?php

$rolesPermitidos = [1, 2, 5];
require_once __DIR__ . '/../../Config/cadenero.php';

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
</head>
<body data-usuario-id="<?= htmlspecialchars((string)($_SESSION['idCuenta'] ?? 0)) ?>">
    
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

            <?php 
                // 1. PREPARAMOS EL MENSAJE DEL MODAL SI HAY ALERTAS EN LA SESIÓN
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

                // 2. RECUPERAMOS LOS DATOS DEL FORMULARIO SI EXISTEN
                $formData = $_SESSION['form_data'] ?? [];
                $estibadoresSeleccionados = $formData['estibadores'] ?? [];
                $productosForm = $formData['productos'] ?? [['id_producto' => '', 'nombre_producto' => '', 'kilos' => '', 'piezas' => '']];
                unset($_SESSION['form_data']); 
            ?>

            <!-- 3. SI HAY UN MENSAJE, DISPARAMOS EL MODAL AUTOMÁTICAMENTE AL CARGAR LA PÁGINA -->
            <?php if ($mensajeModal !== ''): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        mostrarAlerta('<?= $mensajeModal ?>', '<?= $tipoModal ?>');
                    });
                </script>
            <?php endif; ?>
            <form action="../Controllers/vendedorController.php" method="POST" id="formVendedor"> 
                <!-- Datos del Cliente -->
                <section class="card">
                    <h2 class="card-title"><i class="fa-solid fa-id-card"></i> 1. Información General</h2>
                    
                    <div class="form-group">
                        <label for="cliente">Nombre del Cliente *</label>
                        <input type="text" id="cliente" name="nombre_cliente" class="form-control" placeholder="Ej. Taquería El Paisa / Juan Pérez" value="<?= htmlspecialchars((string)($formData['nombre_cliente'] ?? '')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="estibadores">Estibador(es) * <small>(Selecciona uno o varios)</small></label>
                        <select id="estibadores" name="estibadores[]" class="form-control" multiple required style="height: 90px;">
                            <?php foreach ($estibadores as $estibador): 
                                $isSelected = in_array((string)$estibador['id_usuario'], $estibadoresSeleccionados) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars((string)$estibador['id_usuario']) ?>" <?= $isSelected ?>>
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
                                data-precio="<?= htmlspecialchars((string)($producto['precio'] ?? '0')) ?>"
                                data-por-piezas="<?= htmlspecialchars((string)($producto['porPiezas'] ?? '0')) ?>"
                                data-stock-piezas="<?= htmlspecialchars((string)($producto['cantidadPiezas'] ?? '0')) ?>"
                                data-stock-cajas="<?= htmlspecialchars((string)($producto['cantidadCajas'] ?? '0')) ?>"
                                data-stock-peso="<?= htmlspecialchars((string)($producto['cantidadPeso'] ?? '0')) ?>"
                            ></option>
                        <?php endforeach; ?>
                    </datalist>

                    <div id="products-container" style="margin-top: 15px;">
                        <?php 
                        $contador = 0;
                        foreach ($productosForm as $index => $prod): 
                            $contador++;
                        ?>
                        <div class="product-item card-inner">
                            <div class="product-item-header">
                                <span class="product-number">Producto #<?= $contador ?></span>
                                <button type="button" class="btn-delete" onclick="removeProduct(this)" title="Eliminar producto">&times;</button>
                            </div>

                            <div class="form-group">
                                <label>Buscar Producto *</label>
                                <!-- NUEVO: Se agregó el name="productos[x][nombre_producto]" para recuperar el texto -->
                                <input type="text" list="lista-productos" name="productos[<?= $index ?>][nombre_producto]" class="form-control producto-search" placeholder="Escribe para buscar..." onchange="capturarIdProducto(this)" autocomplete="off" value="<?= htmlspecialchars((string)($prod['nombre_producto'] ?? '')) ?>" required>
                                <input type="hidden" name="productos[<?= $index ?>][id_producto]" class="producto-id-hidden" value="<?= htmlspecialchars((string)($prod['id_producto'] ?? '')) ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group col">
                                    <label>Kilos *</label>
                                    <input type="number" step="0.01" name="productos[<?= $index ?>][kilos]" class="form-control input-kilos" placeholder="0.00" value="<?= htmlspecialchars((string)($prod['kilos'] ?? '')) ?>" required>
                                </div>

                                <div class="form-group col">
                                    <label>Piezas <small>(Opcional)</small></label>
                                    <input type="number" name="productos[<?= $index ?>][piezas]" class="form-control input-piezas" placeholder="0" value="<?= htmlspecialchars((string)($prod['piezas'] ?? '')) ?>">
                                </div>
                            </div>

                            <div class="form-group contenedor-abrir-caja" style="display: none; margin-top: 10px;">
                                <button type="button" class="btn-abrir-caja" style="background-color: #d97706; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%;" onclick="procesarAperturaCaja(this)">
                                    <i class="fa-solid fa-box-open"></i> Abrir 1 Caja a Granel
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- TICKET / CALCULADORA EN TIEMPO REAL -->
                    <div class="ticket-container" style="background-color: #f8fafc; border: 1px dashed #cbd5e0; border-radius: 8px; padding: 15px; margin-top: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                        <h3 style="margin-top: 0; font-size: 1rem; color: #2d3748; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-receipt"></i> Resumen de Venta (Aprox)
                        </h3>
                        
                        <div id="ticket-items" style="font-size: 0.9rem; color: #4a5568; margin-top: 10px; min-height: 40px; display: flex; flex-direction: column; gap: 6px;">
                            <span style="color: #a0aec0; font-style: italic;">Agrega productos para ver el estimado...</span>
                        </div>
                        
                        <div style="border-top: 2px dashed #cbd5e0; margin-top: 12px; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="font-size: 1.1rem; color: #2d3748;">Total Estimado:</strong>
                            <strong id="ticket-total" style="font-size: 1.25rem; color: #38a169;">$0.00</strong>
                        </div>
                        
                        <p style="font-size: 0.75rem; color: #6b6b6b; margin-top: 12px; margin-bottom: 0; text-align: center; font-style: italic; font-weight: 600;">
                            El total es una estimación. Los precios pueden variar en caja y no estar actualizados en tiempo real.
                        </p>
                    </div>

                    <!-- Botón Enviar Formulario -->
                    <!-- Botones de Acción -->
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" name="accion_boton" value="guardar_espera" class="btn-secondary-sm" style="flex: 1; background-color: #4a5568; color: #fff; padding: 12px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer;">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar en Espera
                        </button>
                        <button type="submit" name="accion_boton" value="enviar_caja" class="btn-primary" style="flex: 1;">
                            <i class="fa-solid fa-paper-plane"></i> Enviar a Caja
                        </button>
                    </div>
                </section>

            </form>
        </div>

        <!-- VISTA 3: NOTAS GUARDADAS / EN ESPERA -->
        <div id="tab-notas" class="tab-content">
            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Notas en Espera y Rechazadas</h2>

                <?php if (empty($notasVendedor)): ?>
                    <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                        <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>No tienes notas pendientes ni en espera.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($notasVendedor as $nv): ?>
                            <div style="background: var(--card-bg, #fff); border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span style="font-weight: bold; font-size: 0.95rem; color: var(--primary-color, #1a365d);"><?= htmlspecialchars($nv['folio']) ?> - <?= htmlspecialchars($nv['nombre_cliente']) ?></span>
                                    <span style="font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; font-weight: bold; background: <?= $nv['estado'] === 'RECHAZADA' ? '#ffebee; color: #c62828;' : '#fff3e0; color: #ef6c00;' ?>"><?= $nv['estado'] ?></span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;"><strong>Productos:</strong> <?= htmlspecialchars($nv['resumen_productos'] ?? 'Sin productos') ?></p>
                                
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="#" onclick="confirmarEditarNota(event, '../Controllers/vendedorController.php?accion=editar_nota&id_nota=<?= $nv['id_nota'] ?>')" class="btn-secondary-sm" style="background-color: #d97706; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem;">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar / Retomar
                                    </a>
                                    <a href="#" onclick="event.preventDefault(); mostrarAlerta('¿Estás seguro de cancelar esta nota? El inventario será devuelto.', 'confirmacion', function(acepta) { if(acepta) window.location.href='../Controllers/vendedorController.php?accion=cancelar_nota&id_nota=<?= $nv['id_nota'] ?>'; });" style="background-color: #e53e3e; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
                                        <i class="fa-solid fa-trash"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
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



    <!-- MODAL PARA CAPTURAR PESO DE LA CAJA -->
    <div id="modal-abrir-caja" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-scale-balanced"></i> Apertura de Caja</h3>
                <button type="button" class="btn-close-modal" onclick="cerrarModalCaja()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 12px; font-size: 0.95rem;">Ingresa los kilos <strong>exactos</strong> que arrojó la báscula al abrir esta caja de pechos:</p>
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="number" id="peso-caja-input" class="form-control" step="0.01" placeholder="Ej. 25.40">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-sm" onclick="cerrarModalCaja()">Cancelar</button>
                <button type="button" class="btn-primary" style="background-color: #d97706;" onclick="confirmarAperturaCaja(event)">Convertir a Kilos</button>
            </div>
        </div>
    </div>

    <!-- BARRA NAVEGACIÓN INFERIOR TIPO APP -->
    <nav class="bottom-nav">
        <button type="button" class="nav-item active" onclick="switchTab('inicio', this)">
            <i class="fa-solid fa-house"></i>
            <span>Inicio</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('notas', this)">
            <i class="fa-solid id-badge fa-receipt"></i>
            <span>Notas</span>
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
            const currentTheme = localStorage.getItem('theme');
            const toggleInput = document.getElementById('toggle-dark-mode');
            
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (toggleInput) toggleInput.checked = true;
            }


            document.querySelectorAll('.producto-search').forEach(input => {
                if (input.value.trim() !== '') {
                    capturarIdProducto(input, true);
                }
            });


            // Escucha global: si se escribe en cualquier input de kilos, piezas o buscador, se recalcula el ticket
            document.getElementById('products-container').addEventListener('input', function(e) {
                if (e.target.classList.contains('input-kilos') || 
                    e.target.classList.contains('input-piezas') || 
                    e.target.classList.contains('producto-search')) {
                    calcularTicket();
                }
            });
            
            calcularTicket();
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
            } else if (tabName === 'notas') {
                document.getElementById('tab-notas').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Notas en Espera y Rechazadas';
            } else if (tabName === 'config') {
                document.getElementById('tab-config').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Configuración del Sistema';
            }

            btnElement.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function procesarAperturaCaja(btn) {
            const idCaja = btn.getAttribute('data-id');
            const modal = document.getElementById('modal-abrir-caja');
            
            modal.setAttribute('data-id-caja', idCaja);
            document.getElementById('peso-caja-input').value = '';
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('peso-caja-input').focus(), 100);
        }

        function cerrarModalCaja() {
            document.getElementById('modal-abrir-caja').style.display = 'none';
        }

        // Se le agrega el parámetro fromLoad para evitar que borre los números si viene de una recarga de página
        function capturarIdProducto(inputSearch, fromLoad = false) {
            const val = inputSearch.value.trim().toLowerCase();
            const options = document.querySelectorAll('#lista-productos option');
            const row = inputSearch.closest('.product-item');
            const hiddenInput = row.querySelector('.producto-id-hidden');
            const inputKilos = row.querySelector('.input-kilos');
            const inputPiezas = row.querySelector('.input-piezas');
            const contenedorBotonCaja = row.querySelector('.contenedor-abrir-caja');
            
            // Si NO venimos de una recarga, borramos los campos para que escriba
            if (!fromLoad) {
                hiddenInput.value = '';
                inputKilos.value = '';
                inputPiezas.value = '';
            }
            
            inputKilos.disabled = false;
            inputKilos.readOnly = false;
            inputPiezas.disabled = false;
            inputPiezas.readOnly = false;
            inputPiezas.oninput = null; 
            
            if (contenedorBotonCaja) contenedorBotonCaja.style.display = 'none';

            let matchFound = false;

            options.forEach(opt => {
                const optVal = opt.value.trim().toLowerCase();
                
                if (optVal === val) {
                    matchFound = true;
                    const idProducto = opt.getAttribute('data-id');
                    hiddenInput.value = idProducto;
                    
                    // Formateamos a 2 decimales para que "1.0000" se vea como "1.00"
                    const pesoCrudo = parseFloat(opt.getAttribute('data-stock-peso') || '0');
                    const stockKilos = pesoCrudo.toFixed(2); 

                    const stockCajas = parseInt(opt.getAttribute('data-stock-cajas')) || 0;
                    const stockPiezas = parseInt(opt.getAttribute('data-stock-piezas')) || 0;
                    const stockUnidades = stockCajas > 0 ? stockCajas : stockPiezas;
                    
                    const esMazo = optVal.includes('mazo');
                    const esManteca = optVal.includes('manteca');

                    if (optVal === "caja pechos") {
                        inputKilos.readOnly = false;
                        inputKilos.disabled = false;
                        inputKilos.placeholder = 'Kilos totales';
                        inputKilos.required = true; 
                        
                        inputPiezas.readOnly = false;
                        inputPiezas.disabled = false; 
                        inputPiezas.placeholder = `Max: ${stockUnidades} cajas`;
                        inputPiezas.required = true;

                        if (contenedorBotonCaja) {
                            contenedorBotonCaja.style.display = 'block';
                            const btnAbrir = contenedorBotonCaja.querySelector('.btn-abrir-caja');
                            btnAbrir.setAttribute('data-id', idProducto);
                            btnAbrir.innerHTML = '<i class="fa-solid fa-box-open"></i> Abrir 1 Caja a Granel';
                        }
                        
                    } else if (esManteca) {
                        const matchNumeros = optVal.match(/\d+/);
                        const kilosPorPieza = matchNumeros ? parseFloat(matchNumeros[0]) : 0;

                        inputKilos.readOnly = true; 
                        inputKilos.placeholder = 'Auto-calculado';
                        inputKilos.required = true;
                        
                        inputPiezas.disabled = false;
                        inputPiezas.required = true;
                        inputPiezas.placeholder = `Max: ${stockUnidades}`;

                        inputPiezas.oninput = function() {
                            const cantPiezas = parseInt(this.value) || 0;
                            inputKilos.value = (cantPiezas * kilosPorPieza).toFixed(2);
                            calcularTicket();
                        };

                    } else if (esMazo) {
                        inputKilos.readOnly = true;
                        inputKilos.value = '0.00';
                        inputKilos.required = false;
                        
                        inputPiezas.disabled = false;
                        inputPiezas.required = true;
                        inputPiezas.placeholder = `Max: ${stockUnidades}`;
                        
                    } else {
                        inputKilos.disabled = false;
                        inputKilos.required = true;
                        inputKilos.placeholder = `Max: ${stockKilos} kg`;
                        
                        inputPiezas.disabled = false;
                        inputPiezas.placeholder = 'Piezas físicas';
                    }
                }
            });

            if (!matchFound && inputSearch.value !== '' && !fromLoad) {
                inputSearch.value = '';
                mostrarAlerta('Por favor selecciona un producto válido de la lista.', 'error');
            }
        }

        function confirmarAperturaCaja(e) {
            const inputPeso = document.getElementById('peso-caja-input').value;
            const peso = parseFloat(inputPeso);
            const idCaja = document.getElementById('modal-abrir-caja').getAttribute('data-id-caja');
            
            if (isNaN(peso) || peso <= 0) {
                mostrarAlerta('Debes ingresar un peso válido mayor a 0.', 'error');
                return;
            }
            
            const btnTarget = e ? e.target : event.target;
            btnTarget.disabled = true;
            btnTarget.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
            
            const form = document.getElementById('formVendedor');
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="accion_especial" value="abrir_caja">`);
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="id_caja" value="${idCaja}">`);
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="peso" value="${peso}">`);
            
            form.submit();
        }
                
        function actualizarNumeracionYNombres() {
            const items = document.querySelectorAll('.product-item');
            items.forEach((item, index) => {
                const titulo = item.querySelector('.product-number');
                if (titulo) titulo.textContent = `Producto #${index + 1}`;

                const inputSearch = item.querySelector('.producto-search');
                const inputHidden = item.querySelector('.producto-id-hidden');
                const inputKilos = item.querySelector('.input-kilos');
                const inputPiezas = item.querySelector('.input-piezas');

                if (inputSearch) inputSearch.name = `productos[${index}][nombre_producto]`;
                if (inputHidden) inputHidden.name = `productos[${index}][id_producto]`;
                if (inputKilos) inputKilos.name = `productos[${index}][kilos]`;
                if (inputPiezas) inputPiezas.name = `productos[${index}][piezas]`;
            });
        }

        document.getElementById('btn-add-product').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const items = container.querySelectorAll('.product-item');
            
            const ultimoItem = items[items.length - 1];
            const ultimoId = ultimoItem.querySelector('.producto-id-hidden').value;
            
            if (ultimoId === '') {
                mostrarAlerta('Por favor, selecciona un producto en la fila actual antes de agregar uno nuevo.', 'error');
                return; 
            }
            const firstItem = items[0];
            const newItem = firstItem.cloneNode(true);

            newItem.classList.remove('removing');
            newItem.querySelector('.producto-search').value = '';
            newItem.querySelector('.producto-id-hidden').value = '';
            
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
            
            const nBotonCaja = newItem.querySelector('.contenedor-abrir-caja');
            if (nBotonCaja) {
                nBotonCaja.style.display = 'none';
            }

            container.appendChild(newItem);
            actualizarNumeracionYNombres();
        });

        function removeProduct(btn) {
            const items = document.querySelectorAll('.product-item');
            if (items.length > 1) {
                const itemToRemove = btn.closest('.product-item');
                
                itemToRemove.classList.add('removing');

                setTimeout(() => {
                    itemToRemove.remove();
                    actualizarNumeracionYNombres();
                    calcularTicket();
                }, 300);

            } else {
                mostrarAlerta('Debe haber al menos un producto en la nota.', 'error');
            }
        }

        // FUNCIÓN DE SEGURIDAD PARA EDITAR NOTAS SIN PERDER EL PROGRESO ACTUAL
       function confirmarEditarNota(event, urlEditar) {
            event.preventDefault(); // Evitamos que abra el enlace de inmediato
            
            const inputCliente = document.getElementById('cliente').value.trim();
            const primerProductoInput = document.querySelector('.product-item .producto-search');
            const tieneProducto = primerProductoInput && primerProductoInput.value.trim() !== '';

            // Verificamos si hay información activa en el formulario actual
            if (inputCliente !== '' || tieneProducto) {
                // USAMOS EL MODAL PERSONALIZADO
                mostrarAlerta(
                    "Tienes una nota activa en el formulario actual que no has guardado.\n\n¿Deseas guardarla automáticamente en espera antes de abrir la otra nota?",
                    'confirmacion',
                    function(acepta) {
                        if (acepta) {
                            const form = document.getElementById('formVendedor');
                            
                            const inputAccion = document.createElement('input');
                            inputAccion.type = 'hidden';
                            inputAccion.name = 'accion_boton';
                            inputAccion.value = 'guardar_espera';
                            form.appendChild(inputAccion);

                            const inputRedireccion = document.createElement('input');
                            inputRedireccion.type = 'hidden';
                            inputRedireccion.name = 'redirigir_a';
                            inputRedireccion.value = urlEditar;
                            form.appendChild(inputRedireccion);

                            form.submit();
                        } else {
                            window.location.href = urlEditar;
                        }
                    }
                );
            } else {
                window.location.href = urlEditar;
            }
        }


        // FUNCIONES PARA MOSTRAR ALERTAS BONITAS EN MODAL
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
                
                // Si es confirmación, cambiamos los botones para tener Sí / No
                footerEl.innerHTML = `
                    <button type="button" class="btn-secondary-sm" style="flex:1;" onclick="cerrarAlertaGlobal(false)">Cancelar</button>
                    <button type="button" class="btn-primary" style="flex:1; background-color: #d97706;" onclick="cerrarAlertaGlobal(true)">Sí, continuar</button>
                `;
                modal.style.display = 'flex';
                return;
            }

            // Botón estándar de aceptar
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

        function calcularTicket() {
            const items = document.querySelectorAll('.product-item');
            const ticketContainer = document.getElementById('ticket-items');
            const totalContainer = document.getElementById('ticket-total');
            const options = document.querySelectorAll('#lista-productos option');
            
            let htmlTicket = '';
            let granTotal = 0;

            items.forEach(row => {
                const inputSearch = row.querySelector('.producto-search').value.trim().toLowerCase();
                const inputKilos = parseFloat(row.querySelector('.input-kilos').value) || 0;
                const inputPiezas = parseInt(row.querySelector('.input-piezas').value) || 0;
                
                if (inputSearch !== '') {
                    // Extraer el precio del Datalist
                    let precio = 0;
                    let nombreReal = inputSearch;
                    
                    for(let opt of options) {
                        if (opt.value.trim().toLowerCase() === inputSearch) {
                            precio = parseFloat(opt.getAttribute('data-precio')) || 0;
                            nombreReal = opt.value;
                            break;
                        }
                    }

                    const esMazo = inputSearch.includes('mazo');
                    
                    // Condición: Mostrar el producto si ya escribieron Kilos o Piezas
                    const tieneCantidad = esMazo ? (inputPiezas > 0) : (inputKilos > 0 || inputPiezas > 0);

                    if (tieneCantidad) {
                        if (precio <= 0) {
                            // ESCENARIO A: PRODUCTO SIN PRECIO EN BD
                            const cantTexto = esMazo ? `${inputPiezas} pzas` : `${inputKilos.toFixed(2)} kg`;
                            htmlTicket += `
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #edf2f7; padding-bottom: 4px;">
                                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;">${nombreReal}</span>
                                    <span style="color: #e53e3e; font-size: 0.8rem; text-align: right; font-weight: bold;">
                                        ${cantTexto} <i class="fa-solid fa-circle-exclamation"></i> Sin precio
                                    </span>
                                </div>
                            `;
                        } else {
                            // ESCENARIO B: PRODUCTO CON PRECIO NORMAL
                            let subtotal = 0;
                            let textoCalculo = '';

                            if (esMazo) {
                                subtotal = inputPiezas * precio;
                                textoCalculo = `${inputPiezas} pzas x $${precio.toFixed(2)}`;
                            } else {
                                subtotal = inputKilos * precio;
                                textoCalculo = `${inputKilos.toFixed(2)} kg x $${precio.toFixed(2)}`;
                            }

                            granTotal += subtotal;

                            htmlTicket += `
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #edf2f7; padding-bottom: 4px;">
                                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;">${nombreReal}</span>
                                    <span style="color: #718096; font-size: 0.8rem; width: 120px; text-align: right; padding-right: 10px;">${textoCalculo}</span>
                                    <strong style="width: 80px; text-align: right; color: #2d3748;">$${subtotal.toFixed(2)}</strong>
                                </div>
                            `;
                        }
                    }
                }
            });

            if (htmlTicket === '') {
                htmlTicket = '<span style="color: #a0aec0; font-style: italic;">Agrega pesos o piezas para calcular...</span>';
            }

            ticketContainer.innerHTML = htmlTicket;
            totalContainer.textContent = `$${granTotal.toFixed(2)}`;
        }

    </script>


<!-- MODAL DE ALERTAS GENERALES -->
    <div id="modal-alerta-global" class="modal-alert-overlay">
        <div class="modal-alert-content">
            <div class="modal-alert-header">
                <div id="modal-alert-icon-container" class="modal-alert-icon">⚠️</div>
                <h3 id="modal-alert-title" class="modal-alert-title">Aviso del Sistema</h3>
            </div>
            <div id="modal-alerta-mensaje" class="modal-alert-body">
                Mensaje de la alerta...
            </div>
            <div class="modal-alert-footer" id="modal-alert-footer-buttons">
                <button type="button" class="btn-primary" style="width: 100%; padding: 10px;" onclick="cerrarAlertaGlobal()">Aceptar</button>
            </div>
        </div>
    </div>
</body>
</html>