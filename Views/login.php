<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymus"></script>
        <link rel="stylesheet" href="css/login.css">
        <link rel="stylesheet" href="css/cerdito.css">
    
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

                    <form method="post" action="../Controllers/LoginController.php">
                        
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

                <button type="button" id="btnAcercaDe" class="boton-acerca" onclick="abrirModal()">
                    <i class="fa-solid fa-circle-info"></i> Acerca de 
                </button>
            </div>
        </div>


        

        <div class="modal" id="modal">

            <div class="modal-contenido">

                <span class="cerrar" onclick="cerrarModal()">&times;</span>

                <h2>Acerca de</h2>

                <p>Sistema de inventario.</p>
                <p>Versión 1.0</p>
                <p >Desarrollado por Emmanuel Arroyo, Carlos Montes y Ricardo Emmanuel Perez.</p>                                                   
                <p><i class="fa-duotone fa-regular fa-copyright"></i> Todos los derechos reservados</p>

            </div>

        </div>

        

        <div id="cerditoCursor" style="index: 9999">🐷</div>
        <script src="../Services/funciones.js"></script>
        <script src="../Services/cerdito.js"></script>
    </body> 
</html>









