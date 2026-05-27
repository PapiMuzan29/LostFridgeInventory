<?php

session_start();

if (!isset($_SESSION['apodoUsuario'])) {

    header('Location: login.php');

    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LostFridgeInventory</title>
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/principal.css"> 
    <link rel="stylesheet" href="css/cerdito.css">
</head>
    
<body>
    
    <div class="sidebar">
        
        <div class="sidebar-header">
            <img src="../SRC/Logo LFI - copia.png" alt="Logo LFI" class="logo-america">
        </div>

        <ul class="nav-menu">
            <li class="nav-link" onclick="cargarModulo('inicio')"><i class="fa-solid fa-house"></i> INICIO</li>
            <li class="nav-link" onclick="cargarModulo('entradas')"><i class="fa-solid fa-arrow-right-to-bracket"></i> ENTRADAS</li>
            <li class="nav-link" onclick="cargarModulo('salidas')"><i class="fa-solid fa-arrow-right-from-bracket"></i> SALIDAS</li>
            <li class="nav-link" onclick="cargarModulo('inventario')"><i class="fa-solid fa-boxes-stacked"></i> INVENTARIO</li>
            <li class="nav-link" onclick="cargarModulo('ubicaciones')"><i class="fa-solid fa-location-dot"></i> UBICACIONES</li>
            <li class="nav-link" onclick="cargarModulo('reportes')"><i class="fa-solid fa-file-lines"></i> REPORTES</li>
            <li class="nav-link" onclick="cargarModulo('usuarios')"><i class="fa-solid fa-users"></i> USUARIOS</li>
            <li class="nav-link" onclick="cargarModulo('configuracion')"><i class="fa-solid fa-gear"></i> CONFIGURACION</li>
        </ul>
        
        <div class="sidebar-linea-divisoria"></div>

        <div class="sidebar-footer">
            <form action="../Config/Logouth.php">
                 <button class="btn-logout">
                 <i class="fa-solid fa-arrow-right-from-bracket"></i> CERRAR SESION
            </button>

            </form>
           
        </div>

    </div> 

     <div class="top-bar-dashboard">

        <div class="top-bar-metrics">
            <!-- Título dinámico alineado a la izquierda -->
            <div class="titulo-pagina">
                <h2 id="titulo-modulo-gris" class="modulo-titulo">INICIO</h2>
            </div>
            
            <!-- Contenedor derecho para agrupar los datos -->
            <div class="metrics-right-group">
                <!-- Bloque de Fecha (No duplicado) -->
                <div class="metric-item">
                    <i class="fa-solid fa-calendar-days icon-blue"></i>
                    <span class="label">Fecha:</span>
                    <span class="value">
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
                </div>
                
                <!-- Bloque de Hora -->
                <div class="metric-item">
                    <i class="fa-regular fa-clock icon-blue"></i>
                    <span class="label">Hora:</span>
                    <span class="value" id="hora">00:00 PM</span>
                </div>
                
                <!-- Bloque de Usuario Recuperado -->
                <div class="metric-item user-badge">
                    <i class="fa-solid fa-user icon-blue"></i>
                    <div class="user-info">
                        <span class="user-id"><?php echo $_SESSION['apodoUsuario']; ?></span>
                        <span class="user-role">Operador</span>
                    </div>
                </div>
            </div>

        </div>
    </div>


    

                    
    <div class="main-content">
        <div id="contenedor-principal"></div>
        
    </div>

    <div id="cerditoCursor" style="index: 9999">🐷</div>
    <script src="../Services/navegacion.js"></script>
    <script src="../Services/cerdito.js"></script>
    <script src="../Services/funciones.js"></script>

</body>
</html>






   

    