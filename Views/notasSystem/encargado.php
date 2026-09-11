<?php
session_start();

$rolesPermitidos = [1, 5];
require_once __DIR__ . '/../../Config/cadenero.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Módulo Encargado - Grupo Cárnico América</title>
    <link rel="stylesheet" href="CSS/encargado.css">
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>
</head>
<body>
    
  

    <header class="app-header">
        <h1>Grupo Cárnico América</h1>
        <p id="header-subtitle">Revisión de Notas</p>

        <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 14px; border-radius: 20px; display: flex; align-items: center; gap: 8px; color: #ffffff; font-weight: 600; font-size: 0.9rem; white-space: nowrap;">
            <i class="fa-solid fa-circle-user" style="font-size: 1.1rem;"></i>
            <span><?= htmlspecialchars($_SESSION['apodoUsuario'] ?? 'Encargado') ?></span>
        </div>
    </header>

    <main class="app-content">

        <!-- VISTA 1: INICIO (REVISIÓN DE NOTAS) -->
        <div id="tab-inicio" class="tab-content active">
            <section class="card sticky-search">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="buscarNota"><i class="fa-solid fa-magnifying-glass"></i> Buscar Nota</label>
                    <input type="text" id="buscarNota" class="form-control" placeholder="Buscar por folio o cliente..." onkeyup="filtrarNotas()">
                </div>
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-clipboard-list"></i> Notas por Aprobar</h2>
                
                <div id="lista-notas-container">
                    <div style="text-align:center; padding:20px;">
                        <i class="fa-solid fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                </div>

                <div id="no-notes-results" class="empty-state" style="display: none;">
                    <i class="fa-solid fa-magnifying-glass-minus"></i>
                    <p>No se encontraron notas con esa búsqueda.</p>
                </div>
            </section>
        </div>

        <!-- VISTA 2: GESTIÓN Y RESUMEN DE PRODUCTOS -->
        <div id="tab-gestion" class="tab-content">
            
            <!-- CONTROLES NAVEGACIÓN DE FECHA -->
            <div class="date-picker-bar">
                <button type="button" class="btn-date-nav" onclick="cambiarFecha(-1)">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="date-picker-display" onclick="document.getElementById('input-fecha-gestion').showPicker()">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span id="label-fecha-seleccionada">Cargando fecha...</span>
                    <input type="date" id="input-fecha-gestion" style="position:absolute; opacity:0; pointer-events:none;" onchange="alSeleccionarFecha(this.value)">
                </div>
                <button type="button" class="btn-date-nav" onclick="cambiarFecha(1)">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <!-- TÍTULO DE LA SECCIÓN -->
            <div class="resumen-section-header">
                <h3>RESUMEN DEL DÍA</h3>
            </div>

            <!-- CONTENEDOR DE TARJETAS DE RESUMEN -->
            <div id="contenedor-resumen-dia">
                <!-- Pierna -->
                <div class="resumen-card clickable-card" onclick="abrirModuloPiernas()">
                    <div class="resumen-card-icon">
                        <img src="../../SRC/productos/pierna.jpeg" alt="Pierna" class="img-producto">
                    </div>
                    <div class="resumen-card-details">
                        <h4>Pierna</h4>
                        <p class="resumen-metrics">
                            <span class="highlight-qty" id="resumen-pierna-pzs">...</span> <small>piezas</small>
                            <span class="metric-dot">•</span>
                            <span class="weight-qty" id="resumen-pierna-kg">... kg</span>
                        </p>
                    </div>
                </div>

                <!-- Pecho -->
                <div class="resumen-card clickable-card" onclick="abrirModuloPecho()">
                    <div class="resumen-card-icon">
                        <img src="../../SRC/productos/pecho.jpeg" alt="Pecho" class="img-producto">
                    </div>
                    <div class="resumen-card-details">
                        <h4>Pecho</h4>
                        <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 5px;">
                            <p class="resumen-metrics" style="margin: 0;">
                                <span class="highlight-qty" id="resumen-pecho-suelto-pzs">...</span> <small>pzs (suelto)</small>
                                <span class="metric-dot">•</span>
                                <span class="weight-qty" id="resumen-pecho-suelto-kg">... kg</span>
                            </p>
                            <p class="resumen-metrics" style="margin: 0;">
                                <span class="highlight-qty" id="resumen-pecho-caja-pzs">...</span> <small>cajas</small>
                                <span class="metric-dot">•</span>
                                <span class="weight-qty" id="resumen-pecho-caja-kg">... kg</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mazo -->
                <div class="resumen-card clickable-card" onclick="abrirModuloMazos()">
                    <div class="resumen-card-icon">
                        <img src="../../SRC/productos/mazo.jpeg" alt="Mazo" class="img-producto">
                    </div>
                    <div class="resumen-card-details">
                        <h4>Mazo</h4>
                        <p class="resumen-metrics">
                            <span class="highlight-qty" id="resumen-mazo-pzs">...</span> <small>piezas</small>
                        </p>
                    </div>
                </div>

                <!-- Manteca -->
                <div class="resumen-card clickable-card" onclick="abrirModuloManteca()">
                    <div class="resumen-card-icon">
                        <img src="../../SRC/productos/manteca.jpeg" alt="Manteca" class="img-producto">
                    </div>
                    <div class="resumen-card-details">
                        <h4>Manteca</h4>
                        <p class="resumen-metrics">
                            <span class="highlight-qty" id="resumen-manteca-pzs">...</span> <small>unidades</small>
                        </p>
                    </div>
                </div>

                <!-- Chuleta ahumada -->
                <div class="resumen-card clickable-card" onclick="abrirModuloChuletas()">
                    <div class="resumen-card-icon">
                        <img src="../../SRC/productos/chuleta.jpeg" alt="Chuleta" class="img-producto">
                    </div>
                    <div class="resumen-card-details">
                        <h4>Chuleta ahumada</h4>
                        <p class="resumen-metrics">
                            <span class="highlight-qty" id="resumen-chuleta-pzs">...</span> <small>piezas</small>
                            <span class="metric-dot">•</span>
                            <span class="weight-qty" id="resumen-chuleta-kg">... kg</span>
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <!-- VISTA 3: CONFIGURACIÓN -->
        <div id="tab-config" class="tab-content">
             <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-user-gear"></i> Perfil de Usuario</h2>
                <div class="user-info-box">
                    <p><strong>Usuario Activo:</strong> <?= htmlspecialchars($_SESSION['apodoUsuario'] ?? 'Encargado') ?></p>
                    <p><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['nombreRol'] ?? 'Encargado') ?></p>
                </div>
            </section>

            <section class="card sticky-search">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="buscarProducto"><i class="fa-solid fa-magnifying-glass"></i> Buscar Producto</label>
                    <input type="text" id="buscarProducto" class="form-control" placeholder="Nombre de producto..." onkeyup="filtrarProductos()">
                </div>
            </section>
            
            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-boxes-stacked"></i> Configuración de Venta</h2>
                
                <div id="lista-productos-container">
                    <div id="loading-productos" style="text-align:center; padding:20px;">
                        <i class="fa-solid fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                </div>

                <div id="paginacion-productos" class="pagination-container"></div>
                
                <div id="no-products-results" class="empty-state" style="display: none;">
                    <i class="fa-solid fa-box-open"></i>
                    <p>No se encontraron productos.</p>
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

    <!-- NAVEGACIÓN INFERIOR -->
    <nav class="bottom-nav">
        <button type="button" class="nav-item active" onclick="switchTab('inicio', this, 'Revisión de Notas')">
            <i class="fa-solid fa-clipboard-check"></i><span>Inicio</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('gestion', this, 'Gestión de Productos')">
            <i class="fa-solid fa-boxes-stacked"></i><span>Gestión</span>
        </button>
        <button type="button" class="nav-item" onclick="switchTab('config', this, 'Configuración')">
            <i class="fa-solid fa-gear"></i><span>Ajustes</span>
        </button>
    </nav>

    <!-- MODALES -->
    <div id="modalAprobarNota" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-ticket"></i> Confirmar Salida</h2>
                <button type="button" class="btn-close-modal" onclick="cerrarModalAprobar()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formAprobarNota">
                    <input type="hidden" id="modal_aprobar_id_nota" name="id_nota">

                    <div class="form-group" id="grupo_folio_ticket">
                        <label>Folio de Ticket (Entregado en Caja) *</label>
                        <input type="text" id="folio_ticket_1" name="folios[]" class="form-control" required placeholder="Ej. TKT-00123">
                    </div>

                    <div class="form-group" id="grupo_folio_factura" style="display: none;">
                        <label class="label-warning"><i class="fa-solid fa-file-invoice"></i> Folio de Factura *</label>
                        <input type="text" id="folio_ticket_2" name="folios[]" class="form-control" placeholder="Ej. FAC-00456">
                        <small>Esta nota contiene productos que requieren factura.</small>
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" class="btn-primary" id="btnConfirmarAprobacion">
                            <i class="fa-solid fa-check-double"></i> Confirmar y Aprobar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditarProducto" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-pen-to-square"></i> Configurar Producto</h2>
                <button type="button" class="btn-close-modal" onclick="cerrarModalProducto()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="formEditarProducto">
                    <input type="hidden" id="modal_id_producto" name="id_producto">
                    
                    <div class="form-group">
                        <label>Nombre del Producto</label>
                        <input type="text" id="modal_nombre_producto" class="form-control" disabled style="background-color: #f1f5f9; color: #64748b;">
                    </div>

                    <div class="form-group">
                        <label>¿Contabilizar Producto? <small>(Venta por piezas)</small></label>
                        <select id="modal_por_piezas" name="por_piezas" class="form-control">
                            <option value="1">(Contable / Por piezas)</option>
                            <option value="0">(Solo peso / Por kilo)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>¿Requiere Factura? <small>(Para notas y reportes)</small></label>
                        <select id="modal_factura" name="factura" class="form-control">
                            <option value="0">No facturar</option>
                            <option value="1">Sí facturar</option>
                        </select>
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" class="btn-primary" id="btnGuardarProducto">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="encargado.js"></script>
    
</body>
</html>