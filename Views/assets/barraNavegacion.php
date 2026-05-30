<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<div class="sidebar">

        <div class="sidebar-header">
            <img src="../SRC/Logo LFI - copia.png" alt="Logo LFI" class="logo-america">
        </div>

        <ul class="nav-menu">
            <li class="nav-link" onclick="cargarModulo('inicio')">
                <i class="fa-solid fa-house"></i> INICIO
            </li>

            <li class="nav-link" onclick="cargarModulo('entradas')">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> ENTRADAS
            </li>

            <li class="nav-link" onclick="cargarModulo('salidas')">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> SALIDAS
            </li>

            <li class="nav-link" onclick="cargarModulo('inventario')">
                <i class="fa-solid fa-boxes-stacked"></i> INVENTARIO
            </li>

            <li class="nav-link" onclick="cargarModulo('ubicaciones')">
                <i class="fa-solid fa-location-dot"></i> UBICACIONES
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

            <div class="titulo-pagina">
                <h2 id="titulo-modulo-gris" class="modulo-titulo">
                    INICIO
                </h2>
            </div>

            <div class="metrics-right-group">

                <div class="metric-item">
                    <i class="fa-solid fa-calendar-days icon-blue"></i>
                    <span class="label">Fecha:</span>
                    <span class="value">
                        <?php
                        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                        $dia = date("j");
                        $mes = $meses[date("n") - 1];
                        $anio = date("Y");
                        echo "$dia de $mes de $anio";
                        ?>
                    </span>
                </div>

                <div class="metric-item">
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

<div class="modal" id="modalCerrarSesion" style="display: none;">
    <div class="modal-contenido modal-logout" style="max-width: 400px; text-align: center; padding-top: 30px;">

        <span class="globo-texto" id="globoTextoLogout">¡Muuu! ¿Ya te vas?</span>

        <div class="contenedor-animacion-eliminar" style="display: flex; justify-content: center; align-items: center; width: 100%; height: 100px; margin-bottom: 15px; overflow: hidden; margin-top: 10px;">
            <img id="imgAnimacionLogout" src="../SRC/vaca/0.png" alt="Animación de cierre de sesión" class="delete-gif" style="height: 100%; width: auto; object-fit: contain; display: block;">
        </div>

        <h2>¿Desea cerrar sesión?</h2>

        <p class="mensaje-logout">
            Su sesión actual se cerrará y volverá a la pantalla de inicio de sesión.
        </p>

        <div class="acciones-logout">
            <button type="button" class="btnCancelarLogout" onclick="cerrarModalLogout()">
                Cancelar
            </button>

            <form action="../Config/Logouth.php" method="POST">
                <button type="submit" class="btnConfirmarLogout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Confirmar
                </button>
            </form>
        </div>
    </div>
</div>

<div id="cerditoCursor" style="z-index: 9999">🐷</div>
<script src="../Services/cerdito.js"></script>
<script src="../Services/funciones.js"></script>
<script src="../Services/navegacion.js"></script>