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
                    <!-- Al escribir, se llama a filtrarNotas() -->
                    <input type="text" id="buscarNota" class="form-control" placeholder="Buscar por folio o cliente..." onkeyup="filtrarNotas()">
                </div>
            </section>

            <section class="card">
                <h2 class="card-title"><i class="fa-solid fa-clipboard-list"></i> Notas por Aprobar</h2>
                
                <!-- AQUÍ INYECTARÁ JS LAS NOTAS -->
                <div id="lista-notas-container">
                    <div style="text-align:center; padding:20px;">
                        <i class="fa-solid fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                </div>

                <!-- Mensaje de no resultados (oculto por defecto) -->
                <div id="no-notes-results" class="empty-state" style="display: none;">
                    <i class="fa-solid fa-magnifying-glass-minus"></i>
                    <p>No se encontraron notas con esa búsqueda.</p>
                </div>
            </section>
        </div>

        <!-- VISTA 2: GESTIÓN (PRODUCTOS) -->
        <div id="tab-gestion" class="tab-content">
            <section class="card sticky-search">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="buscarProducto"><i class="fa-solid fa-magnifying-glass"></i> Buscar Producto</label>
                    <!-- Funciona igual para los productos -->
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
                
                <!-- Mensaje de no resultados (oculto por defecto) -->
                <div id="no-products-results" class="empty-state" style="display: none;">
                    <i class="fa-solid fa-box-open"></i>
                    <p>No se encontraron productos.</p>
                </div>
            </section>
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

    <!-- ==========================================
         MODAL: EDITAR PRODUCTO
    ========================================== -->
    <div id="modalEditarProducto" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-pen-to-square"></i> Configurar Producto</h2>
                <button type="button" class="btn-close-modal" onclick="cerrarModalProducto()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="formEditarProducto">
                    <!-- ID Oculto para mandarlo al controlador -->
                    <input type="hidden" id="modal_id_producto" name="id_producto">
                    
                    <div class="form-group">
                        <label>Nombre del Producto</label>
                        <!-- disabled para que solo sea de lectura -->
                        <input type="text" id="modal_nombre_producto" class="form-control" disabled style="background-color: #f1f5f9; color: #64748b;">
                    </div>

                    <div class="form-group">
                        <label>¿Contabilizar Producto? <small>(Venta por piezas)</small></label>
                        <select id="modal_por_piezas" name="por_piezas" class="form-control">
                            <option value="1">(Contable / Por piezas)</option>
                            <option value="0">(Solo peso / Por kilo)</option>
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