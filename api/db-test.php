<?php
require 'db.php';

try {
    $pdo = getDbConnection();
    echo json_encode(['status' => 'success', 'message' => 'Connexion OK']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
