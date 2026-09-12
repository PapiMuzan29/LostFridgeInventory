<?php
require_once __DIR__ . '/../Config/BD.php';

class modeloSalidasPendientes {
    private $db;

    public function __construct() {
        $this->db = new BD();
    }

    // Registrar un producto enviado a pendientes de venta
    public function enviarAPendientes($idInventario, $codigo, $descripcion, $cantidad, $cliente, $usuario) {
        $query = "INSERT INTO salidas_ventas_pendientes (id_inventario, codigo_producto, descripcion_producto, cantidad, cliente_destino, usuario_envia, status, fecha_envio) 
                  VALUES (?, ?, ?, ?, ?, ?, 'preparado', NOW())";
        
        $params = [$idInventario, $codigo, $descripcion, $cantidad, $cliente, $usuario];
        
        try {
            $this->db->consulta($query, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar a salidas pendientes: " . $e->getMessage());
            return false;
        }
    }

    // Listar todos los pendientes activos para que el otro perfil los consulte
    public function obtenerPendientes() {
        $query = "SELECT * FROM salidas_ventas_pendientes WHERE status = 'preparado' ORDER BY fecha_envio DESC";
        $stmt = $this->db->consulta($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Cancelar/Regresar al inventario principal
    public function cancelarPendiente($idPendiente) {
        $query = "UPDATE salidas_ventas_pendientes SET status = 'cancelado' WHERE id_salida_pendiente = ?";
        try {
            $this->db->consulta($query, [$idPendiente]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}