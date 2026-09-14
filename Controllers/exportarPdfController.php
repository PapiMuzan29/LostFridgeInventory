<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    http_response_code(401);
    exit('No autorizado');
}

$idEntrada = $_GET['id'] ?? null;
if (!$idEntrada) {
    die('ID de registro no especificado.');
}

$host = 'localhost';
$db   = 'bd_lfi'; 
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 1. Consultamos la cabecera de la entrada
    $stmt = $pdo->prepare("SELECT * FROM entradas WHERE id_entrada = ?");
    $stmt->execute([$idEntrada]);
    $entrada = $stmt->fetch();

    if (!$entrada) {
        die('Registro no encontrado.');
    }

    $idProveedor = $entrada['id_proveedor'] ?? ($entrada['idProveedor'] ?? null);
    $nombreProveedor = 'Proveedor ID: ' . ($idProveedor ?? 'N/A');

    // 2. Buscamos el nombre del proveedor
    if ($idProveedor) {
        $stmtProv = $pdo->prepare("SELECT nombreProveedor FROM proveedor WHERE idProveedor = ?");
        $stmtProv->execute([$idProveedor]);
        $provData = $stmtProv->fetch();
        
        if ($provData && !empty($provData['nombreProveedor'])) {
            $nombreProveedor = $provData['nombreProveedor'];
        }
    }

    // 3. Consultamos los detalles reales de la tabla
    $stmtDetalle = $pdo->prepare("SELECT * FROM entradas_detalle WHERE id_entrada = ?");
    $stmtDetalle->execute([$idEntrada]);
    $detalles = $stmtDetalle->fetchAll();

    $folio         = $entrada['folio'] ?? 'N/A';
    $fechaRegistro = $entrada['fecha_hora_registro'] ?? ($entrada['fechaHoraRegistro'] ?? 'N/A');
    $almacen       = $entrada['id_almacen'] ?? ($entrada['idAlmacen'] ?? '1');
    $usuario       = $_SESSION['apodoUsuario'] ?? 'Sistema';
    $fechaEmision  = date('d/m/Y H:i');

    // 4. Acumuladores basados en las columnas reales ('kgs' y 'cantidad')
    $totalPesoBruto  = 0;
    $totalPesoOrigen = 0;
    $cantidadCombos  = 0;

    foreach ($detalles as $det) {
        $kilosRegistro = floatval($det['kgs'] ?? 0);
        $cantRegistro  = floatval($det['cantidad'] ?? 0);

        // Sumamos los kilos registrados como peso bruto
        $totalPesoBruto += $kilosRegistro;

        // Contamos la cantidad de combos ingresados
        if ($cantRegistro > 0) {
            $cantidadCombos += $cantRegistro;
        } else if ($kilosRegistro > 0) {
            $cantidadCombos++; // Si no hay cantidad explícita pero sí kilos, cuenta como 1 partida/combo
        }
    }

    // Buscamos si el peso origen viene registrado en la cabecera (entradas) o en los detalles
    foreach (['peso_origen', 'pesoOrigen', 'peso_proveedor', 'pesoProveedor', 'peso_remision'] as $col) {
        if (isset($entrada[$col]) && is_numeric($entrada[$col])) {
            $totalPesoOrigen = floatval($entrada[$col]);
            break;
        }
    }

    // Si el peso bruto no se llenó de los detalles, lo buscamos en la cabecera
    if ($totalPesoBruto == 0) {
        $totalPesoBruto = floatval($entrada['totalPeso'] ?? ($entrada['peso_bruto'] ?? 0));
    }

    // Diferencia estricta de kilos (Peso Bruto - Peso Origen)
    $diferenciaTotal = $totalPesoBruto - $totalPesoOrigen;

} catch (Exception $e) {
    die("Error en la BD: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Entrada - Folio <?php echo $folio; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .titulo-seccion {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .sub-seccion {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .fecha-emision {
            text-align: right;
            font-size: 12px;
            color: #475569;
        }
        .linea-divisoria {
            border: none;
            height: 2px;
            background-color: #0f172a;
            margin: 15px 0 25px 0;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 20px;
            background-color: #ffffff;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 13px;
            color: #475569;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .card-value {
            font-size: 26px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .card-sub {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
        }
        .info-box-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .info-item {
            font-size: 14px;
            color: #334155;
            margin-bottom: 8px;
        }
        .info-item strong {
            color: #0f172a;
        }
        .footer-info {
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        @media print {
            body { 
                padding: 0; 
                background-color: #ffffff; 
            }
            .container { 
                box-shadow: none; 
                padding: 0; 
                max-width: 100%; 
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="titulo-seccion">LFI - CONTROL OPERATIVO DE INVENTARIOS</h1>
                    <div class="sub-seccion">Comprobante de Recepción y Comparativa de Pesos (Combos de Pierna)</div>
                </td>
                <td class="fecha-emision">
                    <strong>Fecha de Emisión:</strong><br><?php echo $fechaEmision; ?>
                </td>
            </tr>
        </table>

        <hr class="linea-divisioria">

        <!-- Tarjetas de Resumen -->
        <table style="width: 100%; border-collapse: separate; border-spacing: 15px 0; margin-left: -15px; margin-right: -15px; margin-bottom: 10px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding: 0;">
                    <div class="card" style="border-left: 5px solid #0284c7;">
                        <div class="card-title">Peso Total Registrado (Peso Bruto / Báscula)</div>
                        <p class="card-value" style="color: #0369a1;"><?php echo number_format($totalPesoBruto, 3); ?> kg</p>
                        <div class="card-sub">Folio: #<?php echo $folio; ?> | Combos Ingresados: <strong><?php echo $cantidadCombos; ?></strong></div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding: 0;">
                    <div class="card" style="border-left: 5px solid #d97706;">
                        <div class="card-title">Peso que Manda el Proveedor (Peso Origen)</div>
                        <p class="card-value" style="color: #b45309;"><?php echo number_format($totalPesoOrigen, 3); ?> kg</p>
                        <div class="card-sub">Según datos de origen</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Tarjeta de Diferencia Exacta (Bruto - Origen) -->
        <div class="card" style="border-left: 5px solid <?php echo ($diferenciaTotal == 0) ? '#16a34a' : '#dc2626'; ?>; background-color: #f8fafc;">
            <div class="card-title">Diferencia Total de Kilos (Peso Bruto - Peso Origen)</div>
            <p class="card-value" style="color: <?php echo ($diferenciaTotal == 0) ? '#16a34a' : '#dc2626'; ?>;">
                <?php echo number_format($diferenciaTotal, 3); ?> kg
            </p>
            <div class="card-sub">
                <?php 
                    if (abs($diferenciaTotal) < 0.001) {
                        echo 'Los pesos coinciden exactamente.';
                    } elseif ($diferenciaTotal > 0) {
                        echo 'El peso en báscula es mayor al reportado por el proveedor (' . number_format(abs($diferenciaTotal), 3) . ' kg de más).';
                    } else {
                        echo 'El peso en báscula es menor al reportado por el proveedor (' . number_format(abs($diferenciaTotal), 3) . ' kg de menos).';
                    }
                ?>
            </div>
        </div>

        <!-- Información del Movimiento -->
        <div class="info-box">
            <div class="info-box-title">Información del Movimiento</div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="info-item"><strong>Almacén Destino:</strong> ID <?php echo $almacen; ?></div>
                        <div class="info-item"><strong>Proveedor:</strong> <?php echo htmlspecialchars($nombreProveedor); ?></div>
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="info-item"><strong>Fecha y Hora de Captura:</strong> <?php echo $fechaRegistro; ?></div>
                        <div class="info-item"><strong>Usuario Responsable:</strong> <?php echo htmlspecialchars($usuario); ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-info">
            <div>Documento generado por: <strong><?php echo htmlspecialchars($usuario); ?></strong></div>
            <div>Sistema LFI - Control Operativo de Almacén</div>
        </div>
    </div>

</body>
</html>