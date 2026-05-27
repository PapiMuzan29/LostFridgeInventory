//Función para navegar entre modulos

function cargarModulo(nombreArchivo) {
    const contenedor = document.getElementById('contenedor-principal');
    const tituloGris = document.getElementById('titulo-modulo-gris');

    const titulos = {
        'inicio': 'Inicio',
        'entradas': 'Entradas',
        'salidas': 'Salidas',
        'inventario': 'Inventario',
        'ubicaciones': 'Ubicaciones',
        'reportes': 'Reportes',
        'usuarios': 'Usuarios',
        'configuracion': 'Configuración'
    };

    if (tituloGris && titulos[nombreArchivo]) {
        tituloGris.innerText = titulos[nombreArchivo];
    }

    if (contenedor) {
        contenedor.innerHTML = '<p><i class="fa-solid fa-spinner fa-spin"></i> Cargando información...</p>';

        fetch(`${nombreArchivo}.php`)
        .then(response => {
            if(!response.ok) {
                throw new Error('Error al cargar el módulo: ' + response.statusText);
            }
            return response.text();
        })
        .then(html => {
            contenedor.innerHTML = html;
        })
        .catch(error => {
            contenedor.innerHTML = '<p><i class="fa-solid fa-triangle-exclamation"></i> Error al cargar el contenido.</p>';
            console.error('Detalle del error:', error);
        });
    }
}


document.addEventListener('DOMContentLoaded', () => {
    cargarModulo('inicio'); 
});
//*************************************************************************************** 


//Función para iluminar el modulo clikeado
document.addEventListener('DOMContentLoaded', () => {
    cargarModulo('inicio'); 
    const enlaces = document.querySelectorAll('.nav-link');
    enlaces[0].classList.add('activo');
    
    enlaces.forEach(enlace => {
        enlace.addEventListener('click', function() {
            enlaces.forEach(e => e.classList.remove('activo'));
            this.classList.add('activo');
        });
    });
});
//*************************************************************************************** ```

