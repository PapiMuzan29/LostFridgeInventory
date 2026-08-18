// ../Services/funcionesSalidas.js

let numeroPartida = 0; 

document.addEventListener("DOMContentLoaded", function() {
    console.log("🚀 El script funcionesSalidas.js se ha cargado correctamente.");
    cargarClientesSelect();
    inicializarEscannerSalidas();
    inicializarBotonesSalida();
});

// 1. Cargar clientes select
async function cargarClientesSelect() {
    const select = document.getElementById('idCliente');
    if (!select) return;

    try {
        const respuesta = await fetch('../Controllers/salidasController.php?action=obtenerClientes');
        const clientes = await respuesta.json();
        
        if (Array.isArray(clientes)) {
            select.innerHTML = '<option value="">-- Seleccione un cliente/proveedor --</option>';
            clientes.forEach(cli => {
                const option = document.createElement('option');
                option.value = cli.idCliente || cli.id_cliente;
                option.textContent = cli.nombreCliente || cli.nombre;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.warn("⚠️ Nota: No se pudieron cargar los clientes.", error);
    }
}

// 2. Escáner inteligente híbrido: Delega validación y peso al Controlador
function inicializarEscannerSalidas() {
    const inputCodigo = document.querySelector(".input-with-icon-bar input") || document.querySelector(".input-captura");
    const selectCliente = document.getElementById('idCliente');

    if (!inputCodigo) return;

    inputCodigo.focus();

    inputCodigo.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); 
            
            const trama = this.value.trim();
            const idCliente = selectCliente ? selectCliente.value : '';

            if (!idCliente) {
                alert("❌ Por favor, seleccione un cliente/proveedor primero.");
                this.value = "";
                return;
            }

            // Enviamos la trama y el ID al servidor. 
            // El controlador validará si el producto pertenece a este cliente y calculará el peso.
            fetch(`../Controllers/salidasController.php?action=buscarProducto&codigo=${encodeURIComponent(trama)}&idCliente=${idCliente}`)
                .then(response => response.json())
                .then(data => {
                    // Si el controlador devuelve un error (producto no registrado o de otro proveedor)
                    if (data.error) {
                        alert("❌ " + data.error);
                        return;
                    }

                    // Calculamos el peso recibido del controlador
                    const pEntera = data.codigoEnteros || "0";
                    const pDecimal = data.codigoDecimales || "00";
                    const pesoFinal = parseFloat(`${pEntera}.${pDecimal}`) || 1.00;

                    // Pintamos en tabla solo si todo es correcto
                    agregarFilaSalida(data.codigoProducto, data.nombreProducto, pesoFinal);
                })
                .catch(error => {
                    console.error("Error en la comunicación:", error);
                    alert("❌ Error de comunicación con el servidor.");
                });

            this.value = ""; 
        }
    });
}

// 3. Pintar fila en la tabla de salidas
function agregarFilaSalida(codigo, nombreProducto, cantidad) {
    const tbody = document.querySelector('#tablaPartidasBody') || document.querySelector('tbody');
    if (!tbody) return;

    const inputCosto = document.getElementById("inputCosto") || document.querySelector("input[placeholder='0.00']");
    const costoActual = inputCosto ? parseFloat(inputCosto.value) || 0 : 0;

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
    const cantidades = document.querySelectorAll('.partida-cantidad');
    let acumuladorKgs = 0;
    let contadorPartidas = 0;

    cantidades.forEach(td => {
        acumuladorKgs += parseFloat(td.textContent) || 0;
        contadorPartidas++;
    });

    const divQty = document.getElementById('totalCantidad');
    const divKgs = document.getElementById('totalKgs');

    if (divQty) divQty.textContent = contadorPartidas;
    if (divKgs) divKgs.textContent = acumuladorKgs.toFixed(2);
}

// 4. Botón de Procesar Salida
function inicializarBotonesSalida() {
    const btnGuardar = document.getElementById("btnGuardarSalida") || document.querySelector(".btnAplicar");
    const btnLimpiar = document.getElementById("btnLimpiarPantalla") || document.querySelector(".btnLimpiar");

    if (btnLimpiar) btnLimpiar.addEventListener("click", () => window.location.reload());

    if (btnGuardar) {
        btnGuardar.addEventListener("click", async function() {
            const idCliente = document.getElementById("idCliente").value;
            const inputConcepto = document.querySelector("input[placeholder*='Concepto']");
            const concepto = inputConcepto ? inputConcepto.value : "Venta";
            
            if (!idCliente) {
                alert("❌ Seleccione un cliente para procesar la salida.");
                return;
            }

            const filas = document.querySelectorAll('#tablaPartidasBody tr');
            if (filas.length === 0) {
                alert("❌ No hay productos para guardar.");
                return;
            }

            let detalle = [];
            filas.forEach((fila, index) => {
                const codigo = fila.querySelector('.partida-codigo').innerText;
                const kgs = parseFloat(fila.querySelector('.partida-cantidad').innerText);
                detalle.push({ partida: index + 1, codigo_producto: codigo, kgs: kgs, cantidad_cajas: 1 });
            });

            const payload = {
                id_cliente: idCliente,
                id_almacen: 1,
                concepto: concepto,
                total_kgs: parseFloat(document.getElementById('totalKgs').innerText),
                total_cajas: parseFloat(document.getElementById('totalCantidad').innerText),
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
                    alert(`✅ Éxito. Folio: ${result.folio}`);
                    window.location.reload(); 
                } else {
                    alert(`❌ Error: ${result.error}`);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save"></i> Procesar Salida';
                }
            } catch (error) {
                alert("❌ Error de conexión.");
                this.disabled = false;
            }
        });
    }
}