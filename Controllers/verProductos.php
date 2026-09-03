<?php

require_once __DIR__ . '/../Models/modeloVendedor.php';
$productos = new modeloVendedor();


$productoss = $productos->obtenerProductos();


echo "<pre>";
print_r($productoss);
echo "</pre>";


?>
