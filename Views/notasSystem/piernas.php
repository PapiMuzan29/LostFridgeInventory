<?php
session_start();

$rolesPermitidos = [1, 5];
require_once __DIR__ . '/../../Config/cadenero.php';

$fechaActual = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$comboActual = isset($_GET['combo']) ? $_GET['combo'] : 'combo1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Módulo Piernas</title>
    
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Externo -->
    <link rel="stylesheet" href="CSS/piernas.css">
    
    <style>
        body {
            background-color: #0b0f19;
            color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 16px;
            padding-bottom: 90px;
        }
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .btn-volver {
            background: #1e293b;
            color: #f8fafc;
            border: 1px solid #334155;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .module-badge {
            background: #1e293b;
            color: #38bdf8;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #334155;
        }
        .user-badge {
            background: #1e293b;
            color: #94a3b8;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            border: 1px solid #334155;
        }
        .date-selector {
            background: #131b2e;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .date-selector button {
            background: #1e293b;
            border: 1px solid #334155;
            color: #f8fafc;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
        }
        .date-display {
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f8fafc;
        }
        .section-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }
        .combos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn-combo {
            background: #131b2e;
            border: 1px solid #1e293b;
            color: #94a3b8;
            padding: 14px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-combo.active {
            background: #1e293b;
            border: 2px solid #f59e0b;
            color: #ffffff;
        }
        .card-total-dia {
            background: #1f170e;
            border: 1px solid #78350f;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .card-total-dia span.titulo {
            font-size: 11px;
            font-weight: 700;
            color: #f59e0b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 4px;
        }
        .card-total-dia span.valor {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
        }
        .card-total-dia i {
            font-size: 28px;
            color: #f59e0b;
        }
        .venta-item {
            background: #131b2e;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .venta-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .venta-cliente {
            font-weight: 700;
            font-size: 14px;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .venta-ticket {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .venta-badge-pzs {
            background: #1e293b;
            border: 1px solid #334155;
            color: #f59e0b;
            padding: 8px 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 800;
            font-size: 14px;
            min-width: 45px;
        }
        .bottom-total-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #0b0f19;
            padding: 12px 16px;
            box-sizing: border-box;
            border-top: 1px solid #1e293b;
        }
        .bottom-total-content {
            background: #131b2e;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 14px 20px;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        .bottom-total-content span.label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .bottom-total-content span.val {
            font-size: 18px;
            font-weight: 800;
            color: #f59e0b;
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <div class="top-nav">
        <a href="inventario.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="module-badge"><i class="fa-solid fa-drumstick-bite"></i> Módulo Piernas</div>
        <div class="user-badge"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['apodoUsuario'] ?? 'Admin') ?></div>
    </div>

    <!-- SELECTOR DE FECHA -->
    <div class="date-selector">
        <button type="button"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="date-display">
            <i class="far fa-calendar-alt" style="color: #f59e0b;"></i> 
            <span id="display-fecha"><?php echo htmlspecialchars($fechaActual); ?></span>
        </div>
        <button type="button"><i class="fa-solid fa-chevron-right"></i></button>
    </div>

    <!-- SELECCIONA COMBO -->
    <span class="section-label">SELECCIONA COMBO</span>
    <div class="combos-grid">
        <button type="button" class="btn-combo active" onclick="seleccionarCombo('combo1')">
            <i class="fa-solid fa-box"></i> Combo 1
        </button>
        <button type="button" class="btn-combo" onclick="seleccionarCombo('combo2')">
            <i class="fa-solid fa-box"></i> Combo 2
        </button>
        <button type="button" class="btn-combo" onclick="seleccionarCombo('todos')">
            <i class="fa-solid fa-boxes-stacked"></i> Todos
        </button>
    </div>

    <!-- TOTAL DEL DÍA -->
    <div class="card-total-dia">
        <div>
            <span class="titulo">TOTAL DEL DÍA (COMBO 1)</span>
            <span class="valor" id="txtTotalDia">0 unidades (0.00 kg)</span>
        </div>
        <i class="fa-solid fa-boxes-stacked"></i>
    </div>

    <!-- LISTADO DE VENTAS / NOTAS -->
    <span class="section-label" id="lblSubtituloVentas">VENTAS COMBO 1</span>
    <div id="contenedorVentasPiernas">
        <!-- Ejemplo visual de tarjeta de venta basada en notas -->
        <div class="venta-item">
            <div class="venta-info">
                <div class="venta-cliente">
                    <i class="fa-solid fa-user" style="color: #f59e0b; font-size: 12px;"></i> Cliente: ChuyLux
                </div>
                <div class="venta-ticket">
                    <i class="fa-solid fa-receipt" style="font-size: 11px;"></i> Ticket: #1042
                </div>
            </div>
            <div class="venta-badge-pzs">
                12 <span style="font-size:9px; display:block; font-weight:normal; color:#94a3b8;">pzs</span>
            </div>
        </div>

        <div class="venta-item">
            <div class="venta-info">
                <div class="venta-cliente">
                    <i class="fa-solid fa-user" style="color: #f59e0b; font-size: 12px;"></i> Cliente: Mostrador
                </div>
                <div class="venta-ticket">
                    <i class="fa-solid fa-receipt" style="font-size: 11px;"></i> Ticket: #1045
                </div>
            </div>
            <div class="venta-badge-pzs">
                4 <span style="font-size:9px; display:block; font-weight:normal; color:#94a3b8;">pzs</span>
            </div>
        </div>
    </div>

    <!-- BARRA INFERIOR DE TOTAL GENERAL -->
    <div class="bottom-total-bar">
        <div class="bottom-total-content">
            <span class="label">TOTAL GENERAL ACUMULADO</span>
            <span class="val" id="txtTotalGeneral">16 unidades (124.50 kg)</span>
        </div>
    </div>

    <script>
        function seleccionarCombo(combo) {
            document.querySelectorAll('.btn-combo').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            const nombreCombo = combo === 'todos' ? 'Todos los Combos' : (combo === 'combo1' ? 'Combo 1' : 'Combo 2');
            document.querySelector('.card-total-dia span.titulo').textContent = `TOTAL DEL DÍA (${nombreCombo.toUpperCase()})`;
            document.getElementById('lblSubtituloVentas').textContent = `VENTAS ${nombreCombo.toUpperCase()}`;
            
            // Aquí puedes conectar el consumo visual de tus notas mediante AJAX o recarga
        }
    </script>
</body>
</html>