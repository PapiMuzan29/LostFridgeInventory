<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LostFridgeInventory</title>
    <script src="https://kit.fontawesome.com/646ac4fad6.js" crossorigin="anonymus"></script>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../SRC/Logo LFI - copia" alt="Logo Grupo Cárnico AMERICA" class="logo-america">
        </div>

        <ul class="nav-menu">
            <li class="nav-link activo" onclick="">INICIO</li>
            <li class="nav-link activo" onclick="">ENTRADAS</li>
            <li class="nav-link activo" onclick="">SALIDAS</li>
            <li class="nav-link activo" onclick="">INVENTARIO</li>
            <li class="nav-link activo" onclick="">UBICACIONES</li>
            <li class="nav-link activo" onclick="">REPORTES</li>
            <li class="nav-link activo" onclick="">USUARIOS</li>
            <li class="nav-link activo" onclick="">CONFIGURACION</li>
        </ul>
    </div>
    
    <div class="sidebar-linea-divisoria"></div>

    <div class="sidebar-footer">
        <button class="btn-logout"><p><i class="fa-solid fa-arrow-right-from-bracket"></i></p>CERRAR SESION</button>
    </div>


    <div class="main-content">
        <div id="mod-inicio" class="modulo"></div>
        <div id="mod-entradas" class="modulo"></div>
        <div id="mod-salidas" class="modulo"></div>
        <div id="mod-inventario" class="modulo"></div>
        <div id="mod-ubicaciones" class="modulo"></div>
        <div id="mod-reportes" class="modulo"></div>
        <div id="mod-usuarios" class="modulo"></div>
        <div id="mod-configuracion" class="modulo"></div>
    </div>

</body>
</html>