<style>
    .sidebar .nav-menu .nav-link.menu-enfocado {
        background-color: rgba(47, 94, 167, 0.15) !important; /* Tono azul transparente */
        color: #2f5ea7 !important; /* Cambia el texto al azul rey de tus botones */
        font-weight: 700 !important;
        border-left: 4px solid #2f5ea7; /* Una pestaña azul de enfoque al lado izquierdo */
        padding-left: 16px; /* Ajuste ligero para que no se mueva el ícono */
        transition: all 0.2s ease;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- NUEVO: Overlay (fondo oscuro) para cuando el menú se abre en móvil -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- NUEVO: Se agregó el ID "sidebarDashboard" a la barra -->
<div class="sidebar" id="sidebarDashboard">

        <div class="sidebar-header" style="position: relative;">
            <img src="../SRC/Logo LFI - copia.png" alt="Logo LFI" class="logo-america">
            
            <!-- NUEVO: Botón 'X' para cerrar en móvil -->
            <button id="btnCloseSidebar" class="btn-close-sidebar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <ul class="nav-menu">
            <li class="nav-link" onclick="cargarModulo('inicio')">
                <i class="fa-solid fa-house"></i> INICIO
            </li>
            <li class="nav-link" onclick="cargarModulo('inventario')">
                <i class="fa-solid fa-boxes-stacked"></i> INVENTARIO
            </li>
            <li class="nav-link" onclick="cargarModulo('entradas')">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> ENTRADAS
            </li>
            <li class="nav-link" onclick="cargarModulo('salidas')">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> SALIDAS
            </li>
            <li class="nav-link" onclick="cargarModulo('ubicaciones')">
                <i class="fa-solid fa-location-dot"></i> UBICACIONES
            </li>
            <li class="nav-link" onclick="cargarModulo('movimientos')">
                <i class="fa-solid fa-retweet"></i> MOVIMIENTOS
            </li>
            <li class="nav-link" onclick="cargarModulo('reportes')">
                <i class="fa-solid fa-file-lines"></i> REPORTES
            </li>
            <li class="nav-link" onclick="cargarModulo('usuarios')">
                <i class="fa-solid fa-users"></i> USUARIOS
            </li>
            <li class="nav-link" onclick="cargarModulo('configuracion')">
                <i class="fa-solid fa-gear"></i> CONFIGURACION
            </li>
        </ul>

        <div class="sidebar-linea-divisoria"></div>

        <div class="sidebar-footer">
            <button class="btn-logout" onclick="abrirModalLogout()">
                 <i class="fa-solid fa-arrow-right-from-bracket"></i> CERRAR SESION
            </button>
        </div>
</div>

<div class="top-bar-dashboard">
        <div class="top-bar-metrics">
            
            <!-- NUEVO: Botón Hamburguesa -->
            <button id="btnToggleSidebar" class="btn-toggle-sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="titulo-pagina">
                <h2 id="titulo-modulo-gris" class="modulo-titulo">
                    INICIO
                </h2>
            </div>

            <div class="metrics-right-group">
                <!-- Se mantienen tus métricas igual -->
                <div class="metric-item metric-date">
                    <i class="fa-solid fa-calendar-days icon-blue"></i>
                    <span class="label">Fecha:</span>
                    <span class="value">
                        <?php
                        date_default_timezone_set('America/Mexico_City');
                        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                        $dia = date("j");
                        $mes = $meses[date("n") - 1];
                        $anio = date("Y");
                        echo "$dia de $mes de $anio";
                        ?>
                    </span>
                </div>

                <div class="metric-item metric-time">
                    <i class="fa-regular fa-clock icon-blue"></i>
                    <span class="label">Hora:</span>
                    <span class="value" id="hora">00:00 PM</span>
                </div>

                <div class="metric-item user-badge">
                    <i class="fa-solid fa-user icon-blue"></i>
                    <div class="user-info">
                        <span class="user-id"><?php echo $_SESSION['apodoUsuario'] ?? 'Usuario'; ?></span>
                        <span class="user-role"><?php echo $_SESSION['nombreRol'] ?? 'Sin rol'; ?></span>
                    </div>
                </div>
            </div>
        </div>
</div>

<div id="cerditoCursor" style="z-index: 9999">🐷</div>
<script src="../Services/cerdito.js"></script>
<script src="../Services/funciones.js"></script>
<script src="../Services/navegacion.js"></script>
<script src="../../Services/tema.js"></script>