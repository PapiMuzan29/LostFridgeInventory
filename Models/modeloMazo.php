<?php
class modeloMazo {
    private BD $db;

    public function __construct(BD $db) {
        $this->db = $db;
    }

    public function obtenerVentasMazoPorFecha(string $fecha): array {
        // Consulta para buscar productos que contengan "Mazo"
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
                WHERE p.nombreProducto LIKE '%Mazo%' 
                  AND DATE(n.fecha_creacion) = :fecha
                GROUP BY dn.id_detalle, p.nombreProducto, n.nombre_cliente";

        $resultados = $this->db->select($sql, ['fecha' => $fecha]);

        // Estructura de respuesta adaptada a Mazo (nacional / importado)
        $respuesta = [
            'nacional'  => ['piezas' => 0, 'kilos' => 0.0, 'ventas' => []],
            'importado' => ['piezas' => 0, 'kilos' => 0.0, 'ventas' => []],
            'total_general_piezas' => 0,
            'total_general_kilos'  => 0.0
        ];

        foreach ($resultados as $fila) {
            $nombre = $fila['nombreProducto'];
            $piezas = (int)$fila['piezas'];
            $kilos = (float)$fila['kilos'];

            // Clasificación según el nombre del producto
            $tipoMazo = null;
            if (mb_strpos(mb_strtolower($nombre), 'importado') !== false) {
                $tipoMazo = 'importado';
            } else {
                // Si no dice importado o dice nacional, se asigna a nacional por defecto
                $tipoMazo = 'nacional';
            }

            if ($tipoMazo) {
                $respuesta[$tipoMazo]['piezas'] += $piezas;
                $respuesta[$tipoMazo]['kilos'] += $kilos;
                $respuesta[$tipoMazo]['ventas'][] = [
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