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
                    <label>Motivo / Concepto</label>
                    <input type="text" class="input-captura" placeholder="Ej. Venta a cliente, Merma, Traspaso...">
                </div>

                <div class="grupo-campo text-span-2">
                    <label>Almacén Origen</label>
                    <select class="select-captura" disabled style="background-color: #e2e8f0; cursor: not-allowed; opacity: 0.8; color: #334155;">
                        <option>(01) - EMBARQUES</option>
                    </select>
                </div>

                <div class="grupo-campo">
                    <label>Status</label>
                    <input type="text" class="input-readonly text-success-status font-bold" value="S" readonly style="color: #ef4444;">
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
            <div class="input-codigo-line" style="flex: 2; display: flex; align-items: center; gap: 12px;">
                <label class="label-inline" style="font-weight: 700; white-space: nowrap;">Código de Barras:</label>
                <div class="input-with-icon-bar" style="position: relative; width: 100%;">
                    <input type="text" class="input-captura" id="inputCodigoBarras" placeholder="Escanee la caja a dar de salida..." style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <i class="fas fa-barcode" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                </div>
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
                    <thead>
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
                        <!-- Las cajas escaneadas aparecerán aquí -->
                    </tbody>
                </table>
            </div>

            <div class="panel-totales-entradas">
                <div class="card-total-indicador bg-total-qty shadow-soft">
                    <h3>Total Cajas Salientes</h3>
                    <span id="totalCantidad">0</span>
                </div>

                <div class="card-total-indicador bg-total-kgs shadow-soft">
                    <h3>Total Kgs Salientes</h3>
                    <span id="totalKgs">0.00</span>
                </div>
                
                <div class="grupo-botones-captura">
                    <button class="btnAplicar btn-block-matriz" id="btnGuardarSalida"><i class="fas fa-save"></i> Procesar Salida</button>
                    <button class="btnLimpiar btn-block-matriz" id="btnLimpiarPantalla"><i class="fas fa-eraser"></i> Limpiar Pantalla</button>
                </div>
            </div>

        </div>

    </div>
    
    <script src="../Services/funcionesSalidas.js"></script>
</body>
</html>