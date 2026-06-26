// ../Services/funcionesEntradas.js

let numeroPartida = 0; 

document.addEventListener("DOMContentLoaded", function() {
    console.log("🚀 El script funcionesEntradas.js se ha cargado correctamente.");
    cargarProveedoresSelect();
    inicializarEscanner();
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

    if (!inputCodigo) {
        console.error("❌ ERROR: No se encontró el input del código de barras en el HTML.");
        return;
    }

    inputCodigo.focus();
    console.log("🎯 Lector inteligente híbrido activado.");

    inputCodigo.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); 
            
            const trama = this.value.trim();
            console.log("⌨️ Se presionó Enter. Trama detectada: ", trama);

            if (!trama) return;

            const idProveedor = selectProveedor ? selectProveedor.value : '';

            if (!idProveedor) {
                alert("❌ Por favor, seleccione un proveedor primero.");
                return;
            }

            // =========================================================================
            // 🔥 DETECTOR INTEGRADO PARA TRAMAS INDUSTRIALES CON '|' (EVITA FALLAS DE BD)
            // =========================================================================
            let cantidadCalculada = 1.00;
            let codigoParaBuscar = trama;

            if (trama.includes('|')) {
                console.log("🧩 Trama segmentada detectada. Extrayendo datos dinámicamente...");
                const bloques = trama.split('|');
                
                bloques.forEach(bloque => {
                    const textoLimpio = bloque.trim();
                    // Si empieza con P, es el código limpio del producto (Ej: P124603A1)
                    if (textoLimpio.startsWith('P')) {
                        codigoParaBuscar = textoLimpio.substring(1);
                    }
                    // Si empieza con Q, es el peso directo (Ej: Q 26.60)
                    if (textoLimpio.startsWith('Q')) {
                        const pesoTexto = textoLimpio.substring(1).trim();
                        cantidadCalculada = parseFloat(pesoTexto) || 0.00;
                    }
                });
                
                console.log(`🎯 Datos extraídos por JS -> Producto: ${codigoParaBuscar}, Peso: ${cantidadCalculada}`);
            }

            // Enviamos la clave limpia calculada al backend
            fetch(`../Controllers/entradasController.php?action=buscarProducto&codigo=${encodeURIComponent(codigoParaBuscar)}&idProveedor=${idProveedor}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.warn("⚠️ Producto no catalogado:", data.error);
                        agregarFilaTabla(codigoParaBuscar, `Código: ${codigoParaBuscar} (No Catalogado)`, cantidadCalculada);
                    } else {
                        // Si la trama NO tenía '|', calculamos el peso usando las posiciones de la BD que ya te servían
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
                    console.error("❌ Error de comunicación:", error);
                    agregarFilaTabla(codigoParaBuscar, "Error de Comunicación con Servidor", cantidadCalculada);
                });

            this.value = ""; 
        }
    });
}

function agregarFilaTabla(codigo, nombreProducto, cantidad) {
    console.log("✏️ Pintando fila en la tabla...");
    
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
        <td style="padding: 16px; font-weight: 600; text-align: left; color: #1e293b;">${codigo}</td>
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

    const divQty = document.querySelector('.bg-total-qty span') || document.getElementById('totalCantidad') || document.querySelector('.bg-total-qty');
    const divKgs = document.querySelector('.bg-total-kgs span') || document.getElementById('totalKgs') || document.querySelector('.bg-total-kgs');

    if (divQty) divQty.textContent = contadorPartidas;
    if (divKgs) divKgs.textContent = acumuladorKgs.toFixed(2);
}