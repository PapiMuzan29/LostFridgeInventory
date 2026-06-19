// ../Services/escannerEntradas.js

document.addEventListener("DOMContentLoaded", function () {
    // Seleccionamos el input del código de barras
    const inputCodigo = document.querySelector(".input-with-icon-bar input");
    const inputCosto = document.querySelector(".input-costo-line input");

    if (inputCodigo) {
        inputCodigo.addEventListener("keypress", function (e) {
            // El escáner envía un "Enter" al terminar de leer la etiqueta
            if (e.key === "Enter") {
                e.preventDefault(); // Evita que se recargue la página o se envíe el formulario
                
                const trama = this.value.trim();
                
                // Validamos si es una trama QR separada por barras verticales '|'
                if (trama.includes('|')) {
                    const partes = trama.split('|');
                    
                    let folio = "";
                    let codigoProducto = "";
                    let lote = "";
                    let fecha = "";
                    let peso = 0;

                    partes.forEach(parte => {
                        parte = parte.trim();
                        if (parte.startsWith("01H")) {
                            folio = parte; 
                        } else if (parte.startsWith("P")) {
                            codigoProducto = parte.substring(1); // Quita la 'P' -> '124603A1'
                        } else if (parte.startsWith("L")) {
                            lote = parte.substring(1); // Quita la 'L' -> '0027236570'
                        } else if (parte.startsWith("D")) {
                            fecha = parte.substring(1); // Quita la 'D' -> '04/06/2026'
                        } else if (parte.startsWith("Q")) {
                            peso = parseFloat(parte.substring(1).trim()); // Quita la 'Q' -> 26.60
                        }
                    });

                    // --- AQUÍ SE PROCESAN LOS DATOS ---
                    console.log("Datos Procesados del QR:", { folio, codigoProducto, lote, fecha, peso });
                    
                    // Alerta temporal para confirmar que el escáner funciona en web
                    alert(`¡Caja Escaneada con Éxito!\nCódigo Producto: ${codigoProducto}\nPeso: ${peso} kg\nLote: ${lote}`);

                    // Limpiamos el lector para recibir la siguiente caja inmediatamente
                    this.value = ""; 
                } else {
                    console.log("Código de barras lineal estándar:", trama);
                }
            }
        });
    }
});
