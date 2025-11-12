<?php
require_once 'db.php';
require_once 'auth_helpers.php'; // Inclure le nouveau fichier d'aide

header('Content-Type: application/json');

$pdo = getDbConnection();

// Pour toutes les méthodes (GET, POST), une authentification est requise
$user = verifyToken($pdo);
if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized: Invalid or expired token']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($pdo, $user);
        break;
    case 'POST':
        handlePost($pdo, $user);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['message' => 'Method not supported for stock movements']);
        break;
}

function handleGet($pdo, $user) {
    // Récupérer l'historique des mouvements, en joignant le nom du produit
    $sql = "SELECT sm.id, sm.product_id, p.name as product_name, sm.movement_type, sm.quantity, sm.date 
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            ORDER BY sm.date DESC";
    
    $stmt = $pdo->query($sql);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($movements);
}

function handlePost($pdo, $user) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['product_id'], $data['movement_type'], $data['quantity'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields: product_id, movement_type, quantity']);
        return;
    }

    $productId = intval($data['product_id']);
    $movementType = $data['movement_type'];
    $quantity = intval($data['quantity']);

    if (!in_array($movementType, ['IN', 'OUT'])) {
        http_response_code(400);
        echo json_encode(['message' => "Invalid movement_type. Must be 'IN' or 'OUT'"]);
        return;
    }
    if ($quantity <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Quantity must be positive']);
        return;
    }

    try {
        // Démarrer une transaction pour garantir l'intégrité des données
        $pdo->beginTransaction();

        // 1. Récupérer la quantité actuelle et verrouiller la ligne pour la mise à jour
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new Exception('Product not found', 404);
        }
        $currentQuantity = $product['quantity'];

        // 2. Calculer la nouvelle quantité et vérifier le stock si c'est une sortie
        $newQuantity = $currentQuantity;
        if ($movementType == 'IN') {
            $newQuantity += $quantity;
        } else { // 'OUT'
            if ($currentQuantity < $quantity) {
                throw new Exception('Insufficient stock', 409); // 409 Conflict
            }
            $newQuantity -= $quantity;
        }

        // 3. Mettre à jour la quantité du produit
        $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $productId]);

        // 4. Insérer le mouvement de stock
        $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$productId, $movementType, $quantity]);

        // Si tout s'est bien passé, valider la transaction
        $pdo->commit();
        http_response_code(201);
        echo json_encode(['message' => 'Stock movement recorded successfully', 'new_quantity' => $newQuantity]);

    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorCode = $e->getCode() >= 400 ? $e->getCode() : 500;
        http_response_code($errorCode);
        echo json_encode(['message' => $e->getMessage()]);
    }
}
?>
