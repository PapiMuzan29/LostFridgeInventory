// ==========================================
// FUNCIÓN DE PAGINACIÓN UNIVERSAL
// ==========================================
window.renderizarPaginacionUniversal = function(paginaActual, totalPaginas, contenedorId, callback) {
    const contenedor = document.getElementById(contenedorId);
    if (!contenedor) return;
    
    contenedor.innerHTML = '';
    if (totalPaginas <= 1) return; // Si hay 1 o 0 páginas, no dibujamos nada

    // --- FLECHA ATRÁS ---
    const btnPrev = document.createElement('button');
    btnPrev.className = 'page-btn';
    btnPrev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    btnPrev.disabled = paginaActual === 1;
    btnPrev.onclick = () => callback(paginaActual - 1);
    contenedor.appendChild(btnPrev);

    // --- LÓGICA DE BLOQUES DE 4 ---
    const tamañoBloque = 4;
    const bloqueActual = Math.ceil(paginaActual / tamañoBloque);
    
    const inicioBloque = (bloqueActual - 1) * tamañoBloque + 1;
    const finBloque = Math.min(inicioBloque + tamañoBloque - 1, totalPaginas);

    if (inicioBloque > 1) {
        const puntosIzquierda = document.createElement('span');
        puntosIzquierda.className = 'page-ellipsis';
        puntosIzquierda.innerText = '...';
        puntosIzquierda.onclick = () => callback(inicioBloque - 1);
        contenedor.appendChild(puntosIzquierda);
    }

    for (let i = inicioBloque; i <= finBloque; i++) {
        const btn = document.createElement('button');
        btn.className = `page-btn ${i === paginaActual ? 'active' : ''}`;
        btn.innerText = i;
        btn.onclick = () => callback(i);
        contenedor.appendChild(btn);
    }

    if (finBloque < totalPaginas) {
        const puntosDerecha = document.createElement('span');
        puntosDerecha.className = 'page-ellipsis';
        puntosDerecha.innerText = '...';
        puntosDerecha.onclick = () => callback(finBloque + 1);
        contenedor.appendChild(puntosDerecha);
    }

    // --- FLECHA ADELANTE ---
    const btnNext = document.createElement('button');
    btnNext.className = 'page-btn';
    btnNext.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    btnNext.disabled = paginaActual === totalPaginas;
    btnNext.onclick = () => callback(paginaActual + 1);
    contenedor.appendChild(btnNext);
};

// ==========================================
// CARGAR PRODUCTOS MODIFICADO
// ==========================================
function cargarProductos(pagina = 1) {
    // Leemos el valor del input en este momento exacto
    // Así, si damos clic en la "Página 2", mantendrá el filtro aplicado
    const textoBusqueda = document.getElementById('buscarProducto').value;
    const url = `/LostFridgeInventory/Controllers/encargadoController.php?action=listar&pagina=${pagina}&q=${encodeURIComponent(textoBusqueda)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('lista-productos-container');
            const paginacionContainer = document.getElementById('paginacion-productos');
            container.innerHTML = ''; 

            if (data.success && data.productos.length > 0) {
                let html = '';
                data.productos.forEach((prod, index) => {
                    const esContable = parseInt(prod.porPiezas) === 1;
                    const badgeClass = esContable ? 'badge-success' : 'badge-secondary';
                    const badgeIcon = esContable ? 'fa-check' : 'fa-scale-balanced';
                    const badgeText = esContable ? 'Contable (Piezas)' : 'Solo Kilo';
                    const delay = index * 0.1;

                    html += `
                        <div class="product-list-item card-inner fade-in-up" style="animation-delay: ${delay}s;">
                            <div class="item-details">
                                <h4 class="product-name">${prod.nombreProducto}</h4>
                                <span class="badge ${badgeClass}" id="badge-${prod.idProducto}">
                                    <i class="fa-solid ${badgeIcon}"></i> ${badgeText}
                                </span>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-outline btn-edit" onclick="abrirModalProducto(${prod.idProducto}, '${prod.nombreProducto.replace(/'/g, "\\'")}', ${prod.porPiezas})">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;

                // Dibujar botones de paginación
                renderizarPaginacionUniversal(
                    data.paginaActual, 
                    data.totalPaginas, 
                    'paginacion-productos', 
                    cargarProductos // <-- Este callback pasará el número de página
                );
                
            } else {
                const msj = textoBusqueda !== '' ? 'No se encontraron productos.' : 'No hay productos disponibles.';
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <p>${msj}</p>
                    </div>`;
                paginacionContainer.innerHTML = ''; // Limpiar botones de paginación
            }
        })
        .catch(error => {
            console.error('Error al cargar productos:', error);
            document.getElementById('lista-productos-container').innerHTML = '<p style="color:red; text-align:center;">Error de conexión.</p>';
        });
}

// ==========================================
// FILTRAR PRODUCTOS (CON DEBOUNCE)
// ==========================================
let timeoutBusquedaProductos;

function filtrarProductos() {
    clearTimeout(timeoutBusquedaProductos);
    
    timeoutBusquedaProductos = setTimeout(() => {
        // Al buscar algo nuevo, SIEMPRE regresamos a la página 1
        cargarProductos(1);
    }, 400);
}

// Al inicio (DOMContentLoaded) llamamos a cargarProductos(1)
document.addEventListener('DOMContentLoaded', () => {
    cargarProductos(1);

    cargarNotasRevision();
const formEditarProducto = document.getElementById('formEditarProducto');
    if (formEditarProducto) {
        formEditarProducto.addEventListener('submit', function(e) {
            e.preventDefault(); // Evita que la página recargue
            
            const btn = document.getElementById('btnGuardarProducto');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            btn.disabled = true;

            // Atrapa id_producto y por_piezas automáticamente del HTML
            const formData = new FormData(this); 

            fetch('/LostFridgeInventory/Controllers/encargadoController.php?action=actualizarEstado', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    cerrarModalProducto();
                    
                    // Averiguamos en qué página estamos actualmente
                    let paginaActual = 1;
                    const botonActivo = document.querySelector('.page-btn.active');
                    if (botonActivo) {
                        paginaActual = parseInt(botonActivo.innerText);
                    }
                    
                    // Recargamos los productos para que se vea el cambio visual
                    cargarProductos(paginaActual); 
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al guardar la configuración.');
            })
            .finally(() => {
                // Restauramos el botón
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }
});

// ==========================================
// NAVEGACIÓN ENTRE PESTAÑAS
// ==========================================

window.switchTab = function(tabName, btnElement, subtitleText) {
    
    // 1. Ocultar todos los contenedores de las pestañas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // 2. Quitar el color azul (clase active) de todos los botones inferiores
    document.querySelectorAll('.nav-item').forEach(nav => {
        nav.classList.remove('active');
    });

    // 3. Mostrar la pestaña que se solicitó (ej: 'tab-inicio' o 'tab-gestion')
    const targetTab = document.getElementById(`tab-${tabName}`);
    if (targetTab) {
        targetTab.classList.add('active');
    }

    // 4. Poner en azul (clase active) el botón que el usuario acaba de presionar
    if (btnElement) {
        btnElement.classList.add('active');
    }

    // 5. Cambiar el subtítulo del encabezado superior
    const subtitle = document.getElementById('header-subtitle');
    if (subtitle) {
        subtitle.textContent = subtitleText;
    }

    // 6. Regresar la pantalla hasta arriba de forma suave
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

function aprobarNota(idNota, btnElement) {
    btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Aprobando...';
    btnElement.disabled = true;

    const params = new URLSearchParams();
    params.append('id_nota', idNota);

    fetch('/LostFridgeInventory/Controllers/encargadoController.php?action=aprobar', {
        method: 'POST',
        body: params
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // 4. Si fue exitoso, animamos la tarjeta para que desaparezca
            const card = document.getElementById(`nota-${idNota}`);
            card.classList.add('removing');
            
            setTimeout(() => {
                card.remove();
                // Opcional: Revisar si la lista quedó vacía para mostrar el mensaje de "No hay notas"
                if(document.querySelectorAll('.note-card').length === 0) {
                    document.getElementById('lista-notas-container').innerHTML = `
                        <div class="empty-state">
                            <i class="fa-solid fa-check-double"></i>
                            <p>¡Todo al día! No hay más notas pendientes.</p>
                        </div>`;
                }
            }, 300); 
        } else {
            alert('Error: ' + data.message);
            btnElement.innerHTML = '<i class="fa-solid fa-check"></i> Aprobar Nota';
            btnElement.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btnElement.innerHTML = '<i class="fa-solid fa-check"></i> Aprobar Nota';
        btnElement.disabled = false;
    });
}

// ==========================================
// 4. CARGAR NOTAS EN REVISIÓN (Pestaña Inicio)
// ==========================================
function cargarNotasRevision(textoBusqueda = '') {
    const container = document.getElementById('lista-notas-container');
    const url = `/LostFridgeInventory/Controllers/encargadoController.php?action=listarRevision&q=${encodeURIComponent(textoBusqueda)}`;
    
    // Solo ponemos el loader si la búsqueda está vacía (para que al escribir no parpadee tan feo)
    if (textoBusqueda === '') {
        container.innerHTML = `
            <div style="text-align:center; padding:20px;">
                <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--text-muted);"></i>
                <p style="color: var(--text-muted); margin-top: 10px;">Buscando notas...</p>
            </div>`;
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            container.innerHTML = ''; // Limpiamos el loader

            if (data.success && data.notas.length > 0) {
                let html = '';
                data.notas.forEach((nota, index) => {
                    const delay = index * 0.1; 
                    
                    // 1. Armamos la lista HTML de Productos
                    let listaProductosHTML = '';
                    if (nota.productos && nota.productos.length > 0) {
                        nota.productos.forEach(prod => {
                            // Si tiene piezas > 0 las mostramos, si no, solo kilos
                            const textoPiezas = prod.piezas > 0 ? ` / ${prod.piezas} pz` : '';
                            listaProductosHTML += `<li class="word-break-fix" style="margin-bottom: 4px;"><i class="fa-solid fa-box"></i> ${prod.nombreProducto} (<strong>${prod.kilos} kg${textoPiezas}</strong>)</li>`;
                        });
                    } else {
                        listaProductosHTML = '<li>Sin productos registrados</li>';
                    }

                    // 2. Armamos la lista HTML de Estibadores
                    let listaEstibadoresHTML = '';
                    if (nota.estibadores && nota.estibadores.length > 0) {
                        const nombres = nota.estibadores.map(e => e.nombre_estibador).join(', ');
                        listaEstibadoresHTML = `<span>${nombres}</span>`;
                    } else {
                        listaEstibadoresHTML = '<span style="color:var(--text-muted);">Sin asignar</span>';
                    }
                    
                    // 3. Insertamos todo en la tarjeta
                    html += `
                        <div class="note-card card-inner fade-in-up" id="nota-${nota.id_nota}" style="animation-delay: ${delay}s;">
                            <div class="note-header">
                                <span class="badge badge-info"><i class="fa-solid fa-hashtag"></i> ${nota.folio}</span>
                                <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Revisión</span>
                            </div>
                            
                            <div class="note-body">
                                <p class="note-cliente"><i class="fa-solid fa-user"></i> <strong>${nota.nombre_cliente}</strong></p>
                                <p class="note-date" style="margin-bottom: 10px;"><i class="fa-regular fa-calendar"></i> ${nota.fecha_creacion}</p>
                                
                                <!-- DETALLES DE PRODUCTOS -->
                                <div style="background: #ffffff; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); margin-bottom: 10px;">
                                    <p style="font-size: 0.85rem; font-weight: bold; color: var(--navy-header); margin-bottom: 5px;">Detalle del Pedido:</p>
                                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: var(--text-main);">
                                        ${listaProductosHTML}
                                    </ul>
                                </div>
                                
                                <!-- PERSONAL INVOLUCRADO -->
                                <p class="note-date" style="color: var(--text-main); margin-bottom: 5px;">
                                    <i class="fa-solid fa-user-tag"></i> <strong>Vendedor:</strong> ${nota.nombre_vendedor}
                                </p>
                                <p class="note-date" style="color: var(--text-main);">
                                    <i class="fa-solid fa-people-carry-box"></i> <strong>Estibadores:</strong> ${listaEstibadoresHTML}
                                </p>
                            </div>
                            
                            <div class="note-footer" style="margin-top: 15px; border-top: 1px dashed var(--border-color); padding-top: 15px;">
                                <button type="button" class="btn-primary" style="min-height: 40px;" onclick="aprobarNota(${nota.id_nota}, this)">
                                    <i class="fa-solid fa-check"></i> Aprobar Nota
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                // Si no encontró nada (ya sea en general o por la búsqueda)
                const msj = textoBusqueda !== '' 
                    ? 'No se encontraron notas con esa búsqueda.' 
                    : '¡Todo al día! No hay notas pendientes de revisión.';
                    
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-check-double"></i>
                        <p>${msj}</p>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar las notas:', error);
            container.innerHTML = `<div class="empty-state"><p style="color:red;">Error de conexión.</p></div>`;
        });
}

// ==========================================
// FILTRAR NOTAS (CON DEBOUNCE / RETRASO)
// ==========================================
let timeoutBusquedaNotas;

function filtrarNotas() {
    const input = document.getElementById('buscarNota').value;
    
    clearTimeout(timeoutBusquedaNotas);
    timeoutBusquedaNotas = setTimeout(() => {
        cargarNotasRevision(input);
    }, 400);
}

// ==========================================
// LÓGICA DEL MODAL DE PRODUCTOS
// ==========================================
function abrirModalProducto(idProducto, nombreProducto, porPiezas) {
    // 1. Llenamos el modal con los datos actuales del producto
    document.getElementById('modal_id_producto').value = idProducto;
    document.getElementById('modal_nombre_producto').value = nombreProducto;
    document.getElementById('modal_por_piezas').value = porPiezas;
    
    // 2. Mostramos el modal
    const modal = document.getElementById('modalEditarProducto');
    modal.classList.add('active');
}

function cerrarModalProducto() {
    const modal = document.getElementById('modalEditarProducto');
    modal.classList.remove('active');
}