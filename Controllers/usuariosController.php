<?php

session_start();

require_once __DIR__ . '/../Services/usuariosServicio.php';
require_once __DIR__ . '/../Models/modeloUsuarios.php';

$service = new usuariosServicio();

$action = $_GET['action'] ?? '';

switch ($action) {

    /* =========================
       CREAR USUARIO
    ========================= */

    case 'create':

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [

                'apodoUsuario' =>
                    $_POST['apodoUsuario'],

                'nombreUsuario' =>
                    $_POST['nombreUsuario'],

                'apellidoPaternoUsuario' =>
                    $_POST['apellidoPaternoUsuario'],

                'apellidoMaternoUsuario' =>
                    $_POST['apellidoMaternoUsuario'],

                'contrasena' =>
                    $_POST['contrasena'],

                'estado' =>
                    $_POST['estado'],

                'idRol' =>
                    $_POST['idRol']
            ];

            $service->create($data);
        }

        header('Location: ../Views/principal.php');

        exit;

    /* =========================
       ACTUALIZAR USUARIO
    ========================= */

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 🔧 Corrección: ahora se usa idUsuario
            $id = (int) $_POST['idUsuario'];

            $data = [
                'apodoUsuario' => $_POST['apodoUsuario'],
                'nombreUsuario' => $_POST['nombreUsuario'],
                'apellidoPaternoUsuario' => $_POST['apellidoPaternoUsuario'],
                'apellidoMaternoUsuario' => $_POST['apellidoMaternoUsuario'],
                'estado' => $_POST['estado'],
                'idRol' => $_POST['idRol'],
                // opcional: contraseña si quieres actualizarla
                'contrasena' => $_POST['contrasena'] ?? ''
            ];

            $service->update($id, $data);
        }
        header('Location: ../Views/usuarios.php');

        

    exit;

    /* =========================
       ELIMINAR USUARIO
    ========================= */

    case 'delete':

        $id = (int) $_GET['id'];

        $service->delete($id);

        header('Location: ../Views/usuarios.php');

        exit;

    /* =========================
       BUSQUEDA AJAX
    ========================= */

    case 'busqueda':

        $textoBusqueda =
            $_GET['busqueda'] ?? '';

        $estado =
            $_GET['estado'] ?? '';

        $listaUsuarios =
            $service->getUsers(
                $textoBusqueda,
                $estado
            );

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode($listaUsuarios);

        exit;

    /* =========================
       ACCION INVALIDA
    ========================= */

    default:

        header('Location: ../Views/principal.php');

        exit;
}




?>