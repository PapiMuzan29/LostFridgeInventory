// Función global para manejar la descarga o impresión directa del reporte sin modales molestos
function DescargarReporte(ruta) {
    if (!ruta || ruta === '#' || ruta === '') {
        alert("❌ El archivo de este reporte aún no ha sido generado.");
        return;
    }

    // Usamos un iframe totalmente invisible para procesar el comprobante por detrás
    let iframeOculto = document.getElementById('iframeImpresionOculto');
    if (!iframeOculto) {
        iframeOculto = document.createElement('iframe');
        iframeOculto.id = 'iframeImpresionOculto';
        iframeOculto.style.display = 'none';
        document.body.appendChild(iframeOculto);
    }

    // Cargamos la ruta en el iframe oculto
    iframeOculto.src = ruta;

    // Lanza la impresión de forma automática y transparente sin mostrar pantallas intermedias
    iframeOculto.onload = function() {
        try {
            iframeOculto.contentWindow.focus();
            iframeOculto.contentWindow.print();
        } catch (e) {
            window.location.href = ruta;
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    let paginaActual  = 1;
    let totalPaginas  = 1; 
    const tbodyReportes      = document.getElementById('tabla-documentos-tbody');
    const selectTipo         = document.getElementById('tipoReporte');
    const inputFechaInicio   = document.getElementById('fechaInicio');
    const inputFechaFin      = document.getElementById('fechaFin');
    const btnFiltrar         = document.getElementById('btnFiltrar');
    const btnLimpiar         = document.getElementById('btnLimpiarFiltros');
    const btnAnterior        = document.getElementById('btnAnterior');
    const btnSiguiente       = document.getElementById('btnSiguiente');

    // ── Carga y renderizado ──────────────────────────────────────────
    async function cargarReportes() {
        if (!tbodyReportes) return;
        tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center;">Buscando reportes... <i class="fa-solid fa-spinner fa-spin"></i></td></tr>';

        const tipo   = selectTipo ? selectTipo.value : '';
        const inicio = inputFechaInicio ? inputFechaInicio.value : '';
        const fin    = inputFechaFin ? inputFechaFin.value : '';

        try {
            const url = `../Controllers/reportesController.php?action=busqueda`
                + `&tipoReporte=${encodeURIComponent(tipo)}`
                + `&fechaInicio=${encodeURIComponent(inicio)}`
                + `&fechaFin=${encodeURIComponent(fin)}`
                + `&pagina=${paginaActual}`;

            const respuesta = await fetch(url);
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);

            const resultado = await respuesta.json();
            totalPaginas = resultado.totalPaginas ?? 1;

            renderizarTablaReportes(resultado.datos ?? []);
            actualizarPaginacion();

        } catch (error) {
            console.error('Error detallado:', error);
            tbodyReportes.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red;">❌ Error al conectar con el servidor.</td></tr>`;
        }
    }

    function renderizarTablaReportes(datos) {
        if (!Array.isArray(datos) || datos.length === 0) {
            tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center;">No se encontraron resultados con estos filtros.</td></tr>';
            return;
        }

        let htmlContent = '';

        datos.forEach(documento => {
            const rutaArchivo = documento.rutaArchivo || '#';
            let iconoImagen = '<i class="fa-solid fa-file-lines"></i>';
            let tituloReporte = 'Reporte General';
            let descripcionInventario = 'Registro del sistema';

            if (documento.tipoDocumento === 'movimiento_inventario') {
                iconoImagen = '<i class="fa-solid fa-clipboard-check"></i>';
                tituloReporte = 'Movimiento de Inventario';
                descripcionInventario = 'Entradas y salidas por rango de fechas';
            } else if (documento.tipoDocumento === 'inventario_actual') {
                iconoImagen = '<i class="fa-solid fa-cube"></i>';
                tituloReporte = 'Inventario Actual';
                descripcionInventario = 'Stock actual por producto';
            }

            htmlContent += `
                <tr>
                    <td class="reporteNombre">${iconoImagen} ${tituloReporte}</td>
                    <td><span style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 12px;">${documento.tipoDocumento}</span></td>
                    <td>${descripcionInventario}</td>
                    <td>${documento.nombreCompletoResponsable || 'Sistema'}</td>
                    <td>${documento.fechaCreacion}</td>
                    <td>${documento.fechaFinalizacion || 'N/A'}</td>
                    <td>
                        <button type="button" class="btnDescargar" onclick="DescargarReporte('${rutaArchivo}')">
                            <i class="fa-solid fa-download"></i>
                        </button>
                    </td>
                </tr>`;
        });

        tbodyReportes.innerHTML = htmlContent;
    }

    // ── Paginación ───────────────────────────────────────────────────
    function actualizarPaginacion() {
        if (btnAnterior) btnAnterior.disabled  = paginaActual <= 1;
        if (btnSiguiente) btnSiguiente.disabled = paginaActual >= totalPaginas;

        const contenedor = document.querySelector('.botones-paginacion');
        if (!contenedor) return;

        contenedor.querySelectorAll('.numero-pagina').forEach(b => b.remove());

        for (let i = 1; i <= totalPaginas; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.classList.add('btn-pagina', 'numero-pagina');
            if (i === paginaActual) btn.classList.add('activa');
            btn.textContent = i;
            btn.addEventListener('click', () => {
                paginaActual = i;
                cargarReportes();
            });
            contenedor.insertBefore(btn, btnSiguiente);
        }
    }

    // ── Eventos ──────────────────────────────────────────────────────
    if (btnFiltrar) {
        btnFiltrar.addEventListener('click', () => {
            const inicio = inputFechaInicio ? inputFechaInicio.value : '';
            const fin    = inputFechaFin ? inputFechaFin.value : '';

            if (inicio && fin && inicio > fin) {
                alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
                return;
            }

            paginaActual = 1;
            cargarReportes();
        });
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            if (selectTipo) selectTipo.value = '';
            if (inputFechaInicio) inputFechaInicio.value = '';
            if (inputFechaFin) inputFechaFin.value = '';
            paginaActual = 1;
            cargarReportes();
        });
    }

    if (btnAnterior) {
        btnAnterior.addEventListener('click', () => {
            if (paginaActual > 1) {
                paginaActual--;
                cargarReportes();
            }
        });
    }

    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', () => {
            if (paginaActual < totalPaginas) {
                paginaActual++;
                cargarReportes();
            }
        });
    }

    // ── Inicio ───────────────────────────────────────────────────────
    cargarReportes();
});