// ===================================================================================
// 1. MÓDULO DE EXPORTACIÓN E IMPRESIÓN
// ===================================================================================
function abrirModalExportar() {
    const modal = document.getElementById('modalExportarExcel');
    if (modal) modal.style.display = 'flex';
}

function cerrarModalExportar() {
    const modal = document.getElementById('modalExportarExcel');
    if (modal) modal.style.display = 'none';
}

async function ejecutarExportacion(tipoOpcion) {
    cerrarModalExportar();

    if (tipoOpcion === 'vista') {
        exportarTablaAExcel("#thead-inventario tr, #tabla-inventario-tbody tr", "Inventario_Vista_Actual");
    } else {
        try {
            const tipo = document.getElementById('selectTipoBusqueda')?.value || 'producto';
            const tipoInventario = window.tipoInventarioActual || 'cajas';
            let todosLosRegistros = [];
            let pagina = 1;
            let continuar = true;

            while (continuar) {
                const url = `../Controllers/inventarioController.php?action=busqueda&tipo_busqueda=${encodeURIComponent(tipo)}&tipo_inventario=${encodeURIComponent(tipoInventario)}&busqueda=&estado=&pagina=${pagina}`;
                const respuesta = await fetch(url);
                const resultado = await respuesta.json();
                const registros = resultado.datos ?? [];

                if (registros.length > 0) {
                    todosLosRegistros = todosLosRegistros.concat(registros);
                    pagina++;
                } else {
                    continuar = false;
                }
            }

            if (todosLosRegistros.length === 0) {
                alert("❌ No hay registros en el inventario.");
                return;
            }

            tipo === 'proveedor' ? exportarDatosProveedoresCSV(todosLosRegistros) : exportarDatosProductosCSV(todosLosRegistros);

        } catch (error) {
            console.error("Error al exportar todo el inventario:", error);
            alert("❌ Ocurrió un error al obtener todos los datos.");
        }
    }
}

function exportarTablaAExcel(selectorFilas, nombreArchivo) {
    const filas = document.querySelectorAll(selectorFilas);
    let csv = [];
    
    csv.push('Código;Producto;Categoría;Proveedor;Existencia;Peso Total');

    filas.forEach(fila => {
        let filaDatos = [];
        let columnas = fila.querySelectorAll("th, td");
        for (let i = 0; i < columnas.length; i++) {
            let texto = columnas[i].innerText.trim();
            if (texto.toLowerCase().includes("estado") || texto.toLowerCase().includes("acciones") || columnas[i].querySelector('.acciones, .btn-accion, .estado')) continue;
            filaDatos.push(texto.replace(/(\r\n|\n|\r)/gm, " ").replace(/;/g, ',').trim());
        }
        if (filaDatos.length > 0) csv.push(filaDatos.join(";"));
    });
    descargarCSV(csv, nombreArchivo);
}

function exportarDatosProductosCSV(datos) {
    let csv = [];
    csv.push('Código;Nombre del Producto;Categoría;Proveedor Principal;Existencia;Peso Total');
    
    datos.forEach(p => {
        const codigo = (p.codigoProducto || '').replace(/;/g, ',');
        const producto = (p.nombreProducto || '').replace(/;/g, ',');
        const categoria = (p.nombreCategoria || 'Sin asignar').replace(/;/g, ',');
        const proveedor = (p.nombreProveedor || '').replace(/;/g, ',');
        const cajas = p.totalCajas || 0;
        const peso = `${parseFloat(p.totalPeso || 0).toFixed(2)} kg`;

        csv.push(`${codigo};${producto};${categoria};${proveedor};${cajas};${peso}`);
    });
    
    descargarCSV(csv, "Inventario_Completo_Productos");
}

function exportarDatosProveedoresCSV(datos) {
    let csv = [];
    csv.push('Código;Nombre / Empresa;RFC;Dirección;Región / Estado');
    
    datos.forEach(p => {
        const codigo = (p.codigoProveedor || '').replace(/;/g, ',');
        const nombre = (p.nombreProveedor || '').replace(/;/g, ',');
        const rfc = (p.rfc || '').replace(/;/g, ',');
        const direccion = (p.direccion || '').replace(/;/g, ',');
        const estado = (p.estadoRepublica || '').replace(/;/g, ',');

        csv.push(`${codigo};${nombre};${rfc};${direccion};${estado}`);
    });
    
    descargarCSV(csv, "Catalogo_Proveedores_Completo");
}

function descargarCSV(csvArray, nombreBase) {
    let excelHtml = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="utf-8">
        <style>
            .banner-superior { background-color: #0f172a; color: #ffffff; font-size: 18pt; font-weight: bold; text-align: center; vertical-align: middle; height: 55px; }
            .subbanner-reporte { background-color: #1e293b; color: #38bdf8; font-size: 12pt; font-weight: bold; text-align: center; vertical-align: middle; height: 35px; }
            .tabla-header { background-color: #334155; color: #ffffff; font-size: 11pt; font-weight: bold; text-align: center; border: 2px solid #000000; height: 40px; padding: 10px; vertical-align: middle; }
            .celda-dato { font-size: 11pt; border: 1px solid #94a3b8; padding: 10px 14px; vertical-align: middle; text-align: center; height: 30px; }
        </style>
    </head>
    <body>
        <table>
            <tr><td colspan="6" class="banner-superior">LFI - CONTROL OPERATIVO DE INVENTARIOS</td></tr>
            <tr><td colspan="6" class="subbanner-reporte">REPORTE GENERAL DE INVENTARIO Y PRODUCTOS</td></tr>
            <tr><td colspan="6" style="height: 20px;"></td></tr>
    `;

    csvArray.forEach((fila, index) => {
        excelHtml += '<tr>';
        let celdas = fila.split(';');
        celdas.forEach((celda) => {
            let valorLimpio = celda.replace(/^"|"$/g, '');
            if (index === 0) {
                excelHtml += `<th class="tabla-header">${valorLimpio}</th>`;
            } else {
                excelHtml += `<td class="celda-dato">${valorLimpio}</td>`;
            }
        });
        excelHtml += '</tr>';
    });

    excelHtml += '</table></body></html>';

    const blob = new Blob([excelHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${nombreBase}_${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

async function ejecutarImpresion(tipoOpcion) {
    cerrarModalExportar(); 
    const fechaHoy = new Date().toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
    document.querySelector('.main-content')?.setAttribute('data-fecha', fechaHoy);

    if (tipoOpcion === 'vista') {
        window.print();
    } else {
        const tipo = document.getElementById('selectTipoBusqueda')?.value || 'producto';
        const tipoInventario = window.tipoInventarioActual || 'cajas';
        let todosLosRegistros = [];
        let pagina = 1;
        let continuar = true;

        try {
            while (continuar) {
                const url = `../Controllers/inventarioController.php?action=busqueda&tipo_busqueda=${encodeURIComponent(tipo)}&tipo_inventario=${encodeURIComponent(tipoInventario)}&busqueda=&estado=&pagina=${pagina}`;
                const respuesta = await fetch(url);
                const resultado = await respuesta.json();
                const registros = resultado.datos ?? [];

                if (registros.length > 0) {
                    todosLosRegistros = todosLosRegistros.concat(registros);
                    pagina++;
                } else {
                    continuar = false;
                }
            }

            if (todosLosRegistros.length === 0) {
                alert("❌ No hay registros para imprimir.");
                return;
            }

            if (tipo === 'proveedor') {
                renderizarProveedoresParaImprimir(todosLosRegistros);
            } else {
                renderizarProductosParaImprimir(todosLosRegistros);
            }

            setTimeout(() => {
                window.print();
                if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
            }, 500);

        } catch (error) {
            console.error("Error al preparar impresión completa:", error);
            alert("❌ Ocurrió un error al obtener todos los datos para imprimir.");
        }
    }
}

function renderizarProductosParaImprimir(datos) {
    const tbody = document.getElementById('tabla-inventario-tbody');
    if (!tbody) return;
    let html = '';
    datos.forEach(prod => {
        const cajas = parseInt(prod.totalCajas) || 0; 
        const pesoDb = parseFloat(prod.pesoProductive) || parseFloat(prod.totalPeso) || 0;
        const esActivo = parseInt(prod.activo) === 1;
        html += `<tr>
            <td>${prod.codigoProducto || 'N/A'}</td>
            <td><strong>${prod.nombreProducto || ''}</strong></td>
            <td>${prod.nombreCategoria || 'Sin asignar'}</td>
            <td>${prod.nombreProveedor || ''}</td>
            <td>${cajas}</td> 
            <td><strong>${pesoDb.toFixed(2)} kg</strong></td>
            <td>${esActivo ? 'Activo' : 'Inactivo'}</td>
            <td></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderizarProveedoresParaImprimir(datos) {
    const tbody = document.getElementById('tabla-inventario-tbody');
    if (!tbody) return;
    let html = '';
    datos.forEach(prov => {
        const esActivo = parseInt(prov.status) === 1;
        html += `<tr>
            <td>${prov.codigoProveedor || 'N/A'}</td>
            <td><strong>${prov.nombreProveedor || ''}</strong></td>
            <td>${prov.rfc || ''}</td>
            <td>${prov.direccion || ''}</td>
            <td>${prov.estadoRepublica || ''}</td>
            <td>${esActivo ? 'Activo' : 'Inactivo'}</td>
            <td></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// ===================================================================================
// 2. GESTIÓN DE MODALES Y CRUD (PRODUCTOS Y PROVEEDORES)
// ===================================================================================
function abrirModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (!modal) return;
    document.getElementById('formNuevoProducto').reset();
    modal.style.display = 'flex';
}

function cerrarModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (modal) modal.style.display = 'none';
}

function cerrarModalEditarProducto() {
    const modal = document.getElementById('modalEditarProducto');
    if (modal) { 
        modal.style.display = 'none'; 
        document.getElementById('formEditarProducto')?.reset(); 
    }
}

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
            if (inputBusqueda) inputBusqueda.value = '';
            if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo guardar el producto.'));
        }
    } catch (error) {
        console.error('Error al guardar producto:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

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
        const respuesta = await fetch('../Controllers/inventarioController.php?action=actualizarProducto', { method: 'POST', body: formData });
        const resultado = await respuesta.json();
        if (resultado.status === 'success') {
            alert('¡Producto actualizado con éxito!');
            cerrarModalEditarProducto();
            if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
        } else {
            alert('Error: ' + (resultado.message || 'No se pudo actualizar.'));
        }
    } catch (error) { 
        console.error('Error al actualizar producto:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

async function eliminarProducto(id) {
    if (!confirm("¿Estás seguro de que deseas eliminar este producto del inventario?")) return;

    try {
        const respuesta = await fetch(`../Controllers/inventarioController.php?action=eliminarProducto&id=${id}`, { method: 'POST' });
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
            if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo eliminar el producto.'));
        }
    } catch (error) { 
        console.error('Error al eliminar producto:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

function abrirModalAgregarProveedor() {
    const modal = document.getElementById('modalAgregarProveedor');
    if (!modal) return;
    document.getElementById('formNuevoProveedor').reset(); 
    modal.style.display = 'flex';
}

function cerrarModalAgregarProveedor() {
    const modal = document.getElementById('modalAgregarProveedor');
    if (modal) modal.style.display = 'none';
}

function cerrarModalEditarProveedor() {
    const modal = document.getElementById('modalEditarProveedor');
    if (modal) {
        modal.style.display = 'none';
        const form = document.getElementById('formEditarProveedor');
        if (form) form.reset();
    }
}

async function guardarProveedor(event) {
    event.preventDefault(); 
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
                if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
            }
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo guardar el proveedor.'));
        }
    } catch (error) {
        console.error('Error al guardar proveedor:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

function editarProveedor(provJsonCodificado) {
    const modal = document.getElementById('modalEditarProveedor');
    if (!modal) return;

    try {
        const prov = JSON.parse(decodeURIComponent(provJsonCodificado));

        document.getElementById('editProvId').value = prov.idProveedor ?? '';
        document.getElementById('editProvCodigo').value = prov.codigoProveedor ?? '';
        document.getElementById('editProvNombre').value = prov.nombreProveedor ?? '';
        document.getElementById('editProvRfc').value = prov.rfc ?? '';
        document.getElementById('editProvDireccion').value = prov.direccion ?? '';
        document.getElementById('editProvColonia').value = prov.colonia ?? '';
        document.getElementById('editProvCp').value = prov.codigoPostal ?? '';
        document.getElementById('editProvEstado').value = prov.estadoRepublica ?? '';

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
        console.error("Error al decodificar proveedor:", e);
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
    if (!confirm("¿Estás seguro de que deseas eliminar este proveedor?")) return;

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
            if (typeof window.cargarInventario === 'function') window.cargarInventario(0);
        } else {
            alert('Error: ' + (resultado.message || resultado.error || 'No se pudo eliminar el proveedor.'));
        }
    } catch (error) {
        console.error('Error al eliminar proveedor:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}

// ===================================================================================
// 3. INICIALIZADOR PRINCIPAL DE LA VISTA DE INVENTARIO
// ===================================================================================
document.addEventListener('DOMContentLoaded', () => {
    let paginaActual = 1;

    const selectTipoBusqueda = document.getElementById('selectTipoBusqueda');
    const labelDinamicoBusqueda = document.getElementById('labelDinamicoBusqueda');
    const inputBusqueda = document.getElementById('inputBusqueda');
    const selectEstado = document.getElementById('selectEstado');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const theadInventario = document.getElementById('thead-inventario');
    const tbodyInventario = document.getElementById('tabla-inventario-tbody');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnPag1 = document.getElementById('btnPag1');
    const btnPag2 = document.getElementById('btnPag2');

    const btnExportar = document.querySelector(".btn-exportar");
    const btnImprimir = document.querySelector(".btn-imprimir");

    if (btnExportar) btnExportar.addEventListener("click", abrirModalExportar);
    if (btnImprimir) btnImprimir.addEventListener("click", abrirModalExportar);

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

        const tipo = selectTipoBusqueda ? selectTipoBusqueda.value : 'producto';
        const tipoInventario = window.tipoInventarioActual || 'cajas';
        const buscar = inputBusqueda ? inputBusqueda.value : '';
        const estado = selectEstado ? selectEstado.value : '';

        if (tipo === 'proveedor') {
            if (labelDinamicoBusqueda) labelDinamicoBusqueda.textContent = "Buscar proveedor";
            if (theadInventario) theadInventario.innerHTML = headersProveedor;
            if (inputBusqueda) inputBusqueda.placeholder = "Nombre, RFC o código de proveedor...";
        } else {
            if (labelDinamicoBusqueda) labelDinamicoBusqueda.textContent = "Buscar producto";
            let tituloCantidad = 'Existencia (cajas)';
            if (tipoInventario === 'pierna' || tipoInventario === 'codillo') tituloCantidad = 'Cantidad de Combos';
            if (tipoInventario === 'mantecas') tituloCantidad = 'Envases / Botes';

            if (theadInventario) {
                if (tipoInventario === 'mantecas') {
                    theadInventario.innerHTML = `<tr><th>Código</th><th>Producto / Presentación</th><th>Proveedor</th><th>Presentación (Kg)</th><th>Envases Disponibles</th><th>Kilos Totales</th><th>Estado</th><th>Acciones</th></tr>`;
                } else {
                    theadInventario.innerHTML = `<tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Proveedor</th><th>${tituloCantidad}</th><th>Total Peso</th><th>Estado</th><th>Acciones</th></tr>`;
                }
            }
            if (inputBusqueda) inputBusqueda.placeholder = tipoInventario === 'mantecas' ? "Buscar manteca por código o proveedor..." : "Nombre, código o descripción...";
        }

        try {
            const url = `../Controllers/inventarioController.php?action=busqueda`
                    + `&tipo_busqueda=${encodeURIComponent(tipo)}`
                    + `&tipo_inventario=${encodeURIComponent(tipoInventario)}`
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

        const tipoActual = window.tipoInventarioActual || 'cajas';

        if (tipoActual === 'mantecas') {
            let totalEnvases10 = 0, totalKilos10 = 0;
            let totalEnvases15 = 0, totalKilos15 = 0;
            let totalEnvases18 = 0, totalKilos18 = 0;

            let html = '';
            
            datos.forEach(prod => {
                const totalCajas = parseInt(prod.totalCajas || 0);
                const totalPeso = parseFloat(prod.totalPeso || 0);
                const textoCompleto = ((prod.nombreProducto || '') + ' ' + (prod.codigoProducto || '')).toLowerCase();

                let presentacion = 18;
                if (textoCompleto.includes('10')) {
                    presentacion = 10;
                } else if (textoCompleto.includes('15')) {
                    presentacion = 15;
                } else if (textoCompleto.includes('18')) {
                    presentacion = 18;
                } else {
                    presentacion = (totalPeso >= 70 && totalPeso <= 80) ? 18 : 18;
                }

                if (presentacion === 10) { 
                    totalEnvases10 += totalCajas; 
                    totalKilos10 += totalPeso; 
                } else if (presentacion === 15) { 
                    totalEnvases15 += totalCajas; 
                    totalKilos15 += totalPeso; 
                } else { 
                    totalEnvases18 += totalCajas; 
                    totalKilos18 += totalPeso; 
                }

                const esActivo = parseInt(prod.activo) === 1;

                html += `<tr>
                    <td>${escaparHTML(prod.codigoProducto)}</td>
                    <td><strong>${escaparHTML(prod.nombreProducto)}</strong></td>
                    <td>${escaparHTML(prod.nombreProveedor)}</td>
                    <td><span style="background: #e2e8f0; padding: 4px 8px; border-radius: 6px; font-weight: bold; color: #0f172a;">${presentacion} kg</span></td>
                    <td><span style="font-weight:700; color: #0f172a; font-size: 15px;">${totalCajas} unidad(es)</span></td> 
                    <td><strong>${totalPeso.toFixed(2)} kg</strong></td>
                    <td><span class="estado ${esActivo ? 'activo' : 'inactivo'}">${esActivo ? 'Activo' : 'Inactivo'}</span></td>
                    <td>
                        <div class="acciones" style="display: flex; gap: 6px; align-items: center;">
                            <button type="button" class="btn-accion editar" onclick="editarProducto(${prod.idProducto})"><i class="fa-solid fa-pen"></i></button>
                            <button type="button" class="btn-accion eliminar" onclick="eliminarProducto(${prod.idProducto})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
            });

            const kpi10 = document.getElementById('kpiManteca10');
            const kpiKgs10 = document.getElementById('kpisKgs10');
            const kpi15 = document.getElementById('kpiManteca15');
            const kpiKgs15 = document.getElementById('kpisKgs15');
            const kpi18 = document.getElementById('kpiManteca18');
            const kpiKgs18 = document.getElementById('kpisKgs18');

            if (kpi10) kpi10.textContent = totalEnvases10;
            if (kpiKgs10) kpiKgs10.textContent = `Total: ${totalKilos10.toFixed(2)} kg`;
            if (kpi15) kpi15.textContent = totalEnvases15;
            if (kpiKgs15) kpiKgs15.textContent = `Total: ${totalKilos15.toFixed(2)} kg`;
            if (kpi18) kpi18.textContent = totalEnvases18;
            if (kpiKgs18) kpiKgs18.textContent = `Total: ${totalKilos18.toFixed(2)} kg`;

            tbodyInventario.innerHTML = html !== '' ? html : '<tr><td colspan="8" style="text-align:center;">No hay existencias de manteca registradas.</td></tr>';
            return;
        }

        let html = '';
        datos.forEach(prod => {
            let cantidadMostrar = parseInt(prod.totalCajas) || 0;
            if (tipoActual !== 'cajas' && cantidadMostrar === 0) {
                cantidadMostrar = prod.cantidadRegistros || (prod.totalPeso > 0 ? 1 : 0);
            }

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
                <td><span style="font-weight:700; color: #0f172a;">${cantidadMostrar}</span></td> 
                <td><strong>${pesoDb.toFixed(2)} kg</strong></td>
                <td><span class="estado ${esActivo ? 'activo' : 'inactivo'}">${esActivo ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <div class="acciones">
                        <button type="button" class="btn-accion editar" onclick="editarProducto(${prod.idProducto})"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="btn-accion eliminar" onclick="eliminarProducto(${prod.idProducto})"><i class="fa-solid fa-trash"></i></button>
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
            const provJson = encodeURIComponent(JSON.stringify(prov));
            html += `<tr>
                <td>${escaparHTML(prov.codigoProveedor)}</td>
                <td><strong>${escaparHTML(prov.nombreProveedor)}</strong></td>
                <td>${escaparHTML(prov.rfc)}</td>
                <td>${escaparHTML(prov.direccion)}</td>
                <td>${escaparHTML(prov.estadoRepublica)}</td>
                <td><span class="estado ${esActivo ? 'activo' : 'inactivo'}">${esActivo ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <div class="acciones">
                        <button type="button" class="btn-accion editar" onclick="editarProveedor('${provJson}')"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="btn-accion eliminar" onclick="eliminarProveedor(${prov.idProveedor})"><i class="fa-solid fa-trash"></i></button>
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