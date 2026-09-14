// ../Services/funcionesEntradas.js

let numeroPartida = 0; 

document.addEventListener("DOMContentLoaded", function() {
    cargarProveedoresSelect();
    initializarEscanner();
    inicializarBotones(); 

    // 🧈 Escuchar cuando cambie el proveedor para autocompletar el código de manteca
    const selectProveedor = document.getElementById('idProveedor');
    if (selectProveedor) {
        selectProveedor.addEventListener('change', async function() {
            const idProveedor = this.value;
            const inputCodigoManteca = document.getElementById('inputCodigoManteca');
            if (!idProveedor || !inputCodigoManteca) return;

            try {
                const response = await fetch(`../Controllers/entradasController.php?action=obtenerProductosPorProveedor&idProveedor=${idProveedor}`);
                const productos = await response.json();
                
                if (Array.isArray(productos) && productos.length > 0) {
                    const prodManteca = productos.find(p => 
                        (p.nombreProducto && p.nombreProducto.toLowerCase().includes('manteca')) ||
                        (p.descripcion && p.descripcion.toLowerCase().includes('manteca')) ||
                        (p.codigoProducto && p.codigoProducto.toUpperCase().includes('MANT'))
                    );

                    if (prodManteca) {
                        inputCodigoManteca.value = prodManteca.codigoProducto;
                    } else {
                        inputCodigoManteca.value = productos[0].codigoProducto || '';
                    }
                } else {
                    inputCodigoManteca.value = '';
                }
            } catch (error) {
                console.warn("No se pudo autocompletar el código de manteca:", error);
            }
        });
    }

    // Recalcular filas que ya existan estáticamente al cargar la página
    document.querySelectorAll('.tablaUsuarios tbody tr, tbody tr').forEach(tr => {
        recalcularFila(tr);
    });
});

async function cargarProveedoresSelect() {
    const select = document.getElementById('idProveedor');
    if (!select) return;
    try {
        const respuesta = await fetch('../Controllers/entradasController.php?action=obtenerProveedores');
        const proveedores = await respuesta.json();
        if (Array.isArray(proveedores)) {
            select.innerHTML = '<option value="">-- Seleccione un proveedor --</option>';
            proveedores.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov.idProveedor;
                option.textContent = prov.nombreProveedor;
                select.appendChild(option);
            });
        }
    } catch (error) { console.warn("Error proveedores:", error); }
}

function initializarEscanner() {
    const inputCodigo = document.querySelector(".input-with-icon-bar input") || document.querySelector(".input-captura");
    const selectProveedor = document.getElementById('idProveedor');

    if (!inputCodigo) return;
    inputCodigo.focus();

    inputCodigo.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); 
            const trama = this.value.trim();
            if (!trama) return;

            const idProveedor = selectProveedor ? selectProveedor.value : '';
            if (!idProveedor) { alert("❌ Seleccione un proveedor."); return; }

            let cantidadCalculada = 1.00;
            let codigoParaBuscar = trama;

            if (trama.includes('|')) {
                const bloques = trama.split('|');
                bloques.forEach(bloque => {
                    const textoLimpio = bloque.trim();
                    if (textoLimpio.startsWith('P')) {
                        codigoParaBuscar = textoLimpio.replace(/[^0-9]/g, '').substring(0, 6);
                    }
                    if (textoLimpio.startsWith('Q')) {
                        cantidadCalculada = parseFloat(textoLimpio.substring(1).trim()) || 0.00;
                    }
                });
            }

            fetch(`../Controllers/entradasController.php?action=buscarProducto&codigo=${encodeURIComponent(codigoParaBuscar)}&idProveedor=${idProveedor}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        agregarFilaTabla(codigoParaBuscar, `⚠️ ${data.error}`, cantidadCalculada, 0.00);
                    } else {
                        let pesoFinal = parseFloat(data.peso || data.codigoEnteros) || cantidadCalculada;
                        if (isNaN(pesoFinal) || pesoFinal <= 0) pesoFinal = 1.00;
                        
                        const codigoProd = data.codigoProducto || data.producto || codigoParaBuscar;
                        const descripcionProd = data.nombreProducto || data.descripcion || "Producto sin nombre";
                        const costoProd = parseFloat(data.costo) || 0.00;

                        agregarFilaTabla(codigoProd, descripcionProd, pesoFinal, costoProd);
                    }
                })
                .catch(error => {
                    agregarFilaTabla(codigoParaBuscar, "❌ Error de conexión", cantidadCalculada, 0.00);
                });

            this.value = ""; 
        }
    });
}

function agregarFilaTabla(codigo, nombreProducto, pesoBruto, costo) {
    const tbody = document.querySelector('.tablaUsuarios tbody') || document.querySelector('tbody');
    if (!tbody) return;

    const modoActual = document.getElementById('selectModoCaptura')?.value || 'estandar';
    numeroPartida++; 
    const tr = document.createElement('tr');

    if (modoActual === 'estandar') {
        tr.style.background = "#ffffff";
        tr.style.borderBottom = "1px solid #e2e8f0";

        const cantidadReal = pesoBruto > 0 ? pesoBruto : 1.00;
        const importeInicial = (cantidadReal * costo).toFixed(2);

        tr.innerHTML = `
            <td style="padding: 10px 8px; text-align: center;">▶</td>
            <td style="padding: 10px 8px; text-align: center;">${numeroPartida}</td>
            <td style="padding: 10px 8px; text-align: left;">${codigo}</td>
            <td style="padding: 10px 8px; text-align: left;">${nombreProducto}</td>
            <td style="padding: 10px 8px; text-align: right;">
                <input type="number" step="any" class="input-cantidad" value="${cantidadReal.toFixed(2)}" style="width: 90px; text-align: right; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px;">
            </td>
            <td style="padding: 10px 8px; text-align: right;">
                <input type="number" step="any" class="input-costo-partida" value="${costo.toFixed(2)}" style="width: 80px; text-align: right; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px;">
            </td>
            <td style="padding: 10px 8px; text-align: right; font-weight: bold;" class="td-importe">${importeInicial}</td>
            <td style="padding: 10px 8px; text-align: center;">
                <button type="button" class="btn-borrar-partida" onclick="this.closest('tr').remove(); actualizarTotalesEstandar();">
                    <i class="fas fa-trash"></i>
                </button>
                <input type="hidden" class="input-codigo-prod" value="${codigo}">
                <input type="hidden" class="input-desc-prod" value="${nombreProducto}">
            </td>
        `;
        tbody.appendChild(tr);
        actualizarTotalesEstandar();

    } else {
        tr.style.background = "#ffffff";
        tr.style.borderBottom = "1px solid #e2e8f0";

        tr.innerHTML = `
            <td style="padding: 6px; text-align: center;"><strong>${numeroPartida}</strong></td>
            <td style="padding: 6px; text-align: center;">
                <input type="number" step="any" class="input-combo-grid peso-bruto input-peso-bruto" value="${pesoBruto > 0 ? pesoBruto.toFixed(2) : ''}">
            </td>
            <td style="padding: 6px; text-align: center;">
                <input type="number" step="any" class="input-combo-grid lb-tara input-lb-tara" value="58">
            </td>
            <td style="padding: 6px; text-align: right;" class="kg-tara td-kg-tara">0.00</td>
            <td style="padding: 6px; text-align: right; font-weight: bold;" class="peso-neto text-success td-peso-neto">0.00</td>
            <td style="padding: 6px; text-align: center;">
                <input type="number" step="any" class="input-combo-grid peso-origen input-peso-origen" value="">
            </td>
            <td style="padding: 6px; text-align: right;" class="merma td-merma">0.00</td>
            <td style="padding: 6px; text-align: right;" class="porcentaje td-porcentaje">0.00</td>
            <td style="padding: 6px; text-align: center;">
                <input type="number" step="any" class="input-combo-grid diferencia input-diferencia" value="0.00">
            </td>
            <input type="hidden" class="input-codigo-prod" value="${codigo}">
            <input type="hidden" class="input-desc-prod" value="${nombreProducto}">
            <input type="hidden" class="input-costo" value="${costo.toFixed(2)}">
        `;
        tbody.appendChild(tr);
        recalcularFila(tr);
        actualizarTotales();
    }
}

// 🧈 AGREGAR FILA DE MANTECA TEMPORALMENTE EN PANTALLA
function agregarFilaManteca() {
    const inputCod = document.getElementById('inputCodigoManteca');
    const codigoProd = inputCod ? inputCod.value.trim() : '';
    
    if (!codigoProd) {
        alert("❌ Por favor ingresa o selecciona el código del producto asociado.");
        if(inputCod) inputCod.focus();
        return;
    }

    const selectPres = document.getElementById('selectPresentacionManteca');
    const kgsUnitario = parseFloat(selectPres.value);
    const nombrePres = selectPres.options[selectPres.selectedIndex].text;
    const cantidad = parseInt(document.getElementById('inputCantidadManteca').value) || 0;

    if (cantidad <= 0) {
        alert("❌ Por favor ingresa una cantidad válida de envases.");
        return;
    }

    const totalKgs = cantidad * kgsUnitario;
    const tbody = document.getElementById('tablaMantecaBody');
    if (!tbody) return;

    if (tbody.querySelector('td[colspan]')) {
        tbody.innerHTML = '';
    }

    const tr = document.createElement('tr');
    tr.style.background = "#ffffff";
    tr.style.borderBottom = "1px solid #e2e8f0";
    tr.innerHTML = `
        <td style="padding: 10px 8px; text-align: center; font-weight: 600;">${codigoProd}</td>
        <td style="padding: 10px 8px; text-align: left;">${nombrePres}</td>
        <td style="padding: 10px 8px; text-align: right;">
            <input type="number" step="any" class="input-manteca-cant" value="${cantidad}" style="width: 80px; text-align: right; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px;" readonly>
        </td>
        <td style="padding: 10px 8px; text-align: right; font-weight: bold;" class="manteca-fila-total">${totalKgs.toFixed(2)} kg</td>
        <td style="padding: 10px 8px; text-align: center;">
            <button type="button" class="btn-borrar-partida" onclick="this.closest('tr').remove(); recalcularTotalesManteca();">
                <i class="fas fa-trash"></i>
            </button>
            <input type="hidden" class="input-manteca-codigo" value="${codigoProd}">
            <input type="hidden" class="input-manteca-desc" value="${nombrePres}">
            <input type="hidden" class="input-manteca-kgsunit" value="${kgsUnitario}">
        </td>
    `;
    tbody.appendChild(tr);
    recalcularTotalesManteca();
}

function recalcularTotalesManteca() {
    let totalEnvases = 0;
    let totalKgsGeneral = 0;

    document.querySelectorAll('#tablaMantecaBody tr').forEach(tr => {
        const inputCant = tr.querySelector('.input-manteca-cant');
        const spanTotal = tr.querySelector('.manteca-fila-total');
        if (inputCant && spanTotal) {
            totalEnvases += parseInt(inputCant.value) || 0;
            totalKgsGeneral += parseFloat(spanTotal.textContent) || 0;
        }
    });

    const spanQty = document.getElementById('totalCantidad');
    const spanKgs = document.getElementById('totalKgs');
    if (spanQty) spanQty.textContent = totalEnvases;
    if (spanKgs) spanKgs.textContent = totalKgsGeneral.toFixed(2);
}

document.addEventListener('input', function(e) {
    const tr = e.target.closest('tr');
    if (tr) {
        const modoActual = document.getElementById('selectModoCaptura')?.value;
        if (modoActual === 'estandar') {
            actualizarTotalesEstandar();
        } else if (modoActual !== 'Manteca') {
            recalcularFila(tr);
        }
    }
});

function recalcularFila(tr) {
    if (!tr) return;

    const inputs = tr.querySelectorAll('input');
    const celdas = tr.querySelectorAll('td');

    const inputBruto = tr.querySelector('.input-peso-bruto') || inputs[0];
    const inputLbTara = tr.querySelector('.input-lb-tara') || inputs[1];
    const inputOrigen = tr.querySelector('.input-peso-origen') || inputs[2];
    const inputDiferencia = tr.querySelector('.input-diferencia') || inputs[3];

    const tdKgTara = celdas[3] || tr.querySelector('.td-kg-tara');
    const tdPesoNeto = celdas[4] || tr.querySelector('.td-peso-neto');
    const tdMerma = celdas[6] || tr.querySelector('.td-merma');
    const tdPorcentaje = celdas[7] || tr.querySelector('.td-porcentaje');

    if (!inputBruto) return;

    const pesoBruto = parseFloat(inputBruto.value) || 0;
    const lbTara = parseFloat(inputLbTara ? inputLbTara.value : 58) || 0;
    const kgTara = lbTara * 0.45359237;
    const pesoNeto = pesoBruto - kgTara;
    const pesoOrigen = parseFloat(inputOrigen ? inputOrigen.value : 0) || 0;
    const merma = (pesoOrigen > 0 && pesoNeto > 0) ? (pesoOrigen - pesoNeto) : 0;
    const porcentaje1 = pesoNeto > 0 ? (pesoNeto * 0.01) : 0;
    const diferencia = porcentaje1 - merma;

    if (tdKgTara) tdKgTara.textContent = kgTara.toFixed(2);
    if (tdPesoNeto) tdPesoNeto.textContent = pesoNeto > 0 ? pesoNeto.toFixed(2) : "0.00";
    if (tdMerma) tdMerma.textContent = merma.toFixed(2);
    if (tdPorcentaje) tdPorcentaje.textContent = porcentaje1.toFixed(2);
    
    if (inputDiferencia) {
        inputDiferencia.value = diferencia.toFixed(2);
        inputDiferencia.style.color = diferencia >= 0 ? '#16a34a' : '#dc2626';
    }

    actualizarTotales();
}

function actualizarTotales() {
    let acumuladorBruto = 0;
    document.querySelectorAll('.tablaUsuarios tbody tr, tbody tr').forEach(tr => {
        const inputBruto = tr.querySelector('.input-peso-bruto') || tr.querySelectorAll('input')[0];
        if (inputBruto) {
            acumuladorBruto += parseFloat(inputBruto.value) || 0;
        }
    });
    
    const divKgs = document.querySelector('#totalKgs') || document.querySelector('.bg-total-kgs span');
    if (divKgs) divKgs.textContent = acumuladorBruto.toFixed(2);
}

function actualizarTotalesEstandar() {
    let totalCantidad = 0;
    let totalKgs = 0;

    document.querySelectorAll('#tablaEstandar tbody tr').forEach(tr => {
        const cantidadInput = tr.querySelector('.input-cantidad');
        const costoInput = tr.querySelector('.input-costo-partida');
        const tdImporte = tr.querySelector('.td-importe');

        const cantidad = parseFloat(cantidadInput?.value) || 0;
        const costo = parseFloat(costoInput?.value) || 0;
        const importe = cantidad * costo;
        if (tdImporte) tdImporte.textContent = importe.toFixed(2);

        totalCantidad += 1;
        totalKgs += cantidad;
    });

    const spanQty = document.getElementById('totalCantidad');
    const spanKgs = document.getElementById('totalKgs');
    if (spanQty) spanQty.textContent = totalCantidad;
    if (spanKgs) spanKgs.textContent = totalKgs.toFixed(2);
}

function inicializarBotones() {
    const formEntrada = document.getElementById("formEntradaMatriz");
    const btnLimpiar = document.querySelector(".btnLimpiar") || document.getElementById("btnLimpiarPantalla");

    if (btnLimpiar) {
        btnLimpiar.addEventListener("click", () => window.location.reload());
    }

    if (formEntrada) {
        formEntrada.addEventListener("submit", async function(e) {
            e.preventDefault(); 

            const idProveedor = document.getElementById("idProveedor").value;
            if (!idProveedor) { alert("❌ Seleccione un proveedor."); return; }

            const modoActual = document.getElementById('selectModoCaptura')?.value || 'estandar';
            let filas = [];

            if (modoActual === 'Manteca') {
                filas = document.querySelectorAll('#tablaMantecaBody tr');
            } else if (modoActual === 'estandar') {
                filas = document.querySelectorAll('#tablaEstandar tbody tr');
            } else {
                filas = document.querySelectorAll('.tablaUsuarios tbody tr, tbody tr');
            }

            if (filas.length === 0 || (filas.length === 1 && filas[0].querySelector('td[colspan]'))) { 
                alert("❌ La tabla está vacía. Agrega al menos un producto."); 
                return; 
            }

            let detalle = [];
            let totalPesoBrutoGeneral = 0;
            let totalPesoOrigenGeneral = 0;

            if (modoActual === 'Manteca') {
                filas.forEach((fila) => {
                    const inputCant = fila.querySelector('.input-manteca-cant');
                    const spanTotal = fila.querySelector('.manteca-fila-total');
                    const inputCodigo = fila.querySelector('.input-manteca-codigo');
                    const inputDesc = fila.querySelector('.input-manteca-desc');
                    const inputKgsUnit = fila.querySelector('.input-manteca-kgsunit');

                    if (inputCant && spanTotal) {
                        const cantVal = parseInt(inputCant.value) || 0;
                        const totalFilaKgs = parseFloat(spanTotal.textContent) || 0;
                        const codigoVal = inputCodigo ? inputCodigo.value : "";
                        const descVal = inputDesc ? inputDesc.value : "Manteca";
                        const kgsUnitVal = parseFloat(inputKgsUnit?.value) || 0;

                        if (cantVal > 0) {
                            totalPesoBrutoGeneral += totalFilaKgs;
                            for(let i = 0; i < cantVal; i++) {
                                detalle.push({
                                    partida: detalle.length + 1,
                                    codigo_producto: codigoVal,
                                    descripcion: descVal,
                                    cantidad: 1,
                                    costo: 0,
                                    kgs: kgsUnitVal
                                });
                            }
                        }
                    }
                });
            } else if (modoActual === 'estandar') {
                filas.forEach((fila, index) => {
                    const inputCant = fila.querySelector('.input-cantidad');
                    const inputCosto = fila.querySelector('.input-costo-partida');
                    const cantVal = parseFloat(inputCant?.value) || 0;
                    const costoVal = parseFloat(inputCosto?.value) || 0;
                    const codigoVal = fila.querySelector('.input-codigo-prod')?.value || '';
                    const descVal = fila.querySelector('.input-desc-prod')?.value || '';

                    if (cantVal > 0) {
                        totalPesoBrutoGeneral += cantVal;
                        detalle.push({
                            partida: index + 1,
                            codigo_producto: codigoVal,
                            descripcion: descVal,
                            cantidad: 1,
                            costo: costoVal,
                            kgs: cantVal
                        });
                    }
                });
            } else {
                filas.forEach((fila, index) => {
                    const inputBruto = fila.querySelector('.input-peso-bruto') || fila.querySelectorAll('input')[0];
                    const inputLbTara = fila.querySelector('.input-lb-tara') || fila.querySelectorAll('input')[1];
                    const inputOrigen = fila.querySelector('.input-peso-origen') || fila.querySelectorAll('input')[2];
                    const inputDiferencia = fila.querySelector('.input-diferencia') || fila.querySelectorAll('input')[3];

                    const pesoBrutoVal = parseFloat(inputBruto?.value) || 0;
                    const lbTaraVal = parseFloat(inputLbTara?.value) || 58;
                    const pesoOrigenVal = parseFloat(inputOrigen?.value) || 0;
                    const diferenciaVal = parseFloat(inputDiferencia?.value) || 0;
                    const codigoVal = fila.querySelector('.input-codigo-prod')?.value || '';
                    const descVal = fila.querySelector('.input-desc-prod')?.value || '';
                    const costoVal = parseFloat(fila.querySelector('.input-costo')?.value) || 0;

                    if (pesoBrutoVal > 0 || pesoOrigenVal > 0) {
                        totalPesoBrutoGeneral += pesoBrutoVal;
                        totalPesoOrigenGeneral += pesoOrigenVal;

                        detalle.push({
                            partida: index + 1,
                            codigo_producto: codigoVal,
                            descripcion: descVal,
                            cantidad: 1, 
                            costo: costoVal,
                            peso_bruto: pesoBrutoVal, 
                            lb_tara: lbTaraVal,        
                            peso_origen: pesoOrigenVal,
                            diferencia: diferenciaVal,
                            kgs: pesoBrutoVal
                        });
                    }
                });
            }

            if (detalle.length === 0) {
                alert("❌ No hay productos con cantidades o pesos capturados para guardar.");
                return;
            }

            const payload = {
                id_proveedor: idProveedor,
                id_almacen: 1,
                total_kgs: totalPesoBrutoGeneral,
                peso_origen: totalPesoOrigenGeneral, 
                detalle: detalle
            };
            
            const btnGuardarSubmit = formEntrada.querySelector('button[type="submit"]');

            try {
                if (btnGuardarSubmit) {
                    btnGuardarSubmit.disabled = true;
                    btnGuardarSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                }

                const endpoint = (modoActual === 'estandar' || modoActual === 'Manteca') ? "guardarEntrada" : "guardarEntradaCombos";
                const response = await fetch(`../Controllers/entradasController.php?action=${endpoint}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const textResponse = await response.text();
                let result;
                try {
                    result = JSON.parse(textResponse);
                } catch (e) {
                    throw new Error("El servidor devolvió texto en lugar de JSON.");
                }

                if (result.success) {
                    alert(`✅ Entrada registrada correctamente. Folio: ${result.folio}`);
                    window.location.reload(); 
                } else {
                    alert("❌ Error: " + (result.error || "Desconocido"));
                    if (btnGuardarSubmit) {
                        btnGuardarSubmit.disabled = false;
                        btnGuardarSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Entrada';
                    }
                }
            } catch (error) {
                alert("❌ Error al guardar: " + error.message);
                if (btnGuardarSubmit) {
                    btnGuardarSubmit.disabled = false;
                    btnGuardarSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Entrada';
                }
            }
        });
    }
}