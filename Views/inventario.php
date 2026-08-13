<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

// Conectamos con el modelo para extraer las métricas en tiempo real
require_once __DIR__ . '/../Models/modeloInventario.php';
$modeloInv = new modeloInventario();
$stats = $modeloInv->getKpiStats();

// CONSULTAS COLECTADAS EN EL MODELO PARA LOS SELECTS DEL MODAL
$listaProveedores = $modeloInv->getProveedores(); 
$listaCategorias = $modeloInv->getCategorias();   
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - LFI</title>
    
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/usuarios.css">
    <link rel="stylesheet" href="../Views/css/inventario.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="main-content contenedor">
        
        <div class="inventario-header">
            <div class="header-titulos">
                <h1>Inventario</h1>
                <p>Consulta y gestión del inventario actual</p>
            </div>
            
            <div class="header-acciones">
                <button type="button" class="btn-exportar">
                    <i class="fa-solid fa-file-excel"></i> Exportar a Excel
                </button>
                <button type="button" class="btn-imprimir">
                    <i class="fa-solid fa-print"></i> Imprimir
                </button>
                <button type="button" class="btn-agregar-prod" onclick="abrirModalAgregarProducto()">
                    <i class="fa-solid fa-plus"></i> Agregar producto
                </button>
                <button type="button" class="btn-agregar-prov" onclick="abrirModalAgregarProveedor()">
                    <i class="fa-solid fa-user-plus"></i> Agregar proveedor
                </button>
            </div>
        </div>

        <!-- CARDS DE ESTADÍSTICAS -->
        <div class="cards">
            <div class="card general-mov">
                <h3>Total de productos</h3>
                <span id="kpiTotalProductos"><?= htmlspecialchars((string)($stats['productos'] ?? 0)) ?></span>
                <p class="card-subtexto">Productos diferentes</p>
            </div>

            <div class="card success">
                <h3>Total de cajas</h3>
                <span id="kpiTotalCajas"><?= htmlspecialchars((string)($stats['cajas'] ?? 0)) ?></span>
                <p class="card-subtexto">Cajas en inventario</p>
            </div>
        </div>

        <div class="inventario-filtro-container">
            <form id="formFiltrosInventario" class="inventario-filtro-form" onsubmit="event.preventDefault();">
                <div class="filtro-grupo">
                    <label>Buscar por</label>
                    <select id="selectTipoBusqueda">
                        <option value="producto">📦 Producto</option>
                        <option value="proveedor">🚚 Proveedor</option>
                    </select>
                </div>
                <div class="filtro-grupo flex-grande">
                    <label id="labelDinamicoBusqueda">Buscar producto</label>
                    <div class="inventario-buscador-wrapper">
                        <input type="text" id="inputBusqueda" placeholder="Nombre, código o descripción...">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>
                <div class="filtro-grupo">
                    <label>Ubicación</label>
                    <select id="selectUbicacion"><option value="">Todas</option></select>
                </div>
                <div class="filtro-grupo">
                    <label>Estado</label>
                    <select id="selectEstado">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos / Disponibles</option>
                        <option value="0">Inactivos / Desactivados</option>
                    </select>
                </div>
                <div class="filtro-botones">
                    <button type="button" id="btnLimpiar" class="btn-limpiar-inv"><i class="fa-solid fa-rotate"></i> Limpiar filtros</button>
                </div>
            </form>
        </div>

        <table class="tablaUsuarios">
            <thead id="thead-inventario"></thead>
            <tbody id="tabla-inventario-tbody"></tbody>
        </table>

        <div class="botones-paginacion" style="display: flex; gap: 6px; justify-content: center; margin-top: 25px; padding-bottom: 40px;">
            <button type="button" class="btn-pagina" id="btnAnterior">
                <i class="fa-solid fa-chevron-left"></i> Anterior
            </button>
            
            <button type="button" class="btn-pagina numero-pagina activa" id="btnPag1">1</button>
            <button type="button" class="btn-pagina numero-pagina" id="btnPag2">2</button>
            
            <button type="button" class="btn-pagina" id="btnSiguiente">
                Siguiente <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- MODAL AGREGAR PRODUCTO -->
    <div class="modal" id="modalAgregarProducto" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 500px; position: relative;">
            <span class="cerrar-modal" onclick="cerrarModalAgregarProducto()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2f56; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Registrar Nuevo Producto</h2>
            
            <form id="formNuevoProducto" onsubmit="guardarProducto(event)">
                <div class="modal-grid" style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                    <div class="grupo-input">
                        <label for="prodCodigo">Código de Producto *</label>
                        <input type="text" id="prodCodigo" name="codigoProducto" placeholder="Ej: H1175104157" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px;">
                    </div>
                    <div class="grupo-input">
                        <label for="prodNombre">Nombre del Producto *</label>
                        <input type="text" id="prodNombre" name="nombreProducto" placeholder="Ej: Harina de Trigo 1kg" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px;">
                    </div>
                    <div class="grupo-input">
                        <label for="prodProveedor">Proveedor Asociado *</label>
                        <select id="prodProveedor" name="idProveedor" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background-color: white;">
                            <option value="" disabled selected>Seleccione un proveedor...</option>
                            <?php 
                            if (!empty($listaProveedores)) {
                                foreach ($listaProveedores as $prov) {
                                    echo '<option value="' . htmlspecialchars((string)$prov['idProveedor']) . '">' . htmlspecialchars($prov['nombreProveedor']) . '</option>';
                                }
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="grupo-input">
                        <label for="prodCategoria">Categoría del Producto *</label>
                        <select id="prodCategoria" name="idCategoria" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background-color: white;">
                            <option value="" disabled selected>Seleccione una categoría...</option>
                            <?php 
                            if (!empty($listaCategorias)) {
                                foreach ($listaCategorias as $cat) {
                                    echo '<option value="' . htmlspecialchars((string)$cat['idCategoria']) . '">' . htmlspecialchars($cat['nombreCategoria']) . '</option>';
                                }
                            } 
                            ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btnGuardarUsuario" style="width: 100%; height: 40px; background: #0d6efd; color: white; border: none; border-radius: 8px; margin-top: 20px; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
                </button>
            </form>
        </div>
    </div>


    <!-- MODAL AGREGAR PROVEEDOR -->
    <div class="modal" id="modalAgregarProveedor" style="display: none;">
        <div class="modal-contenido">
            <span class="cerrar-modal" onclick="cerrarModalAgregarProveedor()">&times;</span>
            <h2>Registrar Nuevo Proveedor</h2>
            
            <form action="../Controllers/inventarioController.php?action=agregarProveedor" method="POST" id="formNuevoProveedor" onsubmit="guardarProveedor(event)">
                <div class="modal-grid">
                    <div class="grupo-input">
                        <label for="provCodigo">Código de Proveedor *</label>
                        <input type="text" id="provCodigo" name="codigoProveedor" placeholder="Ej: PROV-001" required>
                    </div>
                    <div class="grupo-input">
                        <label for="provNombre">Nombre / Empresa *</label>
                        <input type="text" id="provNombre" name="nombreProveedor" placeholder="Nombre comercial" required>
                    </div>
                    <div class="grupo-input">
                        <label for="provRfc">RFC *</label>
                        <input type="text" id="provRfc" name="rfc" placeholder="12 o 13 dígitos" maxlength="13" required>
                    </div>
                    <div class="grupo-input">
                        <label for="provDireccion">Dirección (Calle y Número) *</label>
                        <input type="text" id="provDireccion" name="direccion" placeholder="Av. Principal #123" required>
                    </div>
                    <div class="grupo-input">
                        <label for="provColonia">Colonia *</label>
                        <input type="text" id="provColonia" name="colonia" placeholder="Centro" required>
                    </div>
                    <div class="grupo-input">
                        <label for="provCp">Código Postal *</label>
                        <input type="text" id="provCp" name="codigoPostal" placeholder="72000" maxlength="5" required>
                    </div>
                    <div class="grupo-input" style="grid-column: span 2;">
                        <label for="provEstado">Estado de la República *</label>
                        <input type="text" id="provEstado" name="estadoRepublica" placeholder="Ej: Puebla, CDMX, Veracruz..." autocomplete="off" required>
                    </div>
                    
                    <div style="grid-column: span 2; margin-top: 15px; border-top: 2px dashed #e2e8f0; padding-top: 15px;">
                        <h3 style="font-size: 14px; font-weight: 700; color: #475569; margin-bottom: 10px;">
                            <i class="fa-solid fa-qrcode"></i> Configuración de Lectura (Escáner QR)
                        </h3>
                    </div>

                    <div style="grid-column: span 2; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc;">
                            <span style="font-size: 12px; font-weight: 700; color: #0d6efd; display: block; margin-bottom: 8px;">Código de Producto</span>
                            <div class="grupo-input" style="margin-bottom: 6px;">
                                <label style="font-size: 11px;">Posición Inicio</label>
                                <input type="number" name="codigoBarrasProductosPosicion" value="0" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Letras)</label>
                                <input type="number" name="codigoBarrasProductosLongitud" value="0" min="0" required style="height: 32px;">
                            </div>
                        </div>

                        <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc;">
                            <span style="font-size: 12px; font-weight: 700; color: #0d6efd; display: block; margin-bottom: 8px;">Peso Kilos (Enteros)</span>
                            <div class="grupo-input" style="margin-bottom: 6px;">
                                <label style="font-size: 11px;">Posición Inicio</label>
                                <input type="number" name="codigoBarrasEnterosPosicion" value="0" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Dígitos)</label>
                                <input type="number" name="codigoBarrasEnterosLongitud" value="0" min="0" required style="height: 32px;">
                            </div>
                        </div>

                        <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc;">
                            <span style="font-size: 11px; font-weight: 700; color: #0d6efd; display: block; margin-bottom: 8px;">Peso Gramos (Decimales)</span>
                            <div class="grupo-input" style="margin-bottom: 6px;">
                                <label style="font-size: 11px;">Posición Inicio</label>
                                <input type="number" name="codigoBarrasDecimalesPosicion" value="0" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Dígitos)</label>
                                <input type="number" name="codigoBarrasDecimalesLongitud" value="0" min="0" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btnGuardarUsuario" style="background: #1e293b;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Proveedor
                </button>
            </form>
        </div>
    </div>


    <!-- MODAL EDITAR PROVEEDOR -->
    <div class="modal" id="modalEditarProveedor" style="display: none;">
        <div class="modal-contenido">
            <span class="cerrar-modal" onclick="cerrarModalEditarProveedor()">&times;</span>
            <h2>Editar Proveedor Existente</h2>
            
            <form action="../Controllers/inventarioController.php?action=editarProveedor" method="POST" id="formEditarProveedor" onsubmit="actualizarProveedor(event)">
                <input type="hidden" id="editProvId" name="idProveedor">

                <div class="modal-grid">
                    <div class="grupo-input">
                        <label for="editProvCodigo">Código de Proveedor *</label>
                        <input type="text" id="editProvCodigo" name="codigoProveedor" required>
                    </div>
                    <div class="grupo-input">
                        <label for="editProvNombre">Nombre / Empresa *</label>
                        <input type="text" id="editProvNombre" name="nombreProveedor" required>
                    </div>
                    <div class="grupo-input">
                        <label for="editProvRfc">RFC *</label>
                        <input type="text" id="editProvRfc" name="rfc" maxlength="13" required>
                    </div>
                    <div class="grupo-input">
                        <label for="editProvDireccion">Dirección (Calle y Número) *</label>
                        <input type="text" id="editProvDireccion" name="direccion" required>
                    </div>
                    <div class="grupo-input">
                        <label for="editProvColonia">Colonia *</label>
                        <input type="text" id="editProvColonia" name="colonia" required>
                    </div>
                    <div class="grupo-input">
                        <label for="editProvCp">Código Postal *</label>
                        <input type="text" id="editProvCp" name="codigoPostal" maxlength="5" required>
                    </div>
                    <div class="grupo-input" style="grid-column: span 2;">
                        <label for="editProvEstado">Estado de la República *</label>
                        <input type="text" id="editProvEstado" name="estadoRepublica" autocomplete="off" required>
                    </div>
                    
                    <div style="grid-column: span 2; margin-top: 15px; border-top: 2px dashed #e2e8f0; padding-top: 15px;">
                        <h3 style="font-size: 14px; font-weight: 700; color: #475569; margin-bottom: 10px;">
                            <i class="fa-solid fa-qrcode"></i> Configuración de Lectura (Escáner QR)
                        </h3>
                    </div>

                    <div style="grid-column: span 2; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc;">
                            <span style="font-size: 12px; font-weight: 700; color: #0d6efd; display: block; margin-bottom: 8px;">Código de Producto</span>
                            <div class="grupo-input" style="margin-bottom: 6px;">
                                <label style="font-size: 11px;">Posición Inicio</label>
                                <input type="number" id="editCodigoBarrasProductosPosicion" name="codigoBarrasProductosPosicion" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Letras)</label>
                                <input type="number" id="editCodigoBarrasProductosLongitud" name="codigoBarrasProductosLongitud" min="0" required style="height: 32px;">
                            </div>
                        </div>

                        <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc;">
                            <span style="font-size: 12px; font-weight: 700; color: #0d6efd; display: block; margin-bottom: 8px;">Peso Kilos (Enteros)</span>
                            <div class="grupo-input" style="margin-bottom: 6px;">
                                <label style="font-size: 11px;">Posición Inicio</label>
                                <input type="number" id="editCodigoBarrasEnterosPosicion" name="codigoBarrasEnterosPosicion" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Dígitos)</label>
                                <input type="number" id="editCodigoBarrasEnterosLongitud" name="codigoBarrasEnterosLongitud" min="0" required style="height: 32px;">
                            </div>
                        </div>

                        <div style="border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; background: #f8fafc;">
                            <span style="font-size: 11px; font-weight: 700; color: #0d6efd; display: block; margin-bottom: 8px;">Peso Gramos (Decimales)</span>
                            <div class="grupo-input" style="margin-bottom: 6px;">
                                <label style="font-size: 11px;">Posición Inicio</label>
                                <input type="number" id="editCodigoBarrasDecimalesPosicion" name="codigoBarrasDecimalesPosicion" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Dígitos)</label>
                                <input type="number" id="editCodigoBarrasDecimalesLongitud" name="codigoBarrasDecimalesLongitud" min="0" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btnGuardarUsuario" style="background: #0284c7;">
                    <i class="fa-solid fa-floppy-disk"></i> Actualizar Proveedor
                </button>
            </form>
        </div>
    </div>

    <script src="../Services/funcionesInventario.js"></script>
    <script src="../Services/tema.js"></script>

</body>
</html>