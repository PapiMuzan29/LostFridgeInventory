<?php
class modeloPecho {
    private BD $db;

    public function __construct(BD $db) {
        $this->db = $db;
    }

    public function obtenerVentasPechoPorFecha(string $fecha): array {
        $sql = "SELECT 
                    p.nombreProducto,
                    dn.piezas,
                    dn.kilos,
                    n.nombre_cliente,
                    GROUP_CONCAT(DISTINCT ft.folio_ticket SEPARATOR ', ') AS tickets
                FROM detalle_notas dn
                INNER JOIN notas n ON dn.id_nota = n.id_nota
                INNER JOIN producto p ON dn.idProducto = p.idProducto
                INNER JOIN folios_tickets ft ON n.id_nota = ft.id_nota
                WHERE p.nombreProducto LIKE '%Pecho%' 
                  AND DATE(n.fecha_creacion) = :fecha
                GROUP BY dn.id_detalle, p.nombreProducto, n.nombre_cliente";

        $resultados = $this->db->select($sql, ['fecha' => $fecha]);

        $respuesta = [
            'ventas' => [],
            'total_suelto_piezas'  => 0,
            'total_suelto_kilos'   => 0.0,
            'total_caja_piezas'    => 0,
            'total_caja_kilos'     => 0.0,
            'total_general_piezas' => 0, // Lo mantenemos por si el código viejo lo requiere
            'total_general_kilos'  => 0.0
        ];

        foreach ($resultados as $fila) {
            $piezas = (int)$fila['piezas'];
            $kilos = (float)$fila['kilos'];
            $nombreProd = strtolower($fila['nombreProducto']); 
            
            $respuesta['ventas'][] = [
                'producto' => $fila['nombreProducto'],
                'cliente'  => $fila['nombre_cliente'] ?? 'Sin cliente',
                'ticket'   => !empty($fila['tickets']) ? $fila['tickets'] : 'S/T',
                'piezas'   => $piezas,
                'kilos'    => $kilos
            ];

            // Separamos matemáticamente si es caja o pieza suelta
            if (strpos($nombreProd, 'caja') !== false) {
                $respuesta['total_caja_piezas'] += $piezas;
                $respuesta['total_caja_kilos'] += $kilos;
            } else {
                $respuesta['total_suelto_piezas'] += $piezas;
                $respuesta['total_suelto_kilos'] += $kilos;
            }

            // Totales acumulados
            $respuesta['total_general_piezas'] += $piezas;
            $respuesta['total_general_kilos'] += $kilos;
        }

        return $respuesta;
    }
}
?>