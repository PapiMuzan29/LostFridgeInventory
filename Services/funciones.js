// ===================================================================================
// 1. MUESTRA LA HORA EN TIEMPO REAL
// ===================================================================================
function actualizarHora() {
    const ahora = new Date();
    const horas = ahora.getHours().toString().padStart(2, '0');
    const minutos = ahora.getMinutes().toString().padStart(2, '0');
    const segundos = ahora.getSeconds().toString().padStart(2, '0');
    
    const contenedorHora = document.getElementById("hora"); 
    if (contenedorHora) {
        contenedorHora.textContent = `${horas}:${minutos}:${segundos}`;
    }
}    
  const ahora = new Date();
  const horas = ahora.getHours().toString().padStart(2, "0");
  const minutos = ahora.getMinutes().toString().padStart(2, "0");
  const segundos = ahora.getSeconds().toString().padStart(2, "0");

  const contenedorHora = document.getElementById("hora"); // Asegúrate de que coincida con tu ID
  if (contenedorHora) {
    contenedorHora.textContent = `${horas}:${minutos}:${segundos}`;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  setInterval(actualizarHora, 1000);
  actualizarHora();
});

// ===================================================================================
// 2. FUNCIÓN PARA MOSTRAR Y OCULTAR CONTRASEÑA
// ===================================================================================
document.addEventListener("DOMContentLoaded", () => {
  const inputPassword = document.getElementById("inputPassword");
  const iconoOjo = document.getElementById("iconoOjo");

  if (iconoOjo && inputPassword) {
    iconoOjo.addEventListener("click", () => {
      if (inputPassword.type === "password") {
        inputPassword.type = "text";
        iconoOjo.classList.remove("fa-eye");
        iconoOjo.classList.add("fa-eye-slash");
      } else {
        inputPassword.type = "password";
        iconoOjo.classList.remove("fa-eye-slash");
        iconoOjo.classList.add("fa-eye");
      }
    });
  }
});

// ===================================================================================
// 3. FUNCIÓN PARA REALIZAR UNA BÚSQUEDA EN TIEMPO REAL (CORREGIDA)
// ===================================================================================
function configurarBusquedaRealTime(inputId, tbodyId, urlBackend, renderFila) {
  const input = document.getElementById(inputId);
  const tbody = document.getElementById(tbodyId);

  let temporizador = null;
  let paginaActual = 1;

    if (!input) {
        console.error(`Error: No se encontró el input con ID "${inputId}"`);
  // Alerta visual en consola si los IDs no coinciden con el HTML
  if (!input) {
    console.error(`Error: No se encontró el input con ID "${inputId}"`);
    return;
  }
  if (!tbody) {
    console.error(`Error: No se encontró el tbody con ID "${tbodyId}"`);
    return;
  }

  async function cargarDatos() {
    const termino = input.value;
    const estado = document.getElementById("selectEstado")?.value ?? "";

    try {
      const respuesta = await fetch(
        `${urlBackend}?action=busqueda` +
          `&busqueda=${encodeURIComponent(termino)}` +
          `&estado=${encodeURIComponent(estado)}` +
          `&pagina=${paginaActual}`,
      );

      const datos = await respuesta.json();

      tbody.innerHTML = "";

      if (!Array.isArray(datos) || datos.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="5">No se encontraron resultados</td></tr>';
        return;
      }

      datos.forEach((item) => {
        tbody.innerHTML += renderFila(item);
      });
    } catch (error) {
      console.error("Error al buscar:", error);
      tbody.innerHTML = '<tr><td colspan="5">Error al cargar datos</td></tr>';
    }
  }

  // 🔥 CORRECCIÓN 1: Cargar los datos automáticamente al abrir la página
  cargarDatos();

  input.addEventListener("input", function () {
    clearTimeout(temporizador);
    paginaActual = 1;
    temporizador = setTimeout(() => {
      cargarDatos();
    }, 300);
  });

  // 🔥 CORRECCIÓN 2: Paginación inteligente que acepta direcciones ('anterior' / 'siguiente')
  window.cambiarPaginaUsuarios = function (accion) {
    if (accion === "anterior") {
      if (paginaActual > 1) {
        paginaActual--;
        cargarDatos();
      }
    } else if (accion === "siguiente") {
      // Incrementa la página de manera dinámica
      paginaActual++;
      cargarDatos();
    } else {
      // Por si acaso pasas un número directo
      const nuevaPagina = parseInt(accion);
      if (!isNaN(nuevaPagina) && nuevaPagina >= 1) {
        paginaActual = nuevaPagina;
        cargarDatos();
      }
    }

    async function cargarDatos() {
        const termino = input.value;
        const estado = document.getElementById('selectEstado')?.value ?? '';

        try {
            const respuesta = await fetch(
                `${urlBackend}?action=busqueda`
                + `&busqueda=${encodeURIComponent(termino)}`
                + `&estado=${encodeURIComponent(estado)}`
                + `&pagina=${paginaActual}`
            );

            const datos = await respuesta.json();

            tbody.innerHTML = '';

            if (!Array.isArray(datos) || datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5">No se encontraron resultados</td></tr>';
                return;
            }

            datos.forEach(item => {
                tbody.innerHTML += renderFila(item);
            });

        } catch (error) {
            console.error("Error al buscar:", error);
            tbody.innerHTML = '<tr><td colspan="5">Error al cargar datos</td></tr>';
        }
    }

    cargarDatos();

    input.addEventListener('input', function () {
        clearTimeout(temporizador);
        paginaActual = 1;
        temporizador = setTimeout(() => {
            cargarDatos();
        }, 300);
    });

    window.cambiarPaginaUsuarios = function (accion) {
        if (accion === 'anterior') {
            if (paginaActual > 1) {
                paginaActual--;
                cargarDatos();
            }
        } else if (accion === 'siguiente') {
            paginaActual++;
            cargarDatos();
        } else {
            const nuevaPagina = parseInt(accion);
            if (!isNaN(nuevaPagina) && nuevaPagina >= 1) {
                paginaActual = nuevaPagina;
                cargarDatos();
            }
        }
    };
  };
}

// ===================================================================================
// 4. RENDERIZADOR DE FILAS PARA LA TABLA DE USUARIOS
// ===================================================================================
function renderizarFilaUsuario(usuario) {
    const estadoClase = Number(usuario.estado) === 1 ? 'activo' : 'inactivo';
    const estadoTexto = Number(usuario.estado) === 1 ? 'Activo' : 'Inactivo';
  const estadoClase = Number(usuario.estado) === 1 ? "activo" : "inactivo";
  const estadoTexto = Number(usuario.estado) === 1 ? "Activo" : "Inactivo";

  const apodoEscapado = (usuario.apodoUsuario ?? "").replace(/'/g, "\\'");
  const nombreEscapado = (usuario.nombreUsuario ?? "").replace(/'/g, "\\'");
  const apellidoPEscapado = (usuario.apellidoPaternoUsuario ?? "").replace(
    /'/g,
    "\\'",
  );
  const apellidoMEscapado = (usuario.apellidoMaternoUsuario ?? "").replace(
    /'/g,
    "\\'",
  );

  return `
        <tr>
            <td class="usuarioCell">
                <i class="fa-solid fa-circle-user"></i> ${usuario.apodoUsuario ?? ""}
            </td>
            <td>
                ${usuario.nombreUsuario ?? ""} ${usuario.apellidoPaternoUsuario ?? ""} ${usuario.apellidoMaternoUsuario ?? ""}
            </td>
            <td>
                <span class="estado ${estadoClase}">
                    ${estadoTexto}
                </span>
            </td>
            <td>
                ${usuario.nombreRol ?? ""}
            </td>
            <td class="acciones">
                <button type="button" class="btn-accion editar" onclick="abrirModalEditar({
                    id: '${usuario.idCuenta}',
                    apodo: '${apodoEscapado}',
                    nombre: '${nombreEscapado}',
                    apellidoP: '${apellidoPEscapado}',
                    apellidoM: '${apellidoMEscapado}',
                    estado: '${usuario.estado}',
                    idRol: '${usuario.idRol}'
                })">
                    <i class="fa-solid fa-pen"></i>
                </button>

                <button type="button" class="btn-accion eliminar" onclick="abrirModalEliminar('${usuario.idCuenta}', '${apodoEscapado}')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

// ===================================================================================
// 5. GESTIÓN DE MODALES (CREAR, EDITAR, ELIMINAR - USUARIOS E INVENTARIO)
// ===================================================================================
function abrirModalUsuario() {
  document.getElementById("modalNuevoUsuario").style.display = "flex";
}

function cerrarModalUsuario() {
  document.getElementById("modalNuevoUsuario").style.display = "none";
}

function abrirModalEditar(usuario) {
  document.getElementById("editIdUsuario").value = usuario.id;
  document.getElementById("editApodo").value = usuario.apodo;
  document.getElementById("editNombre").value = usuario.nombre;
  document.getElementById("editApellidoP").value = usuario.apellidoP;
  document.getElementById("editApellidoM").value = usuario.apellidoM;
  document.getElementById("editEstado").value = usuario.estado;
  document.getElementById("editRol").value = usuario.idRol;

  document.getElementById("modalEditarUsuario").style.display = "flex";
}

function cerrarModalEditar() {
  document.getElementById("modalEditarUsuario").style.display = "none";
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminarUsuario').style.display = 'none';
    clearInterval(temporizadorAnimacion);
}

/* --- MODALES DE INVENTARIO (PRODUCTOS Y PROVEEDORES) --- */
function abrirModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (modal) modal.style.display = 'flex';
}

function cerrarModalAgregarProducto() {
    const modal = document.getElementById('modalAgregarProducto');
    if (modal) modal.style.display = 'none';
}

function cerrarModalEditarProducto() {
    const modal = document.getElementById('modalEditarProducto');
    if (modal) modal.style.display = 'none';
}

function cerrarModalEliminarProducto() {
    const modal = document.getElementById('modalEliminarProducto');
    if (modal) modal.style.display = 'none';
}

function abrirModalAgregarProveedor() {
    const modal = document.getElementById('modalAgregarProveedor');
    if (modal) {
        document.getElementById('formNuevoProveedor').reset();
        modal.style.display = 'flex';
    }
}

function cerrarModalAgregarProveedor() {
    const modal = document.getElementById('modalAgregarProveedor');
    if (modal) modal.style.display = 'none';
}

function cerrarModalEditarProveedor() {
    const modal = document.getElementById('modalEditarProveedor');
    if (modal) modal.style.display = 'none';
}

function cerrarModalEliminarProveedor() {
    const modal = document.getElementById('modalEliminarProveedor');
    if (modal) modal.style.display = 'none';
/* --- MODAL ELIMINAR USUARIO --- */
function abrirModalEliminar(id, apodo) {
  document.getElementById("nombreUsuarioEliminar").textContent = apodo;
  document.getElementById("btnConfirmarEliminar").href =
    `../Controllers/usuariosController.php?action=delete&id=${id}`;
  document.getElementById("modalEliminarUsuario").style.display = "flex";
}

function cerrarModalEliminar() {
  document.getElementById("modalEliminarUsuario").style.display = "none";
}

/* --- CIERRE GLOBAL DE MODALES (CLICK FUERA DE LA CAJA) --- */
window.addEventListener("click", function (e) {
  const modalNuevo = document.getElementById("modalNuevoUsuario");
  const modalEditar = document.getElementById("modalEditarUsuario");
  const modalEliminar = document.getElementById("modalEliminarUsuario");
  const modalLogout = document.getElementById("modalCerrarSesion");

    const modalAgregarProd = document.getElementById('modalAgregarProducto');
    const modalEditarProd = document.getElementById('modalEditarProducto');
    const modalEliminarProd = document.getElementById('modalEliminarProducto');
    
    const modalAgregarProv = document.getElementById('modalAgregarProveedor');
    const modalEditarProv = document.getElementById('modalEditarProveedor');
    const modalEliminarProv = document.getElementById('modalEliminarProveedor');

    if (e.target === modalNuevo) cerrarModalUsuario();
    if (e.target === modalEditar) cerrarModalEditar();
    if (e.target === modalEliminar) cerrarModalEliminar();
    if (e.target === modalLogout) cerrarModalLogout();

    if (e.target === modalAgregarProd) cerrarModalAgregarProducto();
    if (e.target === modalEditarProd) cerrarModalEditarProducto();
    if (e.target === modalEliminarProd) cerrarModalEliminarProducto();
    
    if (e.target === modalAgregarProv) cerrarModalAgregarProveedor();
    if (e.target === modalEditarProv) cerrarModalEditarProveedor();
    if (e.target === modalEliminarProv) cerrarModalEliminarProveedor();
  if (e.target === modalNuevo) cerrarModalUsuario();
  if (e.target === modalEditar) cerrarModalEditar();
  if (e.target === modalEliminar) cerrarModalEliminar();
  if (e.target === modalLogout) cerrarModalLogout();
});

/* ===================================================================================
   ANIMACIÓN DE ELIMINAR (USUARIOS)
=================================================================================== */
let temporizadorAnimacion = null;
const totalFotogramas = 10;      
const velocidadAnimacion = 100;   
const rutaCarpeta = '../SRC/animacion/'; 
const extensionImagen = '.png';
const totalFotogramas = 10; // Siguen siendo 15 imágenes en total
const velocidadAnimacion = 100; // Velocidad en milisegundos
const rutaCarpeta = "../SRC/animacion/";
const extensionImagen = ".png";

function abrirModalEliminar(id, apodo) {
  document.getElementById("nombreUsuarioEliminar").textContent = apodo;
  document.getElementById("btnConfirmarEliminar").href =
    `../Controllers/usuariosController.php?action=delete&id=${id}`;

  const modal = document.getElementById("modalEliminarUsuario");
  modal.style.display = "flex";

  const imgElement = document.getElementById("imgAnimacionEliminar");

  // 🔥 CORRECCIÓN: Forzamos a que inicie mostrando la imagen cero
  let fotogramaActual = 0;
  imgElement.src = `${rutaCarpeta}${fotogramaActual}${extensionImagen}`;

  clearInterval(temporizadorAnimacion);

  // Bucle de animación corregido para base cero (0 a 14)
  temporizadorAnimacion = setInterval(() => {
    fotogramaActual++;

    // 🔥 Si llega a 15, significa que ya pasó por el 14, así que reinicia a 0
    if (fotogramaActual >= totalFotogramas) {
      fotogramaActual = 0;
    }

    const imgElement = document.getElementById('imgAnimacionEliminar');
    let fotogramaActual = 0; 
    imgElement.src = `${rutaCarpeta}${fotogramaActual}${extensionImagen}`;

    clearInterval(temporizadorAnimacion);

    temporizadorAnimacion = setInterval(() => {
        fotogramaActual++;
        if (fotogramaActual >= totalFotogramas) {
            fotogramaActual = 0; 
        }
        imgElement.src = `${rutaCarpeta}${fotogramaActual}${extensionImagen}`;
    }, velocidadAnimacion);
}

    imgElement.src = `${rutaCarpeta}${fotogramaActual}${extensionImagen}`;
  }, velocidadAnimacion);
}

function cerrarModalEliminar() {
  document.getElementById("modalEliminarUsuario").style.display = "none";
  clearInterval(temporizadorAnimacion);
}

/* ===================================================================================
   ANIMACIÓN LOGOUT (CON TEXTO BLANCO AUTOMÁTICO EN MODO OSCURO)
=================================================================================== */
let temporizadorLogout = null;
const totalFotogramasLogout = 40;     
const rutaCarpetaLogout = '../SRC/vaca/'; 
const extensionImagenLogout = '.png'; 

const totalFotogramasLogout = 40; // 🐮 Tus 40 imágenes estables
const rutaCarpetaLogout = "../SRC/vaca/";
const extensionImagenLogout = ".png";

// 🐮 REPERTORIO DE FRASES ULTRA-BURLONAS DE LA VACA
const mensajesVaca = [
  "¡Muuu! ¿El cursor es un cerdo o es tu reflejo? ¡Broma! 🐮🤭",
  "Mira a ese puerquito... a un click de convertirse en chicharrón. 🥓🔥",
  "Mucho oink-oink pero aquí la que manda soy YO. 🐮💅",
  "¡Muuu! Dile al cursor que no deje lodo en MI dashboard. 🧼🐖",
  "Traes al puerco corriendo como loco y a mí ni me pelas. 🐮🤣",
  "¡Muuu-chacho! El puerquito trabaja y yo cobro las regalías. 🐮👑",
  "¡Muuu-ajaja! Pobrecito, lo traes de esclavo por toda la pantalla. 🖱️🐽",
  "¿Te vas porque el puerco ya se mareó de dar vueltas? 🌀🐖",
  "Cierra ya, antes de que el cursor huela a tocino quemado. 💨🍖",
  "¿Un puerquito de cursor? Qué bajo presupuesto, ¡muuu! 📉🐄",
  "¡A pastar! Y dejas al puerco encerrado en su corral de píxeles. 🌾",
  "¡Muuu-ve ese dedo! El esclavo de la colita torcida ya se cansó. 💤",
];

let musicaLogout = null;

function abrirModalLogout() {
    const modal = document.getElementById('modalCerrarSesion');
    if (!modal) return;
    
    // 1. Movemos el modal al body y forzamos el fondo oscuro general de la pantalla
    document.body.appendChild(modal);
    
    modal.style.setProperty('display', 'flex', 'important');
    modal.style.setProperty('position', 'fixed', 'important');
    modal.style.setProperty('top', '0', 'important');
    modal.style.setProperty('left', '0', 'important');
    modal.style.setProperty('width', '100vw', 'important');
    modal.style.setProperty('height', '100vh', 'important');
    modal.style.setProperty('background-color', 'rgba(0, 0, 0, 0.6)', 'important'); 
    modal.style.setProperty('z-index', '999999', 'important');
    modal.style.setProperty('justify-content', 'center', 'important');
    modal.style.setProperty('align-items', 'center', 'important');

    // 2. Detección automática del modo oscuro
    const esOscuro = document.body.classList.contains('dark-mode') || 
                     document.documentElement.classList.contains('dark-mode') ||
                     localStorage.getItem('darkMode') === 'true' ||
                     localStorage.getItem('theme') === 'dark';

    // 3. Aplicamos colores dinámicos al cuadro y a los textos internos
    const contenidoModal = modal.querySelector('.modal-contenido');
    if (contenidoModal) {
        contenidoModal.style.setProperty('max-width', '480px', 'important');
        contenidoModal.style.setProperty('width', '90%', 'important');
        contenidoModal.style.setProperty('padding', '38px', 'important');
        contenidoModal.style.setProperty('border-radius', '18px', 'important');
        contenidoModal.style.setProperty('box-shadow', '0 12px 30px rgba(0,0,0,0.35)', 'important');
        contenidoModal.style.setProperty('overflow', 'hidden', 'important');

        // Seleccionamos todos los textos dentro del modal (h2, p, etc.)
        const textosInternos = contenidoModal.querySelectorAll('h2, p');

        if (esOscuro) {
            // Fondo oscuro para el cuadro y letras blancas
            contenidoModal.style.setProperty('background-color', '#1e293b', 'important');
            contenidoModal.style.setProperty('color', '#f8fafc', 'important');
            textosInternos.forEach(el => el.style.setProperty('color', '#f8fafc', 'important'));
        } else {
            // Fondo blanco para el cuadro y letras oscuras
            contenidoModal.style.setProperty('background-color', '#ffffff', 'important');
            contenidoModal.style.setProperty('color', '#0f172a', 'important');
            textosInternos.forEach(el => el.style.setProperty('color', '#0f172a', 'important'));
        }
    }

    const imgElement = document.getElementById('imgAnimacionLogout');
    const globoElement = document.getElementById('globoTextoLogout'); 
    if (!imgElement) return;

    let fotogramaActual = 0; 
    let indiceMensaje = 0;         
    let contadorCambioTexto = 0;  

  const modal = document.getElementById("modalCerrarSesion");
  if (!modal) return;

  modal.style.display = "flex";

  const imgElement = document.getElementById("imgAnimacionLogout");
  const globoElement = document.getElementById("globoTextoLogout"); // 🔥 Capturamos el globo
  if (!imgElement) return;

  let fotogramaActual = 0;
  let indiceMensaje = 0; // 🔥 Rastrea qué mensaje se está mostrando
  let contadorCambioTexto = 0; // 🔥 Mide el tiempo para cambiar el diálogo

  // Inicializamos el primer fotograma y el primer mensaje de la lista
  imgElement.src = `${rutaCarpetaLogout}${fotogramaActual}${extensionImagenLogout}`;
  if (globoElement) {
    globoElement.textContent = mensajesVaca[indiceMensaje];
  }

  // Configuración segura del audio vaca.mp3
  if (!musicaLogout) {
    musicaLogout = new Audio("../SRC/vaca/vaca.mp3");
    musicaLogout.loop = true;
  }

  musicaLogout.currentTime = 0;
  musicaLogout.play().catch((error) => {
    console.error("Error crítico al reproducir el audio:", error);
  });

  clearInterval(temporizadorLogout);

  // Bucle unificado para animación, música y texto dinámico
  temporizadorLogout = setInterval(() => {
    // 1. Avanzar fotograma de la animación
    fotogramaActual++;
    if (fotogramaActual >= totalFotogramasLogout) {
      fotogramaActual = 0;
    }
    imgElement.src = `${rutaCarpetaLogout}${fotogramaActual}${extensionImagenLogout}`;

    // 2. 🔥 LÓGICA DEL TEXTO DINÁMICO: Cambia cada 2 segundos (20 fotogramas * 100ms = 2000ms)
    contadorCambioTexto++;
    if (contadorCambioTexto >= 40) {
      contadorCambioTexto = 0; // Reiniciamos el contador de tiempo
      indiceMensaje++; // Saltamos a la siguiente frase

      // Si recorrió todos los mensajes, vuelve a empezar desde el primero
      if (indiceMensaje >= mensajesVaca.length) {
        indiceMensaje = 0;
      }

      // Inyectamos el nuevo texto en el HTML del globo
      if (globoElement) {
        globoElement.textContent = mensajesVaca[indiceMensaje];
      }
    }

    if (!musicaLogout) {
        musicaLogout = new Audio('../SRC/vaca/vaca.mp3'); 
        musicaLogout.loop = true;
    }
    
    musicaLogout.currentTime = 0; 
    musicaLogout.play().catch(error => {
        console.error("Error crítico al reproducir el audio:", error);
    });

    clearInterval(temporizadorLogout);

    temporizadorLogout = setInterval(() => {
        fotogramaActual++;
        if (fotogramaActual >= totalFotogramasLogout) {
            fotogramaActual = 0; 
        }
        imgElement.src = `${rutaCarpetaLogout}${fotogramaActual}${extensionImagenLogout}`;

        contadorCambioTexto++;
        if (contadorCambioTexto >= 40) { 
            contadorCambioTexto = 0; 
            indiceMensaje++;         
            
            if (indiceMensaje >= mensajesVaca.length) {
                indiceMensaje = 0;
            }
            
            if (globoElement) {
                globoElement.textContent = mensajesVaca[indiceMensaje];
            }
        }
    }, 100); 
  }, 100);
}

function cerrarModalLogout() {
  const modal = document.getElementById("modalCerrarSesion");
  if (modal) modal.style.display = "none";

  clearInterval(temporizadorLogout);

  if (musicaLogout) {
    musicaLogout.pause();
    musicaLogout.currentTime = 0;
  }
}

// ===================================================================================
// 🔥 FUNCIÓN MEJORADA: CARGA LOS VALORES GUARDADOS AL EDITAR PROVEEDOR
// ===================================================================================
async function editarProveedor(id) {
    const modal = document.getElementById('modalEditarProveedor');
    if (!modal) return;

    try {
        const respuesta = await fetch(`../Controllers/inventarioController.php?action=obtenerProveedor&id=${id}`);
        const resultado = await respuesta.json();

        if (resultado.status === 'success' && resultado.datos) {
            const prov = resultado.datos;

            // Rellenamos los campos principales del proveedor
            document.getElementById('editProvId').value = prov.idProveedor ?? '';
            document.getElementById('editProvCodigo').value = prov.codigoProveedor ?? '';
            document.getElementById('editProvNombre').value = prov.nombreProveedor ?? '';
            document.getElementById('editProvRfc').value = prov.rfc ?? '';
            document.getElementById('editProvDireccion').value = prov.direccion ?? '';
            document.getElementById('editProvColonia').value = prov.colonia ?? '';
            document.getElementById('editProvCp').value = prov.codigoPostal ?? '';
            document.getElementById('editProvEstado').value = prov.estadoRepublica ?? '';

            // Rellenamos la configuración del lector QR si existe en el formulario de edición
            if (document.getElementById('editCodigoBarrasProductosPosicion')) {
                document.getElementById('editCodigoBarrasProductosPosicion').value = prov.codigoBarrasProductosPosicion ?? 0;
                document.getElementById('editCodigoBarrasProductosLongitud').value = prov.codigoBarrasProductosLongitud ?? 0;
                document.getElementById('editCodigoBarrasEnterosPosicion').value = prov.codigoBarrasEnterosPosicion ?? 0;
                document.getElementById('editCodigoBarrasEnterosLongitud').value = prov.codigoBarrasEnterosLongitud ?? 0;
                document.getElementById('editCodigoBarrasDecimalesPosicion').value = prov.codigoBarrasDecimalesPosicion ?? 0;
                document.getElementById('editCodigoBarrasDecimalesLongitud').value = prov.codigoBarrasDecimalesLongitud ?? 0;
            }

            modal.style.display = 'flex';
        } else {
            alert('Error: No se pudieron cargar los datos del proveedor.');
        }
    } catch (error) {
        console.error('Error al obtener el proveedor:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
}
