// ../Services/funcionesSalidas.js

let numeroPartida = 0; 

document.addEventListener("DOMContentLoaded", function() {
    console.log("🚀 El script funcionesSalidas.js se ha cargado correctamente.");
    cargarClientesSelect();
    inicializarEscannerSalidas();
    inicializarBotonesSalida();
});

// 1. Cargar proveedores / clientes y sus posiciones de escáner exactamente igual que en Entradas
async function cargarClientesSelect() {
    const select = document.getElementById('idCliente');
    if (!select) {
        console.warn("⚠️ Nota: No se encontró el elemento HTML con id='idCliente'");
        return;
    }

    try {
        const respuesta = await fetch('../Controllers/salidasController.php?action=obtenerClientes');
        const clientes = await respuesta.json();
        
        if (Array.isArray(clientes)) {
            select.innerHTML = '<option value="">-- Seleccione un proveedor/cliente --</option>';
            clientes.forEach(cli => {
                const option = document.createElement('option');
                option.value = cli.idCliente || cli.id_cliente;
                option.textContent = cli.nombreCliente || cli.nombre;
                
                // Mapeo exacto de las posiciones de la pistola idéntico a Entradas
                option.setAttribute('data-prod-pos', cli.codigoBarrasProductosPosicion || 0);
                option.setAttribute('data-prod-lon', cli.codigoBarrasProductosLongitud || 0);
                option.setAttribute('data-kg-pos', cli.codigoBarrasEnterosPosicion || 0);
                option.setAttribute('data-kg-lon', cli.codigoBarrasEnterosLongitud || 0);
                option.setAttribute('data-gr-pos', cli.codigoBarrasDecimalesPosicion || 0);
                option.setAttribute('data-gr-lon', cli.codigoBarrasDecimalesLongitud || 0);

                select.appendChild(option);
            });
            console.log("👥 Proveedores y posiciones de pistola cargados exitosamente.");
        }
    } catch (error) {
        console.warn("⚠️ Nota: No se pudieron cargar los proveedores.", error);
    }
}

// 2. Escáner de salidas con la extracción estricta de peso sin desfases
function inicializarEscannerSalidas() {
    const inputCodigo = document.querySelector(".input-with-icon-bar input") || document.querySelector(".input-captura");
    const selectCliente = document.getElementById('idCliente');

    if (!inputCodigo) return;

    inputCodigo.focus();
    console.log("🎯 Lector de salidas activado.");

    inputCodigo.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); 
            
            const trama = this.value.trim();
            if (!trama) return;

            const selectedOption = selectCliente ? selectCliente.options[selectCliente.selectedIndex] : null;
            const idCliente = selectCliente ? selectCliente.value : '';
            
            if (!idCliente) {
                alert("❌ Por favor, seleccione un proveedor primero.");
                this.value = "";
                return;
            }

            let codigoParaBuscar = trama;
            let cantidadCalculada = 1.00;

            // Procesar posiciones de la BD aplicando un ajuste estricto para evitar desfases de lectura
            if (selectedOption && parseInt(selectedOption.dataset.prodPos) > 0) {
                const prodPos = parseInt(selectedOption.dataset.prodPos) - 1;
                const prodLon = parseInt(selectedOption.dataset.prodLon) || 0;
                const kgPos = parseInt(selectedOption.dataset.kgPos) - 1;
                const kgLon = parseInt(selectedOption.dataset.kgLon) || 0;
                const grPos = parseInt(selectedOption.dataset.grPos) - 1;
                const grLon = parseInt(selectedOption.dataset.grLon) || 0;

                if (prodLon > 0 && trama.length >= (prodPos + prodLon)) {
                    codigoParaBuscar = String(parseInt(trama.substr(prodPos, prodLon), 10));
                }

                let parteEntera = "0";
                let parteDecimal = "00";
                
                // Extracción limpia usando .substring() para prevenir errores de índices
                if (kgLon > 0 && trama.length >= kgPos) {
                    parteEntera = trama.substring(kgPos, kgPos + kgLon).trim();
                }
                if (grLon > 0 && trama.length >= grPos) {
                    parteDecimal = trama.substring(grPos, grPos + grLon).trim();
                }

                // Forzar parseo numérico exacto a decimal de dos cifras sin alterar el valor real
                const pesoParseado = parseFloat(`${parseInt(parteEntera, 10)}.${parteDecimal}`);
                if (!isNaN(pesoParseado) && pesoParseado > 0) {
                    cantidadCalculada = pesoParseado;
                }
            } else {
                const limpio = trama.replace(/^0+/, '');
                if (limpio.length > 0) {
                    codigoParaBuscar = limpio;
                }
            }

            // Consultamos al controlador buscando el producto real en la base de datos
            fetch(`../Controllers/salidasController.php?action=buscarProducto&codigo=${encodeURIComponent(codigoParaBuscar)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error || !data.nombreProducto) {
                        alert("❌ Producto no encontrado con el código extraído: " + codigoParaBuscar);
                    } else {
                        const codigoFinal = data.codigoProducto || codigoParaBuscar;
                        const nombreFinal = data.nombreProducto;
                        agregarFilaSalida(codigoFinal, nombreFinal, cantidadCalculada);
                    }
                })
                .catch(error => {
                    console.error("Error en la petición:", error);
                    alert("❌ Error al conectar con el servidor para buscar el producto.");
                });

            this.value = ""; 
        }
    });
}

// 3. Pintar fila en la tabla de salidas alineado al diseño de Entradas
function agregarFilaSalida(codigo, nombreProducto, cantidad) {
    const tbody = document.querySelector('#tablaPartidasBody') || document.querySelector('tbody');
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
        <td class="partida-cantidad" style="padding: 16px; text-align: right; font-weight: 600;">${cantidad.toFixed(2)}</td>
        <td style="padding: 16px; text-align: right; font-weight: 600;">0.00</td>
        <td style="padding: 16px; text-align: right; font-weight: 600;">0.00</td>
        <td style="padding: 16px; text-align: center;">
            <button class="btn-borrar-partida" onclick="this.closest('tr').remove(); actualizarTotalesSalida();" style="background: none; border: none; color: #ef4444; cursor: pointer;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    actualizarTotalesSalida(); 
}

function actualizarTotalesSalida() {
    const filas = document.querySelectorAll('tbody tr');
    let acumuladorKgs = 0;
    let contadorCajas = 0;

    filas.forEach(fila => {
        const kgsCell = fila.querySelector('.partida-cantidad') || fila.children[4];
        
        const valorKgs = parseFloat(kgsCell?.textContent) || 0;
        contadorCajas += 1;
        acumuladorKgs += valorKgs;
    });

    const divQty = document.getElementById('totalCantidad');
    const divKgs = document.getElementById('totalKgs');

    if (divQty) divQty.textContent = contadorCajas;
    if (divKgs) divKgs.textContent = acumuladorKgs.toFixed(2);
}

// 4. Botón de Procesar Salida
function inicializarBotonesSalida() {
    const btnGuardar = document.getElementById("btnGuardarSalida") || document.querySelector(".btnAplicar");
    const btnLimpiar = document.getElementById("btnLimpiar") || document.querySelector(".btnLimpiar");

    if (btnLimpiar) {
        btnLimpiar.addEventListener("click", () => window.location.reload());
    }

    if (btnGuardar) {
        btnGuardar.addEventListener("click", async function() {
            const idCliente = document.getElementById("idCliente").value;
            const inputConcepto = document.querySelector("input[placeholder*='Concepto']");
            const concepto = inputConcepto ? inputConcepto.value : "Venta";
            const idAlmacen = 1; 
            
            if (!idCliente) {
                alert("❌ Seleccione un proveedor para procesar la salida.");
                return;
            }

            const filas = document.querySelectorAll('tbody tr');
            if (filas.length === 0) {
                alert("❌ No hay productos en la tabla.");
                return;
            }

            let detalle = [];
            let totalKgs = 0;
            let totalCajas = 0;

            filas.forEach((fila, index) => {
                const codigoCell = fila.querySelector('.partida-codigo') || fila.children[2];
                const kgsCell = fila.querySelector('.partida-cantidad') || fila.children[4];
                
                if (codigoCell && kgsCell) {
                    const codigo = codigoCell.innerText.trim();
                    const kgs = parseFloat(kgsCell.innerText) || 0;
                    const cajas = 1; 
                    
                    totalCajas += cajas;
                    totalKgs += kgs;

                    detalle.push({
                        partida: index + 1,
                        codigo_producto: codigo,
                        cantidad_cajas: cajas,
                        kgs: kgs
                    });
                }
            });

            const payload = {
                id_cliente: idCliente,
                id_almacen: idAlmacen,
                concepto: concepto,
                total_kgs: totalKgs,
                total_cajas: totalCajas,
                detalle: detalle
            };

            try {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                const response = await fetch("../Controllers/salidasController.php?action=guardarSalida", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Salida procesada con éxito.\nFolio de Salida: ${result.folio}`);
                    window.location.reload(); 
                } else {
                    alert(`❌ Error al procesar: ${result.error}`);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save"></i> Procesar Salida';
                }
            } catch (error) {
                console.error("Error en la petición:", error);
                alert("❌ Ocurrió un error de conexión al procesar la salida.");
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> Procesar Salida';
            }
        });
    }
}