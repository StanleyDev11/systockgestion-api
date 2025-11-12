<?php
// Ce fichier est maintenant un simple endpoint qui utilise les fonctions de auth_helpers.php
require_once 'db.php';
require_once 'auth_helpers.php';

header('Content-Type: application/json');

$conn = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'login'; // Default to 'login'

    if ($action == 'login') {
        handleLogin($conn, $data);
    } elseif ($action == 'change_password') {
        handleUpdatePassword($conn, $data);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid action']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Only POST method is supported for auth']);
}

$conn->close();
?>

