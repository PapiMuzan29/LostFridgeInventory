<?php

declare(strict_types=1);

class BD extends PDO {

    private string $host = "localhost";
    private string $dbname = "bd_lfi";
    private string $user = "root";
    private string $pass = "";

    private int $registros = 0;

    private static ?BD $instancia = null;

    private array $bloqueos = [
        '/--/',
        '/#/',
        '/;/',
        // Palabras clave peligrosas
        '/\bUNION\b/i',
        '/\bDROP\b/i',
        '/\bTRUNCATE\b/i',
        '/\bALTER\b/i',
        // DELETE o UPDATE sin WHERE
        '/\bDELETE\b(?!.*WHERE)/is',
        '/\bUPDATE\b(?!.*WHERE)/is',
        // Funciones peligrosas
        '/\bLOAD_FILE\b/i',
        '/\bINTO OUTFILE\b/i'
    ];

    public function registrosAfectados(): int{
        return $this->registros;
    }

    private bool $conexionExitosa = false;

    public function __construct() {

        $stringConexion = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";

        try {

            parent::__construct(
                $stringConexion,
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-06:00'"
                ]
            );

            $this->conexionExitosa = true;

        } catch (PDOException $e) {

            $this->conexionExitosa = false;

            error_log("Error de conexión: " . $e->getMessage());
        }
    }

    public static function obtenerInstancia(): ?BD {

        if (self::$instancia === null) {
            self::$instancia = new self();
        }

        return self::$instancia;
    }

    public function validar(string $query): bool {
        foreach ($this->bloqueos as $regex) {
            if (preg_match($regex, $query)) {
                return false;
            }
        }
        return true;
    }

    public function validarExcepcion(string $query): void {
        if (!$this->validar($query)) {
            throw new Exception('Error en la consulta');
        }
    }

    public function conexionActiva(): bool {

        return $this->conexionExitosa;
    }

    public function consulta(string $query, array $params = []): PDOStatement {
        $this->validarExcepcion($query);
        $this->registros = 0;
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        $this->registros = $stmt->rowCount();
        return $stmt;
    }

    public function select(string $query, array $params = []): array {
        return $this->consulta($query, $params)->fetchAll();
    }

    public function insert(string $query, array $params = []): int {

        $this->consulta($query, $params);

        return (int)$this->lastInsertId();
    }

    public function update(string $query, array $params = []): int {

        return $this->consulta($query, $params)->rowCount();
    }

    public function delete(string $query, array $params = []): int {

        return $this->consulta($query, $params)->rowCount();
    }

    public function getRegistros(): int {

        return $this->registros;
    }
}

?>