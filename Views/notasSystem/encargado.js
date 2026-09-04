// ==========================================
// FUNCIÓN AUXILIAR DE ESCAPE HTML
// ==========================================
function escapeHtml(str) {
  if (typeof str !== 'string') return str;
  return str
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// ==========================================
// FUNCIÓN DE PAGINACIÓN UNIVERSAL
// ==========================================
window.renderizarPaginacionUniversal = function (
  paginaActual,
  totalPaginas,
  contenedorId,
  callback,
) {
  const contenedor = document.getElementById(contenedorId);
  if (!contenedor) return;

  contenedor.innerHTML = "";
  if (totalPaginas <= 1) return;

  // --- FLECHA ATRÁS ---
  const btnPrev = document.createElement("button");
  btnPrev.className = "page-btn";
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
    const puntosIzquierda = document.createElement("span");
    puntosIzquierda.className = "page-ellipsis";
    puntosIzquierda.innerText = "...";
    puntosIzquierda.onclick = () => callback(inicioBloque - 1);
    contenedor.appendChild(puntosIzquierda);
  }

  for (let i = inicioBloque; i <= finBloque; i++) {
    const btn = document.createElement("button");
    btn.className = `page-btn ${i === paginaActual ? "active" : ""}`;
    btn.innerText = i;
    btn.onclick = () => callback(i);
    contenedor.appendChild(btn);
  }

  if (finBloque < totalPaginas) {
    const puntosDerecha = document.createElement("span");
    puntosDerecha.className = "page-ellipsis";
    puntosDerecha.innerText = "...";
    puntosDerecha.onclick = () => callback(finBloque + 1);
    contenedor.appendChild(puntosDerecha);
  }

  // --- FLECHA ADELANTE ---
  const btnNext = document.createElement("button");
  btnNext.className = "page-btn";
  btnNext.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
  btnNext.disabled = paginaActual === totalPaginas;
  btnNext.onclick = () => callback(paginaActual + 1);
  contenedor.appendChild(btnNext);
};

// ==========================================
// CARGAR PRODUCTOS
// ==========================================
function cargarProductos(pagina = 1) {
  const inputBusqueda = document.getElementById("buscarProducto");
  const textoBusqueda = inputBusqueda ? inputBusqueda.value : "";
  const url = `/LostFridgeInventory/Controllers/encargadoController.php?action=listar&pagina=${pagina}&q=${encodeURIComponent(textoBusqueda)}`;

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      const container = document.getElementById("lista-productos-container");
      const paginacionContainer = document.getElementById("paginacion-productos");
      if (!container) return;
      
      container.innerHTML = "";

      if (data.success && data.productos && data.productos.length > 0) {
        let html = "";
        data.productos.forEach((prod, index) => {
          const esContable = parseInt(prod.porPiezas) === 1;
          const badgeClass = esContable ? "badge-success" : "badge-secondary";
          const badgeIcon = esContable ? "fa-check" : "fa-scale-balanced";
          const badgeText = esContable ? "Contable (Piezas)" : "Solo Kilo";

          const esFacturable = parseInt(prod.factura) === 1;
          const badgeFacturaClass = esFacturable ? "badge-warning" : "badge-secondary";
          const badgeFacturaIcon = esFacturable ? "fa-file-invoice-dollar" : "fa-receipt";
          const badgeFacturaText = esFacturable ? "Requiere Factura" : "Ticket Normal";

          const delay = index * 0.05;
          const nombreSanitizado = escapeHtml(prod.nombreProducto);
          const nombreJS = prod.nombreProducto.replace(/\\/g, "\\\\").replace(/'/g, "\\'").replace(/"/g, '&quot;');

          html += `
            <div class="product-list-item card-inner fade-in-up" style="animation-delay: ${delay}s;">
                <div class="item-details">
                    <h4 class="product-name">${nombreSanitizado}</h4>
                    <div style="display: flex; gap: 8px; margin-top: 5px; flex-wrap: wrap;">
                        <span class="badge ${badgeClass}" id="badge-piezas-${prod.idProducto}">
                            <i class="fa-solid ${badgeIcon}"></i> ${badgeText}
                        </span>
                        <span class="badge ${badgeFacturaClass}" id="badge-factura-${prod.idProducto}">
                            <i class="fa-solid ${badgeFacturaIcon}"></i> ${badgeFacturaText}
                        </span>
                    </div>
                </div>
                <div class="item-actions">
                    <button type="button" class="btn-outline btn-edit" onclick="abrirModalProducto(${prod.idProducto}, '${nombreJS}', ${prod.porPiezas}, ${prod.factura})">
                        <i class="fa-solid fa-pen"></i> Editar
                    </button>
                </div>
            </div>`;
        });
        container.innerHTML = html;

        if (paginacionContainer) {
          renderizarPaginacionUniversal(
            data.paginaActual,
            data.totalPaginas,
            "paginacion-productos",
            cargarProductos
          );
        }
      } else {
        const msj = textoBusqueda !== ""
          ? "No se encontraron productos."
          : "No hay productos disponibles.";
        container.innerHTML = `
          <div class="empty-state">
              <i class="fa-solid fa-box-open"></i>
              <p>${msj}</p>
          </div>`;
        if (paginacionContainer) paginacionContainer.innerHTML = "";
      }
    })
    .catch((error) => {
      console.error("Error al cargar productos:", error);
      const container = document.getElementById("lista-productos-container");
      if (container) {
        container.innerHTML = '<p style="color:red; text-align:center;">Error de conexión.</p>';
      }
    });
}

// ==========================================
// FILTRAR PRODUCTOS (DEBOUNCE)
// ==========================================
let timeoutBusquedaProductos;

function filtrarProductos() {
  clearTimeout(timeoutBusquedaProductos);
  timeoutBusquedaProductos = setTimeout(() => {
    cargarProductos(1);
  }, 400);
}

// ==========================================
// INICIALIZACIÓN (DOMContentLoaded)
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
  cargarProductos(1);
  cargarNotasRevision();
  iniciarEscaneoDeNotas();
  inicializarFechaGestion();

  const titulos = {
    'inicio': 'Revisión de Notas',
    'gestion': 'Gestión de Productos',
    'config': 'Ajustes'
  };

  const ultimaPestana = localStorage.getItem("ultimaPestanaEncargado") || "inicio";
  const btnCorrespondiente = document.querySelector(`.nav-item[onclick*="'${ultimaPestana}'"]`);

  window.switchTab(ultimaPestana, btnCorrespondiente, titulos[ultimaPestana] || 'Gestión');

  // FORMULARIO EDITAR PRODUCTO
  const formEditarProducto = document.getElementById("formEditarProducto");
  if (formEditarProducto) {
    formEditarProducto.addEventListener("submit", function (e) {
      e.preventDefault();

      const btn = document.getElementById("btnGuardarProducto");
      const originalText = btn ? btn.innerHTML : "";
      if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        btn.disabled = true;
      }

      const formData = new FormData(this);

      fetch("/LostFridgeInventory/Controllers/encargadoController.php?action=actualizarEstado", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            cerrarModalProducto();

            let paginaActual = 1;
            const botonActivo = document.querySelector("#paginacion-productos .page-btn.active");
            if (botonActivo) {
              paginaActual = parseInt(botonActivo.innerText) || 1;
            }

            cargarProductos(paginaActual);
          } else {
            alert("Error: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Ocurrió un error al guardar la configuración.");
        })
        .finally(() => {
          if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
          }
        });
    });
  }

  // FORMULARIO APROBAR NOTA
  const formAprobar = document.getElementById("formAprobarNota");
  if (formAprobar) {
    formAprobar.addEventListener("submit", function (e) {
      e.preventDefault();

      const btn = document.getElementById("btnConfirmarAprobacion");
      const originalText = btn ? btn.innerHTML : "";
      if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Aprobando y Guardando...';
        btn.disabled = true;
      }

      const formData = new FormData(this);

      fetch("/LostFridgeInventory/Controllers/encargadoController.php?action=aprobar", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            cerrarModalAprobar();

            const inputId = document.getElementById("modal_aprobar_id_nota");
            const idNotaAprobada = inputId ? inputId.value : null;
            const card = document.getElementById(`nota-${idNotaAprobada}`);

            if (card) {
              card.classList.add("removing");
              setTimeout(() => {
                card.remove();
                if (document.querySelectorAll(".note-card").length === 0) {
                  const container = document.getElementById("lista-notas-container");
                  if (container) {
                    container.innerHTML = `
                      <div class="empty-state">
                          <i class="fa-solid fa-check-double"></i>
                          <p>¡Todo al día! No hay notas pendientes de revisión.</p>
                      </div>`;
                  }
                }
              }, 300);
            }
          } else {
            alert("Error: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Ocurrió un error al intentar aprobar la nota.");
        })
        .finally(() => {
          if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
          }
        });
    });
  }
});

// ==========================================
// NAVEGACIÓN ENTRE PESTAÑAS
// ==========================================
window.switchTab = function (tabName, btnElement, subtitleText) {
  // Cierra el módulo de piernas si cambia de pestaña principal
  cerrarModuloPiernas();

  localStorage.setItem("ultimaPestanaEncargado", tabName);

  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });

  document.querySelectorAll(".nav-item").forEach((nav) => {
    nav.classList.remove("active");
  });

  const targetTab = document.getElementById(`tab-${tabName}`);
  if (targetTab) {
    targetTab.classList.add("active");
  }

  if (btnElement) {
    btnElement.classList.add("active");
  }

  const subtitle = document.getElementById("header-subtitle");
  if (subtitle && subtitleText) {
    subtitle.textContent = subtitleText;
  }

  window.scrollTo({ top: 0, behavior: "smooth" });
};

// ==========================================
// APROBAR NOTA (VERIFICACIÓN)
// ==========================================
function aprobarNota(idNota, btnElement) {
  const originalText = btnElement.innerHTML;

  btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
  btnElement.disabled = true;

  fetch(`/LostFridgeInventory/Controllers/encargadoController.php?action=verificarFactura&id_nota=${idNota}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        abrirModalAprobar(idNota, data.requiere_factura);
      } else {
        alert("Error al verificar nota: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error de conexión.");
    })
    .finally(() => {
      btnElement.innerHTML = originalText;
      btnElement.disabled = false;
    });
}

// ==========================================
// MODALES DE APROBACIÓN Y EDICIÓN
// ==========================================
function abrirModalAprobar(idNota, requiereFactura) {
  const inputId = document.getElementById("modal_aprobar_id_nota");
  const inputTicket1 = document.getElementById("folio_ticket_1");
  const inputTicket2 = document.getElementById("folio_ticket_2");
  
  if (inputId) inputId.value = idNota;
  if (inputTicket1) inputTicket1.value = "";
  if (inputTicket2) inputTicket2.value = "";

  const divFactura = document.getElementById("grupo_folio_factura");
  if (divFactura && inputTicket2) {
    if (requiereFactura) {
      divFactura.style.display = "block";
      inputTicket2.required = true;
    } else {
      divFactura.style.display = "none";
      inputTicket2.required = false;
    }
  }

  const modal = document.getElementById("modalAprobarNota");
  if (modal) modal.classList.add("active");
}

function cerrarModalAprobar() {
  const modal = document.getElementById("modalAprobarNota");
  if (modal) modal.classList.remove("active");
}

function abrirModalProducto(idProducto, nombreProducto, porPiezas, factura) {
  const inputId = document.getElementById('modal_id_producto');
  const inputNombre = document.getElementById('modal_nombre_producto');
  const selectPiezas = document.getElementById('modal_por_piezas');
  const selectFactura = document.getElementById('modal_factura');

  if (inputId) inputId.value = idProducto;
  if (inputNombre) inputNombre.value = nombreProducto;
  if (selectPiezas) selectPiezas.value = porPiezas;
  if (selectFactura) selectFactura.value = factura;

  const modal = document.getElementById('modalEditarProducto');
  if (modal) modal.classList.add('active');
}

function cerrarModalProducto() {
  const modal = document.getElementById("modalEditarProducto");
  if (modal) modal.classList.remove("active");
}

// ==========================================
// MÓDULO DE PIERNAS (DESPLEGABLE / SUB-PESTAÑAS)
// ==========================================
function abrirModuloPiernas() {
  registrarModalEnHistorial();
  const modal = document.getElementById('modal-modulo-piernas');
  if (modal) {
    modal.classList.add('active');
  }
}

function cerrarModuloPiernas() {
  const modal = document.getElementById('modal-modulo-piernas');
  if (modal) {
    modal.classList.remove('active');
  }
}

function cambiarSubTabPiernas(subTab, element) {
  const btns = document.querySelectorAll('.sub-tab-btn');
  btns.forEach(btn => btn.classList.remove('active'));
  if (element) element.classList.add('active');

  const tabRegistros = document.getElementById('subtab-registros');
  const tabResumen = document.getElementById('subtab-resumen');

  if (subTab === 'registros') {
    if (tabRegistros) tabRegistros.style.display = 'block';
    if (tabResumen) tabResumen.style.display = 'none';
  } else if (subTab === 'resumen') {
    if (tabRegistros) tabRegistros.style.display = 'none';
    if (tabResumen) tabResumen.style.display = 'block';
  }
}

function actualizarFechaTextoPiernas(fechaIso) {
  if (!fechaIso) return;
  const partes = fechaIso.split('-');
  const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
  const opciones = { day: 'numeric', month: 'long', year: 'numeric' };
  
  const label = document.getElementById('piernas-fecha-texto');
  if (label) {
    label.innerText = fecha.toLocaleDateString('es-ES', opciones);
  }
}

function cargarRegistrosCombo(comboCodigo) {
  console.log(`Cargando registros para combo: ${comboCodigo}`);
}

// ==========================================
// CONTROLADORES DE FECHA (SECCIÓN GESTIÓN)
// ==========================================
let fechaActualGestion = new Date();

function inicializarFechaGestion() {
  actualizarVistaFechaGestion();
}

function cambiarFecha(dias) {
  fechaActualGestion.setDate(fechaActualGestion.getDate() + dias);
  actualizarVistaFechaGestion();
}

function alSeleccionarFecha(valorFecha) {
  if (!valorFecha) return;
  const partes = valorFecha.split('-');
  fechaActualGestion = new Date(partes[0], partes[1] - 1, partes[2]);
  actualizarVistaFechaGestion();
}

function actualizarVistaFechaGestion() {
  const yyyy = fechaActualGestion.getFullYear();
  const mm = String(fechaActualGestion.getMonth() + 1).padStart(2, '0');
  const dd = String(fechaActualGestion.getDate()).padStart(2, '0');
  
  const inputFecha = document.getElementById('input-fecha-gestion');
  if (inputFecha) {
    inputFecha.value = `${yyyy}-${mm}-${dd}`;
  }

  const opciones = { day: 'numeric', month: 'short' };
  const label = document.getElementById('label-fecha-seleccionada');
  if (label) {
    label.innerText = fechaActualGestion.toLocaleDateString('es-ES', opciones);
  }
}

// ==========================================
// POLLING (VERIFICACIÓN DE NUEVAS NOTAS)
// ==========================================
let totalNotasActuales = -1;
let intervaloPolling = null;

function iniciarEscaneoDeNotas() {
  if (intervaloPolling) clearInterval(intervaloPolling);

  intervaloPolling = setInterval(() => {
    const inputBusqueda = document.getElementById('buscarNota');
    if (inputBusqueda && inputBusqueda.value !== '') return;

    fetch('/LostFridgeInventory/Controllers/encargadoController.php?action=verificarNuevasNotas')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const nuevoTotal = parseInt(data.total);

          if (totalNotasActuales === -1) {
            totalNotasActuales = nuevoTotal;
            return;
          }

          if (nuevoTotal !== totalNotasActuales) {
            totalNotasActuales = nuevoTotal;

            const tabInicio = document.querySelector('.nav-item[onclick*="inicio"] i');
            if (tabInicio) {
              tabInicio.classList.add('fa-bounce');
              setTimeout(() => tabInicio.classList.remove('fa-bounce'), 1000);
            }

            cargarNotasRevision();
          }
        }
      })
      .catch(err => console.error("Error en polling:", err));
  }, 4000);
}

// ==========================================
// CARGAR NOTAS EN REVISIÓN
// ==========================================
function cargarNotasRevision(textoBusqueda = "") {
  const container = document.getElementById("lista-notas-container");
  if (!container) return;

  const url = `/LostFridgeInventory/Controllers/encargadoController.php?action=listarRevision&q=${encodeURIComponent(textoBusqueda)}`;

  if (textoBusqueda === "") {
    if (container.innerHTML.trim() === '') {
        container.innerHTML = `
          <div style="text-align:center; padding:20px;">
              <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--text-muted);"></i>
              <p style="color: var(--text-muted); margin-top: 10px;">Buscando notas...</p>
          </div>`;
    }
  }

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      let nuevoHTML = "";

      if (data.success && data.notas && data.notas.length > 0) {
        data.notas.forEach((nota, index) => {
          const delay = index * 0.05;

          let listaProductosHTML = "";
          if (nota.productos && nota.productos.length > 0) {
            nota.productos.forEach((prod) => {
              const textoPiezas = prod.piezas > 0 ? ` / ${prod.piezas} pz` : "";
              listaProductosHTML += `<li class="word-break-fix" style="margin-bottom: 4px;"><i class="fa-solid fa-box"></i> ${escapeHtml(prod.nombreProducto)} (<strong>${prod.kilos} kg${textoPiezas}</strong>)</li>`;
            });
          } else {
            listaProductosHTML = "<li>Sin productos registrados</li>";
          }

          let listaEstibadoresHTML = "";
          if (nota.estibadores && nota.estibadores.length > 0) {
            const nombres = nota.estibadores
              .map((e) => escapeHtml(e.nombre_estibador))
              .join(", ");
            listaEstibadoresHTML = `<span>${nombres}</span>`;
          } else {
            listaEstibadoresHTML = '<span style="color:var(--text-muted);">Sin asignar</span>';
          }

          nuevoHTML += `
            <div class="note-card card-inner fade-in-up" id="nota-${nota.id_nota}" style="animation-delay: ${delay}s;">
                <div class="note-header">
                    <span class="badge badge-info"><i class="fa-solid fa-hashtag"></i> ${escapeHtml(nota.folio)}</span>
                    <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Revisión</span>
                </div>
                
                <div class="note-body">
                    <p class="note-cliente"><i class="fa-solid fa-user"></i> <strong>${escapeHtml(nota.nombre_cliente)}</strong></p>
                    <p class="note-date" style="margin-bottom: 10px;"><i class="fa-regular fa-calendar"></i> ${escapeHtml(nota.fecha_creacion)}</p>
                    
                    <div style="background: #ffffff; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); margin-bottom: 10px;">
                        <p style="font-size: 0.85rem; font-weight: bold; color: var(--navy-header); margin-bottom: 5px;">Detalle del Pedido:</p>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: var(--text-main);">
                            ${listaProductosHTML}
                        </ul>
                    </div>
                    
                    <p class="note-date" style="color: var(--text-main); margin-bottom: 5px;">
                        <i class="fa-solid fa-user-tag"></i> <strong>Vendedor:</strong> ${escapeHtml(nota.nombre_vendedor)}
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
            </div>`;
        });
      } else {
        const msj = textoBusqueda !== ""
          ? "No se encontraron notas con esa búsqueda."
          : "¡Todo al día! No hay notas pendientes de revisión.";

        nuevoHTML = `
          <div class="empty-state fade-in-up">
              <i class="fa-solid fa-check-double"></i>
              <p>${msj}</p>
          </div>`;
      }

      const tarjetasViejas = container.querySelectorAll('.note-card');
      
      if (tarjetasViejas.length > 0) {
          tarjetasViejas.forEach(tarjeta => {
              tarjeta.style.animationDelay = '0s';
              tarjeta.classList.add('fade-out-down');
          });
          
          setTimeout(() => {
              container.innerHTML = nuevoHTML;
          }, 300);
      } else {
          container.innerHTML = nuevoHTML;
      }
    })
    .catch((error) => {
      console.error("Error al cargar las notas:", error);
      document.getElementById("lista-notas-container").innerHTML = `<div class="empty-state"><p style="color:red;">Error de conexión.</p></div>`;
    });
}

// ==========================================
// FILTRAR NOTAS (DEBOUNCE)
// ==========================================
let timeoutBusquedaNotas;

function filtrarNotas() {
  const input = document.getElementById("buscarNota");
  const valor = input ? input.value : "";

  clearTimeout(timeoutBusquedaNotas);
  timeoutBusquedaNotas = setTimeout(() => {
    cargarNotasRevision(valor);
  }, 400);
}

// ==========================================
// DETECCIÓN DE SWIPE (TACTIL)
// ==========================================
let startX = 0;
let startY = 0;
let endX = 0;
let endY = 0;

const tabsOrder = [
  { id: 'inicio', title: 'Revisión de Notas' },
  { id: 'gestion', title: 'Gestión de Productos' },
  { id: 'config', title: 'Ajustes' }
];

const appContent = document.querySelector('.app-content');

if (appContent) {
  appContent.addEventListener('touchstart', (e) => {
    startX = e.changedTouches[0].screenX;
    startY = e.changedTouches[0].screenY;
  }, { passive: true });

  appContent.addEventListener('touchend', (e) => {
    endX = e.changedTouches[0].screenX;
    endY = e.changedTouches[0].screenY;
    maniobrarSwipe();
  }, { passive: true });
}

function maniobrarSwipe() {
  const umbralMinimo = 60;
  const diferenciaX = startX - endX;
  const diferenciaY = startY - endY;

  if (Math.abs(diferenciaY) > Math.abs(diferenciaX)) {
    return;
  }

  if (document.querySelector('.modal-overlay.active, .modal.active, .fullscreen-modal.active')) {
    return;
  }

  const tabActiva = document.querySelector('.tab-content.active');
  if (!tabActiva) return;

  const actualId = tabActiva.id.replace('tab-', '');
  const indexActual = tabsOrder.findIndex(t => t.id === actualId);

  if (indexActual === -1) return;

  if (diferenciaX > umbralMinimo && indexActual < tabsOrder.length - 1) {
    const siguiente = tabsOrder[indexActual + 1];
    const btnNav = document.querySelectorAll('.bottom-nav .nav-item')[indexActual + 1];
    switchTab(siguiente.id, btnNav, siguiente.title);
  }
  else if (diferenciaX < -umbralMinimo && indexActual > 0) {
    const anterior = tabsOrder[indexActual - 1];
    const btnNav = document.querySelectorAll('.bottom-nav .nav-item')[indexActual - 1];
    switchTab(anterior.id, btnNav, anterior.title);
  }
}

// ==========================================
// CONTROL DEL BOTÓN "ATRÁS" DEL TELÉFONO
// ==========================================

window.addEventListener("DOMContentLoaded", () => {
  const tabActiva = localStorage.getItem("ultimaPestanaEncargado") || "inicio";
  history.replaceState({ modalAbierto: false, tab: tabActiva }, "");
});

const originalSwitchTab = window.switchTab;
window.switchTab = function (tabName, btnElement, subtitleText) {
  if (typeof originalSwitchTab === "function") {
    originalSwitchTab(tabName, btnElement, subtitleText);
  }
  history.pushState({ modalAbierto: false, tab: tabName }, "");
};

function registrarModalEnHistorial() {
  history.pushState({ modalAbierto: true }, "");
}

const originalAbrirAprobar = window.abrirModalAprobar;
window.abrirModalAprobar = function (idNota, requiereFactura) {
  registrarModalEnHistorial();
  if (typeof originalAbrirAprobar === "function") {
    originalAbrirAprobar(idNota, requiereFactura);
  } else {
    const inputId = document.getElementById("modal_aprobar_id_nota");
    const inputTicket1 = document.getElementById("folio_ticket_1");
    const inputTicket2 = document.getElementById("folio_ticket_2");

    if (inputId) inputId.value = idNota;
    if (inputTicket1) inputTicket1.value = "";
    if (inputTicket2) inputTicket2.value = "";

    const divFactura = document.getElementById("grupo_folio_factura");
    if (divFactura && inputTicket2) {
      divFactura.style.display = requiereFactura ? "block" : "none";
      inputTicket2.required = requiereFactura;
    }

    const modal = document.getElementById("modalAprobarNota");
    if (modal) modal.classList.add("active");
  }
};

const originalAbrirProducto = window.abrirModalProducto;
window.abrirModalProducto = function (idProducto, nombreProducto, porPiezas, factura) {
  registrarModalEnHistorial();
  if (typeof originalAbrirProducto === "function") {
    originalAbrirProducto(idProducto, nombreProducto, porPiezas, factura);
  } else {
    const inputId = document.getElementById("modal_id_producto");
    const inputNombre = document.getElementById("modal_nombre_producto");
    const selectPiezas = document.getElementById("modal_por_piezas");
    const selectFactura = document.getElementById("modal_factura");

    if (inputId) inputId.value = idProducto;
    if (inputNombre) inputNombre.value = nombreProducto;
    if (selectPiezas) selectPiezas.value = porPiezas;
    if (selectFactura) selectFactura.value = factura;

    const modal = document.getElementById("modalEditarProducto");
    if (modal) modal.classList.add("active");
  }
};

window.addEventListener("popstate", (e) => {
  const modalAprobar = document.getElementById("modalAprobarNota");
  const modalEditar = document.getElementById("modalEditarProducto");
  const modalPiernas = document.getElementById("modal-modulo-piernas");

  const hayModalAbierto =
    (modalAprobar && modalAprobar.classList.contains("active")) ||
    (modalEditar && modalEditar.classList.contains("active")) ||
    (modalPiernas && modalPiernas.classList.contains("active"));

  if (hayModalAbierto) {
    if (modalAprobar) modalAprobar.classList.remove("active");
    if (modalEditar) modalEditar.classList.remove("active");
    if (modalPiernas) modalPiernas.classList.remove("active");
    return;
  }

  if (e.state && e.state.tab) {
    const titulos = {
      inicio: "Revisión de Notas",
      gestion: "Gestión de Productos",
      config: "Ajustes",
    };
    const btnNav = document.querySelector(`.nav-item[onclick*="'${e.state.tab}'"]`);

    document.querySelectorAll(".tab-content").forEach((tab) => tab.classList.remove("active"));
    document.querySelectorAll(".nav-item").forEach((nav) => nav.classList.remove("active"));

    const targetTab = document.getElementById(`tab-${e.state.tab}`);
    if (targetTab) targetTab.classList.add("active");
    if (btnNav) btnNav.classList.add("active");

    const subtitle = document.getElementById("header-subtitle");
    if (subtitle) subtitle.textContent = titulos[e.state.tab] || "Gestión";

    localStorage.setItem("ultimaPestanaEncargado", e.state.tab);
  }
});



function abrirModuloPiernas() {
    // Redirige completamente la ventana hacia piernas.php
    window.location.href = 'piernas.php';
}

function abrirModuloPiernas() {
    window.location.href = 'piernas.php';
}

function abrirModuloManteca() {
    window.location.href = 'manteca.php';
}

function abrirModuloChuletas() {
   window.location.href = 'chuleta.php';
}

function abrirModuloMazos() {
  window.location.href = 'mazo.php';
}
