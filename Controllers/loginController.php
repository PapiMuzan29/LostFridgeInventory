<?php

session_start();
require_once __DIR__ . '/../Services/AuthService.php';

$auth = new AuthService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['user'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($usuario && $password) {
        if ($auth->login($usuario, $password)) {
            // Login correcto → redirige al dashboard
            header("Location: ../Views/principal.php");
            exit;
        } else {
            // Login incorrecto → regresa al login con mensaje
            header("Location: ../Views/login.php?error=1");
            exit;
        }
    } else {
        // Si no se mandaron datos
        header("Location: ../Views/login.php?error=2");
        exit;
    }
}

?>