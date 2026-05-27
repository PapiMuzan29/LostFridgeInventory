//NMuestra la hora en tiempo real
    function actualizarHora() 
    {
        const ahora = new Date();
        const horas = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        const segundos = ahora.getSeconds().toString().padStart(2, '0');
        document.getElementById("hora").textContent = `${horas}:${minutos}:${segundos}`;
    }         
    setInterval(actualizarHora, 1000);
    actualizarHora();

    document.addEventListener("DOMContentLoaded", () => {
        setInterval(actualizarHora, 1000);
        actualizarHora();
    });
//***********************************************************************************


//Control de movimiento del cursor del cerdito



//***********************************************************************************



// Abrir modales.
    function abrirModal()
    {
        document.getElementById("modal").style.display = "flex";
    }

    function cerrarModal()
    {
        document.getElementById("modal").style.display = "none";
    }

//***********************************************************************************

const inputPassword = document.getElementById("inputPassword");
const iconoOjo = document.getElementById("iconoOjo");

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



            
