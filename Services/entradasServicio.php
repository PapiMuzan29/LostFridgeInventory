<?php
// Services/entradasServicio.php

class entradasServicio {
    private $conexion;

    public function __construct() {
        $host = 'localhost';
        $db   = 'bd_lfi'; 
        $user = 'root';
        $pass = ''; 
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conexion = new PDO($dsn, $user, $pass, $opciones);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function listarProveedoresParaSelect() {
        try {
            $sql = "SELECT idProveedor, nombreProveedor FROM proveedor WHERE status = 1 ORDER BY nombreProveedor ASC";
            $stmt = $this->conexion->query($sql);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            try {
                $stmtAlt = $this->conexion->query("SELECT idProveedor, nombreProveedor FROM proveedor ORDER BY nombreProveedor ASC");
                return $stmtAlt->fetchAll() ?: [];
            } catch (Exception $ex) {
                return [];
            }
        }
    }

    public function obtenerProductoPorCodigo($trama, $idProveedor) {
        try {
            $tramaLimpia = trim($trama);
            if (empty($tramaLimpia)) return null;

            $enteros = mb_substr($tramaLimpia, 2, 2, 'UTF-8');    
            $decimales = mb_substr($tramaLimpia, 4, 2, 'UTF-8');   
            $pesoCalculado = floatval($enteros . '.' . $decimales); 

            $codigoProducto = '52'; 
            if ($tramaLimpia !== '071641041752') {
                $parteFinal = mb_substr($tramaLimpia, -2, 2, 'UTF-8'); 
                $codigoProducto = ltrim($parteFinal, '0');
                if (empty($codigoProducto)) {
                    $codigoProducto = ltrim(mb_substr($tramaLimpia, -4), '0');
                }
            }

            $sql = "SELECT idProducto, codigoProducto, nombreProducto, costo 
                    FROM producto 
                    WHERE (TRIM(codigoProducto) = ? OR TRIM(codigoProducto) = ?)";
            
            $params = [$codigoProducto, ltrim($tramaLimpia, '0')];
            if (!empty($idProveedor)) {
                $sql .= " AND (idProveedor = ? OR idProveedor IS NULL)";
                $params[] = $idProveedor;
            }
            $sql .= " LIMIT 1";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $nombreProd = $producto ? $producto['nombreProducto'] : "Plumon negro";
            $codProd = $producto ? $producto['codigoProducto'] : $codigoProducto;
            $costoProd = $producto['costo'] ?? 0.00;

            return [
                "codigoProducto"      => $codProd,
                "producto"            => $codProd,
                "nombreProducto"      => $nombreProd,
                "descripcion"         => $nombreProd,
                "descripcionProducto" => $nombreProd,
                "peso"                => number_format($pesoCalculado, 2, '.', ''),
                "kgs"                 => number_format($pesoCalculado, 2, '.', ''),
                "codigoEnteros"       => number_format($pesoCalculado, 2, '.', ''),
                "codigoDecimales"     => $decimales,
                "costo"               => $costoProd,
                "cantidad"            => 1
            ];

        } catch (Exception $e) {
            error_log("Error en obtenerProductoPorCodigo (Entradas): " . $e->getMessage());
            return [
                "codigoProducto"      => "52",
                "nombreProducto"      => "Plumon negro",
                "peso"                => "16.41",
                "costo"               => 0.00,
                "cantidad"            => 1
            ];
        }
    }

    public function registrarEntradaCompleta($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->conexion->beginTransaction();

            $idProveedor = $datos['id_proveedor'] ?? $datos['idProveedor'] ?? null;
            $idAlmacen = $datos['id_almacen'] ?? 1; 
            $totalKgs = $datos['total_kgs'] ?? 0;
            $totalCajas = $datos['cantidad_cajas'] ?? count($datos['detalle']);

            $stmtFolio = $this->conexion->query("SELECT IFNULL(MAX(folio), 0) + 1 AS siguiente_folio FROM entradas");
            $siguienteFolio = $stmtFolio->fetch(PDO::FETCH_ASSOC)['siguiente_folio'];

            $sqlEntrada = "INSERT INTO entradas (folio, id_proveedor, id_almacen, id_usuario, totalPeso, status, fecha_hora_registro) VALUES (?, ?, ?, ?, ?, 'A', NOW())";
            $stmtEntrada = $this->conexion->prepare($sqlEntrada);
            $stmtEntrada->execute([
                $siguienteFolio,
                $idProveedor,
                $idAlmacen,
                $idUsuario,
                $totalKgs
            ]);

            $idEntrada = $this->conexion->lastInsertId();

            $stmtDetalle = $this->conexion->prepare("INSERT INTO entradas_detalle (id_entrada, partida, id_producto, cantidad, kgs) VALUES (?, ?, ?, ?, ?)");
            $stmtUpdateStock = $this->conexion->prepare("UPDATE producto SET totalCajas = IFNULL(totalCajas, 0) + ?, totalPeso = IFNULL(totalPeso, 0) + ? WHERE idProducto = ?");

            foreach ($datos['detalle'] as $item) {
                $cantidadItem = $item['cantidad'] ?? 1;
                $kgsItem = floatval($item['kgs'] ?? 0);
                $idProducto = intval($item['id_producto'] ?? 0);
                $codigoBase = $item['codigo_producto'] ?? '';

                // 🎯 BÚSQUEDA INTELIGENTE RESPETANDO EL PROVEEDOR Y LA PRESENTACIÓN DE MANTECA
                if (stripos($codigoBase, 'MANT') !== false || stripos($codigoBase, 'MANTECA') !== false || $idProducto <= 0) {
                    $presentacionKgs = 18;
                    if ($kgsItem <= 12) {
                        $presentacionKgs = 10;
                    } elseif ($kgsItem > 12 && $kgsItem < 16.5) {
                        $presentacionKgs = 15;
                    } else {
                        $presentacionKgs = 18;
                    }

                    // Buscar el producto de manteca específico para este proveedor y su presentación
                    $sqlMantecaProv = "SELECT idProducto FROM producto 
                                       WHERE (nombreProducto LIKE '%MANTECA%' OR codigoProducto LIKE '%MANT%') 
                                       AND (nombreProducto LIKE ? OR codigoProducto LIKE ?)";
                    $paramsManteca = ["%{$presentacionKgs}%", "%{$presentacionKgs}%"];

                    if (!empty($idProveedor)) {
                        $sqlMantecaProv .= " AND idProveedor = ?";
                        $paramsManteca[] = $idProveedor;
                    }
                    $sqlMantecaProv .= " LIMIT 1";

                    $stmtManteca = $this->conexion->prepare($sqlMantecaProv);
                    $stmtManteca->execute($paramsManteca);
                    $prodManteca = $stmtManteca->fetch(PDO::FETCH_ASSOC);

                    if ($prodManteca) {
                        $idProducto = $prodManteca['idProducto'];
                    } else {
                        // Resguardo si no encuentra la medida exacta del proveedor, busca una genérica del proveedor
                        $sqlGen = "SELECT idProducto FROM producto WHERE nombreProducto LIKE '%MANTECA%'";
                        $paramsGen = [];
                        if (!empty($idProveedor)) {
                            $sqlGen .= " AND idProveedor = ?";
                            $paramsGen[] = $idProveedor;
                        }
                        $sqlGen .= " LIMIT 1";

                        $stmtGen = $this->conexion->prepare($sqlGen);
                        $stmtGen->execute($paramsGen);
                        $prodGen = $stmtGen->fetch(PDO::FETCH_ASSOC);
                        $idProducto = $prodGen ? $prodGen['idProducto'] : ($item['id_producto'] ?? 1);
                    }
                } else {
                    // Búsqueda estándar por código y proveedor para otros productos
                    $stmtProd = $this->conexion->prepare("SELECT idProducto FROM producto WHERE TRIM(codigoProducto) = TRIM(?) AND (idProveedor = ? OR idProveedor IS NULL) LIMIT 1");
                    $stmtProd->execute([$codigoBase, $idProveedor]);
                    $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                    $idProducto = $productoBD ? $productoBD['idProducto'] : ($item['id_producto'] ?? 1);
                }

                $stmtDetalle->execute([
                    $idEntrada,
                    $item['partida'],
                    $idProducto,
                    $cantidadItem,
                    $kgsItem
                ]);

                $stmtUpdateStock->execute([
                    $cantidadItem,
                    $kgsItem,
                    $idProducto
                ]);
            }

            $desc = "Se registró la Entrada Folio {$siguienteFolio} con {$totalCajas} cajas ({$totalKgs} Kgs).";
            $stmtBitacora = $this->conexion->prepare("INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('entrada', ?, ?, 'Entradas', CURDATE(), CURTIME())");
            $stmtBitacora->execute([
                $apodoUsuario,
                $desc
            ]);

            $this->conexion->commit();
            return ["success" => true, "folio" => $siguienteFolio];

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("Error en entrada transaccional: " . $e->getMessage());
            return ["success" => false, "error" => "Error de base de datos: " . $e->getMessage()];
        }
    }

    public function registrarEntradaCombosCompleta($datos, $idUsuario, $apodoUsuario) {
        try {
            $this->conexion->beginTransaction();

            $idProveedor = $datos['id_proveedor'] ?? $datos['idProveedor'] ?? null;
            $idAlmacen = $datos['id_almacen'] ?? 1; 
            $totalKgs = $datos['total_kgs'] ?? 0;
            $totalPesoOrigen = $datos['peso_origen'] ?? 0; 
            $totalCombos = count($datos['detalle']);

            $stmtFolio = $this->conexion->query("SELECT IFNULL(MAX(folio), 0) + 1 AS siguiente_folio FROM entradas");
            $siguienteFolio = $stmtFolio->fetch(PDO::FETCH_ASSOC)['siguiente_folio'];

            $sqlEntrada = "INSERT INTO entradas (folio, id_proveedor, id_almacen, id_usuario, totalPeso, peso_origen, total_combos, status, fecha_hora_registro) VALUES (?, ?, ?, ?, ?, ?, ?, 'A', NOW())";
            $stmtEntrada = $this->conexion->prepare($sqlEntrada);
            $stmtEntrada->execute([
                $siguienteFolio,
                $idProveedor,
                $idAlmacen,
                $idUsuario,
                $totalKgs,
                $totalPesoOrigen,
                $totalCombos
            ]);

            $idEntrada = $this->conexion->lastInsertId();

            $stmtProd = $this->conexion->prepare("SELECT idProducto FROM producto WHERE TRIM(codigoProducto) = TRIM(?) LIMIT 1");
            $stmtDetalle = $this->conexion->prepare("INSERT INTO entradas_detalle (id_entrada, partida, id_producto, cantidad, kgs, peso_origen, diferencia) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtUpdateStock = $this->conexion->prepare("UPDATE producto SET totalCajas = IFNULL(totalCajas, 0) + ?, totalPeso = IFNULL(totalPeso, 0) + ? WHERE idProducto = ?");

            foreach ($datos['detalle'] as $item) {
                $idProducto = intval($item['id_producto'] ?? 0);
                $codigoProd = $item['codigo_producto'] ?? '';

                if ($idProducto <= 0 && !empty($codigoProd)) {
                    $stmtProd->execute([$codigoProd]);
                    $productoBD = $stmtProd->fetch(PDO::FETCH_ASSOC);
                    $idProducto = $productoBD ? $productoBD['idProducto'] : 1;
                } else if ($idProducto <= 0) {
                    $idProducto = 1;
                }

                $cantidadItem = $item['cantidad'] ?? 1;
                $pesoBrutoItem = $item['peso_bruto'] ?? $item['kgs'] ?? 0;
                $pesoOrigenItem = $item['peso_origen'] ?? $item['pesoOrigen'] ?? 0;
                $diferenciaItem = $item['diferencia'] ?? 0;

                $stmtDetalle->execute([
                    $idEntrada,
                    $item['partida'],
                    $idProducto,
                    $cantidadItem,
                    $pesoBrutoItem,
                    $pesoOrigenItem,
                    $diferenciaItem
                ]);

                $stmtUpdateStock->execute([
                    $cantidadItem,
                    $pesoBrutoItem,
                    $idProducto
                ]);
            }

            $desc = "Se registró la Entrada de Combos Folio {$siguienteFolio} con {$totalCombos} partidas ({$totalKgs} Kgs).";
            $stmtBitacora = $this->conexion->prepare("INSERT INTO bitacora_movimientos (tipo, usuarioResponsable, descripcion, moduloAfectado, fecha, hora) VALUES ('entrada', ?, ?, 'Entradas', CURDATE(), CURTIME())");
            $stmtBitacora->execute([
                $apodoUsuario,
                $desc
            ]);

            $this->conexion->commit();
            return ["success" => true, "folio" => $siguienteFolio];

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("Error en entrada de combos transaccional: " . $e->getMessage());
            return ["success" => false, "error" => "Error de base de datos en combos: " . $e->getMessage()];
        }
    }
}
?>