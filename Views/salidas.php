<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

// 📦 CARGA EXCLUSIVA DEL SERVICIO DE SALIDAS
try {
    require_once __DIR__ . '/../Services/salidasServicio.php';
    $salidasService = new salidasServicio();
    
    // Obtenemos los clientes en lugar de proveedores
    $clientes = $salidasService->listarClientesParaSelect();
} catch (Throwable $e) {
    error_log("Error en la vista salidas al cargar clientes: " . $e->getMessage());
    $clientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura de Salidas</title>
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    
    <!-- Hojas de estilo externas -->
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/entradas.css"> 
    <link rel="stylesheet" href="../Views/css/salidas.css">
    <script src="../Services/tema.js" defer></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    
    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="contenedor">
    
        <div class="header">
            <div class="header-titulos">
                <h1>Captura de Salidas (Despacho)</h1>
            </div>
        </div>

        <div class="card-entrada shadow-soft">
            <div class="formulario-matriz-grid">
                
                <div class="grupo-campo">
                    <label>Folio Salida</label>
                    <input type="text" class="input-readonly" value="AUTO" readonly style="background-color: #e2e8f0; cursor: not-allowed;">
                </div>
                
                <div class="grupo-campo">
                    <label>Fecha de Salida</label>
                    <input type="date" class="input-readonly" value="<?php echo date('Y-m-d'); ?>" readonly style="background-color: #e2e8f0; cursor: not-allowed;">
                </div>

                <div class="grupo-campo text-span-2">
                    <label for="selectConceptoSalida">Concepto de Salida *</label>
                    <select id="selectConceptoSalida" name="concepto" class="select-captura" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background-color: white; font-weight: 500; color: #1e293b;" required>
                        <option value="" disabled selected>Selecciona un concepto de salida...</option>
                        <option value="ventas_cliente">Ventas Cliente</option>
                        <option value="inventariotemporalsalida">Traspaso a Mayoreo</option>
                        <option value="merma">Merma</option>
                        <option value="traspaso_morelos">Traspaso a Morelos</option>
                    </select>
                </div>

                <div class="grupo-campo">
                    <label>Tipo de Despacho</label>
                    <select id="selectTipoDespacho" class="select-captura" style="width: 100%; height: 40px; border: 1.5px solid #0284c7; border-radius: 8px; padding: 0 12px; font-weight: bold; color: #0369a1; background-color: #f0f9ff;">
                        <option value="cajas" selected>📦 Salida de Cajas (Piezas)</option>
                        <option value="combos">🥩 Salida de Combos (Tarados)</option>
                        <option value="manteca">🛢️ Salida de Manteca</option>
                    </select>
                </div>

                <div class="grupo-campo">
                    <label>Cliente / Destino</label>
                    <select class="select-captura" name="idCliente" id="idCliente" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; background-color: white;">
                        <option value="">-- Seleccione un cliente --</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo htmlspecialchars($cliente['idCliente']); ?>">
                                <?php echo htmlspecialchars($cliente['nombreCliente']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-entrada captura-line-bar shadow-soft">
            <!-- Contenedor para Cajas (Escáner) -->
            <div id="contenedorEscannerCajas" style="flex: 2; display: flex; align-items: center; gap: 12px;">
                <label class="label-inline" style="font-weight: 700; white-space: nowrap;">Código de Barras:</label>
                <div class="input-with-icon-bar" style="position: relative; width: 100%;">
                    <input type="text" class="input-captura" id="inputCodigoBarras" placeholder="Escanee la caja a dar de salida..." style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <i class="fas fa-barcode" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                </div>
            </div>

            <!-- Contenedor para Combos (Selector de inventario) -->
            <div id="contenedorSelectorCombos" style="flex: 2; display: none; align-items: center; gap: 12px;">
                <label class="label-inline" style="font-weight: 700; white-space: nowrap;">Seleccionar Combo:</label>
                <select id="selectComboInventario" class="select-captura" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; background-color: white;">
                    <option value="">-- Seleccione un combo disponible --</option>
                </select>
            </div>

            <div class="input-costo-line" style="flex: 1; display: flex; align-items: center; gap: 12px;">
                <label class="label-inline" style="font-weight: 700;">Precio/Valor:</label>
                <input type="number" id="inputCosto" class="input-captura" value="0.00" step="0.01" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px;">
            </div>

            <div class="modos-captura-radios" style="display: flex; gap: 16px;">
                <label class="radio-inline" style="cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <input type="radio" name="modo_captura" value="codigo" checked style="accent-color: #0d6efd;">
                    <i class="fas fa-keyboard"></i> Escáner
                </label>
                <label class="radio-inline" style="cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <input type="radio" name="modo_captura" value="manual" style="accent-color: #0d6efd;">
                    <i class="fas fa-hand-paper"></i> Manual
                </label>
            </div>
        </div>

        <div class="seccion-tabla-totales-grid">
            
            <div class="tabla-partidas-container shadow-soft">
                <table class="tablaUsuarios" style="margin-top: 0; border: none; width: 100%; border-collapse: collapse;">
                    <thead id="tablaCabecera">
                        <tr style="background: #f8fafc;">
                            <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 40px;">▶</th>
                            <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0;">Lote</th>
                            <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Producto</th>
                            <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Descripcion</th>
                            <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Cantidad (Cajas)</th>
                            <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Kgs</th>
                            <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPartidasBody">
                        <!-- Las partidas escaneadas o seleccionadas aparecerán aquí -->
                    </tbody>
                </table>
            </div>

            <!-- Panel Lateral con los Dos Indicadores Clásicos -->
            <div class="panel-totales-entradas">
                <div class="card-total-indicador bg-total-qty shadow-soft">
                    <h3 id="labelTotalCantidad">Total Cajas Salientes</h3>
                    <span id="totalCantidad">0</span>
                </div>

                <div class="card-total-indicador bg-total-kgs shadow-soft">
                    <h3>Total Kgs Salientes</h3>
                    <span id="totalKgs">0.00</span>
                </div>
                
                <div class="grupo-botones-captura">
                    <button class="btnAplicar btn-block-matriz" id="btnGuardarSalida"><i class="fas fa-save"></i> Procesar Salida</button>
                    <!-- NUEVO BOTÓN PARA ABRIR OTRA PESTAÑA DE SALIDAS -->
                    <a href="salidas.php" target="_blank" class="btn-block-matriz" style="background: #0284c7; color: white; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-external-link-alt"></i> Hacer otra salida
                    </a>
                    <button class="btnLimpiar btn-block-matriz" id="btnLimpiarPantalla"><i class="fas fa-eraser"></i> Limpiar Pantalla</button>
                </div>
            </div>

        </div>

    </div>
    
    <!-- MODAL DE ALERTA PERSONALIZADO -->
    <div id="modalAlertaSalidas" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); justify-content: center; align-items: center;">
        <div style="background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="font-size: 40px; color: #ef4444; margin-bottom: 12px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Atención</h3>
            <p id="mensajeAlertaTexto" style="font-size: 14px; color: #64748b; margin-bottom: 20px; line-height: 1.5;"></p>
            <button type="button" onclick="document.getElementById('modalAlertaSalidas').style.display = 'none';" style="background: #0f172a; color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; cursor: pointer; width: 100%;">
                Entendido
            </button>
        </div>
    </div>
    
    <script src="../Services/funcionesSalidas.js"></script>
</body>
</html>