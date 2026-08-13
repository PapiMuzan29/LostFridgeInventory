<?php
require_once __DIR__ . '/../Config/BD.php'; 

class modeloEntradas {
    private $db;

    public function __construct() {
        $this->db = new BD(); 
    }

    /**
     * 👥 Trae los proveedores activos
     */
    public function obtenerTodosProveedores() {
        $query = "SELECT idProveedor, nombreProveedor FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC";
        try {
            $stmt = $this->db->consulta($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error en modeloEntradas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerCodigoDeProducto(
        string $columna = '',
        string $valor = '',
        string $CodigoBarras = ''
    ): array {

        if ($CodigoBarras === '' || $columna === '' || $valor === '') {
            return [];
        }

        $columnasValidas = ['idProveedor', 'codigoProveedor', 'nombreProveedor', 'rfc'];
        if (!in_array($columna, $columnasValidas)) {
            return [];
        }

        $query = "SELECT 
                    codigoBarrasProductosPosicion,
                    codigoBarrasProductosLongitud,
                    codigoBarrasEnterosPosicion,
                    codigoBarrasEnterosLongitud,
                    codigoBarrasDecimalesPosicion,
                    codigoBarrasDecimalesLongitud 
                  FROM proveedor 
                  WHERE {$columna} = ? 
                  AND status = 1";
    
        $params = [$valor]; 

        try {
            $stmt = $this->db->consulta($query, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return []; 
            }
        } catch (Exception $e) {
            error_log("Error en modeloEntradas: " . $e->getMessage());
            return [];
        }

        $codigoBarrasProductosPosicion = $result['codigoBarrasProductosPosicion'] ?? 0;
        $codigoBarrasProductosLongitud = $result['codigoBarrasProductosLongitud'] ?? 0;
        $codigoBarrasEnterosPosicion   = $result['codigoBarrasEnterosPosicion'] ?? 0;
        $codigoBarrasEnterosLongitud   = $result['codigoBarrasEnterosLongitud'] ?? 0;
        $codigoBarrasDecimalesPosicion = $result['codigoBarrasDecimalesPosicion'] ?? 0;
        $codigoBarrasDecimalesLongitud = $result['codigoBarrasDecimalesLongitud'] ?? 0;

        $codigoProducto = mb_substr($CodigoBarras, $codigoBarrasProductosPosicion, $codigoBarrasProductosLongitud, 'UTF-8');
        $codigoEnteros = mb_substr($CodigoBarras, $codigoBarrasEnterosPosicion, $codigoBarrasEnterosLongitud, 'UTF-8');
        $codigoDecimales = mb_substr($CodigoBarras, $codigoBarrasDecimalesPosicion, $codigoBarrasDecimalesLongitud, 'UTF-8');

        return [
            'codigoProducto' => $codigoProducto,
            'codigoEnteros' => $codigoEnteros,
            'codigoDecimales' => $codigoDecimales
        ];
    }

   public function obtenerProducto(
        string $codigoBarras = '',
        string $codigoProveedor = '',
        string $nombreProveedor = '',
        string $rfc = ''
    ): array {

        if ($codigoBarras === '') {
            return [];
        }

        $columna = '';
        $valor = '';

        if ($codigoProveedor !== '') {
            $columna = 'idProveedor'; 
            $valor = $codigoProveedor;
        } else if ($nombreProveedor !== '') {
            $columna = 'nombreProveedor';
            $valor = $nombreProveedor;
        } else if ($rfc !== '') {
            $columna = 'rfc';
            $valor = $rfc;
        } else {
            return [];
        }
        
        // Ejecutamos el desglose matemático (Devuelve el producto, enteros y decimales)
        $Producto = $this->obtenerCodigoDeProducto($columna, $valor, $codigoBarras);

        if (empty($Producto) || !isset($Producto['codigoProducto']) || $Producto['codigoProducto'] === '') {
            return []; 
        }

        $claveLimpia = trim($Producto['codigoProducto']); // Ej: "3390"

        // 🛡️ BÚSQUEDA ULTRA-FLEXIBLE: 
        // 1. Busca coincidencia exacta.
        // 2. Busca ignorando ceros a la izquierda (por si en la BD está como 03390).
        // 3. Busca con LIKE por si hay espacios invisibles en tu catálogo.
        $query = "SELECT nombreProducto FROM Producto 
                  WHERE TRIM(codigoProducto) = ? 
                     OR LTRIM(REPLACE(codigoProducto, '0', ' ')) = LTRIM(REPLACE(?, '0', ' '))
                     OR codigoProducto LIKE ? 
                  LIMIT 1";
                  
        $params = [$claveLimpia, $claveLimpia, "%" . $claveLimpia . "%"]; 

        try {
            $stmt = $this->db->consulta($query, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return []; // Si de verdad no existe de ninguna forma, sale vacío
            }

            // 🔥 AQUÍ ESTÁ EL TRUCO DEL PESO:
            // Si el producto SÍ existe, regresamos los cortes reales de los kilogramos
            // para que el JavaScript pinte 0.54 en lugar de caer en el "1.00" de emergencia.
            return [
                'nombreProducto' => $result['nombreProducto'] ?? '',
                'codigoEnteros' => $Producto['codigoEnteros'] ?? '0',
                'codigoDecimales' => $Producto['codigoDecimales'] ?? '00'
            ];
        } catch (Exception $e) {
            error_log("Error en modeloEntradas (obtenerProducto): " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🎛️ PUENTE DE ENTRADA DIRECTA (MODO QR)
     */
    public function buscarProductoPorCodigoYProveedor($codigo, $idProveedor) {
        // 🛡️ OPTIMIZACIÓN ULTRA-ESTRICTA: Mismo criterio para búsquedas globales manuales
        $query = "SELECT nombreProducto FROM Producto WHERE CAST(TRIM(codigoProducto) AS CHAR) = CAST(TRIM(?) AS CHAR) LIMIT 1";
        $params = [trim($codigo)];

        try {
            $stmt = $this->db->consulta($query, $params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error en buscarProductoPorCodigoYProveedor: " . $e->getMessage());
            return null;
        }
    }
    /**
     * 🔥 REGISTRO TRANSACCIONAL Y ACTUALIZACIÓN DE INVENTARIO DIRECTO
     * Guarda la entrada, el detalle, la bitácora e incrementa el stock en la tabla producto.
     */
    public function registrarEntradaTransaccion($datos, $idUsuario, $apodoUsuario) {
        try {
            // INICIAMOS TRANSACCIÓN SQL
            $this->db->consulta("START TRANSACTION");

            // 1. Insertar en tabla `entradas`
            $queryEntrada = "INSERT INTO entradas (id_almacen, id_proveedor, id_usuario, totalKgs, status) VALUES (?, ?, ?, ?, 'A')";
            $this->db->consulta($queryEntrada, [
                $datos['id_almacen'], 
                $datos['id_proveedor'], 
                $idUsuario, 
                $datos['total_kgs']
            ]);
            
            // Obtener el ID insertado
            $stmtLastId = $this->db->consulta("SELECT LAST_INSERT_ID() as id");
            $idEntrada = $stmtLastId->fetch(PDO::FETCH_ASSOC)['id'];

            // 2. Procesar cada caja/producto escaneado
            foreach ($datos['detalle'] as $item) {
                
                // A) Buscar ID real del producto por su código
                $queryProd = "SELECT idProducto FROM producto WHERE codigoProducto = ? LIMIT 1";
                $stmtProd = $this->db->consulta($queryProd, [$item['codigo_producto']]);
                $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                $idProducto = $productoBD ? $productoBD['idProducto'] : 1; 

                // B) Insertar en entradas_detalle
                $queryDetalle = "INSERT INTO entradas_detalle (id_entrada, partida, id_producto, cantidad, kgs) VALUES (?, ?, ?, ?, ?)";
                $this->db->consulta($queryDetalle, [
                    $idEntrada, 
                    $item['partida'], 
                    $idProducto, 
                    $item['cantidad_cajas'], 
                    $item['kgs']
                ]);

                // C) 🔥 ACTUALIZAR EL INVENTARIO EN LA TABLA PRODUCTO 🔥
                // Usamos IFNULL para que, si el campo está en NULL, lo trate como 0 antes de sumar
                $queryUpdateStock = "UPDATE producto 
                                     SET totalCajas = IFNULL(totalCajas, 0) + ?, 
                                         totalPeso = IFNULL(totalPeso, 0) + ? 
                                     WHERE idProducto = ?";
                $this->db->consulta($queryUpdateStock, [
                    $item['cantidad_cajas'],
                    $item['kgs'],
                    $idProducto
                ]);
            }

            // 3. Registrar en bitacora_movimientos
            $desc = "Se registró la entrada Folio {$idEntrada} con {$datos['total_cajas']} cajas ({$datos['total_kgs']} Kgs).";
            $queryBitacora = "INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('entrada', ?, ?, 'Entradas', CURDATE(), CURTIME())";
            $this->db->consulta($queryBitacora, [
                $apodoUsuario,
                $desc
            ]);

            // CONFIRMAR TRANSACCIÓN
            $this->db->consulta("COMMIT");

            return ["success" => true, "folio" => $idEntrada];

        } catch (Exception $e) {
            // Deshacer todo si hubo un fallo
            $this->db->consulta("ROLLBACK");
            error_log("Error guardando entrada e inventario: " . $e->getMessage());
            return ["success" => false, "error" => "Error de base de datos: " . $e->getMessage()];
        }
    }
}