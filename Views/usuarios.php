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
                        </select>d
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
     
   
    

    <script>
        // Elementos del DOM
        const formFiltros = document.getElementById('formFiltrosUsuarios');
        const inputBusqueda = document.getElementById('inputBusqueda');
        const selectEstado = document.getElementById('selectEstado');
        const btnLimpiar = document.getElementById('btnLimpiar');

        /* FUNCION FILTRAR */
        function aplicarFiltros() {
            const busqueda = inputBusqueda.value;
            const estado = selectEstado.value;

            // Petición asíncrona a sí mismo para no recargar la página entera
            fetch(`usuarios.php?busqueda=${encodeURIComponent(busqueda)}&estado=${estado}`)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nuevaTabla = doc.querySelector('.tablaUsuarios tbody');
                
                // Inyectamos solo las filas nuevas en la tabla
                document.querySelector('.tablaUsuarios tbody').innerHTML = nuevaTabla.innerHTML;
            })
            .catch(error => console.error('Error al procesar los filtros:', error));
        }

        /* EVENTO SUBMIT DEL FORMULARIO (Al dar Enter o clic en Aplicar) */
        formFiltros.addEventListener('submit', function(e) {
            e.preventDefault(); // Evita el envío tradicional y recarga de página
            aplicarFiltros();
        });

        /* FILTRADO AUTOMÁTICO (Opcional: filtra al cambiar el selector de Estado) */
        selectEstado.addEventListener('change', () => {
            inputBusqueda.dispatchEvent(new Event('input'));
        });

        /* BOTON LIMPIAR */
        btnLimpiar.addEventListener('click', function () {
            inputBusqueda.value = '';
            selectEstado.value = '';
            inputBusqueda.dispatchEvent(new Event('input')); 
        });


        const botonesNumero = document.querySelectorAll('.numero-pagina');

        const btnAnterior = document.getElementById('btnAnterior');

        const btnSiguiente = document.getElementById('btnSiguiente');

        /* =========================
        PAGINA INICIAL
        ========================= */

        let paginaActual = 1;

        /* =========================
        ACTUALIZAR NUMEROS
        ========================= */

        function actualizarNumeros() {

            botonesNumero.forEach((boton, index) => {

                boton.textContent = paginaActual + index;

            });

        }

        /* =========================
        SIGUIENTE
        ========================= */

        btnSiguiente.addEventListener('click', () => {

            paginaActual++;

            actualizarNumeros();

        });

        /* =========================
        ANTERIOR
        ========================= */

        btnAnterior.addEventListener('click', () => {

            if (paginaActual > 1) {

                paginaActual--;

                actualizarNumeros();

            }

        });

        document.addEventListener('DOMContentLoaded', () => {
            configurarBusquedaRealTime(
                'inputBusqueda', 
                'tabla-usuarios-tbody', 
                '../Controllers/usuariosController.php', 
                renderizarFilaUsuario
            );

            const input = document.getElementById('inputBusqueda');
            input.dispatchEvent(new Event('input')); 
        });



    </script>


</body>
</html>