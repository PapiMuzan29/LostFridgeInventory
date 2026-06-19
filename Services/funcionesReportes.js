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
        tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center;">Buscando reportes... <i class="fa-solid fa-spinner fa-spin"></i></td></tr>';

        const tipo   = selectTipo.value;
        const inicio = inputFechaInicio.value;
        const fin    = inputFechaFin.value;

        try {
            const url = `../Controllers/reportesController.php?action=busqueda`
                + `&tipoReporte=${encodeURIComponent(tipo)}`
                + `&fechaInicio=${encodeURIComponent(inicio)}`
                + `&fechaFin=${encodeURIComponent(fin)}`
                + `&pagina=${paginaActual}`;

            const respuesta = await fetch(url);
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);

            const resultado = await respuesta.json(); // { datos, total, pagina, totalPaginas }

            totalPaginas = resultado.totalPaginas ?? 1;

            renderizarTablaReportes(resultado.datos ?? []);
            actualizarPaginacion();

        } catch (error) {
            console.error('Error al cargar la información:', error);
            tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Error al conectar con el servidor.</td></tr>';
        }
    }

    function renderizarTablaReportes(datos) {
        if (!Array.isArray(datos) || datos.length === 0) {
            tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center;">No se encontraron resultados con estos filtros.</td></tr>';
            return;
        }

        let htmlContent = '';

        datos.forEach(documento => {
            const rutaArchivo = documento.rutaArchivo;
            let iconoImagen = '';
            let descripcionInventario = '';

            switch (documento.tipoDocumento) {
                case 'movimiento_inventario':
                    iconoImagen = '<i class="fa-solid fa-clipboard-check"></i>';
                    descripcionInventario = 'Entradas y salidas por rango de fechas';
                    break;
                case 'inventario_actual':
                    iconoImagen = '<i class="fa-solid fa-cube"></i>';
                    descripcionInventario = 'Stock actual por producto y ubicación';
                    break;
                case 'vencimiento':
                    iconoImagen = '<i class="fa-solid fa-calendar"></i>';
                    descripcionInventario = 'Productos próximos a vencer';
                    break;
                case 'movimientos_usuario':
                    iconoImagen = '<i class="fa-solid fa-users"></i>';
                    descripcionInventario = 'Actividad realizada por cada usuario';
                    break;
                case 'utilizacion_ubicaciones':
                    iconoImagen = '<i class="fa-solid fa-chart-pie"></i>';
                    descripcionInventario = 'Utilización de ubicaciones';
                    break;
                default:
                    iconoImagen = '<i class="fa-solid fa-file-lines"></i>';
                    descripcionInventario = 'Reporte general';
                    break;
            }

            const responsable = documento.nombreCompletoResponsable || 'Sistema';
            const fechaFin    = documento.fechaFinalizacion || 'N/A';

            htmlContent += `
                <tr>
                    <td class="reporteNombre">${iconoImagen} ${documento.tipoDocumento}</td>
                    <td>${documento.tipoDocumento}</td>
                    <td>${descripcionInventario}</td>
                    <td>${responsable}</td>
                    <td>${documento.fechaCreacion}</td>
                    <td>${fechaFin}</td>
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
        // Deshabilitar / habilitar botones
        btnAnterior.disabled  = paginaActual <= 1;
        btnSiguiente.disabled = paginaActual >= totalPaginas;

        // Redibujar los números de página
        const contenedor = document.querySelector('.botones-paginacion');

        // Eliminar los botones de número que ya existen
        contenedor.querySelectorAll('.numero-pagina').forEach(b => b.remove());

        // Insertar los nuevos números antes del botón Siguiente
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
    btnFiltrar.addEventListener('click', () => {
        const inicio = inputFechaInicio.value;
        const fin    = inputFechaFin.value;

        if (inicio && fin && inicio > fin) {
            alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
            return;
        }

        paginaActual = 1;
        cargarReportes();
    });

    btnLimpiar.addEventListener('click', () => {
        selectTipo.value        = '';
        inputFechaInicio.value  = '';
        inputFechaFin.value     = '';
        paginaActual = 1;
        cargarReportes();
    });

    btnAnterior.addEventListener('click', () => {
        if (paginaActual > 1) {
            paginaActual--;
            cargarReportes();
        }
    });

    btnSiguiente.addEventListener('click', () => {
        if (paginaActual < totalPaginas) {
            paginaActual++;
            cargarReportes();
        }
    });

    // ── Inicio ───────────────────────────────────────────────────────
    cargarReportes();
});