<?php
require_once 'db.php';
require_once 'auth_helpers.php'; // Inclure le nouveau fichier d'aide

header('Content-Type: application/json');

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// La méthode GET reste publique
if ($method == 'GET') {
    handleGet($pdo);
    exit();
}

// Pour POST, PUT, DELETE, une authentification est requise
$user = verifyToken($pdo);
if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized: Invalid or expired token']);
    exit();
}

// Un simple routeur basé sur la méthode HTTP
switch ($method) {
    case 'POST':
        handlePost($pdo, $user);
        break;
    case 'PUT':
        handlePut($pdo, $user);
        break;
    case 'DELETE':
        handleDelete($pdo, $user);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['message' => 'Method not supported']);
        break;
}

// --- Fonctions de gestion pour chaque méthode ---

function handleGet($pdo) {
    if (isset($_GET['id'])) {
        // Récupérer un seul produit
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        if ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Product not found']);
        }
    } else {
        // Récupérer tous les produits
        $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    }
}

function handlePost($pdo, $user) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['name']) || !isset($data['quantity']) || !isset($data['price'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Missing required fields: name, quantity, price']);
        return;
    }

    $name = $data['name'];
    $description = $data['description'] ?? '';
    $quantity = intval($data['quantity']);
    $price = floatval($data['price']);

    // Note: Pour PostgreSQL, on utilise SERIAL ou BIGSERIAL pour l'auto-incrément.
    // On peut retourner l'ID inséré avec RETURNING id.
    $sql = "INSERT INTO products (name, description, quantity, price) VALUES (?, ?, ?, ?) RETURNING id";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$name, $description, $quantity, $price]);
        $newId = $stmt->fetchColumn();
        http_response_code(201); // Created
        echo json_encode(['id' => $newId, 'name' => $name, 'description' => $description, 'quantity' => $quantity, 'price' => $price]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to create product', 'error' => $e->getMessage()]);
    }
}

function handlePut($pdo, $user) {
    if ($user['role'] !== 'Admin' && $user['role'] !== 'Manager') {
        http_response_code(403); // Forbidden
        echo json_encode(['message' => 'Forbidden: You do not have permission to perform this action']);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Product ID is required']);
        return;
    }
    $id = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);

    $name = $data['name'];
    $description = $data['description'] ?? '';
    $quantity = intval($data['quantity']);
    $price = floatval($data['price']);

    $sql = "UPDATE products SET name = ?, description = ?, quantity = ?, price = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$name, $description, $quantity, $price, $id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Product updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Product not found or no changes made']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update product', 'error' => $e->getMessage()]);
    }
}

function handleDelete($pdo, $user) {
    if ($user['role'] !== 'Admin') {
        http_response_code(403); // Forbidden
        echo json_encode(['message' => 'Forbidden: You do not have permission to perform this action']);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Product ID is required']);
        return;
    }
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");

    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Product deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Product not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to delete product', 'error' => $e->getMessage()]);
    }
}
?>
