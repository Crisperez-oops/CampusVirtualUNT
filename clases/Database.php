<?php
/**
 * clases/Database.php
 * Maneja una única instancia PDO para toda la app (patrón Singleton).
 * Esto evita abrir múltiples conexiones a MySQL en cada request, algo
 * importante en hosting compartido con límites de conexiones (InfinityFree).
 */

require_once __DIR__ . '/../config/database.php';

class Database
{
    private static ?PDO $instancia = null;

    // Constructor privado: nadie puede hacer "new Database()"
    private function __construct() {}

    public static function obtenerConexion(): PDO
    {
        if (self::$instancia === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // usa prepares reales (más seguro)
            ];

            try {
                self::$instancia = new PDO($dsn, DB_USER, DB_PASS, $opciones);
            } catch (PDOException $e) {
                // No exponemos detalles de la BD en producción
                if (MODO_DEBUG) {
                    die('Error de conexión a la base de datos: ' . $e->getMessage());
                }
                die('No se pudo conectar a la base de datos. Verifica config/database.php.');
            }
        }

        return self::$instancia;
    }
}
