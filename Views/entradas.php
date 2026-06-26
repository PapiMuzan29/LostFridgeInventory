<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

// 📦 CARGA EXCLUSIVA DEL SERVICIO DE ENTRADAS
try {
    require_once __DIR__ . '/../Services/entradasServicio.php';
    $entradasService = new entradasServicio();
    // 🛠️ Cambiado al método dinámico que te extrae también las posiciones del escáner por proveedor
    $proveedores = $entradasService->listarProveedoresParaSelect();
} catch (Throwable $e) {
    error_log("Error en la vista entradas al cargar proveedores: " . $e->getMessage());
    $proveedores = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura de Entradas</title>
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/entradas.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        /* Fuerza la estructura Grid de 4 columnas para los campos superiores */
        .formulario-matriz-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 16px 20px !important;
            width: 100% !important;
        }
        /* Fuerza que los grupos coloquen la etiqueta arriba y el input abajo */
        .grupo-campo {
            display: flex !important;
            flex-direction: column !important;
            gap: 6px !important;
        }
        .text-span-2 {
            grid-column: span 2 !important;
        }
        /* Configuración de la barra de código de barras */
        .captura-line-bar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 25px !important;
        }
        /* Rejilla inferior para dividir Tabla y Totales */
        .seccion-tabla-totales-grid {
            display: grid !important;
            grid-template-columns: 1fr 300px !important;
            gap: 20px !important;
            width: 100% !important;
            align-items: start !important;
        }
    </style>
</head>

<body>
    
    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="contenedor">
    
        <div class="header">
            <div class="header-titulos">
                <h1>Captura de Entradas (Matriz)</h1>
            </div>
        </div>

        <div class="card-entrada shadow-soft">
            <div class="formulario-matriz-grid">
                <div class="grupo-campo">
                    <label>Folio</label>
                    <input type="text" class="input-readonly" value="21064" readonly>
                </div>
                
                <div class="grupo-campo">
                    <label>Fecha de Captura</label>
                    <input type="date" class="input-readonly" value="<?php echo date('Y-m-d'); ?>" readonly style="background-color: #e2e8f0; cursor: not-allowed;">
                </div>

                <div class="grupo-campo text-span-2">
                    <label>Concepto de Entrada</label>
                    <input type="text" class="input-captura" placeholder="Ej. Compra, Traspaso, Ajuste...">
                </div>

                <div class="grupo-campo text-span-2">
                    <label>Almacén / Cámara</label>
                    <select class="select-captura" disabled style="background-color: #e2e8f0; cursor: not-allowed; opacity: 0.8; color: #334155;">
                        <option>(01) - EMBARQUES</option>
                    </select>
                </div>

                <div class="grupo-campo">
                    <label>Status</label>
                    <input type="text" class="input-readonly text-success-status font-bold" value="A" readonly>
                </div>

                <div class="grupo-campo">
                    <label>Proveedor</label>
                    <select class="select-captura" name="idProveedor" id="idProveedor" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; background-color: white;">
                        <option value="">-- Seleccione un proveedor --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo htmlspecialchars($prov['idProveedor']); ?>"
                                    data-prod-pos="<?php echo (int)($prov['codigoBarrasProductosPosicion'] ?? 0); ?>"
                                    data-prod-lon="<?php echo (int)($prov['codigoBarrasProductosLongitud'] ?? 0); ?>"
                                    data-kg-pos="<?php echo (int)($prov['codigoBarrasEnterosPosicion'] ?? 0); ?>"
                                    data-kg-lon="<?php echo (int)($prov['codigoBarrasEnterosLongitud'] ?? 0); ?>"
                                    data-gr-pos="<?php echo (int)($prov['codigoBarrasDecimalesPosicion'] ?? 0); ?>"
                                    data-gr-lon="<?php echo (int)($prov['codigoBarrasDecimalesLongitud'] ?? 0); ?>">
                                <?php echo htmlspecialchars($prov['nombreProveedor']); ?>
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
                    <input type="text" class="input-captura" placeholder="Escanee el producto aquí..." style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <i class="fas fa-barcode" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                </div>
            </div>

            <div class="input-costo-line" style="flex: 1; display: flex; align-items: center; gap: 12px;">
                <label class="label-inline" style="font-weight: 700;">Costo:</label>
                <input type="number" class="input-captura" value="0.00" step="0.01" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px;">
            </div>

            <div class="modos-captura-radios" style="display: flex; gap: 16px;">
                <label class="radio-inline" style="cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <input type="radio" name="modo_captura" checked style="accent-color: #0d6efd;">
                    <i class="fas fa-keyboard"></i> Códigos de Barras
                </label>
                <label class="radio-inline" style="cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <input type="radio" name="modo_captura" style="accent-color: #0d6efd;">
                    <i class="fas fa-hand-paper"></i> Captura Manual
                </label>
            </div>
        </div>

        <div class="seccion-tabla-totales-grid">
            
            <div class="tabla-partidas-container shadow-soft">
                <table class="tablaUsuarios" style="margin-top: 0; border: none; width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 40px;">▶</th>
                            <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0;">Lib</th>
                            <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Producto</th>
                            <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Descripcion Producto</th>
                            <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Cantidad</th>
                            <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Costo</th>
                            <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Importe</th>
                            <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPartidasBody">
                    </tbody>
                </table>
            </div>

            <div class="panel-totales-entradas">
                <div class="card-total-indicador bg-total-qty shadow-soft">
                    <h3>Cantidad Total</h3>
                    <span id="totalCantidad">0</span>
                </div>

                <div class="card-total-indicador bg-total-kgs shadow-soft">
                    <h3>Total Kgs</h3>
                    <span id="totalKgs">0.00</span>
                </div>
                
                <div class="grupo-botones-captura">
                    <button class="btnAplicar btn-block-matriz"><i class="fas fa-save"></i> Guardar Entrada</button>
                    <button class="btnLimpiar btn-block-matriz"><i class="fas fa-eraser"></i> Limpiar Pantalla</button>
                </div>
            </div>

        </div>

    </div>
    
    <script src="../Services/funcionesEntradas.js"></script>
</body>
</html>