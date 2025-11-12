<?php
// Ce fichier contient uniquement les fonctions et n'exécute rien directement.

/**
 * Gère la logique de connexion.
 * @param PDO $pdo
 * @param array $data
 */
function handleLogin($pdo, $data) {
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Username and password are required']);
        return;
    }

    $username = $data['username'];
    $password = $data['password'];

    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $user['password'])) {
            $token = bin2hex(random_bytes(32));
            $expiryDate = date('Y-m-d H:i:s', strtotime('+1 day'));

            $updateStmt = $pdo->prepare("UPDATE users SET session_token = ?, token_expires_at = ? WHERE id = ?");
            
            if ($updateStmt->execute([$token, $expiryDate, $user['id']])) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'token' => $token,
                    'role' => $user['role']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update session token']);
            }
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
    }
}

/**
 * Gère la mise à jour du mot de passe.
 * @param PDO $pdo
 * @param array $data
 */
function handleUpdatePassword($pdo, $data) {
    $user = verifyToken($pdo);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => 'Authentication failed: Invalid or expired token']);
        return;
    }

    if (!isset($data['current_password']) || !isset($data['new_password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Current and new passwords are required']);
        return;
    }
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
        return;
    }

    if (!password_verify($currentPassword, $userRow['password'])) {
        http_response_code(403);
        echo json_encode(['message' => 'Incorrect current password']);
        return;
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($updateStmt->execute([$newPasswordHash, $user['id']])) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update password']);
    }
}

/**
 * Fonction pour vérifier un token (à utiliser dans les autres scripts)
 * @param PDO $pdo
 * @return array|null
 */
function verifyToken($pdo) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if (!$authHeader) {
        return null;
    }

    $parts = explode(' ', $authHeader, 2);
    if (count($parts) < 2 || strcasecmp($parts[0], 'Bearer') != 0) {
        return null;
    }
    $token = $parts[1];

    $stmt = $pdo->prepare("SELECT id, role, token_expires_at FROM users WHERE session_token = ?");
    $stmt->execute([$token]);
    
    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (strtotime($user['token_expires_at']) > time()) {
            return $user;
        } else {
            return null; // Token expiré
        }
    }
    
    return null; // Token invalide ou non trouvé
}
?>
