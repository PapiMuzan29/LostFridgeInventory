<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Models/modeloVendedor.php';

$auth = new AuthService();

// Validar sesión
if (!$auth->isAuthenticated()) {
    header('Location: ../Views/login.php');
    exit;
}

// Cargar catálogos desde modeloVendedor
$productos = modeloVendedor::obtenerProductos();
$estibadores = modeloVendedor::obtenerEstibadores();

// Renderizar la vista
require_once __DIR__ . '/../Views/notasSystem/vendedor.php';