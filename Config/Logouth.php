<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../Services/AuthService.php';

$auth = new AuthService();

$auth->logout();

header("Location: ../Views/login.php");

exit;