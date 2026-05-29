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
// 3. FUNCIÓN PARA REALIZAR UNA BÚSQUEDA EN TIEMPO REAL
// ===================================================================================
/**
 * @param {string} inputId ID del input de búsqueda
 * @param {string} tbodyId ID del <tbody> de la tabla
 * @param {string} urlBackend Ruta del archivo PHP que devolverá el JSON
 * @param {function} renderFila Función que sabe cómo dibujar el HTML de cada fila
 */
function configurarBusquedaRealTime(inputId, tbodyId, urlBackend, renderFila) {
    const input = document.getElementById(inputId);
    const tbody = document.getElementById(tbodyId);
    let temporizador = null;

    if (!input || !tbody) return;

    input.addEventListener('input', function (e) {
        clearTimeout(temporizador); 
        const termino = e.target.value;
        const estado = document.getElementById('selectEstado')?.value ?? '';

        temporizador = setTimeout(async () => {
            try {
                const respuesta = await fetch(`${urlBackend}?action=busqueda&busqueda=${encodeURIComponent(termino)}&estado=${encodeURIComponent(estado)}`);
                const datos = await respuesta.json();

                tbody.innerHTML = '';

                if (datos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="100%">No se encontraron resultados</td></tr>';
                    return;
                }

                datos.forEach(item => {
                    tbody.innerHTML += renderFila(item);
                });

            } catch (error) {
                console.error("Error al buscar:", error);
            }
        }, 300);
    });
}

// ===================================================================================
// 4. RENDERIZADOR DE FILAS PARA LA TABLA DE USUARIOS (ACTUALIZADO PARA MODALES)
// ===================================================================================
function renderizarFilaUsuario(usuario) {
    const estadoClase = usuario.estado == 1 ? 'activo' : 'inactivo';
    const estadoTexto = usuario.estado == 1 ? 'Activo' : 'Inactivo';

    // Escapamos comillas simples en las cadenas de texto para evitar errores de sintaxis en el HTML inline
    const apodoEscapado = usuario.apodoUsuario.replace(/'/g, "\\'");
    const nombreEscapado = usuario.nombreUsuario.replace(/'/g, "\\'");
    const apellidoPEscapado = usuario.apellidoPaternoUsuario.replace(/'/g, "\\'");
    const apellidoMEscapado = usuario.apellidoMaternoUsuario.replace(/'/g, "\\'");

    return `
        <tr>
            <td class="usuarioCell">
                <i class="fa-solid fa-circle-user"></i> ${usuario.apodoUsuario}
            </td>
            <td>
                ${usuario.nombreUsuario} ${usuario.apellidoPaternoUsuario} ${usuario.apellidoMaternoUsuario}                        
            </td>
            <td>
                <span class="estado ${estadoClase}">
                    ${estadoTexto}
                </span>
            </td>
            <td>
                ${usuario.nombreRol}
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

    if (e.target === modalNuevo) cerrarModalUsuario();
    if (e.target === modalEditar) cerrarModalEditar();
    if (e.target === modalEliminar) cerrarModalEliminar();
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