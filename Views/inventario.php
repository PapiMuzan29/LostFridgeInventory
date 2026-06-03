<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

// Datos de prueba temporales para las tarjetas KPI superiores
$stats = [
    'productos' => 128,
    'cajas' => '1,245',
    'proximos' => 18,
    'vencidos' => 3
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - LFI</title>
    
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/usuarios.css">
    <link rel="stylesheet" href="../Views/css/inventario.css">
</head>
<body>

    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="main-content contenedor">
        
        <div class="inventario-header">
            <div class="header-titulos">
                <h1>Inventario</h1>
                <p>Consulta y gestión del inventario actual</p>
            </div>
            
            <div class="header-acciones">
                <button type="button" class="btn-exportar">
                    <i class="fa-solid fa-file-excel"></i> Exportar a Excel
                </button>
                <button type="button" class="btn-imprimir">
                    <i class="fa-solid fa-print"></i> Imprimir
                </button>
                <button type="button" class="btn-agregar-prod" onclick="abrirModalAgregarProducto()">
                    <i class="fa-solid fa-plus"></i> Agregar producto
                </button>
                <button type="button" class="btn-agregar-prov" onclick="abrirModalAgregarProveedor()">
                    <i class="fa-solid fa-user-plus"></i> Agregar proveedor
                </button>
            </div>
        </div>

        <div class="inventario-cards-grid">
            <div class="kpi-card">
                <div class="kpi-icon-circle bg-azul">
                    <i class="fa-solid fa-cubes"></i>
                </div>
                <div class="kpi-datos">
                    <h3>Total de productos</h3>
                    <span class="kpi-numero"><?= $stats['productos'] ?></span>
                    <p class="kpi-subtexto">Productos diferentes</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-circle bg-verde">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div class="kpi-datos">
                    <h3>Total de cajas</h3>
                    <span class="kpi-numero"><?= $stats['cajas'] ?></span>
                    <p class="kpi-subtexto">Cajas en inventario</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-circle bg-naranja">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="kpi-datos">
                    <h3>Próximos a vencer</h3>
                    <span class="kpi-numero"><?= $stats['proximos'] ?></span>
                    <p class="kpi-subtexto">En los próximos 7 días</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-circle bg-rojo">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="kpi-datos">
                    <h3>Vencidos</h3>
                    <span class="kpi-numero"><?= $stats['vencidos'] ?></span>
                    <p class="kpi-subtexto">Cajas vencidas</p>
                </div>
            </div>
        </div>

        <div class="inventario-filtro-container">
            <form id="formFiltrosInventario" class="inventario-filtro-form" onsubmit="event.preventDefault();">
                
                <div class="filtro-grupo">
                    <label>Buscar por</label>
                    <select id="selectTipoBusqueda">
                        <option value="producto">📦 Producto</option>
                        <option value="proveedor">🚚 Proveedor</option>
                    </select>
                </div>

                <div class="filtro-grupo flex-grande">
                    <label id="labelDinamicoBusqueda">Buscar producto</label>
                    <div class="inventario-buscador-wrapper">
                        <input type="text" id="inputBusquedaInventario" placeholder="Nombre, código o descripción...">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>

                <div class="filtro-grupo">
                    <label>Ubicación</label>
                    <select id="selectUbicacion">
                        <option value="">Todas</option>
                    </select>
                </div>

                <div class="filtro-grupo">
                    <label>Estado</label>
                    <select id="selectEstadoInventario">
                        <option value="">Todos</option>
                        <option value="optimo">Óptimo</option>
                        <option value="proximo">Próximo a vencer</option>
                        <option value="vencido">Vencido</option>
                    </select>
                </div>

                <div class="filtro-botones">
                    
                    <button type="button" id="btnLimpiarInventario" class="btn-limpiar-inv"><i class="fa-solid fa-rotate"></i> Limpiar filtros</button>
                </div>
            </form>
        </div>

        <table class="tabla-inventario">
            <thead id="thead-inventario">
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Existencia (cajas)</th>
                    <th>Peso total (kg)</th>
                    <th>Próx. vencimiento</th>
                    <th>Estado</th>
                    <th class="txt-centro">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-inventario-tbody">
                </tbody>
        </table>

        <div class="inventario-paginacion-footer">
            
            <div class="paginacion-controles">
                <button type="button" class="btn-pagina-inv" id="btnAnteriorInv">Anterior</button>
                <div id="contenedorNumerosInv" class="numeros-wrapper"></div>
                <button type="button" class="btn-pagina-inv" id="btnSiguienteInv">Siguiente</button>
            </div>
        </div>

    </div>

    <script>
        // 📊 BD Temporal interna para que la tabla muestre datos inmediatamente en la pantalla
        const dePruebaProductos = [
            { codigo: "PROD-001", nombre: "Rib Eye Premium", existencia: 45, ubicacion: "Cámara 1", vencimiento: "2026-06-15", estado: "Óptimo" },
            { codigo: "PROD-002", nombre: "Filete de Salmón",  existencia: 20, ubicacion: "Cámara 3", vencimiento: "2026-06-03", estado: "Próximo" },
            { codigo: "PROD-003", nombre: "Costilla de Cerdo", existencia: 63, ubicacion: "Cámara 1", vencimiento: "2026-05-20", estado: "Vencido" }
        ];

        const dePruebaProveedores = [
            { id: "PROV-101", empresa: "Distribuidora de Carnes del Norte", rfc: "DCN920412AA1", telefono: "811-234-5678", correo: "ventas@carnesnorte.com", direccion: "Av. Industrial #450, Monterrey", estado: "Activo" },
            { id: "PROV-102", empresa: "Mariscos del Pacífico S.A.", rfc: "MPA8810305B2", telefono: "664-987-6543", correo: "contacto@marispacifico.mx", direccion: "Calle Marina #12, Ensenada", estado: "Activo" },
            { id: "PROV-103", empresa: "Empaques Frigoríficos Robles", rfc: "EFR150722TR4", telefono: "555-432-1098", correo: "info@roblesfrigo.com", direccion: "Eje Central #89, CDMX", estado: "Inactivo" }
        ];

        document.addEventListener('DOMContentLoaded', () => {
            const selectTipo = document.getElementById('selectTipoBusqueda');
            const labelDinamico = document.getElementById('labelDinamicoBusqueda');
            const inputBusqueda = document.getElementById('inputBusquedaInventario');
            const theadInventario = document.getElementById('thead-inventario');
            const tbodyInventario = document.getElementById('tabla-inventario-tbody');
            const txtMostrando = document.getElementById('txtMostrandoRegistros');

            // Cabeceras HTML estructuradas
            const columnasProducto = `
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Existencia (cajas)</th>
                    <th>Peso total (kg)</th>
                    <th>Próx. vencimiento</th>
                    <th>Estado</th>
                    <th class="txt-centro">Acciones</th>
                </tr>
            `;

            const columnasProveedor = `
                <tr>
                    <th>Código/ID</th>
                    <th>Nombre / Empresa</th>
                    <th>RFC</th>
                    <th>Dirección</th>
                    <th>Colonia</th>
                    <th>C.P.</th>
                    <th>Estado</th>
                    <th>Status</th>
                    <th class="txt-centro">Acciones</th>
                </tr>
            `;

            // Función encargada de filtrar y pintar las filas en la pantalla
            function actualizarTabla() {
                const tipo = selectTipo.value;
                const busqueda = inputBusqueda.value.toLowerCase().trim();
                tbodyInventario.innerHTML = ''; 

                if (tipo === 'proveedor') {
                    // Filtrar proveedores
                    const filtrados = dePruebaProveedores.filter(p => 
                        p.id.toLowerCase().includes(busqueda) || 
                        p.empresa.toLowerCase().includes(busqueda) || 
                        p.rfc.toLowerCase().includes(busqueda)
                    );

                    if (filtrados.length === 0) {
                        tbodyInventario.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px;">No se encontraron proveedores</td></tr>`;
                    } else {
                        filtrados.forEach(p => {
                            const claseEstado = p.estado === 'Activo' ? 'activo' : 'inactivo';
                            tbodyInventario.innerHTML += `
                                <tr>
                                    <td>${p.codigo}</td>
                                    <td><strong>${p.nombre}</strong></td>
                                    <td>${p.existencia}</td>
                                    <td>${p.ubicacion}</td>
                                    <td>${p.vencimiento}</td>
                                    <td>
                                        <span class="estado ${claseEstado}">
                                            ${p.estado}
                                        </span>
                                    </td>
                                    <td class="txt-centro">
                                        <div class="acciones">
                                            <button type="button" class="btn-accion editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn-accion eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    txtMostrando.textContent = `Mostrando 1 a ${filtrados.length} de ${filtrados.length} proveedores`;

                } else {
                    // Filtrar productos
                    const filtrados = dePruebaProductos.filter(p => 
                        p.codigo.toLowerCase().includes(busqueda) || 
                        p.nombre.toLowerCase().includes(busqueda) || 
                        p.categoria.toLowerCase().includes(busqueda)
                    );

                    if (filtrados.length === 0) {
                        tbodyInventario.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px;">No se encontraron productos</td></tr>`;
                    } else {
                        filtrados.forEach(p => {
                            let claseEstado = 'activo'; // óptimo
                            if(p.estado === 'Próximo') claseEstado = 'inactivo'; // naranja/rojo alternativo
                            if(p.estado === 'Vencido') claseEstado = 'inactivo'; 

                            tbodyInventario.innerHTML += `
                                <tr>
                                    <td>${p.codigo}</td>
                                    <td><strong>${p.nombre}</strong></td>
                                    <td>${p.categoria}</td>
                                    <td>${p.existencia}</td>
                                    <td>${p.ubicacion}</td>
                                    <td>${p.vencimiento}</td>
                                    <td><span class="estado ${claseEstado}">${p.estado}</span></td>
                                    <td class="txt-centro">
                                        <div class="acciones">
                                            <button type="button" class="btn-accion editar" onclick="console.log('Editar prod')"><i class="fa-solid fa-pen"></i></button>
                                            <button type="button" class="btn-accion eliminar" onclick="console.log('Borrar prod')"><i class="fa-solid fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    txtMostrando.textContent = `Mostrando 1 a ${filtrados.length} de ${filtrados.length} productos`;
                }
            }

            // Escucha cambios en el tipo de consulta (Producto / Proveedor)
            selectTipo.addEventListener('change', function() {
                if (this.value === 'proveedor') {
                    labelDinamico.textContent = 'Buscar proveedor';
                    inputBusqueda.placeholder = 'Nombre, RFC, teléfono o marca...';
                    theadInventario.innerHTML = columnasProveedor; 
                } else {
                    labelDinamico.textContent = 'Buscar producto';
                    inputBusqueda.placeholder = 'Nombre, código o descripción...';
                    theadInventario.innerHTML = columnasProducto; 
                }
                inputBusqueda.value = ''; // Limpiamos el buscador al cambiar
                actualizarTabla();
            });

            // Escucha la escritura en tiempo real en el buscador
            inputBusqueda.addEventListener('input', actualizarTabla);

            // Cargar la tabla inicialmente con los productos
            actualizarTabla();

            // Botón limpiar filtros
            document.getElementById('btnLimpiarInventario').addEventListener('click', () => {
                inputBusqueda.value = '';
                actualizarTabla();
            });
        });

        // Modales de control del sistema LFI
        function abrirModalAgregarProducto() { console.log("Abriendo modal producto"); }
        function abrirModalAgregarProveedor() { console.log("Abriendo modal proveedor"); }
    </script>
</body>
</html>