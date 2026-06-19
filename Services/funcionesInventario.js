// Funciones para abrir y cerrar el Modal de Proveedores
function abrirModalAgregarProveedor() {
    document.getElementById('formNuevoProveedor').reset(); // Limpia datos anteriores
    document.getElementById('modalAgregarProveedor').style.display = 'flex';
}

function cerrarModalAgregarProveedor() {
    document.getElementById('modalAgregarProveedor').style.display = 'none';
}

// Función para enviar los datos por AJAX
async function guardarProveedor(event) {
    event.preventDefault(); // Evita que la página se recargue

    const formulario = document.getElementById('formNuevoProveedor');
    const formData = new FormData(formulario);

    try {
        const respuesta = await fetch('../Controllers/inventarioController.php?action=crearProveedor', {
            method: 'POST',
            body: formData
        });

        const resultado = await respuesta.json();

        if (resultado.status === 'success') {
            alert('¡Proveedor registrado con éxito!');
            cerrarModalAgregarProveedor();
            
            // Si el buscador actual está en "proveedor", recargamos la tabla para ver el nuevo registro
            if (document.getElementById('selectTipoBusqueda').value === 'proveedor') {
                document.getElementById('btnLimpiar').click(); 
            }
        } else {
            alert('Error: ' + (resultado.message || 'No se pudo guardar el proveedor.'));
        }
    } catch (error) {
        console.error('Error al guardar:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}


document.addEventListener('DOMContentLoaded', () => {
    let paginaActual = 1;

    const selectTipoBusqueda = document.getElementById('selectTipoBusqueda');
    const labelDinamicoBusqueda = document.getElementById('labelDinamicoBusqueda');
    const inputBusqueda      = document.getElementById('inputBusqueda');
    const selectEstado       = document.getElementById('selectEstado');
    const btnLimpiar         = document.getElementById('btnLimpiar');
    const theadInventario    = document.getElementById('thead-inventario');
    const tbodyInventario    = document.getElementById('tabla-inventario-tbody');
    const btnAnterior        = document.getElementById('btnAnterior');
    const btnSiguiente       = document.getElementById('btnSiguiente');
    const btnPag1            = document.getElementById('btnPag1');
    const btnPag2            = document.getElementById('btnPag2');

    // Función de seguridad XSS
    function escaparHTML(cadena) {
        if (!cadena) return 'N/A';
        return String(cadena)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const headersProducto = `<tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Proveedor</th><th>Existencia (cajas)</th><th>Total Peso</th><th>Estado</th><th>Acciones</th></tr>`;
    const headersProveedor = `<tr><th>Código</th><th>Nombre / Empresa</th><th>RFC</th><th>Dirección</th><th>Región / Estado</th><th>Status</th><th>Acciones</th></tr>`;

    inputBusqueda.focus();
    setTimeout(() => { inputBusqueda.focus(); }, 100);

    function actualizarNumerosPaginacion() {
        btnPag1.textContent = paginaActual;
        btnPag2.textContent = paginaActual + 1;
    }

    async function cargarInventario(direccion = 0) {
        const paginaPrevia = paginaActual;
        paginaActual += direccion;

        if (paginaActual < 1) paginaActual = 1;

        const tipo   = selectTipoBusqueda.value;
        const buscar = inputBusqueda.value;
        const estado = selectEstado.value;

        if (tipo === 'proveedor') {
            labelDinamicoBusqueda.textContent = "Buscar proveedor";
            theadInventario.innerHTML = headersProveedor;
            inputBusqueda.placeholder = "Nombre, RFC o código de proveedor...";
        } else {
            labelDinamicoBusqueda.textContent = "Buscar producto";
            theadInventario.innerHTML = headersProducto;
            inputBusqueda.placeholder = "Nombre, código o descripción...";
        }

        try {
            const url = `../Controllers/inventarioController.php?action=busqueda`
                      + `&tipo_busqueda=${encodeURIComponent(tipo)}`
                      + `&busqueda=${encodeURIComponent(buscar)}`
                      + `&estado=${encodeURIComponent(estado)}`
                      + `&pagina=${paginaActual}`;

            const respuesta = await fetch(url);
            const resultado = await respuesta.json(); 

            const registros = resultado.datos ?? [];

            if (registros.length === 0 && paginaActual > 1 && direccion > 0) {
                paginaActual = paginaPrevia;
                actualizarNumerosPaginacion();
                return;
            }

            if (tipo === 'proveedor') {
                renderizarProveedores(registros);
            } else {
                renderizarProductos(registros);
            }

            actualizarNumerosPaginacion();

        } catch (error) {
            console.error('Error en el fetch:', error);
            tbodyInventario.innerHTML = '<tr><td colspan="8" style="text-align:center; color:red;">Error al conectar con el servidor.</td></tr>';
        }
    }

    btnSiguiente.addEventListener('click', () => { cargarInventario(1); });
    btnAnterior.addEventListener('click', () => { if (paginaActual > 1) { cargarInventario(-1); } });

    function renderizarProductos(datos) {
    if (!Array.isArray(datos) || datos.length === 0) {
        tbodyInventario.innerHTML = '<tr><td colspan="8" style="text-align:center;">No se encontraron productos.</td></tr>';
        return;
    }
    let html = '';
    datos.forEach(prod => {
        // 🛠️ AQUÍ ESTABA EL DETALLE: Cambiado para que lea exactamente 'totalCajas'
        const cajas = parseInt(prod.totalCajas) || 0; 
        
        const pesoDb = parseFloat(prod.pesoProductive) || parseFloat(prod.totalPeso) || 0;
        const esActivo = parseInt(prod.activo) === 1;

        const categoriaTexto = prod.nombreCategoria 
            ? `<span style="background: #f1f5f9; color: #334155; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">${escaparHTML(prod.nombreCategoria)}</span>`
            : '<span style="color: #94a3b8; font-style: italic;">Sin asignar</span>';

        html += `<tr>
            <td>${escaparHTML(prod.codigoProducto)}</td>
            <td><strong>${escaparHTML(prod.nombreProducto)}</strong></td>
            <td>${categoriaTexto}</td>
            <td>${escaparHTML(prod.nombreProveedor)}</td>
            <td><span style="font-weight:700; color: #0f172a;">${cajas}</span></td> 
            <td><strong>${pesoDb.toFixed(2)} kg</strong></td>
            <td>
                <span class="estado ${esActivo ? 'activo' : 'inactivo'}">
                    ${esActivo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td>
                <div class="acciones">
                    <button type="button" class="btn-accion editar" onclick="editarProducto(${prod.idProducto})">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" class="btn-accion eliminar" onclick="eliminarProducto(${prod.idProducto})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });
    tbodyInventario.innerHTML = html;
}

    function renderizarProveedores(datos) {
        if (!Array.isArray(datos) || datos.length === 0) {
            tbodyInventario.innerHTML = '<tr><td colspan="7" style="text-align:center;">No se encontraron proveedores.</td></tr>';
            return;
        }
        let html = '';
        datos.forEach(prov => {
            const esActivo = parseInt(prov.status) === 1;

            html += `<tr>
                <td>${escaparHTML(prov.codigoProveedor)}</td>
                <td><strong>${escaparHTML(prov.nombreProveedor)}</strong></td>
                <td>${escaparHTML(prov.rfc)}</td>
                <td>${escaparHTML(prov.direccion)}</td>
                <td>${escaparHTML(prov.estadoRepublica)}</td>
                <td>
                    <span class="estado ${esActivo ? 'activo' : 'inactivo'}">
                        ${esActivo ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <div class="acciones">
                        <button type="button" class="btn-accion editar" onclick="editarProveedor(${prov.idProveedor})">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn-accion eliminar" onclick="eliminarProveedor(${prov.idProveedor})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        tbodyInventario.innerHTML = html;
    }

    inputBusqueda.addEventListener('input', () => { paginaActual = 1; actualizarNumerosPaginacion(); cargarInventario(0); });
    selectTipoBusqueda.addEventListener('change', () => { paginaActual = 1; inputBusqueda.value = ''; actualizarNumerosPaginacion(); cargarInventario(0); });
    selectEstado.addEventListener('change', () => { paginaActual = 1; actualizarNumerosPaginacion(); cargarInventario(0); });
    
    btnLimpiar.addEventListener('click', () => {
        inputBusqueda.value = '';
        selectEstado.value = '';
        paginaActual = 1;
        actualizarNumerosPaginacion();
        cargarInventario(0);
    });

    cargarInventario(0);
});

function editarProducto(id) { console.log("Editar prod:", id); }
function eliminarProducto(id) { console.log("Eliminar prod:", id); }
function editarProveedor(id) { console.log("Editar prov:", id); }
function eliminarProveedor(id) { console.log("Eliminar prov:", id); }


// 🛠️ OPTIMIZADO: Eliminado el fetch redundante que vaciaba tus categorías de PHP
function abrirModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (!modal) return;

    // Reseteamos campos de texto pero mantenemos las opciones cargadas por PHP intactas
    document.getElementById('formNuevoProducto').reset();
    modal.style.display = 'flex';
}

function cerrarModalAgregarProducto() {
    document.getElementById('modalAgregarProducto').style.display = 'none';
}

// Guardar Producto por AJAX
async function guardarProducto(event) {
    event.preventDefault();
    const formulario = document.getElementById('formNuevoProducto');
    const formData = new FormData(formulario);

    try {
        const respuesta = await fetch('../Controllers/inventarioController.php?action=crearProducto', {
            method: 'POST',
            body: formData
        });
        
        // Capturamos el texto plano primero por si PHP arroja un Warning o Error de sintaxis
        const textoRespuesta = await respuesta.text();
        console.log("Respuesta bruta del servidor:", textoRespuesta);

        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            alert("El servidor no devolvió un JSON válido. Revisa la consola (F12).");
            return;
        }

        if (resultado.status === 'success') {
            alert('¡Producto registrado con éxito!');
            cerrarModalAgregarProducto();
            
            const kpiProd = document.getElementById('kpi-total-productos');
            if (kpiProd) kpiProd.textContent = parseInt(kpiProd.textContent) + 1;

            if (document.getElementById('selectTipoBusqueda').value === 'producto') {
                document.getElementById('btnLimpiar').click();
            }
        } else {
            // Si no viene 'message', mostramos el resultado completo estructurado
            alert('Error: ' + (resultado.message || resultado.error || 'Error interno en el controlador.'));
        }
    } catch (error) {
        console.error('Error al guardar producto:', error);
        alert('Error al conectar con el servidor.');
    }
}