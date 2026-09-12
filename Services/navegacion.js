function cargarModulo(nombreArchivo, params = '') {
    localStorage.setItem('moduloActivo', nombreArchivo);

    const destino = params ? `${nombreArchivo}.php?${params}` : `${nombreArchivo}.php`;
    window.location.href = destino;
}

document.addEventListener('DOMContentLoaded', () => {
    const moduloGuardado = localStorage.getItem('moduloActivo') || 'inicio';
    const contenedor = document.getElementById('contenedor-principal');
    const tituloGris = document.getElementById('titulo-modulo-gris');
    const enlaces = document.querySelectorAll('.nav-link');

    const titulos = {
        inicio: 'Inicio',
        entradas: 'Entradas',
        salidas: 'Salidas',
        inventario: 'Inventario',
        ubicaciones: 'Almacén',
        movimientos: 'Movimientos',
        reportes: 'Reportes',
        usuarios: 'Usuarios',
        configuracion: 'Configuración'
    };

    if (tituloGris && titulos[moduloGuardado]) {
        tituloGris.innerText = titulos[moduloGuardado];
    }

    enlaces.forEach(enlace => {
        enlace.classList.remove('activo');

        if (enlace.getAttribute('onclick')?.includes(moduloGuardado)) {
            enlace.classList.add('activo');
        }
    });

});