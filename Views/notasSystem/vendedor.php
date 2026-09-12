<?php

$rolesPermitidos = [1, 2, 5];
require_once __DIR__ . '/../../Config/cadenero.php';

// Validar que la vista reciba los datos desde VendedorController.php
if (!isset($productos) || !isset($estibadores)) {
    header("Location: /LostFridgeInventory/Controllers/vendedorController.php");
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
    <!-- CHAT FLOTANTE -->
    
</head>

<?php include __DIR__ . '/chat.php'; ?>

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
                $observacionForm = $formData['observacion_especial'] ?? '';
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
            <form action="/LostFridgeInventory/Controllers/vendedorController.php" method="POST" id="formVendedor"> 
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
                        <div>
                            <button type="button" class="btn-secondary-sm" id="btn-add-obs" style="background-color: #3182ce; color: white; border: none; margin-right: 8px;">
                                Observación
                            </button>
                            <button type="button" class="btn-secondary-sm" id="btn-add-product">
                                <i class="fa-solid fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Datalist compartido con el catálogo completo de productos/combos con código estructurado -->
                    <datalist id="lista-productos">
                        <?php foreach ($productos as $producto): ?>
                            <?php 
                                $identificadorVisual = $producto['codigoLote'] ?? $producto['codigoProducto'] ?? $producto['nombreProducto'];
                            ?>
                            <option 
                                value="<?= htmlspecialchars((string)$identificadorVisual) ?>" 
                                label="<?= htmlspecialchars((string)($producto['nombreProducto'] ?? '')) ?>" 
                                data-id="<?= htmlspecialchars((string)($producto['id_producto'] ?? $producto['idProducto'] ?? '')) ?>"
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

                   <!-- observación -->
                    <div id="contenedor-observacion" class="card-inner" style="display: <?= $observacionForm !== '' ? 'block' : 'none' ?>; border-left: 4px solid #3182ce; margin-top: 15px;">
                        <div class="product-item-header">
                            <span class="product-number" style="color: #3182ce;"><i class="fa-solid fa-lock"></i> Observación Especial (Requiere Autorización)</span>
                            <button type="button" class="btn-delete" onclick="document.getElementById('contenedor-observacion').style.display='none'; document.getElementById('observacion_especial').value='';" title="Quitar">&times;</button>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <textarea id="observacion_especial" name="observacion_especial" class="form-control" rows="2" placeholder="Ej. Descuento por defecto en caja..."><?= htmlspecialchars((string)$observacionForm) ?></textarea>
                        </div>
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
                                    <a href="#" onclick="confirmarEditarNota(event, '/LostFridgeInventory/Controllers/vendedorController.php?accion=editar_nota&id_nota=<?= $nv['id_nota'] ?>')" class="btn-secondary-sm" style="background-color: #d97706; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem;">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar / Retomar
                                    </a>
                                    <a href="#" onclick="event.preventDefault(); mostrarAlerta('¿Estás seguro de cancelar esta nota? El inventario será devuelto.', 'confirmacion', function(acepta) { if(acepta) window.location.href='/LostFridgeInventory/Controllers/vendedorController.php?accion=cancelar_nota&id_nota=<?= $nv['id_nota'] ?>'; });" style="background-color: #e53e3e; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
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
                    <a href="/LostFridgeInventory/Config/Logouth.php" class="btn-danger-block">
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
            <i class="fa-solid fa-receipt"></i>
            <span>Notas</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('config', this)">
            <i class="fa-solid fa-gear"></i>
            <span>Ajustes</span>
        </button>
    </nav>

    <script src="/LostFridgeInventory/Views/notasSystem/vendedor.js"></script>


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