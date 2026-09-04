<?php
if (ob_get_length()) ob_clean();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/BD.php';

if (!isset($_SESSION['idCuenta'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no activa']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$idUsuarioActual = (int)$_SESSION['idCuenta'];

try {
    $bd = BD::obtenerInstancia();

    switch ($action) {
        case 'obtenerUsuarios':
            // Obtiene solo usuarios con conversación activa (mensajes enviados o recibidos)
            $sql = "SELECT DISTINCT u.idCuenta, u.apodoUsuario, u.nombreUsuario,
                           (SELECT COUNT(*) 
                            FROM chat_mensajes cm 
                            WHERE cm.idCuenta = u.idCuenta 
                              AND cm.idCuentaDestino = ? 
                              AND cm.estatus = 'Sin leer') AS noLeidos
                    FROM cuenta u 
                    INNER JOIN chat_mensajes m 
                        ON (m.idCuenta = u.idCuenta AND m.idCuentaDestino = ?) 
                        OR (m.idCuentaDestino = u.idCuenta AND m.idCuenta = ?)
                    WHERE u.idCuenta != ? AND u.estado = 1 
                    ORDER BY u.apodoUsuario ASC";
            
            $usuarios = $bd->select($sql, [$idUsuarioActual, $idUsuarioActual, $idUsuarioActual, $idUsuarioActual]) ?? [];
            echo json_encode(['success' => true, 'usuarios' => $usuarios]);
            break;

        case 'obtenerTotalNoLeidos':
            $sql = "SELECT COUNT(*) as total 
                    FROM chat_mensajes 
                    WHERE idCuentaDestino = ? AND estatus = 'Sin leer'";
            $res = $bd->select($sql, [$idUsuarioActual]);
            $total = (int)($res[0]['total'] ?? 0);
            echo json_encode(['success' => true, 'total' => $total]);
            break;

        case 'obtenerMensajes':
            $ultimoId = (int)($_GET['ultimo_id'] ?? 0);
            $idDestino = (int)($_GET['idDestino'] ?? 0);

            if ($idDestino <= 0) {
                echo json_encode(['success' => true, 'mensajes' => [], 'noLeidos' => 0]);
                exit;
            }

            $sql = "SELECT c.idChatMensaje, c.idCuenta, c.idCuentaDestino, c.mensaje, c.fechaRegistro, u.apodoUsuario 
                    FROM chat_mensajes c
                    INNER JOIN cuenta u ON c.idCuenta = u.idCuenta
                    WHERE c.idChatMensaje > ? 
                      AND ((c.idCuenta = ? AND c.idCuentaDestino = ?) OR (c.idCuenta = ? AND c.idCuentaDestino = ?))
                    ORDER BY c.idChatMensaje ASC";

            $mensajes = $bd->select($sql, [$ultimoId, $idUsuarioActual, $idDestino, $idDestino, $idUsuarioActual]) ?? [];

            foreach ($mensajes as &$msg) {
                $msg['mensaje'] = htmlspecialchars($msg['mensaje'], ENT_QUOTES, 'UTF-8');
                $msg['apodoUsuario'] = htmlspecialchars($msg['apodoUsuario'], ENT_QUOTES, 'UTF-8');
            }

            $sqlNoLeidos = "SELECT COUNT(*) as total FROM chat_mensajes 
                            WHERE idCuenta = ? AND idCuentaDestino = ? AND estatus = 'Sin leer'";
            $resNoLeidos = $bd->select($sqlNoLeidos, [$idDestino, $idUsuarioActual]);
            $noLeidos = (int)($resNoLeidos[0]['total'] ?? 0);

            echo json_encode([
                'success' => true,
                'mensajes' => $mensajes,
                'noLeidos' => $noLeidos,
                'idCuentaActual' => $idUsuarioActual
            ]);
            break;

        case 'enviarMensaje':
            $mensaje = trim($_POST['mensaje'] ?? '');
            $idDestino = (int)($_POST['idDestino'] ?? 0);

            if (empty($mensaje) || $idDestino <= 0) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }

            $sql = "INSERT INTO chat_mensajes (idCuenta, idCuentaDestino, mensaje, fechaRegistro, estatus) VALUES (?, ?, ?, NOW(), 'Sin leer')";
            $bd->insert($sql, [$idUsuarioActual, $idDestino, $mensaje]);

            echo json_encode(['success' => true]);
            break;

        case 'marcarComoLeidos':
            $idDestino = (int)($_POST['idDestino'] ?? 0);
            if ($idDestino > 0) {
                $sqlMarcar = "UPDATE chat_mensajes SET estatus = 'Leido' WHERE idCuenta = ? AND idCuentaDestino = ? AND estatus = 'Sin leer'";
                $bd->insert($sqlMarcar, [$idDestino, $idUsuarioActual]);
            }
            echo json_encode(['success' => true]);
            break;
        
        case 'obtenerTodosUsuarios':
            // Trae TODOS los usuarios activos excepto el usuario actual
            $sql = "SELECT u.idCuenta, u.apodoUsuario, u.nombreUsuario 
                    FROM cuenta u 
                    WHERE u.idCuenta != ? AND u.estado = 1 
                    ORDER BY u.apodoUsuario ASC";
            $usuarios = $bd->select($sql, [$idUsuarioActual]) ?? [];
            echo json_encode(['success' => true, 'usuarios' => $usuarios]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }

        // Agrega este caso dentro del switch en ChatController.php


} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;