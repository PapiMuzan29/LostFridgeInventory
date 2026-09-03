<?php
class modeloManteca {
    private BD $db;

    public function __construct(BD $db) {
        $this->db = $db;
    }

    public function obtenerVentasMantecaPorFecha(string $fecha): array {
        $sql = "SELECT 
                    p.nombreProducto,
                    dn.piezas,
                    dn.kilos,
                    n.nombre_cliente,
                    GROUP_CONCAT(DISTINCT ft.folio_ticket SEPARATOR ', ') AS tickets
                FROM detalle_notas dn
                INNER JOIN notas n ON dn.id_nota = n.id_nota
                INNER JOIN producto p ON dn.idProducto = p.idProducto
                LEFT JOIN folios_tickets ft ON n.id_nota = ft.id_nota
                WHERE p.nombreProducto LIKE '%Manteca%' 
                  AND DATE(n.fecha_creacion) = :fecha
                GROUP BY dn.id_detalle, p.nombreProducto, n.nombre_cliente";

        $resultados = $this->db->select($sql, ['fecha' => $fecha]);

        $respuesta = [
            10 => ['piezas' => 0, 'kilos' => 0.0, 'ventas' => []],
            15 => ['piezas' => 0, 'kilos' => 0.0, 'ventas' => []],
            18 => ['piezas' => 0, 'kilos' => 0.0, 'ventas' => []],
            'total_general_piezas' => 0,
            'total_general_kilos' => 0.0
        ];

        foreach ($resultados as $fila) {
            $nombre = $fila['nombreProducto'];
            $piezas = (int)$fila['piezas'];
            $kilos = (float)$fila['kilos'];

            $presentacion = null;
            if (strpos($nombre, '10') !== false) {
                $presentacion = 10;
            } elseif (strpos($nombre, '15') !== false) {
                $presentacion = 15;
            } elseif (strpos($nombre, '18') !== false) {
                $presentacion = 18;
            }

            if ($presentacion) {
                $respuesta[$presentacion]['piezas'] += $piezas;
                $respuesta[$presentacion]['kilos'] += $kilos;
                $respuesta[$presentacion]['ventas'][] = [
                    'cliente' => $fila['nombre_cliente'] ?? 'Sin cliente',
                    'ticket'  => !empty($fila['tickets']) ? $fila['tickets'] : 'S/T',
                    'piezas'  => $piezas,
                    'kilos'   => $kilos
                ];
            }

            $respuesta['total_general_piezas'] += $piezas;
            $respuesta['total_general_kilos'] += $kilos;
        }

        return $respuesta;
    }
}
?>