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

document.addEventListener("DOMContentLoaded", () => {
    inicializarMenuMovilDashboard();
});

// ==========================================
// MENÚ MÓVIL: abrir / cerrar el sidebar
// ==========================================
function inicializarMenuMovilDashboard() {
    const btnAbrir = document.getElementById('btnToggleSidebar');
    const btnCerrar = document.getElementById('btnCloseSidebar');
    const sidebar = document.getElementById('sidebarDashboard');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !overlay) return;

    function abrirMenu() {
        sidebar.classList.add('abierto');
        overlay.classList.add('activo');
        // Previene que se haga scroll en la página del fondo mientras el menú está abierto
        document.body.style.overflow = 'hidden'; 
    }

    function cerrarMenu() {
        sidebar.classList.remove('abierto');
        overlay.classList.remove('activo');
        // Restaura el scroll
        document.body.style.overflow = '';
    }

    // Eventos de botones
    if (btnAbrir) btnAbrir.addEventListener('click', abrirMenu);
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarMenu);
    
    // Si dan clic fuera del menú (en el espacio negro), se cierra
    overlay.addEventListener('click', cerrarMenu);

    // Al tocar cualquier enlace (.nav-link) o el botón de salir, cerramos el menú
    sidebar.querySelectorAll('.nav-link, .btn-logout').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                cerrarMenu();
            }
        });
    });

    // Si el usuario gira el celular o agranda la ventana a PC, limpiamos estados
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            cerrarMenu();
        }
    });
}