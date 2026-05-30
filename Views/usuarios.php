<?php

session_start();

if (!isset($_SESSION['apodoUsuario'])) {

    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/../Services/usuariosServicio.php';

$service = new usuariosServicio();

$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';

$usuarios = $service->getUsers($busqueda, $estado);
$stats = $service->getStats();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/usuarios.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
</head>

<body>
    
    <?php
    include 'assets/barraNavegacion.php';
    
    ?>

    <div class="main-content contenedor">

        <div class="header">
            <h1>Informacion de usuarios registrados</h1> 
            
        </div>

        <div class="cards">
            <div class="card">
                <h3>Usuarios Registrados</h3>
                <span><?= $stats['total'] ?></span>
            </div>
            <div class="card success">
                <h3>Usuarios Activos</h3>
                <span><?= $stats['activos'] ?></span>
            </div>
            <div class="card danger">
                <h3>Usuarios Inactivos</h3>
                <span><?= $stats['inactivos'] ?></span>
            </div>
        </div> 

        <div class="barra-filtros">
            <form id="formFiltrosUsuarios" class="filtros-izquierda" method="GET">
                
                <div class="buscador">
                    <input 
                        type="text"
                        name="busqueda"
                        id="inputBusqueda"
                        placeholder="Buscar usuario por nombre..."
                        value="<?= htmlspecialchars($busqueda) ?>"
                    >
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>

                <select 
                    name="estado"
                    class="select-estados"
                    id="selectEstado"
                >
                    <option value="">Todos los estados</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>

               
            </form>

            <button type="button" class="btnLimpiar" id="btnLimpiar">
                <i class="fa-solid fa-rotate"></i>
                Limpiar filtros
            </button>
            <button   type="button" class="btnNuevo" onclick="abrirModalUsuario()">
                <i class="fa-solid fa-plus"></i>
                Nuevo Usuario
            </button>
        </div>

        <table class="tablaUsuarios">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Estado</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-usuarios-tbody"></tbody>
        </table>


        <div class="botones-paginacion">

            <!-- BOTON ANTERIOR -->
            <button
                type="button"
                class="btn-pagina"
                id="btnAnterior"
            >

                <i class="fa-solid fa-chevron-left"></i>
                Anterior

            </button>

            <!-- NUMEROS -->
            <button
                type="button"
                class="btn-pagina numero-pagina activa"
            >
                1
            </button>

            <button
                type="button"
                class="btn-pagina numero-pagina"
            >
                2
            </button>

            <!-- BOTON SIGUIENTE -->
            <button
                type="button"
                class="btn-pagina"
                id="btnSiguiente"
            >

                Siguiente
                <i class="fa-solid fa-chevron-right"></i>

            </button>
        </div>


<!-- =========================
     MODAL NUEVO USUARIO
========================= -->

<div class="modal" id="modalNuevoUsuario" style="display: none;">
    <div class="modal-contenido modal-usuario">
        <span class="cerrar-modal" onclick="cerrarModalUsuario()">&times;</span>
        <h2>Nuevo Usuario</h2>

        <form action="../Controllers/usuariosController.php?action=create" method="POST">
            <div class="modal-grid">
                <div class="grupo-input">
                    <label>Usuario</label>
                    <input type="text" name="apodoUsuario" required>
                </div>

                <div class="grupo-input">
                    <label>Contraseña</label>
                    <input type="password" name="contrasena" required>
                </div>

                <div class="grupo-input">
                    <label>Nombre</label>
                    <input type="text" name="nombreUsuario" required>
                </div>

                <div class="grupo-input">
                    <label>Apellido Paterno</label>
                    <input type="text" name="apellidoPaternoUsuario" required>
                </div>

                <div class="grupo-input">
                    <label>Apellido Materno</label>
                    <input type="text" name="apellidoMaternoUsuario" required>
                </div>

                    <div class="grupo-input">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="grupo-input" style="margin-top: 10px;">
                    <label>Rol</label>
                    <select name="idRol" required>
                        <option value="">Seleccionar rol</option>
                        <option value="1">Administrador</option>
                        <option value="2">Vendedor</option>
                        <option value="3">Operador</option>
                        <option value="4">Ayudante</option>
                        <option value="5">Checador</option>
                    </select>
                </div>

                <button type="submit" class="btnGuardarUsuario">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Usuario
                </button>
            </form>
        </div>
    </div>

    <div class="modal" id="modalEditarUsuario" style="display: none;">
        <div class="modal-contenido modal-usuario">
            <span class="cerrar-modal" onclick="cerrarModalEditar()">&times;</span>
            <h2>Editar Usuario</h2>

            <form action="../Controllers/usuariosController.php?action=update" method="POST">
                <input type="hidden" name="idUsuario" id="editIdUsuario">

                <div class="modal-grid">
                    <div class="grupo-input">
                        <label>Usuario</label>
                        <input type="text" name="apodoUsuario" id="editApodo" required>
                    </div>

                    <div class="grupo-input">
                        <label>Contraseña (Opcional)</label>
                        <input type="password" name="contrasena" placeholder="Nueva contraseña">
                    </div>

                    <div class="grupo-input">
                        <label>Nombre</label>
                        <input type="text" name="nombreUsuario" id="editNombre" required>
                    </div>

                    <div class="grupo-input">
                        <label>Apellido Paterno</label>
                        <input type="text" name="apellidoPaternoUsuario" id="editApellidoP" required>
                    </div>

                    <div class="grupo-input">
                        <label>Apellido Materno</label>
                        <input type="text" name="apellidoMaternoUsuario" id="editApellidoM" required>
                    </div>

                    <div class="grupo-input">
                        <label>Estado</label>
                        <select name="estado" id="editEstado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="grupo-input" style="margin-top: 10px;">
                    <label>Rol</label>
                    <select name="idRol" id="editRol" required>
                        <option value="1">Administrador</option>
                        <option value="2">Vendedor</option>
                        <option value="3">Operador</option>
                        <option value="4">Ayudante</option>
                        <option value="5">Checador</option>
                    </select>
                </div>

                <button type="submit" class="btnGuardarUsuario">
                    <i class="fa-solid fa-floppy-disk"></i> Actualizar Usuario
                </button>
            </form>
        </div>
    </div>

    <div class="modal" id="modalEliminarUsuario" style="display: none;">
    <div class="modal-contenido" style="max-width: 400px; text-align: center;">
        
        <div class="contenedor-animacion-eliminar">
            <img id="imgAnimacionEliminar" src="../SRC/animacion/0.png" alt="Animación de eliminación" class="delete-gif">
        </div>

        <h2>¿Estás seguro?</h2>
        
        <p style="color: #64748b; margin-top: 8px; font-size: 15px;">
            El usuario <strong id="nombreUsuarioEliminar" style="color: #1f2f56;"></strong> será eliminado.
        </p>
        
        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 25px;">
            <button type="button" class="btnLimpiar" onclick="cerrarModalEliminar()" style="margin:0; flex: 1;">Cancelar</button>
            <a id="btnConfirmarEliminar" href="#" class="btnAplicar" style="background: #ef4444; text-decoration: none; justify-content: center; margin:0; flex: 1; height: 44px;">Sí, eliminar</a>
        </div>
    </div>
</div>
     
        <div class="modal" id="modalCerrarSesion" style="display: none;">
    <div class="modal-contenido" style="max-width: 400px; text-align: center; padding: 24px;">
        
        <div class="contenedor-animacion-eliminar">
    <img id="imgAnimacionLogout" src="../SRC/bye/0.png" alt="Animación de cierre de sesión" class="delete-gif">
</div>

        <h2 style="color: #1f2f56; font-size: 22px; font-weight: 700; margin-bottom: 12px;">¿Desea cerrar sesión?</h2>
        
        <p style="color: #64748b; margin-top: 8px; font-size: 15px; line-height: 1.5; padding: 0 10px;">
            Su sesión actual se cerrará y volverá a la pantalla de inicio de sesión.
        </p>
        
        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 25px;">
            <button type="button" class="btnLimpiar" onclick="cerrarModalLogout()" style="margin:0; flex: 1; height: 44px;">Cancelar</button>
            <a href="login.php" class="btnAplicar" style="background: #ef4444; text-decoration: none; display: flex; justify-content: center; align-items: center; gap: 8px; margin:0; flex: 1; height: 44px; color: #ffffff; font-weight: 600; border-radius: 6px;">
                <i class="fa-solid fa-right-from-bracket"></i> Confirmar
            </a>
        </div>
    </div>
</div>
    

    <script>
        // 1. Elementos del DOM
        const formFiltros = document.getElementById('formFiltrosUsuarios');
        const inputBusqueda = document.getElementById('inputBusqueda');
        const selectEstado = document.getElementById('selectEstado');
        const btnLimpiar = document.getElementById('btnLimpiar');
        const botonesNumero = document.querySelectorAll('.numero-pagina');
        const btnAnterior = document.getElementById('btnAnterior');
        const btnSiguiente = document.getElementById('btnSiguiente');

        // 2. Estado de la paginación local (Declarada una sola vez)
        let paginaActual = 1;

        // 3. Función para actualizar la numeración visual de los botones
        function actualizarNumeros() {
            botonesNumero.forEach((boton, index) => {
                boton.textContent = paginaActual + index;
            });
        }

        // 4. Eventos de los botones Anterior y Siguiente
        btnSiguiente.addEventListener('click', () => {
            paginaActual++;
            window.cambiarPaginaUsuarios(paginaActual);
            actualizarNumeros();
        });

        btnAnterior.addEventListener('click', () => {
            if (paginaActual > 1) {
                paginaActual--;
                window.cambiarPaginaUsuarios(paginaActual);
                actualizarNumeros();
            }
        });

        // 5. Sincronización de filtros con la búsqueda en tiempo real
        formFiltros.addEventListener('submit', function(e) {
            e.preventDefault(); // Evita que la página se recargue agresivamente
            inputBusqueda.dispatchEvent(new Event('input')); // Dispara la búsqueda asíncrona
        });

        selectEstado.addEventListener('change', () => {
            inputBusqueda.dispatchEvent(new Event('input'));
        });

        // 6. Botón Limpiar filtros
        btnLimpiar.addEventListener('click', function () {
            inputBusqueda.value = '';
            selectEstado.value = '';
            paginaActual = 1;
            actualizarNumeros();
            inputBusqueda.dispatchEvent(new Event('input')); 
        });

        // 7. Inicialización del módulo (Se ejecuta cuando el HTML está listo)
        document.addEventListener('DOMContentLoaded', () => {
            configurarBusquedaRealTime(
                'inputBusqueda',
                'tabla-usuarios-tbody',
                '../Controllers/usuariosController.php',
                renderizarFilaUsuario
            );

            // Forzar carga inicial de usuarios
            inputBusqueda.dispatchEvent(new Event('input')); 
        });
    </script>


</body>
</html>