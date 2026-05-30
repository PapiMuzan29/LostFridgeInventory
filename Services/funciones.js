// ===================================================================================
// 1. MUESTRA LA HORA EN TIEMPO REAL
// ===================================================================================
function actualizarHora() {
    const ahora = new Date();
    const horas = ahora.getHours().toString().padStart(2, '0');
    const minutos = ahora.getMinutes().toString().padStart(2, '0');
    const segundos = ahora.getSeconds().toString().padStart(2, '0');
    
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

    // 🔥 CORRECCIÓN 1: Cargar los datos automáticamente al abrir la página
    cargarDatos();

    input.addEventListener('input', function () {
        clearTimeout(temporizador);
        paginaActual = 1;
        temporizador = setTimeout(() => {
            cargarDatos();
        }, 300);
    });

    // 🔥 CORRECCIÓN 2: Paginación inteligente que acepta direcciones ('anterior' / 'siguiente')
    window.cambiarPaginaUsuarios = function (accion) {
        if (accion === 'anterior') {
            if (paginaActual > 1) {
                paginaActual--;
                cargarDatos();
            }
        } else if (accion === 'siguiente') {
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
    };
}

// ===================================================================================
// 4. RENDERIZADOR DE FILAS PARA LA TABLA DE USUARIOS (ACTUALIZADO PARA MODALES)
// ===================================================================================
function renderizarFilaUsuario(usuario) {

    const estadoClase = Number(usuario.estado) === 1 ? 'activo' : 'inactivo';
    const estadoTexto = Number(usuario.estado) === 1 ? 'Activo' : 'Inactivo';

    const apodoEscapado = (usuario.apodoUsuario ?? '').replace(/'/g, "\\'");
    const nombreEscapado = (usuario.nombreUsuario ?? '').replace(/'/g, "\\'");
    const apellidoPEscapado = (usuario.apellidoPaternoUsuario ?? '').replace(/'/g, "\\'");
    const apellidoMEscapado = (usuario.apellidoMaternoUsuario ?? '').replace(/'/g, "\\'");

    return `
        <tr>
            <td class="usuarioCell">
                <i class="fa-solid fa-circle-user"></i> ${usuario.apodoUsuario ?? ''}
            </td>
            <td>
                ${(usuario.nombreUsuario ?? '')} ${(usuario.apellidoPaternoUsuario ?? '')} ${(usuario.apellidoMaternoUsuario ?? '')}
            </td>
            <td>
                <span class="estado ${estadoClase}">
                    ${estadoTexto}
                </span>
            </td>
            <td>
                ${usuario.nombreRol ?? ''}
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
// 5. GESTIÓN DE MODALES (CREAR, EDITAR, ELIMINAR)
// ===================================================================================

/* --- MODAL NUEVO USUARIO --- */
function abrirModalUsuario() {
    document.getElementById('modalNuevoUsuario').style.display = 'flex';
}

function cerrarModalUsuario() {
    document.getElementById('modalNuevoUsuario').style.display = 'none';
}

/* --- MODAL EDITAR USUARIO --- */
function abrirModalEditar(usuario) {
    document.getElementById('editIdUsuario').value = usuario.id;
    document.getElementById('editApodo').value = usuario.apodo;
    document.getElementById('editNombre').value = usuario.nombre;
    document.getElementById('editApellidoP').value = usuario.apellidoP;
    document.getElementById('editApellidoM').value = usuario.apellidoM;
    document.getElementById('editEstado').value = usuario.estado;
    document.getElementById('editRol').value = usuario.idRol;

    document.getElementById('modalEditarUsuario').style.display = 'flex';
}

function cerrarModalEditar() {
    document.getElementById('modalEditarUsuario').style.display = 'none';
}

/* --- MODAL ELIMINAR USUARIO --- */
function abrirModalEliminar(id, apodo) {
    document.getElementById('nombreUsuarioEliminar').textContent = apodo;
    document.getElementById('btnConfirmarEliminar').href = `../Controllers/usuariosController.php?action=delete&id=${id}`;
    document.getElementById('modalEliminarUsuario').style.display = 'flex';
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminarUsuario').style.display = 'none';
}

/* --- CIERRE GLOBAL DE MODALES (CLICK FUERA DE LA CAJA) --- */
window.addEventListener('click', function(e) {
    const modalNuevo = document.getElementById('modalNuevoUsuario');
    const modalEditar = document.getElementById('modalEditarUsuario');
    const modalEliminar = document.getElementById('modalEliminarUsuario');
    const modalLogout = document.getElementById('modalCerrarSesion');

    if (e.target === modalNuevo) cerrarModalUsuario();
    if (e.target === modalEditar) cerrarModalEditar();
    if (e.target === modalEliminar) cerrarModalEliminar();
    if (e.target === modalLogout) cerrarModalLogout();
});

/* ===================================================================================
   CONFIGURACIÓN DE LA ANIMACIÓN (0 A 14 FOTOGRAMAS)
=================================================================================== */
let temporizadorAnimacion = null;
const totalFotogramas = 10;      // Siguen siendo 15 imágenes en total
const velocidadAnimacion = 100;   // Velocidad en milisegundos
const rutaCarpeta = '../SRC/animacion/'; 
const extensionImagen = '.png';

/* --- MODAL ELIMINAR USUARIO --- */
function abrirModalEliminar(id, apodo) {
    document.getElementById('nombreUsuarioEliminar').textContent = apodo;
    document.getElementById('btnConfirmarEliminar').href = `../Controllers/usuariosController.php?action=delete&id=${id}`;
    
    const modal = document.getElementById('modalEliminarUsuario');
    modal.style.display = 'flex';

    const imgElement = document.getElementById('imgAnimacionEliminar');
    
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
        
        imgElement.src = `${rutaCarpeta}${fotogramaActual}${extensionImagen}`;
    }, velocidadAnimacion);
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminarUsuario').style.display = 'none';
    clearInterval(temporizadorAnimacion);
}

/* ===================================================================================
   CONFIGURACIÓN DE ANIMACIÓN POR FOTOGRAMAS Y MÚSICA (MÓDULO VACA DINÁMICO)
=================================================================================== */
let temporizadorLogout = null;
const totalFotogramasLogout = 40; // 🐮 Tus 40 imágenes estables     
const rutaCarpetaLogout = '../SRC/vaca/'; 
const extensionImagenLogout = '.png'; 


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
    "¡Muuu-ve ese dedo! El esclavo de la colita torcida ya se cansó. 💤"
];

let musicaLogout = null; 

function abrirModalLogout() {
    const modal = document.getElementById('modalCerrarSesion');
    if (!modal) return;
    
    modal.style.display = 'flex';

    const imgElement = document.getElementById('imgAnimacionLogout');
    const globoElement = document.getElementById('globoTextoLogout'); // 🔥 Capturamos el globo
    if (!imgElement) return;

    let fotogramaActual = 0; 
    let indiceMensaje = 0;        // 🔥 Rastrea qué mensaje se está mostrando
    let contadorCambioTexto = 0;  // 🔥 Mide el tiempo para cambiar el diálogo

    // Inicializamos el primer fotograma y el primer mensaje de la lista
    imgElement.src = `${rutaCarpetaLogout}${fotogramaActual}${extensionImagenLogout}`;
    if (globoElement) {
        globoElement.textContent = mensajesVaca[indiceMensaje];
    }

    // Configuración segura del audio vaca.mp3
    if (!musicaLogout) {
        musicaLogout = new Audio('../SRC/vaca/vaca.mp3'); 
        musicaLogout.loop = true;
    }
    
    musicaLogout.currentTime = 0; 
    musicaLogout.play().catch(error => {
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
            indiceMensaje++;         // Saltamos a la siguiente frase
            
            // Si recorrió todos los mensajes, vuelve a empezar desde el primero
            if (indiceMensaje >= mensajesVaca.length) {
                indiceMensaje = 0;
            }
            
            // Inyectamos el nuevo texto en el HTML del globo
            if (globoElement) {
                globoElement.textContent = mensajesVaca[indiceMensaje];
            }
        }
    }, 100); 
}

function cerrarModalLogout() {
    const modal = document.getElementById('modalCerrarSesion');
    if (modal) modal.style.display = 'none';
    
    clearInterval(temporizadorLogout);
    
    if (musicaLogout) {
        musicaLogout.pause();
        musicaLogout.currentTime = 0; 
    }
}