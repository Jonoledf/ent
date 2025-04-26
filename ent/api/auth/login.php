<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../middleware/rate-limiter.php';

header("Content-Type: application/json");

// Appliquer le rate limiting
applyLoginRateLimiting();

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($data)) {
    http_response_code(400);
    echo json_encode(["message" => "Requête invalide"]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($data['username']);
    $password = $data['password'];
    $remember = isset($data['remember']) && $data['remember'];
    
    // Vérification des champs requis
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(["message" => "Identifiant et mot de passe requis"]);
        exit;
    }
    
    // Recherche de l'utilisateur
    $query = "SELECT * FROM users WHERE username = :username OR email = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(401);
        echo json_encode(["message" => "Identifiant ou mot de passe incorrect"]);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérification du mot de passe
    if (!password_verify($password, $user['password_hash'])) {
        // Enregistrement de la tentative échouée
        logFailedAttempt($user['id'], $db);
        
        http_response_code(401);
        echo json_encode(["message" => "Identifiant ou mot de passe incorrect"]);
        exit;
    }
    
    // Vérification si le compte est bloqué
    if (isAccountLocked($user['id'], $db)) {
        http_response_code(403);
        echo json_encode(["message" => "Trop de tentatives. Veuillez réessayer plus tard."]);
        exit;
    }
    
    // Réinitialisation des tentatives après une connexion réussie
    resetFailedAttempts($user['id'], $db);
    
    // Mise à jour de la dernière connexion
    updateLastLogin($user['id'], $db);
    
    // Génération du token JWT
    $token = generateJWT($user, $remember);
    
    // Réponse avec le token
    echo json_encode([
        "token" => $token,
        "requiresPasswordChange" => $user['force_password_change'] == 1
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur: " . $e->getMessage()]);
}

function generateJWT($user, $remember) {
    $issuedAt = time();
    $expirationTime = $remember ? 
        $issuedAt + Security::$JWT_REMEMBER_EXPIRE : 
        $issuedAt + Security::$JWT_EXPIRE;
    
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'sub' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ];
    
    $jwt = JWT::encode($payload, Security::$JWT_SECRET, 'HS256');
    
    // Définir le cookie si "Se souvenir de moi" est coché
    if ($remember) {
        setcookie('ent_token', $jwt, $expirationTime, "/", "", true, true);
    }
    
    return $jwt;
}

// Fonctions auxiliaires pour la gestion des tentatives...
?>