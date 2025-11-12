<?php
// Inclure uniquement la connexion à la DB au début
require_once 'db.php';

// --- Analyse de la requête ---
$request_uri = strtok($_SERVER['REQUEST_URI'], '?');
$base_path = dirname($_SERVER['SCRIPT_NAME']);

if ($base_path === '/' || $base_path === '\\') {
    $api_path = $request_uri;
} else {
    $api_path = substr($request_uri, strlen($base_path));
}

$path_segments = explode('/', trim($api_path, '/'));
$resource = $path_segments[0] ?? null;

// --- Routeur simple ---
switch ($resource) {
    case 'products':
        require_once 'products.php';
        break;

    case 'stock-movements':
        require_once 'stock_movements.php';
        break;

    case 'auth':
        require_once 'auth.php';
        break;

    case '':
    case null:
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Welcome to the API.']);
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found.']);
        break;
}


