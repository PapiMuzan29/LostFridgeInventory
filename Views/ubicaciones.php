<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almacén - Existencias Pendientes - LFI</title>
    <link rel="stylesheet" href="CSS/barraNavegacion.css">
    <link rel="stylesheet" href="CSS/ubicaciones.css">
    <link rel="stylesheet" href="CSS/cerdito.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="config-container" id="contenedor-principal">
        
      

        <!-- Tarjeta de Búsqueda Única -->
        <div class="card-entrada shadow-soft" style="margin-bottom: 20px;">
            <div class="grupo-campo" style="width: 100%;">
                <label>Buscar Producto / Lote Pendiente:</label>
                <div class="input-with-icon">
                    <input type="text" id="inputBuscarExistencia" class="input-captura" placeholder="Escribe para buscar en existencias pendientes...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
        </div>

        <!-- Tabla Principal de Pendientes -->
        <div class="card-entrada tabla-partidas-container shadow-soft">
            <table id="tablaEstandar" class="tablaUsuarios">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Lote / Producto</th>
                        <th>Descripción</th>
                        <th>Stock Pendiente (Cajas)</th>
                        <th>Kgs Totales</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody id="tablaExistenciasBody">
                    <!-- Datos dinámicos de inventario pendiente -->
                </tbody>
            </table>
        </div>

    </div>

    <script src="../Services/cerdito.js"></script>
    <script src="../Services/funciones.js"></script>
    <script src="../Services/navegacion.js"></script>
    <script src="../../Services/tema.js"></script>
</body>
</html>