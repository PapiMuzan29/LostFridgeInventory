<?php
// piernas.php
$fechaActual = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$comboActual = isset($_GET['combo']) ? $_GET['combo'] : 'todos';
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
    
</head>
<body>

    <header class="piernas-header">
        <div class="piernas-top-bar">
            <button type="button" class="btn-back" onclick="volverPantallaAnterior()">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
            <span class="module-title">MÓDULO PIERNAS</span>
        </div>

        <div class="date-picker-trigger">
            <i class="far fa-calendar-alt"></i>
            <span id="fecha-piernas-display"><?php echo htmlspecialchars($fechaActual); ?></span>
            <input type="date" id="fecha-piernas-input" value="<?php echo htmlspecialchars($fechaActual); ?>" onchange="actualizarFechaPiernas(this.value)">
        </div>

        <div class="combo-selector-wrapper">
            <label for="combo-select">Combo:</label>
            <select id="combo-select" class="combo-select" onchange="cambiarComboPiernas(this.value)">
                <option value="todos" <?php echo $comboActual === 'todos' ? 'selected' : ''; ?>>Todos los combos</option>
                <option value="combo1" <?php echo $comboActual === 'combo1' ? 'selected' : ''; ?>>Combo 1</option>
                <option value="combo2" <?php echo $comboActual === 'combo2' ? 'selected' : ''; ?>>Combo 2</option>
            </select>
            <i class="fas fa-chevron-down combo-dropdown-icon"></i>
        </div>

        <nav class="sub-tabs-container">
            <button type="button" class="sub-tab-btn active" onclick="desplazarAPestania(0)">Registros</button>
            <button type="button" class="sub-tab-btn" onclick="desplazarAPestania(1)">Resumen</button>
        </nav>
    </header>

    <div class="piernas-viewport" id="piernas-viewport">
        <div class="piernas-slider" id="piernas-slider">
            
            <!-- Pestaña 0: Registros -->
            <div class="subtab-pane" id="pane-registros">
                <p class="section-subtitle">DETALLE DE ENTREGAS</p>
                <div id="lista-registros-piernas" class="records-list">
                    <div style="text-align:center; padding: 30px; color: #64748b;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top:12px; font-weight: 600;">Cargando registros...</p>
                    </div>
                </div>
            </div>

            <!-- Pestaña 1: Resumen -->
            <div class="subtab-pane" id="pane-resumen">
                <p class="section-subtitle">TOTALES ACUMULADOS</p>
                <div id="contenido-resumen-piernas">
                    <div style="text-align:center; padding: 30px; color: #64748b;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top:12px; font-weight: 600;">Cargando resumen...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="combo-total-bar" class="combo-total-bar">
        <span class="total-label">TOTAL DEL COMBO</span>
        <span class="total-values" id="texto-totales-piernas">0 Cajas / 0.00 Kg</span>
    </div>

    <script>
        const slider = document.getElementById('piernas-slider');
        const totalBar = document.getElementById('combo-total-bar');
        const tabBtns = document.querySelectorAll('.sub-tab-btn');

        let pestaniaActual = 0;
        let startX = 0;
        let startY = 0;

        function desplazarAPestania(index) {
            pestaniaActual = index;
            if (slider) {
                slider.style.transform = `translateX(-${index * 50}%)`;
            }

            tabBtns.forEach((btn, i) => {
                btn.classList.toggle('active', i === index);
            });

            if (totalBar) {
                if (index === 0) {
                    totalBar.classList.remove('hidden');
                } else {
                    totalBar.classList.add('hidden');
                }
            }
        }

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (!startX || !startY) return;

            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;

            const diffX = startX - endX;
            const diffY = startY - endY;

            if (Math.abs(diffX) > Math.abs(diffY)) {
                if (Math.abs(diffX) > 40) {
                    if (diffX > 0 && pestaniaActual === 0) {
                        desplazarAPestania(1);
                    } else if (diffX < 0 && pestaniaActual === 1) {
                        desplazarAPestania(0);
                    }
                }
            }

            startX = 0;
            startY = 0;
        }, { passive: true });

        function volverPantallaAnterior() {
            if (document.referrer) {
                window.location.href = document.referrer;
            } else {
                window.history.back();
            }
        }

        function actualizarFechaPiernas(fecha) {
            const display = document.getElementById('fecha-piernas-display');
            if (display) display.innerText = fecha;
        }

        function cambiarComboPiernas(combo) {
            // Lógica AJAX
        }
    </script>
</body>
</html>