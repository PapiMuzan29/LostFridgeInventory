<?php

require_once __DIR__ . '/../Services/usuariosServicio.php';

$service = new usuariosServicio();

$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';

$usuarios = $service->getUsers($busqueda, $estado);

?>

<tbody id="tablaUsuariosBody">

<?php foreach ($usuarios as $usuario): ?>

    <tr>

        <td class="usuarioCell">

            <i class="fa-solid fa-circle-user"></i>

            <?= $usuario['apodoUsuario'] ?>

        </td>

        <td>

            <?= 
                $usuario['nombreUsuario'] . ' ' .
                $usuario['apellidoPaternoUsuario'] . ' ' .
                $usuario['apellidoMaternoUsuario']
            ?>

        </td>

        <td>

            <span class="estado <?= $usuario['estado'] == 1 ? 'activo' : 'inactivo' ?>">

                <?= $usuario['estado'] == 1 ? 'Activo' : 'Inactivo' ?>

            </span>

        </td>

        <td>

            <?= $usuario['nombreRol'] ?>

        </td>

        <td class="acciones">

            <a href="#" class="editar">

                <i class="fa-solid fa-pen"></i>

            </a>

            <a 
                href="../Controllers/usuariosController.php?action=delete&id=<?= $usuario['idCuenta'] ?>"
                class="eliminar"
            >

                <i class="fa-solid fa-trash"></i>

            </a>

        </td>

    </tr>

<?php endforeach; ?>

</tbody>