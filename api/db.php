<?php
// Permet les requêtes cross-origin
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
    // Render fournit l'URL de la base de données dans cette variable d'environnement
    $database_url = getenv('DATABASE_URL');

    if ($database_url === false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['message' => 'Database URL not configured.']);
        exit();
    }

    // Analyse de l'URL de la base de données (format postgres://user:password@host:port/dbname)
    $db_parts = parse_url($database_url);

    $host = $db_parts['host'];
    $port = $db_parts['port'];
    $dbname = ltrim($db_parts['path'], '/');
    $user = $db_parts['user'];
    $pass = $db_parts['pass'];

    // Chaîne de connexion DSN pour PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $pdo = new PDO($dsn, $user, $pass);
        // Configurer PDO pour qu'il lance des exceptions en cas d'erreur
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
