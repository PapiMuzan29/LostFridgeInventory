<?php

session_start();

if (!isset($_SESSION['apodoUsuario'])) {

    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/barraNavegacion.css">
    <link rel="stylesheet" href="../Views/css/cerdito.css">
    
</head>
<body>
    <?php
    include 'assets/barraNavegacion.php';
    ?>
    

</body>
</html>

