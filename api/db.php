<?php
// Autorise les requêtes cross-origin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Crée et retourne une connexion à la base de données PostgreSQL en utilisant PDO.
 * Lit la configuration depuis la variable d'environnement DATABASE_URL fournie par Render.
 * @return PDO
 */
function getDbConnection() {
    $database_url = getenv('DATABASE_URL');

    if ($database_url === false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['message' => 'Database URL not configured.']);
        exit();
    }

    // Correction du schéma pour compatibilité avec PDO et parse_url()
    $database_url = str_replace('postgresql://', 'postgres://', $database_url);

    // Analyse de l'URL (format : postgres://user:password@host:port/dbname)
    $db_parts = parse_url($database_url);

    $host = $db_parts['host'] ?? null;
    $port = $db_parts['port'] ?? 5432; // par défaut si manquant
    $dbname = isset($db_parts['path']) ? ltrim($db_parts['path'], '/') : null;
    $user = $db_parts['user'] ?? null;
    $pass = $db_parts['pass'] ?? null;

    if (!$host || !$dbname || !$user) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['message' => 'Invalid DATABASE_URL format.']);
        exit();
    }

    // Construction du DSN PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>
