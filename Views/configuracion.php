<?php
session_start();

if (!isset($_SESSION['apodoUsuario'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../Services/configuracionServicio.php';

$service = new configuracionServicio();
$apodoActual = $_SESSION['apodoUsuario'];

// Consulta de datos del usuario logueado en la BD
$datosUsuario = $service->obtenerDatosUsuario($apodoActual);

if ($datosUsuario) {
    $nombreCompleto = trim(
        $datosUsuario['nombreUsuario'] . ' ' . 
        $datosUsuario['apellidoPaternoUsuario'] . ' ' . 
        $datosUsuario['apellidoMaternoUsuario']
    );
    $estadoTexto = ((int)$datosUsuario['estado'] === 1) ? 'Activo' : 'Inactivo';
} else {
    $nombreCompleto = $_SESSION['nombreUsuario'] ?? $apodoActual;
    $estadoTexto = 'Activo';
}

$ultimoAccesoRaw = $datosUsuario['ultimoAcceso'] ?? $_SESSION['ultimoAcceso'] ?? date('Y-m-d');
$fechaAcceso = date('d/m/Y', strtotime($ultimoAccesoRaw));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - LFI</title>
    <link rel="icon" type="image/png" href="../SRC/Logo LFI - copia.png">
    
    <!-- Hojas de estilo del proyecto -->
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    <link rel="stylesheet" href="../Views/css/usuarios.css">
    <link rel="stylesheet" href="../Views/css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/configuracion.css">
    
    <!-- FontAwesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'assets/barraNavegacion.php'; ?>

    <main class="config-container">
        <div class="cards-layout">
            
            <!-- Tarjeta: Datos de la Empresa -->

            <!-- Tarjeta: Información del Usuario -->
            <div class="user-card">
                <div class="user-card-header">
                    <h3>Informacion del usuairo</h3>
                </div>
                <div class="user-card-body">
                    <div class="avatar-box">
                        <i class="fa-solid fa-circle-user"></i>
                    </div>
                    <div class="user-details">
                        <p><strong>Usuario:</strong> <span class="detail-val"><?= htmlspecialchars($apodoActual) ?></span></p>
                        <p><strong>Nombre:</strong> <span class="detail-val"><?= htmlspecialchars($nombreCompleto) ?></span></p>
                        <p>
                            <strong>Estado:</strong> 
                            <span class="badge-active">
                                <i class="fa-solid fa-circle"></i> <?= htmlspecialchars($estadoTexto) ?>
                            </span>
                        </p>
                        <p><strong>Ultimo Acceso:</strong> <span class="detail-val"><?= htmlspecialchars($fechaAcceso) ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Tarjeta: Información del Sistema -->
            <div class="system-card">
                <div class="system-card-header">
                    <i class="fa-solid fa-circle-info system-icon"></i>
                    <h3>Información del sistema</h3>
                </div>
                
                <div class="system-card-body">
                    <div class="system-item">
                        <span class="system-label">Versión del sistema</span>
                        <span class="system-value">1.0.0</span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Fecha de instalación</span>
                        <span class="system-value">01/05/2026</span>
                    </div>
                    
                    
                    <div class="system-item">
                        <span class="system-label">Estado del sistema</span>
                        <span class="status-badge-optimo">Óptimo</span>
                    </div>
                    
                </div>
            </div>


            <div class="theme-card">
                <div class="theme-card-header">
                    <div class="icon-container-purple">
                        <i class="fa-solid fa-moon"></i>
                    </div>
                    <h3>Apariencia del sistema</h3>
                </div>

                <div class="theme-card-body">
                    <div class="theme-option">
                        <div class="theme-info">
                            <span class="theme-title">Modo Oscuro</span>
                            <span class="theme-desc">Ajusta la interfaz para reducir la fatiga visual.</span>
                        </div>
                        
                        <!-- Botón Toggle Switch -->
                        <label class="switch">
                            <input type="checkbox" id="btnDarkMode">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>



        </div>
    </main>

    <script src="../Services/tema.js"></script>
</body>

</html>