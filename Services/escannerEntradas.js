// ../Services/funcionesEntradas.js

let numeroPartida = 0; // Contador autoincremental para las filas de la tabla

/**
 * 🚀 FUNCIONES EXCLUSIVAS DEL MÓDULO DE ENTRADAS
 */
document.addEventListener("DOMContentLoaded", function() {
    cargarProveedoresSelect();
    inicializarEscanner();
});

/**
 * 👥 Obtiene los proveedores del controlador y los inyecta junto con su configuración de QR
 */
async function cargarProveedoresSelect() {
    const select = document.getElementById('idProveedor');
    if (!select) return;

    try {
        const respuesta = await fetch('../Controllers/entradasController.php?action=obtenerProveedores');
        const proveedores = await respuesta.json();
        
        if (Array.isArray(proveedores)) {
            // Limpiamos opciones antiguas dejando solo la de por defecto
            select.innerHTML = '<option value="">-- Seleccione un proveedor --</option>';
            
            proveedores.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov.idProveedor;
                option.textContent = prov.nombreProveedor;
                
                // Inyectamos dinámicamente la configuración del QR guardada en tu BD
                option.setAttribute("data-prod-pos", prov.codigoBarrasProductosPosicion ?? 0);
                option.setAttribute("data-prod-lon", prov.codigoBarrasProductosLongitud ?? 0);
                option.setAttribute("data-kg-pos", prov.codigoBarrasEnterosPosicion ?? 0);
                option.setAttribute("data-kg-lon", prov.codigoBarrasEnterosLongitud ?? 0);
                option.setAttribute("data-gr-pos", prov.codigoBarrasDecimalesPosicion ?? 0);
                option.setAttribute("data-gr-lon", prov.codigoBarrasDecimalesLongitud ?? 0);
                
                select.appendChild(option);
            });
        } else if (proveedores.error) {
            console.error("Error devuelto por el servidor:", proveedores.error);
        }
    } catch (error) {
        error_log("Error crítico en la petición Fetch de proveedores:", error);
    }
}

/**
 * 🎛️ Lógica de captura del Escáner (QR y Lineal)
 */
function inicializarEscanner() {
    const inputCodigo = document.querySelector(".input-with-icon-bar input");
    const selectProveedor = document.getElementById('idProveedor');

    if (inputCodigo) {
        inputCodigo.focus(); // Auto-focus inicial

        inputCodigo.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                e.preventDefault(); 
                
                const trama = this.value.trim();
                if (!trama) return;

                const idProveedor = selectProveedor ? selectProveedor.value : '';

                if (!idProveedor) {
                    alert("Por favor, seleccione un proveedor antes de escanear.");
                    this.value = "";
                    return;
                }
                
                // =========================================================================
                // 1. MODO QR INDUSTRIAL (Si contiene '|')
                // =========================================================================
                if (trama.includes('|')) {
                    const partes = trama.split('|');
                    let codigoProducto = "";
                    let peso = 0;

                    partes.forEach(parte => {
                        parte = parte.trim();
                        if (parte.startsWith("P")) {
                            codigoProducto = parte.substring(1);
                        } else if (parte.startsWith("Q")) {
                            peso = parseFloat(parte.substring(1).trim()) || 0;
                        }
                    });

                    console.log("Datos QR procesados:", { codigoProducto, peso });

                    // Consultamos los datos reales del producto al controlador
                    fetch(`../Controllers/entradasController.php?action=buscarProducto&codigo=${encodeURIComponent(codigoProducto)}&idProveedor=${idProveedor}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                // Fallback: si no lo encuentra en catálogo, lo agrega con nombre genérico para que no se detenga
                                agregarFilaTabla(codigoProducto, "Producto QR (No catalogado)", peso);
                            } else {
                                agregarFilaTabla(codigoProducto, data.nombreProducto, peso);
                            }
                        })
                        .catch(() => {
                            agregarFilaTabla(codigoProducto, "Caja QR - Fallback Local", peso);
                        });

                    this.value = ""; 
                } 
                // =========================================================================
                // 2. MODO LINEAL DINÁMICO (Configuración por posiciones)
                // =========================================================================
                else {
                    console.log("Código lineal detectado:", trama);

                    fetch(`../Controllers/entradasController.php?action=buscarProducto&codigo=${encodeURIComponent(trama)}&idProveedor=${idProveedor}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                            } else {
                                const parteEntera = data.codigoEnteros || "0";
                                const parteDecimal = data.codigoDecimales || "00";
                                const cantidadCalculada = parseFloat(`${parteEntera}.${parteDecimal}`) || 0;

                                agregarFilaTabla(trama, data.nombreProducto, cantidadCalculada);
                            }
                        })
                        .catch(error => {
                            console.error("Error en código lineal:", error);
                        });

                    this.value = ""; 
                }
            }
        });
    }
}

/**
 * 🛠️ Inserta la fila dinámicamente en la matriz de la vista
 */
function agregarFilaTabla(codigo, nombreProducto, cantidad) {
    const tbody = document.getElementById('tablaPartidasBody');
    if (!tbody) return;

    const inputCosto = document.querySelector(".input-costo-line input");
    const costoActual = inputCosto ? parseFloat(inputCosto.value) || 0 : 0;
    const importeCalculado = cantidad * costoActual;

    numeroPartida++; 

    const tr = document.createElement('tr');
    tr.style.background = "#ffffff";
    tr.style.borderBottom = "1px solid #f1f5f9";

    tr.innerHTML = `
        <td style="padding: 16px; text-align: center; color: #64748b;">${numeroPartida}</td>
        <td style="padding: 16px; text-align: center; color: #16a34a; font-weight: 600;">✓</td>
        <td style="padding: 16px; font-weight: 600;">${codigo}</td>
        <td style="padding: 16px;">${nombreProducto}</td>
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

/**
 * 📊 Recalcula el panel de totales
 */
function actualizarTotales() {
    const cantidades = document.querySelectorAll('.partida-cantidad');
    let acumuladorKgs = 0;
    let contadorPartidas = 0;

    cantidades.forEach(td => {
        acumuladorKgs += parseFloat(td.textContent) || 0;
        contadorPartidas++;
    });

    const spanCantidad = document.getElementById('totalCantidad');
    const spanKgs = document.getElementById('totalKgs');

    if (spanCantidad) spanCantidad.textContent = contadorPartidas;
    if (spanKgs) spanKgs.textContent = acumuladorKgs.toFixed(2);
}