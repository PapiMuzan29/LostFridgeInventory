document.addEventListener('DOMContentLoaded', () => {
    // Variables de estado
    let paginaActual = 1;

    // Referencias al DOM (Elementos de la vista)
    const tbodyReportes = document.getElementById('tabla-documentos-tbody');
    const selectTipo = document.getElementById('tipoReporte');
    const inputFechaInicio = document.getElementById('fechaInicio');
    const inputFechaFin = document.getElementById('fechaFin');
    const btnFiltrar = document.getElementById('btnFiltrar');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const indicadorPagina = document.getElementById('indicadorPagina');

    // Funcion Busqueda
    async function cargarReportes() {
        tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center;">Buscando reportes... <i class="fa-solid fa-spinner fa-spin"></i></td></tr>';

        const tipo = selectTipo.value;
        const inicio = inputFechaInicio.value;
        const fin = inputFechaFin.value;

        try {
            const url = `../Controllers/reportesController.php?action=busqueda&tipoReporte=${encodeURIComponent(tipo)}&fechaInicio=${encodeURIComponent(inicio)}&fechaFin=${encodeURIComponent(fin)}&pagina=${paginaActual}`;

            const respuesta = await fetch(url);
            
            if (!respuesta.ok) {
                throw new Error(`Error HTTP: ${respuesta.status}`);
            }

            const datos = await respuesta.json();

            renderizarTablaReportes(datos);


            if(indicadorPagina) indicadorPagina.textContent = `Página ${paginaActual}`;

        } catch (error) {
            console.error('Error al cargar la información:', error);
            tbodyReportes.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Error al conectar con el servidor.</td></tr>';
        }
    }

    // 2. renderizarTablas
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
            const fechaFin = documento.fechaFinalizacion || 'N/A';

            htmlContent += `
                <tr>
                    <td class="reporteNombre">
                        ${iconoImagen} ${documento.tipoDocumento}
                    </td>
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
                </tr>
            `;
        });

        tbodyReportes.innerHTML = htmlContent;
    }

    // Filtrar
    btnFiltrar.addEventListener('click', () => {
        const inicio = inputFechaInicio.value;
        const fin = inputFechaFin.value;

        if (inicio && fin && inicio > fin) {
            alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
            return;
        }

        paginaActual = 1; 
        cargarReportes();
    });

    btnLimpiar.addEventListener('click', () => {
        selectTipo.value = '';
        inputFechaInicio.value = '';
        inputFechaFin.value = '';
        paginaActual = 1;
        cargarReportes();
    });

    // Paginación: Anterior
    if (btnAnterior) {
        btnAnterior.addEventListener('click', () => {
            if (paginaActual > 1) {
                paginaActual--;
                cargarReportes();
            }
        });
    }

    // Paginación: Siguiente
    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', () => {
            paginaActual++;
            cargarReportes();
        });
    }

    // 4. Incicializacion
    cargarReportes();
});

