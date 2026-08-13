// ../Services/funcionesEntradas.js

let numeroPartida = 0; 

document.addEventListener("DOMContentLoaded", function() {
    console.log("🚀 El script funcionesEntradas.js se ha cargado correctamente.");
    cargarProveedoresSelect();
    inicializarEscanner();
    inicializarBotones(); // 🔥 NUEVA FUNCIÓN AGREGADA
});

async function cargarProveedoresSelect() {
    const select = document.getElementById('idProveedor');
    if (!select) {
        console.error("❌ ERROR: No se encontró el elemento HTML con id='idProveedor'");
        return;
    }

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
            console.log("👥 Proveedores cargados exitosamente vía AJAX.");
        }
    } catch (error) {
        console.warn("⚠️ Nota: No se pudieron cargar proveedores de la BD (Modo local activo).", error);
    }
}

function inicializarEscanner() {
    const inputCodigo = document.querySelector(".input-with-icon-bar input") || document.querySelector(".input-captura");
    const selectProveedor = document.getElementById('idProveedor');

    if (!inputCodigo) return;

    inputCodigo.focus();
    console.log("🎯 Lector inteligente híbrido activado.");

    inputCodigo.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); 
            
            const trama = this.value.trim();
            if (!trama) return;

            const idProveedor = selectProveedor ? selectProveedor.value : '';
            if (!idProveedor) {
                alert("❌ Por favor, seleccione un proveedor primero.");
                return;
            }

            let cantidadCalculada = 1.00;
            let codigoParaBuscar = trama;

            if (trama.includes('|')) {
                const bloques = trama.split('|');
                bloques.forEach(bloque => {
                    const textoLimpio = bloque.trim();
                    if (textoLimpio.startsWith('P')) codigoParaBuscar = textoLimpio.substring(1);
                    if (textoLimpio.startsWith('Q')) {
                        const pesoTexto = textoLimpio.substring(1).trim();
                        cantidadCalculada = parseFloat(pesoTexto) || 0.00;
                    }
                });
            }

            fetch(`../Controllers/entradasController.php?action=buscarProducto&codigo=${encodeURIComponent(codigoParaBuscar)}&idProveedor=${idProveedor}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        agregarFilaTabla(codigoParaBuscar, `Código: ${codigoParaBuscar} (No Catalogado)`, cantidadCalculada);
                    } else {
                        if (!trama.includes('|')) {
                            const parteEntera = data.codigoEnteros || "0";
                            const parteDecimal = data.codigoDecimales || "00";
                            cantidadCalculada = parseFloat(`${parteEntera}.${parteDecimal}`) || 0.00;
                        }
                        const codigoFinal = data.codigoProducto || codigoParaBuscar;
                        agregarFilaTabla(codigoFinal, data.nombreProducto, cantidadCalculada);
                    }
                })
                .catch(error => {
                    agregarFilaTabla(codigoParaBuscar, "Error de Comunicación con Servidor", cantidadCalculada);
                });

            this.value = ""; 
        }
    });
}

function agregarFilaTabla(codigo, nombreProducto, cantidad) {
    const tbody = document.querySelector('.tablaUsuarios tbody') || document.querySelector('tbody');
    if (!tbody) return;

    const inputCosto = document.querySelector(".input-costo-line input") || document.getElementsByName("costo")[0] || document.querySelector("input[placeholder='0.00']");
    const costoActual = inputCosto ? parseFloat(inputCosto.value) || 0 : 0;
    const importeCalculado = cantidad * costoActual;

    numeroPartida++; 

    const tr = document.createElement('tr');
    tr.style.background = "#ffffff";
    tr.style.borderBottom = "1px solid #e2e8f0";

    tr.innerHTML = `
        <td style="padding: 16px; text-align: center; color: #64748b;">${numeroPartida}</td>
        <td style="padding: 16px; text-align: center; color: #16a34a; font-weight: 600;">✓</td>
        <td class="partida-codigo" style="padding: 16px; font-weight: 600; text-align: left; color: #1e293b;">${codigo}</td>
        <td style="padding: 16px; text-align: left; color: #334155;">${nombreProducto}</td>
        <td class="partida-cantidad" style="padding: 16px; text-align: right; font-weight: 600;">${cantidad.toFixed(2)}</td>
        <td style="padding: 16px; text-align: right;">${costoActual.toFixed(2)}</td>
        <td style="padding: 16px; text-align: right; font-weight: 600;">${importeCalculado.toFixed(2)}</td>
        <td style="padding: 16px; text-align: center;">
            <button class="btn-borrar-partida" onclick="this.closest('tr').remove(); actualizarTotales();" style="background: none; border: none; color: #ef4444; cursor: pointer;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    actualizarTotales(); 
}

function actualizarTotales() {
    const cantidades = document.querySelectorAll('.partida-cantidad');
    let acumuladorKgs = 0;
    let contadorPartidas = 0;

    cantidades.forEach(td => {
        acumuladorKgs += parseFloat(td.textContent) || 0;
        contadorPartidas++;
    });

    const divQty = document.querySelector('.bg-total-qty span') || document.getElementById('totalCantidad');
    const divKgs = document.querySelector('.bg-total-kgs span') || document.getElementById('totalKgs');

    if (divQty) divQty.textContent = contadorPartidas;
    if (divKgs) divKgs.textContent = acumuladorKgs.toFixed(2);
}

// =========================================================================
// 🔥 NUEVA LÓGICA DE GUARDADO EN BASE DE DATOS E INVENTARIO
// =========================================================================
function inicializarBotones() {
    const btnGuardar = document.querySelector(".btnAplicar");
    const btnLimpiar = document.querySelector(".btnLimpiar");

    if (btnLimpiar) {
        btnLimpiar.addEventListener("click", () => window.location.reload());
    }

    if (btnGuardar) {
        btnGuardar.addEventListener("click", async function() {
            const idProveedor = document.getElementById("idProveedor").value;
            const inputConcepto = document.querySelector("input[placeholder='Ej. Compra, Traspaso, Ajuste...']");
            const concepto = inputConcepto ? inputConcepto.value : "";
            const idAlmacen = 1; // Asumimos (01) EMBARQUES según tu HTML
            
            if (!idProveedor) {
                alert("❌ Seleccione un proveedor para guardar la entrada.");
                return;
            }

            const filas = document.querySelectorAll('.tablaUsuarios tbody tr');
            if (filas.length === 0) {
                alert("❌ No hay productos escaneados en la tabla.");
                return;
            }

            // Recolectar datos
            let detalle = [];
            let totalKgs = parseFloat(document.getElementById('totalKgs').innerText) || 0;
            let totalCajas = parseFloat(document.getElementById('totalCantidad').innerText) || 0;

            filas.forEach((fila, index) => {
                const codigo = fila.querySelector('.partida-codigo').innerText.trim();
                const kgs = parseFloat(fila.querySelector('.partida-cantidad').innerText);
                
                detalle.push({
                    partida: index + 1,
                    codigo_producto: codigo,
                    kgs: kgs,
                    cantidad_cajas: 1 // Cada escaneo es 1 caja física
                });
            });

            const payload = {
                id_proveedor: idProveedor,
                id_almacen: idAlmacen,
                concepto: concepto,
                total_kgs: totalKgs,
                total_cajas: totalCajas,
                detalle: detalle
            };

            try {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                // Enviamos a PHP
                const response = await fetch("../Controllers/entradasController.php?action=guardarEntrada", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Entrada guardada correctamente.\nFolio generado: ${result.folio}`);
                    window.location.reload(); // Recarga para empezar de nuevo
                } else {
                    alert(`❌ Error al guardar: ${result.error}`);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save"></i> Guardar Entrada';
                }
            } catch (error) {
                console.error("Error en la petición:", error);
                alert("❌ Ocurrió un error de conexión al guardar.");
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> Guardar Entrada';
            }
        });
    }
}