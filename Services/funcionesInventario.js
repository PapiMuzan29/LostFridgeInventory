// Funciones para abrir y cerrar el Modal de Proveedores
function abrirModalAgregarProveedor() {
    const modal = document.getElementById('modalAgregarProveedor');
    if (!modal) return;
    document.getElementById('formNuevoProveedor').reset(); // Limpia datos anteriores
    modal.style.display = 'flex';
}

function cerrarModalAgregarProveedor() {
    document.getElementById('modalAgregarProveedor').style.display = 'none';
}

function cerrarModalEditarProveedor() {
    const modal = document.getElementById('modalEditarProveedor');
    if (modal) {
        modal.style.display = 'none';
        const form = document.getElementById('formEditarProveedor');
        if (form) form.reset();
    }
}

// Función para enviar los datos por AJAX
async function guardarProveedor(event) {
    event.preventDefault(); // Evita que la página se recargue

    const formulario = document.getElementById('formNuevoProveedor');
    const formData = new FormData(formulario);

    try {
        const respuesta = await fetch('../Controllers/inventarioController.php?action=agregarProveedor', {
            method: 'POST',
            body: formData
        });

        const textoRespuesta = await respuesta.text();
        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            alert("Error: El servidor no devolvió una respuesta válida (JSON).");
            return;
        }

        if (resultado.status === 'success') {
            alert('¡Proveedor registrado con éxito!');
            cerrarModalAgregarProveedor();
            
            const selectTipo = document.getElementById('selectTipoBusqueda');
            if (selectTipo && selectTipo.value === 'proveedor') {
                if (typeof window.cargarInventario === 'function') {
                    window.cargarInventario(0);
                }
            }
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo guardar el proveedor.'));
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
    const inputBusqueda        = document.getElementById('inputBusqueda');
    const selectEstado         = document.getElementById('selectEstado');
    const btnLimpiar           = document.getElementById('btnLimpiar');
    const theadInventario      = document.getElementById('thead-inventario');
    const tbodyInventario      = document.getElementById('tabla-inventario-tbody');
    const btnAnterior          = document.getElementById('btnAnterior');
    const btnSiguiente         = document.getElementById('btnSiguiente');
    const btnPag1              = document.getElementById('btnPag1');
    const btnPag2              = document.getElementById('btnPag2');

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

    if (inputBusqueda) {
        inputBusqueda.focus();
        setTimeout(() => { inputBusqueda.focus(); }, 100);
    }

    function actualizarNumerosPaginacion() {
        if (btnPag1 && btnPag2) {
            btnPag1.textContent = paginaActual;
            btnPag2.textContent = paginaActual + 1;
        }
    }

    async function cargarInventario(direccion = 0) {
        const paginaPrevia = paginaActual;
        paginaActual += direccion;

        if (paginaActual < 1) paginaActual = 1;

        const tipo   = selectTipoBusqueda ? selectTipoBusqueda.value : 'producto';
        const buscar = inputBusqueda ? inputBusqueda.value : '';
        const estado = selectEstado ? selectEstado.value : '';

        if (tipo === 'proveedor') {
            if (labelDinamicoBusqueda) labelDinamicoBusqueda.textContent = "Buscar proveedor";
            if (theadInventario) theadInventario.innerHTML = headersProveedor;
            if (inputBusqueda) inputBusqueda.placeholder = "Nombre, RFC o código de proveedor...";
        } else {
            if (labelDinamicoBusqueda) labelDinamicoBusqueda.textContent = "Buscar producto";
            if (theadInventario) theadInventario.innerHTML = headersProducto;
            if (inputBusqueda) inputBusqueda.placeholder = "Nombre, código o descripción...";
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
            if (tbodyInventario) {
                tbodyInventario.innerHTML = '<tr><td colspan="8" style="text-align:center; color:red;">Error al conectar con el servidor.</td></tr>';
            }
        }
    }

    if (btnSiguiente) btnSiguiente.addEventListener('click', () => { cargarInventario(1); });
    if (btnAnterior) btnAnterior.addEventListener('click', () => { if (paginaActual > 1) { cargarInventario(-1); } });

    function renderizarProductos(datos) {
        if (!tbodyInventario) return;
        if (!Array.isArray(datos) || datos.length === 0) {
            tbodyInventario.innerHTML = '<tr><td colspan="8" style="text-align:center;">No se encontraron productos.</td></tr>';
            return;
        }
        let html = '';
        datos.forEach(prod => {
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
        if (!tbodyInventario) return;
        if (!Array.isArray(datos) || datos.length === 0) {
            tbodyInventario.innerHTML = '<tr><td colspan="7" style="text-align:center;">No se encontraron proveedores.</td></tr>';
            return;
        }
        let html = '';
        datos.forEach(prov => {
            const esActivo = parseInt(prov.status) === 1;

            // Guardamos el objeto completo serializado en un atributo seguro de la fila o botón
            const provJson = encodeURIComponent(JSON.stringify(prov));

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
                        <button type="button" class="btn-accion editar" onclick="editarProveedor('${provJson}')">
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

    if (inputBusqueda) inputBusqueda.addEventListener('input', () => { paginaActual = 1; actualizarNumerosPaginacion(); cargarInventario(0); });
    if (selectTipoBusqueda) selectTipoBusqueda.addEventListener('change', () => { paginaActual = 1; inputBusqueda.value = ''; actualizarNumerosPaginacion(); cargarInventario(0); });
    if (selectEstado) selectEstado.addEventListener('change', () => { paginaActual = 1; actualizarNumerosPaginacion(); cargarInventario(0); });
    
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            inputBusqueda.value = '';
            selectEstado.value = '';
            paginaActual = 1;
            actualizarNumerosPaginacion();
            cargarInventario(0);
        });
    }

    window.cargarInventario = cargarInventario;
    cargarInventario(0);
});

// --- FUNCIONES CRUD PARA PRODUCTOS Y PROVEEDORES ---

async function editarProducto(id) {
    const modal = document.getElementById('modalEditarProducto');
    if (!modal) return;
    
    document.getElementById('editIdProducto').value = id;
    modal.style.display = 'flex';
}

async function actualizarProducto(event) {
    event.preventDefault();
    const formulario = document.getElementById('formEditarProducto');
    const formData = new FormData(formulario);

    try {
        const respuesta = await fetch('../Controllers/inventarioController.php?action=actualizarProducto', {
            method: 'POST',
            body: formData
        });
        const resultado = await respuesta.json();

        if (resultado.status === 'success') {
            alert('¡Producto actualizado con éxito!');
            cerrarModalEditarProducto();
            if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
        } else {
            alert('Error: ' + (resultado.message || 'No se pudo actualizar.'));
        }
    } catch (error) {
        console.error('Error al actualizar:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

async function eliminarProducto(id) {
    if (!confirm("¿Estás seguro de que deseas eliminar este producto del inventario?")) {
        return;
    }

    try {
        const respuesta = await fetch(`../Controllers/inventarioController.php?action=eliminarProducto&id=${id}`, {
            method: 'POST'
        });

        const textoRespuesta = await respuesta.text();
        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            alert("Error: El servidor no devolvió una respuesta válida.");
            return;
        }

        if (resultado.status === 'success') {
            alert('¡Producto eliminado correctamente!');
            if (typeof window.cargarInventario === 'function') {
                window.cargarInventario(0); 
            }
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo eliminar el producto.'));
        }
    } catch (error) {
        console.error('Error al eliminar producto:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

// 🔥 FUNCIÓN MEJORADA: RECIBE Y LLENA AUTOMÁTICAMENTE LOS DATOS DEL PROVEEDOR
function editarProveedor(provJsonCodificado) {
    const modal = document.getElementById('modalEditarProveedor');
    if (!modal) return;

    try {
        const prov = JSON.parse(decodeURIComponent(provJsonCodificado));

        // Rellenamos los campos principales con los datos actuales
        document.getElementById('editProvId').value = prov.idProveedor ?? '';
        document.getElementById('editProvCodigo').value = prov.codigoProveedor ?? '';
        document.getElementById('editProvNombre').value = prov.nombreProveedor ?? '';
        document.getElementById('editProvRfc').value = prov.rfc ?? '';
        document.getElementById('editProvDireccion').value = prov.direccion ?? '';
        document.getElementById('editProvColonia').value = prov.colonia ?? '';
        document.getElementById('editProvCp').value = prov.codigoPostal ?? '';
        document.getElementById('editProvEstado').value = prov.estadoRepublica ?? '';

        // Si existen los campos de configuración QR en el modal de editar, los rellenamos también
        if (document.getElementById('editCodigoBarrasProductosPosicion')) {
            document.getElementById('editCodigoBarrasProductosPosicion').value = prov.codigoBarrasProductosPosicion ?? 0;
            document.getElementById('editCodigoBarrasProductosLongitud').value = prov.codigoBarrasProductosLongitud ?? 0;
            document.getElementById('editCodigoBarrasEnterosPosicion').value = prov.codigoBarrasEnterosPosicion ?? 0;
            document.getElementById('editCodigoBarrasEnterosLongitud').value = prov.codigoBarrasEnterosLongitud ?? 0;
            document.getElementById('editCodigoBarrasDecimalesPosicion').value = prov.codigoBarrasDecimalesPosicion ?? 0;
            document.getElementById('editCodigoBarrasDecimalesLongitud').value = prov.codigoBarrasDecimalesLongitud ?? 0;
        }

        modal.style.display = 'flex';
    } catch (e) {
        console.error("Error al decodificar los datos del proveedor:", e);
        alert("Error al abrir los datos de edición del proveedor.");
    }
}

async function actualizarProveedor(event) {
    event.preventDefault();
    const formulario = document.getElementById('formEditarProveedor');
    const formData = new FormData(formulario);

    try {
        const respuesta = await fetch('../Controllers/inventarioController.php?action=actualizarProveedor', {
            method: 'POST',
            body: formData
        });
        const resultado = await respuesta.json();

        if (resultado.status === 'success') {
            alert('¡Proveedor actualizado con éxito!');
            cerrarModalEditarProveedor();
            if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
        } else {
            alert('Error: ' + (resultado.message || 'No se pudo actualizar el proveedor.'));
        }
    } catch (error) {
        console.error('Error al actualizar proveedor:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

async function eliminarProveedor(id) {
    if (!confirm("¿Estás seguro de que deseas eliminar este proveedor?")) {
        return;
    }

    try {
        const respuesta = await fetch(`../Controllers/inventarioController.php?action=eliminarProveedor&id=${id}`, {
            method: 'POST'
        });

        const textoRespuesta = await respuesta.text();
        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            alert("Error: El servidor no devolvió una respuesta válida.");
            return;
        }

        if (resultado.status === 'success') {
            alert('¡Proveedor eliminado correctamente!');
            if (typeof window.cargarInventario === 'function') {
                window.cargarInventario(0); 
            }
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo eliminar el proveedor.'));
        }
    } catch (error) {
        console.error('Error al eliminar proveedor:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

// 🛠️ Funciones para modales de productos
function abrirModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (!modal) return;

    document.getElementById('formNuevoProducto').reset();
    modal.style.display = 'flex';
}

function cerrarModalEditarProducto() {
    const modal = document.getElementById('modalEditarProducto');
    if (modal) {
        modal.style.display = 'none';
        const form = document.getElementById('formEditarProducto');
        if (form) form.reset();
    }
}

function cerrarModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (modal) {
        modal.style.display = 'none';
    }
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
        
        const textoRespuesta = await respuesta.text();
        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            alert("Error: El servidor no devolvió una respuesta válida (JSON).");
            return;
        }

        if (resultado.status === 'success') {
            alert('¡Producto registrado con éxito!');
            cerrarModalAgregarProducto();
            
            const inputBusqueda = document.getElementById('inputBusqueda');
            if (inputBusqueda) {
                inputBusqueda.value = ''; 
            }

            if (typeof window.cargarInventario === 'function') {
                window.cargarInventario(0); 
            }
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo guardar el producto.'));
        }
    } catch (error) {
        console.error('Error al guardar producto:', error);
        alert('Error al conectar con el servidor.');
    }
}