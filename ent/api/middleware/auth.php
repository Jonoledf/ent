<?php
// api/middleware/auth.php

require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/database.php';

class AuthMiddleware {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Middleware d'authentification JWT
     */
    public function authenticate() {
        // Récupérer le token depuis les headers ou cookies
        $token = $this->getBearerToken() ?? ($_COOKIE['ent_token'] ?? null);
        
        if (!$token) {
            $this->unauthorized("Token d'authentification manquant");
            return false;
        }

        // Vérifier si le token est dans la liste noire
        if ($this->isTokenBlacklisted($token)) {
            $this->unauthorized("Session invalide. Veuillez vous reconnecter");
            return false;
        }

        try {
            $decoded = $this->validateJWT($token);
            
            // Vérifier si l'utilisateur existe toujours
            if (!$this->userExists($decoded->sub)) {
                $this->unauthorized("Utilisateur introuvable");
                return false;
            }
            
            // Ajouter les infos utilisateur à la requête
            $this->attachUserToRequest($decoded);
            
            return true;
            
        } catch (Exception $e) {
            $this->unauthorized("Token invalide: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valide un token JWT
     */
    private function validateJWT($token) {
        return JWT::decode($token, Security::$JWT_SECRET, ['HS256']);
    }

    /**
     * Vérifie si un token est blacklisté
     */
    private function isTokenBlacklisted($token) {
        // En production, utiliser Redis:
        // return $redis->exists("blacklist:$token");
        
        // Version basique avec fichier
        $blacklistFile = __DIR__ . '/../../storage/token_blacklist.txt';
        if (!file_exists($blacklistFile)) return false;
        
        $blacklistedTokens = file($blacklistFile, FILE_IGNORE_NEW_LINES);
        return in_array($token, $blacklistedTokens);
    }

    /**
     * Vérifie l'existence de l'utilisateur
     */
    private function userExists($userId) {
        $query = "SELECT id FROM users WHERE id = :id AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Attache les infos utilisateur à la requête
     */
    private function attachUserToRequest($decoded) {
        $_SERVER['AUTH_USER'] = [
            'id' => $decoded->sub,
            'username' => $decoded->username,
            'role' => $decoded->role,
            'exp' => $decoded->exp
        ];
    }

    /**
     * Récupère le token depuis l'en-tête Authorization
     */
    private function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Récupère l'en-tête Authorization
     */
    private function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * Réponse d'erreur non autorisée
     */
    private function unauthorized($message) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'code' => 401
        ]);
        exit;
    }
}

// Fonction pour utiliser le middleware dans les endpoints
function requireAuth() {
    $auth = new AuthMiddleware();
    return $auth->authenticate();
}
?>