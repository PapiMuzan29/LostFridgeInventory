// ../Services/funcionesMovimientos.js

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById('formFiltrosMovimientos');
    const inputBusqueda = document.getElementById('inputBusquedaUsuario');
    const inputFecha = document.getElementById('inputFechaFiltro');
    const selectTipo = document.getElementById('selectTipoMovimiento');
    
    const btnAnterior = document.getElementById('btnAnteriorMov');
    const btnSiguiente = document.getElementById('btnSiguienteMov');
    const inputPagina = document.getElementById('inputPagina');

    // 📡 Carga inicial automática de datos
    cargarMovimientos();

    // ===================================================================================
    // LISTENERS PARA FILTROS
    // ===================================================================================
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (inputPagina) inputPagina.value = 1;
            cargarMovimientos();
        });
    }

    if (selectTipo) {
        selectTipo.addEventListener('change', () => {
            if (inputPagina) inputPagina.value = 1;
            cargarMovimientos();
        });
    }

    if (inputFecha) {
        inputFecha.addEventListener('change', () => {
            if (inputPagina) inputPagina.value = 1;
            cargarMovimientos();
        });
    }

    let timeoutBusqueda;
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', () => {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                if (inputPagina) inputPagina.value = 1;
                cargarMovimientos();
            }, 300);
        });
    }

    // ===================================================================================
    // LISTENERS PARA LAS FLECHAS (AVANZAR Y RETROCEDER PASO A PASO)
    // ===================================================================================
    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', () => {
            let pag = parseInt(inputPagina.value) || 1;
            inputPagina.value = pag + 1;
            cargarMovimientos();
        });
    }

    if (btnAnterior) {
        btnAnterior.addEventListener('click', () => {
            let pag = parseInt(inputPagina.value) || 1;
            if (pag > 1) {
                inputPagina.value = pag - 1;
                cargarMovimientos();
            }
        });
    }

    // Botón Limpiar Filtros
    const btnLimpiar = document.getElementById('btnLimpiarMov');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            if (inputBusqueda) inputBusqueda.value = '';
            if (inputFecha) inputFecha.value = '';
            if (selectTipo) selectTipo.value = 'todos';
            if (inputPagina) inputPagina.value = 1;
            cargarMovimientos();
        });
    }
});

/**
 * 📡 Obtiene los registros filtrados desde el controlador de movimientos
 */
async function cargarMovimientos() {
    const form = document.getElementById('formFiltrosMovimientos');
    const inputPagina = document.getElementById('inputPagina');
    if (!form || !inputPagina) return;

    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();

    try {
        const response = await fetch(`../Controllers/movimientosController.php?action=buscar&${params}`);
        const res = await response.json();

        if (res.error) {
            console.error("Error devuelto por el controlador:", res.error);
            return;
        }

        // 1. Renderizar las filas en la tabla
        renderizarTablaMovimientos(res.datos);

        // 2. Sincronizar el estilo de los botones numéricos fijos
        const totalPaginasBackend = parseInt(res.paginas) || 1;
        actualizarEstiloPaginacion(parseInt(inputPagina.value) || 1, totalPaginasBackend);

        // 3. Actualizar el panel de estadísticas superior
        const cardContador = document.getElementById('cardMovimientosDia');
        if (cardContador && res.contadores && res.contadores.movimientosDia !== undefined) {
            cardContador.textContent = res.contadores.movimientosDia;
        }

    } catch (error) {
        console.error("Error crítico al cargar la bitácora:", error);
    }
}

/**
 * 🎨 Convierte el arreglo de datos JSON en HTML puro para el tbody
 */
function renderizarTablaMovimientos(datos) {
    const tbody = document.getElementById('tablaMovimientosBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!datos || datos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#64748b;">No se encontraron movimientos con los filtros seleccionados.</td></tr>`;
        return;
    }

    datos.forEach(row => {
        let badgeClass = 'badge-usr';
        if (row.tipo === 'entrada') badgeClass = 'badge-ent';
        if (row.tipo === 'salida') badgeClass = 'badge-sal';
        if (row.tipo === 'inventario') badgeClass = 'badge-inv';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${row.hora}</strong></td>
            <td><span class="badge-tipo ${badgeClass}">${row.tipo.toUpperCase()}</span></td>
            <td class="usuarioCell"><i class="fas fa-user-circle"></i> <strong>${row.usuarioResponsable}</strong></td>
            <td>${row.descripcion}</td>
            <td>${row.moduloAfectado}</td>
            <td class="text-center">
                <button type="button" class="btn-info-bitacora" onclick="verDetallesJson(${row.idMovimiento})">
                    <i class="fas fa-info-circle"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * 🔢 CONTROL VISUAL TOTALMENTE DESBLOQUEADO
 */
function actualizarEstiloPaginacion(actual, totales) {
    const numPag1 = document.getElementById('numPagMov1');
    const numPag2 = document.getElementById('numPagMov2');
    const btnAnterior = document.getElementById('btnAnteriorMov');
    const btnSiguiente = document.getElementById('btnSiguienteMov');

    // 1. 🔓 DESBLOQUEO TOTAL DE FLECHAS
    if (btnAnterior) {
        btnAnterior.disabled = (actual <= 1); // Solo se bloquea si estás en la página 1
    }
    if (btnSiguiente) {
        btnSiguiente.disabled = false; // 👈 ¡NUNCA SE BLOQUEA! Siempre te deja avanzar
    }

    // 2. 🪄 EFECTO ILUSIÓN: El primer botón toma el número de la página actual
    // y el segundo botón muestra el número siguiente.
    if (numPag1) numPag1.textContent = actual;
    if (numPag2) numPag2.textContent = actual + 1;

    // 3. 🔒 COLOR CONGELADO: El botón de la izquierda SIEMPRE está iluminado
    numPag1?.classList.add('activa');
    numPag2?.classList.remove('activa'); // El de la derecha se queda gris de fondo

    // 4. Mantener visibles ambos botones en la interfaz
    if (numPag1) numPag1.style.display = 'inline-block';
    if (numPag2) numPag2.style.display = 'inline-block';
}