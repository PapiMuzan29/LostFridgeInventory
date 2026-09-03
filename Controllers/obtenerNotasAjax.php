<?php
session_start();
require_once __DIR__ . '/../Models/modeloCajero.php';

$modelo = new modeloCajero();

// 1. Atrapamos lo que el usuario escribió en el buscador
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

// 2. Atrapamos el tipo de nota que nos están pidiendo (por defecto será 'pendientes')
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'pendientes';

// 3. Dependiendo del tipo, pedimos los datos al modelo y configuramos las etiquetas
if ($tipo === 'historial') {
    // Si tu modelo tiene una función específica para las cobradas, llámala aquí. 
    // (Asegúrate de tener esta función en tu modelo, o usa una general pasando el estado)
    $notas = $modelo->obtenerNotasCobradas($busqueda); 
    $textoBadge = "COBRADO";
    $claseBadge = "badge-success"; // Asumiendo que tienes esta clase CSS para color verde
} else {
    // El flujo normal del cajero
    $notas = $modelo->obtenerNotasPendientes($busqueda);
    $textoBadge = "PENDIENTE";
    $claseBadge = "badge-pending"; // Tu clase CSS naranja/roja
}

// 4. Empezamos a pintar el HTML
if (empty($notas)): ?>
    <section class="card empty-state">
        <?php if ($busqueda !== ''): ?>
            <i class="fa-solid fa-search empty-state-icon" style="color: #64748b;"></i>
            <p style="margin: 0; font-weight: bold;">No se encontraron notas con: "<?= htmlspecialchars($busqueda) ?>"</p>
        <?php else: ?>
            <i class="fa-solid fa-check-circle empty-state-icon" style="color: #16a34a;"></i>
            <p style="margin: 0; font-weight: bold;">
                <?= ($tipo === 'historial') ? 'No hay notas cobradas aún.' : 'No hay notas pendientes por cobrar.' ?>
            </p>
        <?php endif; ?>
    </section>
<?php else: 
    foreach ($notas as $nota): ?>
        <section class="card nota-card" data-id="<?= (int)$nota['id_nota'] ?>">
            <div class="nota-header">
                <div>
                    <strong class="nota-title">Nota #<?= htmlspecialchars((string)$nota['folio']) ?></strong>
                    <small class="nota-date"><?= date('Y-m-d h:i A', strtotime($nota['fecha'])) ?></small>
                </div>
                <!-- Imprimimos el badge dinámico (Pendiente o Cobrado) -->
                <span class="<?= $claseBadge ?>"><?= $textoBadge ?></span>
            </div>

            <p class="nota-info"><strong><i class="fa-solid fa-user"></i> Cliente:</strong> <?= htmlspecialchars((string)$nota['cliente']) ?></p>
            <p class="nota-info"><strong><i class="fa-solid fa-user-tag"></i> Vendedor:</strong> <?= htmlspecialchars((string)$nota['vendedor']) ?></p>

            <div class="detalles-list">
                <strong><i class="fa-solid fa-cubes"></i> Productos Solicitados:</strong><br>
                <?php if (!empty($nota['productos'])): ?>
                    <?php foreach ($nota['productos'] as $prod): ?>
                        • <?= number_format((float)$prod['kilos'], 2) ?> kg - <?= htmlspecialchars((string)$prod['nombre']) ?> (<?= (int)$prod['piezas'] ?> pzs)<br>
                    <?php endforeach; ?>
                <?php else: ?>
                    • <em>Sin productos detallados</em><br>
                <?php endif; ?>

                <?php if (!empty($nota['estibadores'])): ?>
                    <strong style="display:inline-block; margin-top:8px;"><i class="fa-solid fa-people-carry-box"></i> Estibadores:</strong><br>
                    <?php foreach ($nota['estibadores'] as $est): ?>
                        • <?= htmlspecialchars((string)$est['nombre']) ?><br>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Mostrar el botón de cobrar SOLO si estamos en la pestaña de pendientes -->
            <?php if ($tipo !== 'historial'): ?>
                <form action="../Controllers/cajeroController.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Confirmar el cobro de la Nota <?= htmlspecialchars((string)$nota['folio']) ?>?');">
                    <input type="hidden" name="accion" value="pagar">
                    <input type="hidden" name="id_nota" value="<?= (int)$nota['id_nota'] ?>">
                    <button type="submit" class="btn-success">
                        <i class="fa-solid fa-cash-register"></i> Procesar / Cobrar Nota
                    </button>
                </form>
            <?php else: ?>
                <!-- Si es historial, podemos poner un pequeño indicador o dejarlo sin botón -->
                <div style="text-align: right; margin-top: 10px;">
                    <span style="color: #16a34a; font-weight: bold; font-size: 0.9rem;">
                        <i class="fa-solid fa-check-double"></i> Ticket Procesado
                    </span>
                </div>
            <?php endif; ?>
            
        </section>
    <?php endforeach;
endif;