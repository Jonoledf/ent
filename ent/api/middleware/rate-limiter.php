<?php
function applyLoginRateLimiting() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_$ip";
    
    // Utiliser Redis ou Memcached en production
    $attempts = $_SESSION[$key] ?? 0;
    
    if ($attempts >= Security::$MAX_LOGIN_ATTEMPTS) {
        http_response_code(429);
        echo json_encode(["message" => "Trop de tentatives. Veuillez réessayer plus tard."]);
        exit;
    }
    
    $_SESSION[$key] = $attempts + 1;
}

function logFailedAttempt($userId, $db) {
    $query = "INSERT INTO login_attempts (user_id, ip_address, attempt_time) 
              VALUES (:user_id, :ip, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $userId);
    $stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
    $stmt->execute();
}

function isAccountLocked($userId, $db) {
    $query = "SELECT COUNT(*) as attempts 
              FROM login_attempts 
              WHERE user_id = :user_id 
              AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['attempts'] >= Security::$MAX_LOGIN_ATTEMPTS;
}
?>