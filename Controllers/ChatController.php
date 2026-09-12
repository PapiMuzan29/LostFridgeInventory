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
            // 1. Obtiene chats individuales activos con sus no leídos
            $sqlUsuarios = "SELECT DISTINCT u.idCuenta, u.apodoUsuario, u.nombreUsuario, 'individual' AS tipo,
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
            
            $usuarios = $bd->select($sqlUsuarios, [$idUsuarioActual, $idUsuarioActual, $idUsuarioActual, $idUsuarioActual]) ?? [];

            // 2. Obtiene los grupos con el conteo de mensajes sin leer
            $sqlGrupos = "SELECT g.idGrupo AS idCuenta, g.nombreGrupo AS apodoUsuario, 'grupo' AS tipo,
                                 (SELECT COUNT(*) 
                                  FROM chat_mensajes cm 
                                  WHERE cm.idGrupo = g.idGrupo 
                                    AND cm.idCuenta != ? 
                                    AND cm.estatus = 'Sin leer') AS noLeidos
                          FROM chat_grupos g
                          INNER JOIN chat_grupo_miembros gm ON g.idGrupo = gm.idGrupo
                          WHERE gm.idCuenta = ?
                          ORDER BY g.nombreGrupo ASC";
            
            $grupos = $bd->select($sqlGrupos, [$idUsuarioActual, $idUsuarioActual]) ?? [];

            // 3. Mezcla y prioriza primero las conversaciones que tienen notificaciones (noLeidos > 0)
            $listaCompleta = array_merge($grupos, $usuarios);
            usort($listaCompleta, function($a, $b) {
                return $b['noLeidos'] <=> $a['noLeidos'];
            });

            echo json_encode(['success' => true, 'usuarios' => $listaCompleta]);
            break;

        case 'obtenerTotalNoLeidos':
            // Suma no leídos de chats individuales
            $sqlInd = "SELECT COUNT(*) as total 
                       FROM chat_mensajes 
                       WHERE idCuentaDestino = ? AND estatus = 'Sin leer'";
            $resInd = $bd->select($sqlInd, [$idUsuarioActual]);
            $totalInd = (int)($resInd[0]['total'] ?? 0);

            // Suma no leídos de los grupos a los que pertenece el usuario (excluyendo sus propios mensajes)
            $sqlGrup = "SELECT COUNT(*) as total
                        FROM chat_mensajes cm
                        INNER JOIN chat_grupo_miembros gm ON cm.idGrupo = gm.idGrupo
                        WHERE gm.idCuenta = ? 
                          AND cm.idCuenta != ? 
                          AND cm.estatus = 'Sin leer'";
            $resGrup = $bd->select($sqlGrup, [$idUsuarioActual, $idUsuarioActual]);
            $totalGrup = (int)($resGrup[0]['total'] ?? 0);

            echo json_encode(['success' => true, 'total' => ($totalInd + $totalGrup)]);
            break;

        case 'obtenerMensajes':
            $ultimoId = (int)($_GET['ultimo_id'] ?? 0);
            $idDestino = (int)($_GET['idDestino'] ?? 0);
            $esGrupo = isset($_GET['esGrupo']) && $_GET['esGrupo'] === 'true';

            if ($idDestino <= 0) {
                echo json_encode(['success' => true, 'mensajes' => [], 'noLeidos' => 0]);
                exit;
            }

            if ($esGrupo) {
                // Obtener mensajes del grupo
                $sql = "SELECT c.idChatMensaje, c.idCuenta, c.idGrupo, c.mensaje, c.fechaRegistro, u.apodoUsuario 
                        FROM chat_mensajes c
                        INNER JOIN cuenta u ON c.idCuenta = u.idCuenta
                        WHERE c.idChatMensaje > ? AND c.idGrupo = ?
                        ORDER BY c.idChatMensaje ASC";
                $mensajes = $bd->select($sql, [$ultimoId, $idDestino]) ?? [];
                
                // Si la ventana está abierta, marcar mensajes del grupo como leídos automáticamente
                $sqlMarcarGrupo = "UPDATE chat_mensajes SET estatus = 'Leido' WHERE idGrupo = ? AND idCuenta != ? AND estatus = 'Sin leer'";
                $bd->insert($sqlMarcarGrupo, [$idDestino, $idUsuarioActual]);

                $noLeidos = 0;
            } else {
                // Obtener mensajes del chat individual
                $sql = "SELECT c.idChatMensaje, c.idCuenta, c.idCuentaDestino, c.mensaje, c.fechaRegistro, u.apodoUsuario 
                        FROM chat_mensajes c
                        INNER JOIN cuenta u ON c.idCuenta = u.idCuenta
                        WHERE c.idChatMensaje > ? 
                          AND ((c.idCuenta = ? AND c.idCuentaDestino = ?) OR (c.idCuenta = ? AND c.idCuentaDestino = ?))
                        ORDER BY c.idChatMensaje ASC";

                $mensajes = $bd->select($sql, [$ultimoId, $idUsuarioActual, $idDestino, $idDestino, $idUsuarioActual]) ?? [];

                $sqlNoLeidos = "SELECT COUNT(*) as total FROM chat_mensajes 
                                WHERE idCuenta = ? AND idCuentaDestino = ? AND estatus = 'Sin leer'";
                $resNoLeidos = $bd->select($sqlNoLeidos, [$idDestino, $idUsuarioActual]);
                $noLeidos = (int)($resNoLeidos[0]['total'] ?? 0);
            }

            foreach ($mensajes as &$msg) {
                $msg['mensaje'] = htmlspecialchars($msg['mensaje'], ENT_QUOTES, 'UTF-8');
                $msg['apodoUsuario'] = htmlspecialchars($msg['apodoUsuario'], ENT_QUOTES, 'UTF-8');
            }

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
            $esGrupo = isset($_POST['esGrupo']) && $_POST['esGrupo'] === 'true';

            if (empty($mensaje) || $idDestino <= 0) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }

            if ($esGrupo) {
                // Inserta mensaje para un grupo en estatus 'Sin leer' para notificar a los miembros
                $sql = "INSERT INTO chat_mensajes (idCuenta, idGrupo, mensaje, fechaRegistro, estatus) VALUES (?, ?, ?, NOW(), 'Sin leer')";
                $bd->insert($sql, [$idUsuarioActual, $idDestino, $mensaje]);
            } else {
                // Inserta mensaje individual
                $sql = "INSERT INTO chat_mensajes (idCuenta, idCuentaDestino, mensaje, fechaRegistro, estatus) VALUES (?, ?, ?, NOW(), 'Sin leer')";
                $bd->insert($sql, [$idUsuarioActual, $idDestino, $mensaje]);
            }

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
            $sql = "SELECT u.idCuenta, u.apodoUsuario, u.nombreUsuario 
                    FROM cuenta u 
                    WHERE u.idCuenta != ? AND u.estado = 1 
                    ORDER BY u.apodoUsuario ASC";
            $usuarios = $bd->select($sql, [$idUsuarioActual]) ?? [];
            echo json_encode(['success' => true, 'usuarios' => $usuarios]);
            break;

        case 'crearGrupo':
            $nombreGrupo = trim($_POST['nombreGrupo'] ?? '');
            $miembros = isset($_POST['miembros']) ? json_decode($_POST['miembros'], true) : [];

            if (empty($nombreGrupo) || empty($miembros) || !is_array($miembros)) {
                echo json_encode(['success' => false, 'message' => 'Se requiere el nombre del grupo y al menos un integrante.']);
                exit;
            }

            // 1. Crear el grupo con fechaCreacion
            $sqlGrupo = "INSERT INTO chat_grupos (nombreGrupo, idCreador, fechaCreacion) VALUES (?, ?, NOW())";
            $idGrupo = $bd->insert($sqlGrupo, [$nombreGrupo, $idUsuarioActual]);

            if ($idGrupo) {
                // 2. Incluir al creador en la lista de miembros
                $miembros[] = $idUsuarioActual;
                $miembros = array_unique($miembros);

                // 3. Registrar integrantes con fechaUnido
                $sqlMiembro = "INSERT INTO chat_grupo_miembros (idGrupo, idCuenta, fechaUnido) VALUES (?, ?, NOW())";
                foreach ($miembros as $idMiembro) {
                    $bd->insert($sqlMiembro, [$idGrupo, (int)$idMiembro]);
                }

                echo json_encode(['success' => true, 'idGrupo' => $idGrupo]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el grupo']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;