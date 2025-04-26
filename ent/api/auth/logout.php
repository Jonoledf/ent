<?php
// api/auth/logout.php

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../middleware/auth.php';

header("Content-Type: application/json");

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Méthode non autorisée"]);
    exit;
}

try {
    // Récupérer le token JWT
    $token = getBearerToken();
    
    // Si token valide, l'ajouter à la liste noire
    if ($token) {
        addTokenToBlacklist($token);
    }
    
    // Supprimer le cookie côté client
    setcookie('ent_token', '', time() - 3600, "/", "", true, true);
    
    // Détruire la session PHP si elle existe
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Réponse JSON de succès
    echo json_encode([
        "success" => true,
        "message" => "Déconnexion réussie"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur lors de la déconnexion: " . $e->getMessage()
    ]);
}

/**
 * Fonctions utilitaires
 */

// Récupère le token JWT depuis l'en-tête Authorization
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Récupère l'en-tête Authorization
function getAuthorizationHeader() {
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

// Ajoute un token à la liste noire (à implémenter avec Redis en production)
function addTokenToBlacklist($token) {
    $blacklistFile = __DIR__ . '/../../storage/token_blacklist.txt';
    
    // Vérifier si le token n'est pas déjà dans la liste noire
    $blacklistedTokens = file_exists($blacklistFile) ? file($blacklistFile, FILE_IGNORE_NEW_LINES) : [];
    
    if (!in_array($token, $blacklistedTokens)) {
        file_put_contents($blacklistFile, $token . PHP_EOL, FILE_APPEND);
    }
    
    // En production, utiliser plutôt Redis avec une expiration automatique:
    // $redis->setex("blacklist:$token", Security::$JWT_EXPIRE, "1");
}
?>