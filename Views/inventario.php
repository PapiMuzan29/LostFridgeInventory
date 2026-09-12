<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../Models/modeloInventario.php';
$modeloInv = new modeloInventario();
$stats = $modeloInv->getKpiStats();

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

    <div class="main-content contenedor" style="padding: 24px;">
        
        <div class="header-impresion-oficial" style="display: none;">
            <div style="display: flex; align-items: center; gap: 20px; border-bottom: 3px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px;">
                <img src="../SRC/Logo LFI - copia.png" alt="Logo LFI" style="width: 75px; height: 75px; object-fit: contain;">
                <div>
                    <h1 style="margin: 0; font-size: 20px; color: #0f172a; font-weight: bold; letter-spacing: 0.5px;">LFI - CONTROL OPERATIVO DE INVENTARIOS</h1>
                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #475569; font-weight: 600;">Reporte General de Inventario y Productos</p>
                </div>
                <div style="margin-left: auto; text-align: right; font-size: 12px; color: #334155;">
                    <strong>Fecha de Emisión:</strong><br><span class="print-fecha" style="font-size: 14px; font-weight: bold; color: #0f172a;"></span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['envio']) && $_GET['envio'] === 'success'): ?>
            <div style="background: #dcfce7; color: #15803d; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
                <i class="fa-solid fa-check-circle"></i> Producto enviado correctamente al área de ventas pendientes.
            </div>
        <?php endif; ?>

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

        <!-- CARDS DE ESTADÍSTICAS GENERALES (EXCLUSIVAS DE CAJAS) -->
        <div class="cards" id="seccionCardsGenerales">
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

        <!-- 🧈 TARJETAS EXCLUSIVAS DE RESUMEN DE MANTECAS -->
        <div id="seccionCardsMantecas" style="display: none; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="card" style="border-left: 5px solid #3b82f6; background: white;">
                <h3 style="color: #64748b; font-size: 13px; margin-bottom: 6px;">Bolsas de 10 kg</h3>
                <span id="kpiManteca10" style="font-size: 26px; font-weight: bold; color: #0f172a;">0</span>
                <p class="card-subtexto" id="kpisKgs10" style="color: #16a34a; font-weight: 600; margin-top: 4px;">Total: 0.00 kg</p>
            </div>
            <div class="card" style="border-left: 5px solid #f59e0b; background: white;">
                <h3 style="color: #64748b; font-size: 13px; margin-bottom: 6px;">Botes de 15 kg</h3>
                <span id="kpiManteca15" style="font-size: 26px; font-weight: bold; color: #0f172a;">0</span>
                <p class="card-subtexto" id="kpisKgs15" style="color: #16a34a; font-weight: 600; margin-top: 4px;">Total: 0.00 kg</p>
            </div>
            <div class="card" style="border-left: 5px solid #10b981; background: white;">
                <h3 style="color: #64748b; font-size: 13px; margin-bottom: 6px;">Botes de 18 kg</h3>
                <span id="kpiManteca18" style="font-size: 26px; font-weight: bold; color: #0f172a;">0</span>
                <p class="card-subtexto" id="kpisKgs18" style="color: #16a34a; font-weight: 600; margin-top: 4px;">Total: 0.00 kg</p>
            </div>
        </div>

        <!-- 🎯 PESTAÑAS DE FILTRADO RÁPIDO -->
        <div class="tabs-inventario" style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            <button type="button" class="tab-btn activo" data-tipo="cajas" style="padding: 10px 20px; border-radius: 8px; border: none; background: #0f172a; color: white; font-weight: bold; cursor: pointer; transition: all 0.2s;">📦 Cajas / General</button>
            <button type="button" class="tab-btn" data-tipo="pierna" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; color: #334155; font-weight: bold; cursor: pointer; transition: all 0.2s;">🥩 Combo de Pierna</button>
            <button type="button" class="tab-btn" data-tipo="codillo" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; color: #334155; font-weight: bold; cursor: pointer; transition: all 0.2s;">🍖 Combo de Codillo</button>
            <button type="button" class="tab-btn" data-tipo="mantecas" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; color: #334155; font-weight: bold; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-jar"></i> 🧈 Control de Mantecas</button>
        </div>

        <div class="inventario-filtro-container" id="contenedorFiltrosGenerales">
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

        <div class="botones-paginacion" id="paginacionInventarioGeneral" style="display: flex; gap: 6px; justify-content: center; margin-top: 25px; padding-bottom: 40px;">
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

    <!-- MODAL OPCIONES EXPORTAR / IMPRIMIR -->
    <div class="modal" id="modalExportarExcel" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 420px; text-align: center; position: relative;">
            <span class="cerrar-modal" onclick="cerrarModalExportar()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <i class="fa-solid fa-print" style="font-size: 42px; color: #0284c7; margin-bottom: 12px;"></i>
            <h2 style="font-size: 18px; font-weight: 700; color: #1f2f56; margin-bottom: 8px;">Opciones de Reporte</h2>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">Selecciona la acción que deseas realizar:</p>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="button" onclick="ejecutarExportacion('vista')" style="padding: 11px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fa-solid fa-file-excel"></i> Excel: Solo vista actual
                </button>
                <button type="button" onclick="ejecutarExportacion('todo')" style="padding: 11px; background: #16a34a; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fa-solid fa-database"></i> Excel: Inventario completo
                </button>
                
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 5px 0;">

                <button type="button" onclick="ejecutarImpresion('vista')" style="padding: 11px; background: #475569; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fa-solid fa-desktop"></i> Imprimir: Solo vista actual
                </button>
                <button type="button" onclick="ejecutarImpresion('todo')" style="padding: 11px; background: #1e293b; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fa-solid fa-print"></i> Imprimir: Todo el inventario
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PARA ENVIAR A SALIDAS / VENTAS PENDIENTES -->
    <div class="modal" id="modalEnviarVenta" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 400px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <span onclick="cerrarModalEnviarVenta()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <h2 style="font-size: 18px; font-weight: 700; color: #1f2f56; margin-bottom: 8px;">Enviar a Salidas / Venta</h2>
            <p id="infoProductoModal" style="color: #64748b; font-size: 13px; margin-bottom: 16px;"></p>

            <form action="../Controllers/SalidasPendientesController.php" method="POST">
                <input type="hidden" name="id_inventario" id="modal_id_inventario">
                <input type="hidden" name="codigo" id="modal_codigo">
                <input type="hidden" name="descripcion" id="modal_descripcion">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Cantidad a enviar:</label>
                    <input type="number" name="cantidad" id="modal_cantidad" min="1" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Cliente Destino (Opcional):</label>
                    <input type="text" name="cliente_destino" placeholder="Nombre del cliente o sucursal" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>

                <button type="submit" style="width: 100%; height: 40px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Confirmar Envío
                </button>
            </form>
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


    <!-- MODAL EDITAR PRODUCTO -->
    <div class="modal" id="modalEditarProducto" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 500px; position: relative;">
            <span class="cerrar-modal" onclick="cerrarModalEditarProducto()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2f56; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Editar Producto</h2>
            
            <form id="formEditarProducto" onsubmit="actualizarProducto(event)">
                <input type="hidden" id="editIdProducto" name="idProducto">

                <div class="modal-grid" style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                    <div class="grupo-input">
                        <label for="editProdCodigo">Código de Producto *</label>
                        <input type="text" id="editProdCodigo" name="codigoProducto" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px;">
                    </div>
                    <div class="grupo-input">
                        <label for="editProdNombre">Nombre del Producto *</label>
                        <input type="text" id="editProdNombre" name="nombreProducto" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px;">
                    </div>
                    <div class="grupo-input">
                        <label for="editProdProveedor">Proveedor Asociado *</label>
                        <select id="editProdProveedor" name="idProveedor" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background-color: white;">
                            <option value="" disabled>Seleccione un proveedor...</option>
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
                        <label for="editProdCategoria">Categoría del Producto *</label>
                        <select id="editProdCategoria" name="idCategoria" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background-color: white;">
                            <option value="" disabled>Seleccione una categoría...</option>
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
                <button type="submit" class="btnGuardarUsuario" style="width: 100%; height: 40px; background: #0284c7; color: white; border: none; border-radius: 8px; margin-top: 20px; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-floppy-disk"></i> Actualizar Producto
                </button>
            </form>
        </div>
    </div>


    <!-- MODAL ELIMINAR PRODUCTO -->
    <div class="modal" id="modalEliminarProducto" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 400px; text-align: center; position: relative;">
            <span class="cerrar-modal" onclick="cerrarModalEliminarProducto()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #f59e0b; margin-bottom: 15px;"></i>
            
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2f56; margin-bottom: 10px;">¿Eliminar Producto?</h2>
            
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                Estás a punto de eliminar el producto: <strong id="nombreProductoEliminar" style="color: #0f172a;"></strong>. Esta acción no se puede deshacer.
            </p>

            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" onclick="cerrarModalEliminarProducto()" style="padding: 10px 20px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Cancelar
                </button>
                <a id="btnConfirmarEliminarProducto" href="#" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
                    Sí, eliminar
                </a>
            </div>
        </div>
    </div>


    <!-- MODAL AGREGAR PROVEEDOR -->
    <div class="modal" id="modalAgregarProveedor" style="display: none;">
        <div class="modal-contenido">
            <span class="cerrar-modal" onclick="cerrarModalAgregarProveedor()">&times;</span>
            <h2>Registrar Nuevo Proveedor</h2>
            
            <form method="POST" id="formNuevoProveedor" onsubmit="guardarProveedor(event)">
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
                                <input type="number" name="codigoBarrasProductosPosicion" value="1" min="0" required style="height: 32px;">
                            </div>
                            <div class="grupo-input">
                                <label style="font-size: 11px;">Longitud (Letras)</label>
                                <input type="number" name="codigoBarrasProductosLongitud" value="6" min="0" required style="height: 32px;">
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
            
            <form method="POST" id="formEditarProveedor" onsubmit="actualizarProveedor(event)">
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


    <!-- MODAL ELIMINAR PROVEEDOR -->
    <div class="modal" id="modalEliminarProveedor" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 400px; text-align: center; position: relative;">
            <span class="cerrar-modal" onclick="cerrarModalEliminarProveedor()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #f59e0b; margin-bottom: 15px;"></i>
            
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2f56; margin-bottom: 10px;">¿Eliminar Proveedor?</h2>
            
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                Estás a punto de eliminar al proveedor: <strong id="nombreProveedorEliminar" style="color: #0f172a;"></strong>. Esta acción no se puede deshacer.
            </p>

            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" onclick="cerrarModalEliminarProveedor()" style="padding: 10px 20px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Cancelar
                </button>
                <a id="btnConfirmarEliminarProveedor" href="#" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
                    Sí, eliminar
                </a>
            </div>
        </div>
    </div>

    <!-- Script de Pestañas Interactivas y Visibilidad de Secciones -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.tipoInventarioActual = 'cajas'; 

            const botonesTabs = document.querySelectorAll('.tab-btn');
            const seccionCardsGenerales = document.getElementById('seccionCardsGenerales');
            const seccionCardsMantecas = document.getElementById('seccionCardsMantecas');
            const contenedorFiltrosGenerales = document.getElementById('contenedorFiltrosGenerales');
            const paginacionInventarioGeneral = document.getElementById('paginacionInventarioGeneral');

            botonesTabs.forEach(btn => {
                btn.addEventListener('click', function() {
                    botonesTabs.forEach(b => {
                        b.style.background = 'white';
                        b.style.color = '#334155';
                        b.style.border = '1px solid #cbd5e1';
                        b.classList.remove('activo');
                    });
                    this.style.background = '#0f172a';
                    this.style.color = 'white';
                    this.style.border = 'none';
                    this.classList.add('activo');

                    window.tipoInventarioActual = this.dataset.tipo;

                    // 🎯 Control visual dinámico y forzado de visibilidad
                    if (window.tipoInventarioActual === 'mantecas') {
                        if (seccionCardsGenerales) seccionCardsGenerales.style.setProperty('display', 'none', 'important');
                        if (seccionCardsMantecas) seccionCardsMantecas.style.setProperty('display', 'grid', 'important');
                        if (contenedorFiltrosGenerales) contenedorFiltrosGenerales.style.display = 'none';
                        if (paginacionInventarioGeneral) paginacionInventarioGeneral.style.display = 'none';
                    } else if (window.tipoInventarioActual === 'pierna' || window.tipoInventarioActual === 'codillo') {
                        if (seccionCardsGenerales) seccionCardsGenerales.style.setProperty('display', 'none', 'important');
                        if (seccionCardsMantecas) seccionCardsMantecas.style.setProperty('display', 'none', 'important');
                        if (contenedorFiltrosGenerales) contenedorFiltrosGenerales.style.display = 'block';
                        if (paginacionInventarioGeneral) paginacionInventarioGeneral.style.display = 'flex';
                    } else {
                        if (seccionCardsGenerales) seccionCardsGenerales.style.setProperty('display', 'grid', 'important');
                        if (seccionCardsMantecas) seccionCardsMantecas.style.setProperty('display', 'none', 'important');
                        if (contenedorFiltrosGenerales) contenedorFiltrosGenerales.style.display = 'block';
                        if (paginacionInventarioGeneral) paginacionInventarioGeneral.style.display = 'flex';
                    }

                    if (typeof window.cargarInventario === 'function') {
                        window.cargarInventario(0);
                    }
                });
            });
        });

        // Funciones para el Modal de Enviar a Ventas Pendientes
        function abrirModalEnviarVenta(id, codigo, descripcion, stockMax) {
            document.getElementById('modal_id_inventario').value = id;
            document.getElementById('modal_codigo').value = codigo;
            document.getElementById('modal_descripcion').value = descripcion;
            document.getElementById('modal_cantidad').max = stockMax;
            document.getElementById('modal_cantidad').value = 1;
            document.getElementById('infoProductoModal').innerHTML = `Producto: <strong>${descripcion}</strong> (Código: ${codigo})<br>Disponible en stock: ${stockMax}`;
            
            document.getElementById('modalEnviarVenta').style.display = 'flex';
        }

        function cerrarModalEnviarVenta() {
            document.getElementById('modalEnviarVenta').style.display = 'none';
        }
    </script>

    <script src="../Services/funcionesInventario.js"></script>
    <script src="../Services/tema.js"></script>

</body>
</html>