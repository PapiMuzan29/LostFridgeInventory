// ../Services/funcionesSalidas.js

let numeroPartida = 0; 

document.addEventListener("DOMContentLoaded", function() {
    console.log("🚀 El script unificado funcionesSalidas.js se ha cargado correctamente.");
    cargarClientesSelect();
    inicializarEscannerSalidas();
    inicializarBotonesSalidas(); 
    inicializarSelectorTipoDespacho();
});

async function cargarClientesSelect() {
    const select = document.getElementById('idCliente') || document.getElementById('idProveedor');
    if (!select) return;

    try {
        const respuesta = await fetch('../Controllers/salidasController.php?action=obtenerClientes');
        const clientes = await respuesta.json();
        
        if (Array.isArray(clientes)) {
            select.innerHTML = '<option value="">-- Seleccione un cliente --</option>';
            clientes.forEach(cli => {
                const option = document.createElement('option');
                option.value = cli.idCliente || cli.idProveedor || cli.id_cliente;
                option.textContent = cli.nombreCliente || cli.nombreProveedor || cli.nombre;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.warn("⚠️ No se pudieron cargar los clientes:", error);
    }
}

function inicializarSelectorTipoDespacho() {
    const selectTipo = document.getElementById('selectTipoDespacho');
    const contCajas = document.getElementById('contenedorEscannerCajas');
    const contCombos = document.getElementById('contenedorSelectorCombos');
    const tablaCabecera = document.getElementById('tablaCabecera');

    if (!selectTipo) return;

    selectTipo.addEventListener('change', function() {
        const tipo = this.value;
        const tbody = document.getElementById('tablaPartidasBody');
        if (tbody) tbody.innerHTML = ''; 
        document.getElementById('totalCantidad').textContent = '0';
        document.getElementById('totalKgs').textContent = '0.00';
        numeroPartida = 0;

        const labelSelect = document.querySelector('#contenedorSelectorCombos label');

        if (tipo === 'combos') {
            if (contCajas) contCajas.style.display = 'none';
            if (contCombos) contCombos.style.display = 'flex';
            document.getElementById('labelTotalCantidad').textContent = 'Total Combos Salientes';
            if (labelSelect) labelSelect.textContent = 'Seleccionar Combo:';
            
            tablaCabecera.innerHTML = `
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">▶</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; width: 45%;">Lote / Combo</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0; width: 40%;">Peso (Kg)</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 80px;">Quitar</th>
                </tr>
            `;
            cargarCombosDisponibles();
        } else if (tipo === 'manteca') {
            if (contCajas) contCajas.style.display = 'none';
            if (contCombos) contCombos.style.display = 'flex';
            document.getElementById('labelTotalCantidad').textContent = 'Total Botes';
            if (labelSelect) labelSelect.textContent = 'Seleccionar Manteca:';
            
            tablaCabecera.innerHTML = `
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 50px;">▶</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Código</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Producto</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0;">Cantidad (Botes)</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0;">Total Kgs</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">Quitar</th>
                </tr>
            `;
            cargarMantecaDisponibles();
        } else {
            if (contCajas) contCajas.style.display = 'flex';
            if (contCombos) contCombos.style.display = 'none';
            document.getElementById('labelTotalCantidad').textContent = 'Total Cajas Salientes';
            
            tablaCabecera.innerHTML = `
                <tr style="background: #f8fafc;">
                    <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 40px;">▶</th>
                    <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0;">Estado</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Código</th>
                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;">Descripción</th>
                    <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Cajas</th>
                    <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e2e8f0;">Kgs</th>
                    <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">Quitar</th>
                </tr>
            `;
        }
    });
}

async function cargarCombosDisponibles() {
    const selectCombo = document.getElementById('selectComboInventario');
    if (!selectCombo) return;

    try {
        const respuesta = await fetch('../Controllers/salidasController.php?action=obtenerCombosDisponibles');
        const combos = await respuesta.json();

        console.log("🔍 Datos crudos recibidos de combos:", combos);

        selectCombo.innerHTML = '<option value="">-- Seleccione un combo disponible --</option>';
        if (Array.isArray(combos)) {
            combos.forEach((combo, index) => {
                const opt = document.createElement('option');
                
                const idLoteVal = combo.idLote || combo.id_lote || combo.idCombo || combo.id || combo.lote_id || (index + 1);
                const idProdVal = combo.idProducto || combo.id_producto || combo.producto_id || 0;
                
                opt.value = idLoteVal;
                opt.dataset.idLote = idLoteVal;
                opt.dataset.idProducto = idProdVal;
                opt.dataset.pesoBruto = combo.pesoActual || combo.pesoBruto || combo.peso || 0;
                opt.dataset.codigoLote = combo.codigoLote || combo.codigo || combo.lote || `LOTE-${idLoteVal}`;
                
                opt.textContent = `Lote: ${opt.dataset.codigoLote} — ${combo.nombreProducto || combo.nombre || 'Combo'} (${opt.dataset.pesoBruto} kg)`;
                selectCombo.appendChild(opt);
            });
        }
    } catch (error) {
        console.warn("⚠️ Error al cargar combos disponibles:", error);
    }

    selectCombo.onchange = function() {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption.value) return;

        const idLote = parseInt(selectedOption.dataset.idLote) || parseInt(selectedOption.value) || 1;
        const idProducto = parseInt(selectedOption.dataset.idProducto) || 0;
        const codigoLote = selectedOption.dataset.codigoLote;
        const pesoBruto = parseFloat(selectedOption.dataset.pesoBruto) || 0;

        console.log("🟢 Combo seleccionado -> idLote:", idLote, "idProducto:", idProducto, "Código:", codigoLote);

        agregarFilaComboSalida(idLote, idProducto, codigoLote, pesoBruto);
        this.value = ""; 
    };
}

async function cargarMantecaDisponibles() {
    const selectCombo = document.getElementById('selectComboInventario');
    if (!selectCombo) return;

    try {
        const respuesta = await fetch('../Controllers/salidasController.php?action=obtenerMantecaDisponibles');
        const textoCrudo = await respuesta.text();
        
        let botes;
        try {
            botes = JSON.parse(textoCrudo);
        } catch (e) {
            console.error("Error al parsear JSON de mantecas:", textoCrudo);
            selectCombo.innerHTML = '<option value="">-- Error al cargar mantecas --</option>';
            return;
        }

        selectCombo.innerHTML = '<option value="">-- Seleccione tipo de manteca --</option>';
        if (Array.isArray(botes)) {
            if (botes.length === 0) {
                selectCombo.innerHTML = '<option value="">-- No hay manteca disponible en stock --</option>';
                return;
            }

            botes.forEach((bote, index) => {
                const opt = document.createElement('option');
                const idLoteVal = bote.idLote || bote.id_lote || bote.id || (index + 1);
                
                opt.value = idLoteVal;
                opt.dataset.idLote = idLoteVal;
                opt.dataset.idProducto = bote.idProducto || bote.id_producto || 0;
                
                let pesoUnitario = parseFloat(bote.totalCajas) > 0 ? (parseFloat(bote.totalPeso) / parseFloat(bote.totalCajas)) : 15;
                if (bote.nombreProducto.includes('10')) pesoUnitario = 10;
                if (bote.nombreProducto.includes('15')) pesoUnitario = 15;
                if (bote.nombreProducto.includes('18')) pesoUnitario = 18;

                opt.dataset.pesoUnitario = pesoUnitario;
                opt.dataset.codigoProducto = bote.codigoProducto;
                opt.dataset.nombreProducto = bote.nombreProducto;
                
                opt.textContent = `${bote.nombreProducto} (Stock: ${bote.totalCajas} botes / ${bote.totalPeso} kg)`;
                selectCombo.appendChild(opt);
            });
        }
    } catch (error) {
        console.warn("⚠️ Error de red al cargar manteca disponible:", error);
    }

    selectCombo.onchange = function() {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption.value) return;

        const idLote = parseInt(selectedOption.dataset.idLote) || parseInt(selectedOption.value) || 1;
        const idProducto = parseInt(selectedOption.dataset.idProducto) || 0;
        const codigo = selectedOption.dataset.codigoProducto;
        const nombre = selectedOption.dataset.nombreProducto;
        const pesoUnitario = parseFloat(selectedOption.dataset.pesoUnitario) || 15;

        agregarFilaMantecaSalida(idLote, idProducto, codigo, nombre, pesoUnitario);
        this.value = ""; 
    };
}

function inicializarEscannerSalidas() {
    const inputCodigo = document.getElementById("inputCodigoBarras");
    const selectCliente = document.getElementById('idCliente') || document.getElementById('idProveedor');

    if (!inputCodigo) return;

    let procesandoLectura = false;
    inputCodigo.focus();

    inputCodigo.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); 
            
            if (procesandoLectura) return; 
            procesandoLectura = true;

            const selectTipo = document.getElementById('selectTipoDespacho');
            if (selectTipo && (selectTipo.value === 'combos' || selectTipo.value === 'manteca')) {
                procesandoLectura = false;
                return; 
            }

            const trama = this.value.trim();
            if (!trama) {
                procesandoLectura = false;
                return;
            }

            const idCliente = selectCliente ? selectCliente.value : '';
            if (!idCliente) {
                alert("❌ Por favor, seleccione un cliente/proveedor primero.");
                procesandoLectura = false;
                return;
            }

            fetch(`../Controllers/salidasController.php?action=buscarProducto&codigo=${encodeURIComponent(trama)}&idCliente=${idCliente}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert("❌ " + data.error);
                        procesandoLectura = false;
                        return;
                    }

                    const pEntera = data.codigoEnteros || "0";
                    const pDecimal = data.codigoDecimales || "00";
                    const pesoFinal = parseFloat(`${pEntera}.${pDecimal}`) || parseFloat(data.peso) || 16.41;

                    agregarFilaSalida(data.codigoProducto || data.producto || "52", data.nombreProducto || data.descripcion || "Producto", pesoFinal);
                    procesandoLectura = false;
                })
                .catch(error => {
                    console.error("Error en la comunicación:", error);
                    alert("❌ Error de comunicación con el servidor.");
                    procesandoLectura = false;
                });

            this.value = ""; 
        }
    });
}

function esTablaCombos() {
    const selectTipo = document.getElementById('selectTipoDespacho');
    return selectTipo && selectTipo.value === 'combos';
}

function agregarFilaSalida(codigo, nombreProducto, cantidad) {
    const tbody = document.getElementById('tablaPartidasBody');
    if (!tbody) return;

    numeroPartida++; 
    const tr = document.createElement('tr');
    tr.style.background = "#ffffff";
    tr.style.borderBottom = "1px solid #e2e8f0";

    tr.innerHTML = `
        <td style="padding: 16px; text-align: center; color: #64748b;">${numeroPartida}</td>
        <td style="padding: 16px; text-align: center; color: #16a34a; font-weight: 600;">✓</td>
        <td class="partida-codigo" style="padding: 16px; font-weight: 600; text-align: left; color: #1e293b;">${codigo}</td>
        <td style="padding: 16px; text-align: left; color: #334155;">${nombreProducto}</td>
        <td style="padding: 16px; text-align: right;">
            <input type="number" class="partida-cajas" value="1" min="1" style="width: 60px; text-align: center; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px;" oninput="actualizarTotalesSalidas()">
        </td>
        <td class="partida-cantidad" style="padding: 16px; text-align: right; font-weight: 600; color: #0f172a;">${cantidad.toFixed(2)}</td>
        <td style="padding: 16px; text-align: center;">
            <button class="btn-borrar-partida" onclick="this.closest('tr').remove(); actualizarTotalesSalidas();" style="background: none; border: none; color: #ef4444; cursor: pointer;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    actualizarTotalesSalidas(); 
}

function agregarFilaComboSalida(idLote, idProducto, codigoLote, pesoActual) {
    const tbody = document.getElementById('tablaPartidasBody');
    if (!tbody) return;

    const filasExistentes = tbody.querySelectorAll('tr');
    for (let trExistente of filasExistentes) {
        if (trExistente.dataset.idLote === String(idLote)) {
            alert(`⚠️ El lote ${codigoLote} ya se encuentra agregado en la tabla.`);
            return;
        }
    }

    numeroPartida++; 
    const tr = document.createElement('tr');
    tr.style.background = "#ffffff";
    tr.style.borderBottom = "1px solid #e2e8f0";
    
    tr.dataset.idLote = idLote;
    tr.dataset.idProducto = idProducto;

    tr.innerHTML = `
        <td style="padding: 12px; text-align: center; color: #64748b;">${numeroPartida}</td>
        <td style="padding: 12px; font-weight: 600; color: #1e293b; text-align: left;">${codigoLote}</td>
        <td style="padding: 12px; text-align: right;">
            <input type="number" step="0.01" value="${pesoActual > 0 ? pesoActual.toFixed(2) : ''}" class="val-peso-neto" style="width: 180px; text-align: right; font-weight: bold; font-size: 15px; color: #16a34a; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="actualizarTotalesSalidas()">
        </td>
        <td style="padding: 12px; text-align: center;">
            <button class="btn-borrar-partida" onclick="removerFilaCombo(this, '${idLote}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);

    const selectCombo = document.getElementById('selectComboInventario');
    if (selectCombo) {
        const optionToHide = selectCombo.querySelector(`option[value="${idLote}"]`);
        if (optionToHide) optionToHide.style.display = 'none';
    }

    actualizarTotalesSalidas();
}

function agregarFilaMantecaSalida(idLote, idProducto, codigo, nombreProducto, pesoUnitario) {
    const tbody = document.getElementById('tablaPartidasBody');
    if (!tbody) return;

    const filasExistentes = tbody.querySelectorAll('tr');
    for (let trExistente of filasExistentes) {
        if (trExistente.dataset.idProducto === String(idProducto)) {
            alert(`⚠️ El producto ${nombreProducto} ya se encuentra en la tabla. Modifique la cantidad directamente.`);
            return;
        }
    }

    numeroPartida++; 
    const tr = document.createElement('tr');
    tr.style.background = "#ffffff";
    tr.style.borderBottom = "1px solid #e2e8f0";
    
    tr.dataset.idLote = idLote;
    tr.dataset.idProducto = idProducto;
    tr.dataset.pesoUnitario = pesoUnitario;

    tr.innerHTML = `
        <td style="padding: 12px; text-align: center; color: #64748b;">${numeroPartida}</td>
        <td style="padding: 12px; font-weight: 600; color: #1e293b; text-align: left;">${codigo}</td>
        <td style="padding: 12px; text-align: left; color: #334155;">${nombreProducto}</td>
        <td style="padding: 12px; text-align: right;">
            <input type="number" value="1" min="1" class="val-cantidad-botes" style="width: 70px; text-align: center; font-weight: bold; padding: 4px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="actualizarTotalesSalidas()"> botes
        </td>
        <td class="val-peso-total-manteca" style="padding: 12px; text-align: right; font-weight: bold; color: #16a34a;">${pesoUnitario.toFixed(2)} kg</td>
        <td style="padding: 12px; text-align: center;">
            <button class="btn-borrar-partida" onclick="this.closest('tr').remove(); actualizarTotalesSalidas();" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    actualizarTotalesSalidas();
}

function removerFilaCombo(button, idLote) {
    const tr = button.closest('tr');
    if (tr) {
        tr.remove();
        
        const selectCombo = document.getElementById('selectComboInventario');
        if (selectCombo) {
            const optionToShow = selectCombo.querySelector(`option[value="${idLote}"]`);
            if (optionToShow) optionToShow.style.display = 'block';
        }
        
        actualizarTotalesSalidas();
    }
}

function actualizarTotalesSalidas() {
    let acumuladorKgs = 0;
    let contadorItems = 0;
    const selectTipo = document.getElementById('selectTipoDespacho');
    const esManteca = selectTipo && selectTipo.value === 'manteca';

    if (esManteca) {
        document.querySelectorAll('#tablaPartidasBody tr').forEach(tr => {
            const botes = parseInt(tr.querySelector('.val-cantidad-botes')?.value) || 0;
            const pesoUnitario = parseFloat(tr.dataset.pesoUnitario) || 0;
            const pesoTotalFila = botes * pesoUnitario;
            
            const cellPeso = tr.querySelector('.val-peso-total-manteca');
            if (cellPeso) cellPeso.textContent = pesoTotalFila.toFixed(2) + ' kg';

            acumuladorKgs += pesoTotalFila;
            contadorItems += botes;
        });
    } else if (esTablaCombos()) {
        document.querySelectorAll('#tablaPartidasBody tr').forEach(tr => {
            acumuladorKgs += parseFloat(tr.querySelector('.val-peso-neto')?.value) || 0;
            contadorItems++;
        });
    } else {
        document.querySelectorAll('#tablaPartidasBody tr').forEach(tr => {
            acumuladorKgs += parseFloat(tr.querySelector('.partida-cantidad')?.innerText) || 0;
            contadorItems += parseInt(tr.querySelector('.partida-cajas')?.value) || 1;
        });
    }

    const divQty = document.getElementById('totalCantidad');
    const divKgs = document.getElementById('totalKgs');

    if (divQty) divQty.textContent = contadorItems;
    if (divKgs) divKgs.textContent = acumuladorKgs.toFixed(2);
}

function inicializarBotonesSalidas() {
    const btnGuardar = document.getElementById("btnGuardarSalida") || document.querySelector(".btnAplicar");
    const btnLimpiar = document.getElementById("btnLimpiarPantalla") || document.querySelector(".btnLimpiar");

    if (btnLimpiar) btnLimpiar.addEventListener("click", () => window.location.reload());

    if (btnGuardar) {
        btnGuardar.addEventListener("click", async function() {
            const idCliente = document.getElementById('idCliente')?.value || document.getElementById('idProveedor')?.value;
            if (!idCliente) {
                alert("❌ Seleccione un cliente o proveedor.");
                return;
            }

            const selectConcepto = document.getElementById('selectConceptoSalida');
            const inputConcepto = document.querySelector("input[placeholder*='Concepto']");
            const conceptoSeleccionado = selectConcepto ? selectConcepto.value : (inputConcepto ? inputConcepto.value : "Venta");
            
            if (!conceptoSeleccionado) {
                alert("❌ Seleccione o escriba un concepto de salida.");
                return;
            }

            const selectTipo = document.getElementById('selectTipoDespacho');
            const tipoDespacho = selectTipo ? selectTipo.value : 'cajas';

            const filas = document.querySelectorAll('#tablaPartidasBody tr');
            if (filas.length === 0) {
                alert("❌ No hay productos para guardar.");
                return;
            }

            let detalle = [];
            filas.forEach((fila, index) => {
                if (tipoDespacho === 'manteca') {
                    const botesVal = parseInt(fila.querySelector('.val-cantidad-botes')?.value) || 1;
                    const pesoUnitario = parseFloat(fila.dataset.pesoUnitario) || 0;
                    const idProducto = parseInt(fila.dataset.idProducto) || 0;
                    const idLote = parseInt(fila.dataset.idLote) || 0;
                    
                    detalle.push({ 
                        partida: index + 1, 
                        id_lote: idLote,
                        id_producto: idProducto,
                        cantidad: botesVal,
                        cajas: botesVal,
                        kgs: botesVal * pesoUnitario 
                    });
                } else if (esTablaCombos()) {
                    const pesoNetoVal = parseFloat(fila.querySelector('.val-peso-neto')?.value) || 0;
                    const idLote = parseInt(fila.dataset.idLote) || 0;
                    const idProducto = parseInt(fila.dataset.idProducto) || 0;
                    
                    console.log(`📦 Empaquetando Fila ${index + 1} -> id_lote: ${idLote}, id_producto: ${idProducto}`);

                    detalle.push({ 
                        partida: index + 1, 
                        id_lote: idLote,
                        id_producto: idProducto,
                        cantidad: 1,
                        kgs: pesoNetoVal 
                    });
                } else {
                    const cantidadKgs = parseFloat(fila.querySelector('.partida-cantidad')?.innerText) || 0;
                    const cajasVal = parseInt(fila.querySelector('.partida-cajas')?.value) || 1;
                    detalle.push({
                        partida: index + 1,
                        codigo_producto: fila.querySelector('.partida-codigo')?.innerText.trim() || 'GENERAL',
                        kgs: cantidadKgs,
                        cantidad: cajasVal,
                        id_lote: 0,
                        id_producto: 0
                    });
                }
            });

            const totalKgs = parseFloat(document.getElementById('totalKgs')?.innerText) || 0;
            const totalCajas = parseInt(document.getElementById('totalCantidad')?.innerText) || filas.length;

            const payload = {
                concepto: conceptoSeleccionado,
                id_cliente: idCliente,
                tipo_despacho: tipoDespacho,
                total_kgs: totalKgs,
                total_items: totalCajas,
                id_almacen: 1,
                detalle: detalle
            };

            console.log("📤 Detalle de los ítems a enviar:", JSON.stringify(payload.detalle, null, 2));

            try {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                const response = await fetch("../Controllers/salidasController.php?action=guardarSalida", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/json; charset=utf-8" 
                    },
                    body: JSON.stringify(payload)
                });

                const textResponse = await response.text();
                let result;
                try {
                    result = JSON.parse(textResponse);
                } catch (e) {
                    console.error("Respuesta cruda del servidor:", textResponse);
                    alert("❌ Error crítico en PHP (Revisa la consola F12): " + textResponse.substring(0, 150));
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save"></i> Procesar Salida';
                    return;
                }

                if (result.success) {
                    alert(`✅ ${result.mensaje || 'Salida guardada correctamente.'}`);
                    window.location.reload(); 
                } else {
                    alert(`❌ Error del servidor: ${result.error || result.message || 'Desconocido'}`);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save"></i> Procesar Salida';
                }
            } catch (error) {
                console.error("Error al guardar salida:", error);
                alert("❌ Error de conexión al guardar la salida.");
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> Procesar Salida';
            }
        });
    }
}