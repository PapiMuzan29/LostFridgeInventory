

//fin funciones scanner

/**
 * 🚀 FUNCIONES EXCLUSIVAS DEL MÓDULO DE ENTRADAS
 */
document.addEventListener("DOMContentLoaded", function() {
    cargarProveedoresSelect();
});

/**
 * 👥 Obtiene los proveedores del controlador y los inyecta en el desplegable
 */
async function cargarProveedoresSelect() {
    const select = document.getElementById('idProveedor');
    if (!select) return; // Salvaguarda por si cambia el ID

    try {
        const respuesta = await fetch('../Controllers/entradasController.php?action=obtenerProveedores');
        const proveedores = await respuesta.json();
        
        if (Array.isArray(proveedores)) {
            proveedores.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov.idProveedor;
                option.textContent = prov.nombreProveedor;
                select.appendChild(option);
            });
        } else if (proveedores.error) {
            console.error("Error devuelto por el servidor:", proveedores.error);
        }
    } catch (error) {
        console.error("Error crítico en la petición Fetch de proveedores:", error);
    }
}