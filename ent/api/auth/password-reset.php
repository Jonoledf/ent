<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Demande de réinitialisation
    if (empty($data['email'])) {
        http_response_code(400);
        echo json_encode(["message" => "Email requis"]);
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $email = trim($data['email']);
        
        $query = "SELECT id, email FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Pour éviter l'énumération des emails, on ne révèle pas si l'email existe
            http_response_code(200);
            echo json_encode(["message" => "Si l'email existe, un lien de réinitialisation a été envoyé"]);
            exit;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Génération d'un token sécurisé
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", time() + 86400); // 24 heures
        
        // Stockage du token en base
        $query = "UPDATE users SET reset_token = :token, token_expiry = :expiry WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expiry", $expiry);
        $stmt->bindParam(":id", $user['id']);
        $stmt->execute();
        
        // Envoi de l'email (simulé ici)
        sendResetEmail($user['email'], $token);
        
        http_response_code(200);
        echo json_encode(["message" => "Si l'email existe, un lien de réinitialisation a été envoyé"]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erreur serveur"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Réinitialisation du mot de passe
    if (empty($data['token']) || empty($data['new_password'])) {
        http_response_code(400);
        echo json_encode(["message" => "Token et nouveau mot de passe requis"]);
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $token = $data['token'];
        $newPassword = $data['new_password'];
        
        // Vérification du token
        $query = "SELECT id FROM users WHERE reset_token = :token AND token_expiry > NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(["message" => "Token invalide ou expiré"]);
            exit;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Hachage du nouveau mot de passe
        $passwordHash = password_hash($newPassword, Security::$PASSWORD_ALGO, Security::$PASSWORD_OPTIONS);
        
        // Mise à jour du mot de passe et suppression du token
        $query = "UPDATE users SET password_hash = :hash, reset_token = NULL, token_expiry = NULL WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":hash", $passwordHash);
        $stmt->bindParam(":id", $user['id']);
        $stmt->execute();
        
        http_response_code(200);
        echo json_encode(["message" => "Mot de passe réinitialisé avec succès"]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erreur serveur"]);
    }
}

function sendResetEmail($email, $token) {
    $resetLink = "https://ent.yvelines.fr/reset-password?token=$token";
    
    // En production, utiliser une bibliothèque d'envoi d'emails comme PHPMailer
    $subject = "Réinitialisation de votre mot de passe ENT Yvelines";
    $message = "Bonjour,\n\n";
    $message .= "Vous avez demandé à réinitialiser votre mot de passe. ";
    $message .= "Cliquez sur le lien suivant pour procéder (valable 24 heures) :\n\n";
    $message .= $resetLink . "\n\n";
    $message .= "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\n";
    $message .= "Cordialement,\nL'équipe ENT Yvelines";
    
    // En production : mail($email, $subject, $message);
    error_log("Email envoyé à $email avec le lien : $resetLink");
}
?>