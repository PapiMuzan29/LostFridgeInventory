<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

$idUsuarioActual = $_SESSION['idCuenta'] ?? $_SESSION['id_usuario'] ?? 1; 

try {
    require_once __DIR__ . '/../Services/entradasServicio.php';
    $entradasService = new entradasServicio();
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
        .formulario-matriz-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 16px 20px !important;
            width: 100% !important;
        }
        .grupo-campo {
            display: flex !important;
            flex-direction: column !important;
            gap: 6px !important;
        }
        .text-span-2 {
            grid-column: span 2 !important;
        }
        .captura-line-bar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 25px !important;
        }
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

        <!-- FORMULARIO ENVIO DE ENTRADAS -->
        <form id="formEntradaMatriz" action="../Services/guardarEntrada.php" method="POST">
            
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($idUsuarioActual); ?>">
            <input type="hidden" name="id_almacen" value="1">

            <div class="card-entrada shadow-soft">
                <div class="formulario-matriz-grid">
                    <div class="grupo-campo">
                        <label>Folio</label>
                        <input type="text" name="folio" class="input-readonly" value="21064" readonly>
                    </div>
                    
                    <div class="grupo-campo">
                        <label>Fecha de Captura</label>
                        <input type="date" name="fecha_captura" id="inputFecha" class="input-readonly" value="<?php echo date('Y-m-d'); ?>" readonly style="background-color: #e2e8f0; cursor: not-allowed;">
                    </div>

                    <div class="grupo-campo text-span-2">
                        <label>Concepto de Entrada</label>
                        <input type="text" name="concepto" class="input-captura" placeholder="Ej. Compra, Traspaso, Ajuste..." required>
                    </div>

                    <div class="grupo-campo text-span-2">
                        <label>Almacén / Cámara</label>
                        <select class="select-captura" disabled style="background-color: #e2e8f0; cursor: not-allowed; opacity: 0.8; color: #334155;">
                            <option value="1">(01) - EMBARQUES / Almacén Principal</option>
                        </select>
                    </div>

                    <div class="grupo-campo">
                        <label>Status</label>
                        <input type="text" name="status" class="input-readonly text-success-status font-bold" value="A" readonly>
                    </div>

                    <div class="grupo-campo">
                        <label>Proveedor</label>
                        <select class="select-captura" name="idProveedor" id="idProveedor" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; background-color: white;" required>
                            <option value="">-- Seleccione un proveedor --</option>
                            <?php if (!empty($proveedores)): ?>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo htmlspecialchars($prov['idProveedor']); ?>">
                                        <?php echo htmlspecialchars($prov['nombreProveedor']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- SELECTOR INTELIGENTE DE MODO DE CAPTURA -->
                    <div class="grupo-campo text-span-2">
                        <label><i class="fas fa-exchange-alt"></i> Modo de Captura de Entrada</label>
                        <select class="select-captura" id="selectModoCaptura" onchange="cambiarInterfazModo()" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; background-color: white; font-weight: 600; color: #0f172a;">
                            <option value="estandar">📦 Captura Estándar (Código de Barras / Artículos)</option>
                            <option value="Pierna de Cerdo">🥩 Módulo de Combos: Pierna de Cerdo</option>
                            <option value="Codillo de Cerdo">🍖 Módulo de Combos: Codillo de Cerdo</option>
                            <option value="Manteca">🧈 Módulo de Manteca (Botes y Bolsas)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- BARRA DE CÓDIGO DE BARRAS (SOLO PARA MODO ESTÁNDAR) -->
            <div id="seccionEstandarCodigo" class="card-entrada captura-line-bar shadow-soft">
                <div class="input-codigo-line" style="flex: 2; display: flex; align-items: center; gap: 12px;">
                    <label class="label-inline" style="font-weight: 700; white-space: nowrap;">Código de Barras:</label>
                    <div class="input-with-icon-bar" style="position: relative; width: 100%;">
                        <input type="text" id="inputCodigoBarras" class="input-captura" placeholder="Escanee el producto aquí..." style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <i class="fas fa-barcode" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                    </div>
                </div>

                <div class="input-costo-line" style="flex: 1; display: flex; align-items: center; gap: 12px;">
                    <label class="label-inline" style="font-weight: 700;">Costo:</label>
                    <input type="number" id="inputCosto" name="costo" class="input-captura" value="0.00" step="any" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px;">
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

            <!-- BARRA DE CONFIGURACIÓN RÁPIDA DE MANTECA (SOLO PARA MODO MANTECA) -->
            <div id="seccionMantecaControles" class="card-entrada shadow-soft" style="display: none; padding: 15px 20px; margin-bottom: 20px; background: #f8fafc; border: 1px solid #cbd5e1;">
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 180px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Código de Producto *</label>
                        <input type="text" id="inputCodigoManteca" class="input-captura" placeholder="Ej. MANTECA-18" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background: white;">
                    </div>
                    <div style="flex: 1; min-width: 220px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Presentación de Manteca:</label>
                        <select id="selectPresentacionManteca" class="select-captura" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background: white;">
                            <option value="18" selected>Bote de 18 kg</option>
                            <option value="15">Bote de 15 kg</option>
                            <option value="10">Bolsa de 10 kg</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 180px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Cantidad de Envases:</label>
                        <input type="number" id="inputCantidadManteca" class="input-captura" value="1" min="1" step="any" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background: white;">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="button" onclick="agregarFilaManteca()" class="btnAplicar" style="height: 38px; padding: 0 20px; background: #0f172a; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-plus"></i> Agregar a Partidas
                        </button>
                    </div>
                </div>
            </div>

            <div class="seccion-tabla-totales-grid">
                
                <!-- CONTENEDOR DINÁMICO: Tabla Estándar, Tabla de Combos o Tabla de Manteca -->
                <div class="tabla-partidas-container shadow-soft" id="contenedorTablaDinamica">
                    <!-- Tabla Estándar Inicial -->
                    <table class="tablaUsuarios" id="tablaEstandar" style="margin-top: 0; border: none; width: 100%; border-collapse: collapse;">
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
                        <h3>Total Kgs / Neto</h3>
                        <span id="totalKgs">0.00</span>
                    </div>
                    
                    <!-- BOTÓN DE EXPORTAR A EXCEL (SE ACTIVA EN MODO COMBOS) -->
                    <div id="panelBotonExcel" style="margin-top: 10px; margin-bottom: 10px; display: none;">
                        <button type="button" class="btn-excel-panel" onclick="exportarCombosExcel()">
                            <i class="fas fa-file-excel"></i> Descargar Formato Excel
                        </button>
                    </div>

                    <div class="grupo-botones-captura">
                        <button type="submit" class="btnAplicar btn-block-matriz"><i class="fas fa-save"></i> Guardar Entrada</button>
                        <button type="button" class="btnLimpiar btn-block-matriz"><i class="fas fa-eraser"></i> Limpiar Pantalla</button>
                    </div>
                </div>

            </div>
        </form>

    </div>
    
    <script src="../Services/funcionesEntradas.js"></script>
    <script src="../Services/tema.js"></script>

    <script>
        // Lógica para cambiar dinámicamente la interfaz en pantalla
        function cambiarInterfazModo() {
            const modo = document.getElementById('selectModoCaptura').value;
            const seccionEstandarCodigo = document.getElementById('seccionEstandarCodigo');
            const seccionMantecaControles = document.getElementById('seccionMantecaControles');
            const contenedorTabla = document.getElementById('contenedorTablaDinamica');
            const panelBotonExcel = document.getElementById('panelBotonExcel');

            // Reset visual controls
            seccionEstandarCodigo.style.display = 'none';
            seccionMantecaControles.style.display = 'none';
            panelBotonExcel.style.display = 'none';
            contenedorTabla.classList.remove('tabla-combos-scroll-container');

            if (modo === 'estandar') {
                seccionEstandarCodigo.style.display = 'flex';
                contenedorTabla.innerHTML = `
                    <table class="tablaUsuarios" id="tablaEstandar" style="margin-top: 0; border: none; width: 100%; border-collapse: collapse;">
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
                        <tbody id="tablaPartidasBody"></tbody>
                    </table>`;
            } else if (modo === 'Manteca') {
                seccionMantecaControles.style.display = 'block';
                contenedorTabla.innerHTML = `
                    <table class="tablaUsuarios" id="tablaManteca" style="margin-top: 0; border: none; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0;">Código</th>
                                <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Presentación</th>
                                <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Envases</th>
                                <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Total Kgs</th>
                                <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaMantecaBody">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">No hay partidas de manteca agregadas. Configura y da clic en Agregar.</td>
                            </tr>
                        </tbody>
                    </table>`;
            } else {
                // Modo Combos (Pierna o Codillo)
                panelBotonExcel.style.display = 'block';
                contenedorTabla.classList.add('tabla-combos-scroll-container');

                let htmlCombos = `
                    <table class="tabla-combos-interactiva">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>PESO BRUTO</th>
                                <th>LB TARA</th>
                                <th>KG TARA</th>
                                <th>PESO NETO</th>
                                <th>PESO ORIGEN</th>
                                <th>MERMA</th>
                                <th>1%</th>
                                <th>DIFERENCIA</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                for (let i = 1; i <= 50; i++) {
                    htmlCombos += `
                        <tr class="fila-combo" data-index="${i}">
                            <td><strong>${i}</strong></td>
                            <td><input type="number" step="any" class="input-combo-grid peso-bruto" oninput="calcularFilaCombo(${i})" value=""></td>
                            <td><input type="number" step="any" class="input-combo-grid lb-tara" value="58" oninput="calcularFilaCombo(${i})"></td>
                            <td><span class="kg-tara">0.00</span></td>
                            <td><span class="peso-neto font-bold text-success">0.00</span></td>
                            <td><input type="number" step="any" class="input-combo-grid peso-origen" oninput="calcularFilaCombo(${i})" value=""></td>
                            <td><span class="merma">0.00</span></td>
                            <td><span class="porcentaje">0.00</span></td>
                            <td><input type="number" step="any" class="input-combo-grid diferencia" value="0.00"></td>
                        </tr>
                    `;
                }

                htmlCombos += `
                        <tr class="fila-total-combo">
                            <td colspan="4" style="text-align: right;">TOTALES:</td>
                            <td><span id="sumaPesoNeto">0.00</span></td>
                            <td><span id="sumaPesoOrigen">0.00</span></td>
                            <td><span id="sumaMerma">0.00</span></td>
                            <td><span id="sumaPorcentaje">0.00</span></td>
                            <td><span id="sumaDiferencia">0.00</span></td>
                        </tr>
                        </tbody>
                    </table>
                `;

                contenedorTabla.innerHTML = htmlCombos;
            }
        }

        // Función matemática en tiempo real idéntica al formato físico para Combos
        function calcularFilaCombo(i) {
            const fila = document.querySelector(`.fila-combo[data-index="${i}"]`);
            if (!fila) return;

            const pesoBruto = parseFloat(fila.querySelector('.peso-bruto').value) || 0;
            const lbTara = parseFloat(fila.querySelector('.lb-tara').value) || 58;
            const pesoOrigen = parseFloat(fila.querySelector('.peso-origen').value) || 0;

            const kgTara = lbTara * 0.45359237;
            const pesoNeto = pesoBruto > 0 ? pesoBruto - kgTara : 0;
            const merma = pesoNeto > 0 && pesoOrigen > 0 ? pesoNeto - pesoOrigen : 0;
            const porcentaje = pesoNeto * 0.01;

            fila.querySelector('.kg-tara').textContent = kgTara.toFixed(2);
            fila.querySelector('.peso-neto').textContent = pesoNeto.toFixed(2);
            fila.querySelector('.merma').textContent = merma.toFixed(2);
            fila.querySelector('.porcentaje').textContent = porcentaje.toFixed(2);

            actualizarTotalesCombos();
        }

        function actualizarTotalesCombos() {
            let totalNeto = 0, totalOrigen = 0, totalMerma = 0, totalPorcentaje = 0, totalDif = 0;

            document.querySelectorAll('.fila-combo').forEach(fila => {
                totalNeto += parseFloat(fila.querySelector('.peso-neto').textContent) || 0;
                totalOrigen += parseFloat(fila.querySelector('.peso-origen').value) || 0;
                totalMerma += parseFloat(fila.querySelector('.merma').textContent) || 0;
                totalPorcentaje += parseFloat(fila.querySelector('.porcentaje').textContent) || 0;
                totalDif += parseFloat(fila.querySelector('.diferencia').value) || 0;
            });

            document.getElementById('sumaPesoNeto').textContent = totalNeto.toFixed(2);
            document.getElementById('sumaPesoOrigen').textContent = totalOrigen.toFixed(2);
            document.getElementById('sumaMerma').textContent = totalMerma.toFixed(2);
            document.getElementById('sumaPorcentaje').textContent = totalPorcentaje.toFixed(2);
            document.getElementById('sumaDiferencia').textContent = totalDif.toFixed(2);

            document.getElementById('totalKgs').textContent = totalNeto.toFixed(2);
        }

        async function exportarCombosExcel() {
            const proveedorSelect = document.getElementById('idProveedor');
            const proveedorTexto = proveedorSelect.options[proveedorSelect.selectedIndex]?.text || 'GENERAL';
            const tipoCorte = document.getElementById('selectModoCaptura').value;
            const fecha = document.getElementById('inputFecha')?.value || '';

            let combos = [];
            document.querySelectorAll('.fila-combo').forEach(fila => {
                combos.push({
                    pesoBruto: fila.querySelector('.peso-bruto').value || 0,
                    lbTara: fila.querySelector('.lb-tara').value || 58,
                    pesoOrigen: fila.querySelector('.peso-origen').value || 0,
                    diferencia: fila.querySelector('.diferencia').value || 0
                });
            });

            try {
                const respuesta = await fetch('../Controllers/entradasController.php?action=exportarExcelCombos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        proveedor: proveedorTexto, 
                        marca: tipoCorte, 
                        sello: '', 
                        remolque: '', 
                        tractor: '', 
                        fecha: fecha, 
                        combos: combos 
                    })
                });

                if (respuesta.ok) {
                    const blob = await respuesta.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `Control_Combos_${tipoCorte.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().slice(0, 10)}.xls`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                } else {
                    alert("❌ Error al generar el archivo Excel.");
                }
            } catch (error) {
                console.error("Error:", error);
                alert("❌ Ocurrió un fallo en la exportación.");
            }
        }
    </script>
</body>
</html>