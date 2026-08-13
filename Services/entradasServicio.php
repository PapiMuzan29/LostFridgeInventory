<?php
require_once __DIR__ . '/../Models/modeloEntradas.php';

class entradasServicio {
    private $modelo;

    public function __construct() {
        $this->modelo = new modeloEntradas();
    }

    public function listarProveedoresParaSelect() {
        return $this->modelo->obtenerTodosProveedores();
    }

    public function listarProveedores() {
        return $this->modelo->obtenerTodosProveedores();
    }

    /**
     * 🔥 CONSULTA INTELIGENTE DE PRODUCTOS (CORTES DINÁMICOS POR BD)
     */
    public function obtenerProductoPorCodigo($codigo, $idProveedor) {
        // 1. Prioridad: Procesar los recortes dinámicos usando la configuración de la BD
        if (!empty($idProveedor)) {
            // Buscamos el nombre del producto en base a los cortes
            $productoBD = $this->modelo->obtenerProducto($codigo, $idProveedor, '', '');
            
            if (!empty($productoBD) && isset($productoBD['nombreProducto'])) {
                // 🎯 EXTRA: Le pedimos al modelo los pedazos exactos del recorte para mandárselos limpios al JS
                $cortesLimpios = $this->modelo->obtenerCodigoDeProducto('idProveedor', $idProveedor, $codigo);

                return [
                    'nombreProducto'  => $productoBD['nombreProducto'],
                    'codigoEnteros'   => $cortesLimpios['codigoEnteros'] ?? '0',
                    'codigoDecimales' => $cortesLimpios['codigoDecimales'] ?? '00',
                    'codigoProducto'  => $cortesLimpios['codigoProducto'] ?? $codigo // Retorna "3390" limpiamente
                ];
            }
        }

        // 2. Fallback: Búsqueda directa completa si no hay proveedor o falló el corte
        $productoDirecto = $this->modelo->buscarProductoPorCodigoYProveedor($codigo, $idProveedor);
        if ($productoDirecto) {
            return [
                'nombreProducto'  => $productoDirecto['nombreProducto'] ?? 'Producto Encontrado',
                'codigoEnteros'   => '1', // Pieza por defecto
                'codigoDecimales' => '00',
                'codigoProducto'  => $codigo
            ];
        }

        return null;
    }

    public function crearEntrada(array $data) {
        return $this->modelo->crearEntrada($data);
    }

    /**
     * 🔥 NUEVO: Recibe los datos del controlador y los pasa al modelo
     */
    public function registrarEntradaCompleta($datos, $idUsuario, $apodoUsuario) {
        return $this->modelo->registrarEntradaTransaccion($datos, $idUsuario, $apodoUsuario);
    }
}