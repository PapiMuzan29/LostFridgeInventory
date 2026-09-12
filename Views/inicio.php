<?php
session_start();
date_default_timezone_set('America/Mexico_City');

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

$usuarioActual = $_SESSION['apodoUsuario'];
$esAdmin = ($usuarioActual === 'admin_sistema');

require_once __DIR__ . '/../Config/BD.php';
require_once __DIR__ . '/../Models/modeloInventario.php';

$db = new BD();
$modeloInv = new modeloInventario();
$stats = $modeloInv->getKpiStats();

$rendimientoUsuarios = [];
$flujoSemanal = [];
$historialCombos = [];
$errorBD = "";

try {
    if ($esAdmin) {
        $qRendimiento = "SELECT usuarioResponsable, COUNT(*) as total_movimientos, MAX(hora) as ultima_hora 
                         FROM bitacora_movimientos 
                         WHERE fecha = CURDATE() 
                         GROUP BY usuarioResponsable 
                         ORDER BY total_movimientos DESC";
        $stmtR = $db->consulta($qRendimiento);
        $rendimientoUsuarios = $stmtR->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $qFlujo = "SELECT fecha, tipo, COUNT(*) as total 
                   FROM bitacora_movimientos 
                   WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                   GROUP BY fecha, tipo 
                   ORDER BY fecha DESC";
        $stmtF = $db->consulta($qFlujo);
        $flujoSemanal = $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        // 🎯 FILTRADO ESTRICTO: Exclusivo para entradas que contienen combos reales (con peso de origen y total de combos válidos)
        $qCombos = "SELECT DISTINCT e.folio, p.nombreProveedor, e.totalPeso, e.peso_origen, e.total_combos, e.fecha_hora_registro, e.id_entrada 
                    FROM entradas e 
                    LEFT JOIN proveedor p ON e.id_proveedor = p.idProveedor 
                    INNER JOIN entradas_detalle ed ON e.id_entrada = ed.id_entrada 
                    WHERE e.peso_origen > 0 AND e.total_combos > 0
                    ORDER BY e.folio DESC LIMIT 5";
        $stmtC = $db->consulta($qCombos);
        $historialCombos = $stmtC->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

} catch (Throwable $e) {
    $errorBD = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Control Operativo LFI</title>
    
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/usuarios.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/inventario.css">
    <link rel="stylesheet" href="../Views/css/inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="../Services/movimiento.js"></script>

    <style>
        #contenedorImpresionDirecta {
            display: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #contenedorImpresionDirecta, #contenedorImpresionDirecta * {
                visibility: visible;
            }
            #contenedorImpresionDirecta {
                display: block;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                padding: 20px;
                box-sizing: border-box;
            }
            .contenedor, .barra-navegacion, nav, aside {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'assets/barraNavegacion.php'; ?>

    <div class="contenedor" style="padding: 24px;">

        <div class="card-inicio card-bienvenida-usuario" style="position: relative;">
            <div>
                <div class="header-bienvenida-title">
                    <h1 class="titulo-principal">¡Hola, <?php echo htmlspecialchars($usuarioActual); ?>! 👋</h1>
                    <span class="badge-rol <?php echo $esAdmin ? 'admin' : 'operador'; ?>">
                        <?php echo $esAdmin ? 'Administrador General' : 'Operador de Báscula'; ?>
                    </span>
                </div>
                <p class="subtitulo-descripcion">
                    <?php echo $esAdmin ? 'Tablero analítico de rendimiento del personal y flujo operativo del refrigerador.' : 'Panel operativo con acceso directo a la impresión de comprobantes de recepción de combos.'; ?>
                </p>
            </div>
            
            <div style="position: absolute; top: 24px; right: 24px; display: flex; gap: 10px;">
                <?php if ($esAdmin): ?>
                    <button type="button" onclick="abrirModalExportar()" style="padding: 10px 18px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <i class="fa-solid fa-print"></i> Imprimir Reporte
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
            <div class="card general-mov">
                <h3>Total de productos</h3>
                <span style="font-size: 28px; font-weight: bold;"><?= htmlspecialchars((string)($stats['productos'] ?? 0)) ?></span>
                <p class="card-subtexto">Productos diferentes en catálogo</p>
            </div>
            <div class="card success">
                <h3>Total de cajas</h3>
                <span style="font-size: 28px; font-weight: bold;"><?= htmlspecialchars((string)($stats['cajas'] ?? 0)) ?></span>
                <p class="card-subtexto">Cajas disponibles en inventario</p>
            </div>
        </div>

        <?php if (!empty($errorBD)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <strong>Aviso:</strong> <?php echo htmlspecialchars($errorBD); ?>
            </div>
        <?php endif; ?>

        <!-- 🥩 TABLA EXCLUSIVA DE COMPROBANTES DE COMBOS PARA EL OPERADOR -->
        <?php if (!$esAdmin): ?>
        <div class="card-inicio" style="display: block; margin-top: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 class="tabla-titulo" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-file-invoice" style="color: #059669;"></i> Comprobantes de Recepción de Combos
                </h3>
                <span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">Impresión Directa</span>
            </div>

            <div style="overflow-x: auto;">
                <table class="tabla-movimientos">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Proveedor</th>
                            <th style="text-align: center;">Combos</th>
                            <th style="text-align: center;">Peso Báscula</th>
                            <th style="text-align: right;">Fecha y Hora</th>
                            <th style="text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($historialCombos)): ?>
                            <?php foreach ($historialCombos as $combo): 
                                $diferencia = floatval($combo['totalPeso']) - floatval($combo['peso_origen']);
                            ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($combo['folio']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($combo['nombreProveedor'] ?? 'General'); ?></td>
                                    <td style="text-align: center;">
                                        <span style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: bold;">
                                            <?php echo htmlspecialchars($combo['total_combos']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: bold; color: #0f172a;">
                                        <?php echo number_format(floatval($combo['totalPeso']), 2); ?> kg
                                    </td>
                                    <td style="text-align: right; color: #64748b; font-family: monospace;">
                                        <?php echo htmlspecialchars($combo['fecha_hora_registro']); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" onclick="imprimirComprobanteDirecto('<?php echo $combo['folio']; ?>', '<?php echo addslashes($combo['nombreProveedor'] ?? 'General'); ?>', '<?php echo number_format(floatval($combo['totalPeso']), 2); ?>', '<?php echo number_format(floatval($combo['peso_origen']), 2); ?>', '<?php echo number_format($diferencia, 2); ?>', '<?php echo htmlspecialchars($combo['total_combos']); ?>', '<?php echo htmlspecialchars($combo['fecha_hora_registro']); ?>')" style="background: #0284c7; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;" title="Imprimir Comprobante Directo">
                                            <i class="fa-solid fa-print"></i> Imprimir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 25px; color: #64748b;">No hay recepciones de combos registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            
            <div class="card-inicio" style="display: block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 class="tabla-titulo" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-users icon-blue"></i> Rendimiento del Personal (Hoy)
                    </h3>
                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">Actividad Diaria</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="tabla-movimientos">
                        <thead>
                            <tr>
                                <th>Usuario Responsable</th>
                                <th style="text-align: center;">Operaciones</th>
                                <th style="text-align: right;">Última Actividad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rendimientoUsuarios)): ?>
                                <?php foreach ($rendimientoUsuarios as $rend): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-user-circle" style="color: #475569; font-size: 18px;"></i>
                                                <strong><?php echo htmlspecialchars($rend['usuarioResponsable']); ?></strong>
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-weight: bold; color: #1e293b;">
                                                <?php echo htmlspecialchars($rend['total_movimientos']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; color: #64748b; font-family: monospace;"><?php echo htmlspecialchars($rend['ultima_hora']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center; padding: 25px; color: #64748b;">No hay actividad registrada el día de hoy.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-inicio" style="display: block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 class="tabla-titulo" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-chart-line icon-blue"></i> Flujo Operativo (Últimos 7 Días)
                    </h3>
                    <span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">Tendencia</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="tabla-movimientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo de Operación</th>
                                <th style="text-align: right;">Total Registros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($flujoSemanal)): ?>
                                <?php foreach ($flujoSemanal as $flujo): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($flujo['fecha']); ?></td>
                                        <td>
                                            <span class="badge-tipo <?php echo ($flujo['tipo'] === 'entrada') ? 'entrada' : (($flujo['tipo'] === 'salida') ? 'salida' : 'usuario'); ?>" style="text-transform: uppercase;">
                                                <?php echo htmlspecialchars($flujo['tipo']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo htmlspecialchars($flujo['total']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center; padding: 25px; color: #64748b;">No hay datos registrados en los últimos 7 días.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <?php endif; ?>

    </div>

    <!-- 🖨️ PLANTILLA OCULTA PARA IMPRESIÓN DIRECTA -->
    <div id="contenedorImpresionDirecta">
        <div style="display: flex; align-items: center; gap: 20px; border-bottom: 3px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px;">
            <img src="../SRC/Logo LFI - copia.png" alt="Logo LFI" style="width: 75px; height: 75px; object-fit: contain;">
            <div>
                <h1 style="margin: 0; font-size: 20px; color: #0f172a; font-weight: bold;">LFI - CONTROL OPERATIVO DE INVENTARIOS</h1>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #475569; font-weight: 600;">Comprobante de Recepción y Comparativa de Pesos (Combos de Pierna)</p>
            </div>
            <div style="margin-left: auto; text-align: right; font-size: 12px; color: #334155;">
                <strong>Fecha de Emisión:</strong><br><span id="printFechaActual" style="font-size: 14px; font-weight: bold; color: #0f172a;"></span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="border: 2px solid #0284c7; padding: 15px; border-radius: 8px; background: #f0f9ff;">
                <span style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase; display: block; margin-bottom: 5px;">Peso Total Registrado (Báscula)</span>
                <span id="printPesoBascula" style="font-size: 24px; font-weight: bold; color: #0f172a;">0.000 kg</span>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #475569;" id="printFolioInfo">Folio: #- | Combos Ingresados: -</p>
            </div>
            <div style="border: 2px solid #d97706; padding: 15px; border-radius: 8px; background: #fffbeb;">
                <span style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase; display: block; margin-bottom: 5px;">Peso que Manda el Proveedor (Origen)</span>
                <span id="printPesoOrigen" style="font-size: 24px; font-weight: bold; color: #0f172a;">0.000 kg</span>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #475569;">Según datos de origen</p>
            </div>
        </div>

        <div style="border: 2px solid #ef4444; padding: 15px; border-radius: 8px; background: #fef2f2; margin-bottom: 20px;">
            <span style="font-size: 11px; font-weight: 700; color: #dc2626; text-transform: uppercase; display: block; margin-bottom: 5px;">Diferencia Total de Kilos (Báscula - Origen)</span>
            <span id="printDiferencia" style="font-size: 22px; font-weight: bold; color: #991b1b;">0.000 kg</span>
        </div>

        <div style="border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; background: #f8fafc; margin-bottom: 30px;">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">Información del Movimiento</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; font-size: 13px; color: #334155; gap: 8px;">
                <div><strong>Proveedor:</strong> <span id="printProveedor">-</span></div>
                <div><strong>Fecha y Hora de Registro:</strong> <span id="printFechaRegistro">-</span></div>
                <div><strong>Almacén Destino:</strong> ID 1</div>
                <div><strong>Usuario Responsable:</strong> <?php echo htmlspecialchars($usuarioActual); ?></div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 10px;">
            <span>Documento generado por: <?php echo htmlspecialchars($usuarioActual); ?></span>
            <span>Sistema LFI - Control Operativo de Almacén</span>
        </div>
    </div>

    <?php if ($esAdmin): ?>
    <div class="modal" id="modalExportarExcel" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center;">
        <div class="modal-contenido" style="background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 350px; text-align: center; position: relative;">
            <span class="cerrar-modal" onclick="cerrarModalExportar()" style="position: absolute; top: 16px; right: 20px; font-size: 24px; color: #ef4444; cursor: pointer;">&times;</span>
            
            <i class="fa-solid fa-print" style="font-size: 42px; color: #0284c7; margin-bottom: 12px;"></i>
            <h2 style="font-size: 18px; font-weight: 700; color: #1f2f56; margin-bottom: 8px;">Opciones de Impresión</h2>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">Selecciona una opción:</p>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="button" onclick="ejecutarImpresionInicio('vista')" style="padding: 12px; background: #475569; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-desktop"></i> Imprimir: Vista actual
                </button>
                <button type="button" onclick="ejecutarImpresionInicio('todo')" style="padding: 12px; background: #1e293b; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-print"></i> Imprimir: Todo el reporte gerencial
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function abrirModalExportar() {
            const modal = document.getElementById('modalExportarExcel');
            if (modal) modal.style.display = 'flex';
        }

        function cerrarModalExportar() {
            const modal = document.getElementById('modalExportarExcel');
            if (modal) modal.style.display = 'none';
        }

        function ejecutarImpresionInicio(tipoOpcion) {
            cerrarModalExportar();
            const fechaHoy = new Date().toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
            
            const spanFecha = document.querySelector('.print-fecha');
            if (spanFecha) spanFecha.textContent = fechaHoy;

            window.print();
        }

        function imprimirComprobanteDirecto(folio, proveedor, pesoBascula, pesoOrigen, diferencia, combos, fechaRegistro) {
            const fechaHoy = new Date().toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });

            document.getElementById('printFechaActual').textContent = fechaHoy;
            document.getElementById('printPesoBascula').textContent = pesoBascula + ' kg';
            document.getElementById('printPesoOrigen').textContent = pesoOrigen + ' kg';
            document.getElementById('printDiferencia').textContent = diferencia + ' kg';
            document.getElementById('printFolioInfo').textContent = `Folio: #${folio} | Combos Ingresados: ${combos}`;
            document.getElementById('printProveedor').textContent = proveedor;
            document.getElementById('printFechaRegistro').textContent = fechaRegistro;

            window.print();
        }

        document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem('theme') === 'dark' || document.body.classList.contains('dark-mode')) {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
    <script src="../Services/tema.js"></script>
</body>
</html>