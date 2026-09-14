// Aplica el tema inmediatamente al cargar cualquier página para evitar parpadeos
(function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    const btnDarkMode = document.getElementById('btnDarkMode');

    // Si la página actual contiene el botón de cambio de tema (pág. de Configuración)
    if (btnDarkMode) {
        // Sincronizar el estado visual del botón toggle
        btnDarkMode.checked = localStorage.getItem('theme') === 'dark';

        // Escuchar el click en el interruptor
        btnDarkMode.addEventListener('change', () => {
            if (btnDarkMode.checked) {
                document.body.classList.add('dark-theme');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-theme');
                localStorage.setItem('theme', 'light');
            }
        });
    }
});