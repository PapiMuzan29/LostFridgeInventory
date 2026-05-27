<div class="header-seccion">
    <button class="btn-nuevo">Nueva Entrada</button>
</div>

<?php

$entradas_recientes = [
    ['id' => 101, 'producto' => 'Corte Ribeye', 'cantidad' => '50 kg', 'fecha' => date('Y-m-d')],
    ['id' => 102, 'producto' => 'Pierna de Cerdo', 'cantidad' => '120 kg', 'fecha' => date('Y-m-d')]
];
?>

<table class="tabla-inventario">
    <thead>
        <tr>
            <th>ID</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($entradas_recientes as $entrada): ?>
            <tr>
                <td><?php echo $entrada['id']; ?></td>
                <td><?php echo $entrada['producto']; ?></td>
                <td><?php echo $entrada['cantidad']; ?></td>
                <td><?php echo $entrada['fecha']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>