<?php
require_once __DIR__ . '/../Config/BD.php';
class ModeloReportes{
    private BD $db;

    public function __construct() {

        $this->db = BD::obtenerInstancia();
    }

    public function getAllReports(): array {

        $query = "SELECT 
                    Documentos.idDocumento,
                    Documentos.nombreOriginal,
                    Documentos.rutaArchivo,
                    Documentos.tipoDocumento,
                    Documentos.tamanoBytes,
                    Documentos.idCuenta,
                    Documentos.fechaCreacion,
                FROM Documentos ORDER BY Documentos.fechaCreacion DESC;"
        return $this->db->select($query);
    }

    
}
?>