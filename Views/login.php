<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymus"></script>
    <link rel="stylesheet" href="../Views/css/login.css">
</head>
<body>
    <div class="tarjeta-blanca">
        
        <div class="contenido-superior">
            
            <div class="columna-izquierda">
                <img src="../SRC/Logo LFI - copia.png" alt="Logo LFI" class="logo-lfi">
                <h2 class="titulo-lfi">LFI - Local Fridge Inventory</h2>
                <p class="subtitulo-lfi">Sistema Local de Control para<br>Cámaras Frigoríficas</p>
                <img src="../SRC/Logo Grupo America.png" alt="Logo Grupo Cárnico AMERICA" class="logo-america">
            </div>

            <div class="linea-divisoria"></div>

            <div class="columna-derecha">
                
                <div class="encabezado-bienvenida">
                    <p id="usuarioCirculo">
                        <i class="fa-regular fa-circle-user"></i>
                    </p>
                    <div>
                        <h1 id="Bienvenido">Bienvenido</h1>
                        <p id="iniciarSesion">Inicie sesión para continuar</p>
                    </div>
                </div>

                <form method="post" action="login.php">
                    
                    <div class="grupo-input caja-con-icono">
                        <label class="credenciales">Usuario</label>
                        <input type="text" name="user" placeholder=" " required>
                        <p id="usericon">
                            <i class="fa-solid fa-user"></i>
                        </p>
                    </div>

                    <div class="grupo-input caja-con-icono">
                        <label class="credenciales">Contraseña</label>
                        
                        <input type="password" id="inputPassword" name="password" placeholder=" " required>
                        
                        <p id="candado">
                            <i class="fa-solid fa-lock"></i>
                        </p> 
                        
                        <i class="fa-solid fa-eye" id="iconoOjo"></i>
                    </div>

                    <div>
                        <button type="submit" class="boton-iniciar" name="login">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Iniciar Sesión
                        </button>
                    </div>
                </form>

            </div>
        </div> 

        <div class="pie-de-pagina">
    
            <div class="fecha-hora">
                <p id="calendario" style="margin:0;"><i class="fa-duotone fa-solid fa-calendar-days"></i></p>
                <span>
                    <?php
                    $meses = [
                        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
                    ];
                    $dia = date("j");
                    $mes = $meses[date("n")-1];
                    $anio = date("Y");
                    echo "$dia de $mes de $anio"; 
                    ?>
                </span>
                
                <span class="separador-vertical">|</span>
                
                <p id="reloj" style="margin:0;"><i class="fa-regular fa-clock"></i></p>
                <span class="hora" id="hora"></span>
            </div>

            <button type="button" id="btnAcercaDe" class="boton-acerca">
                <i class="fa-solid fa-circle-info"></i> Acerca de
            </button>

        </div>

        <script>
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


            
        </script>

    </div>
    <div id="cerditoCursor">🐷</div>

    <script>
        const cerdito = document.getElementById('cerditoCursor');
        let ultimaPosicionX = 0;

        document.addEventListener('mousemove', (evento) => {
            cerdito.style.left = evento.clientX + 'px';
            cerdito.style.top = evento.clientY + 'px';

            if (evento.clientX < ultimaPosicionX) {
                cerdito.style.transform = 'translate(-50%, -50%) scaleX(-1)';
            } else if (evento.clientX > ultimaPosicionX) {
                cerdito.style.transform = 'translate(-50%, -50%) scaleX(1)';
            }
            ultimaPosicionX = evento.clientX;
        });
    </script>
</body> </html>
</body>
</html>

