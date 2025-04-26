<?php
class Security {
    // Configuration JWT
    public static $JWT_SECRET = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c";
    public static $JWT_EXPIRE = 3600; // 1 heure
    public static $JWT_REMEMBER_EXPIRE = 604800; // 7 jours
    
    // Configuration mot de passe
    public static $PASSWORD_ALGO = PASSWORD_ARGON2ID;
    public static $PASSWORD_OPTIONS = [
        'memory_cost' => 1024,
        'time_cost' => 2,
        'threads' => 2
    ];
    
    // Protection contre le bruteforce
    public static $MAX_LOGIN_ATTEMPTS = 5;
    public static $LOGIN_TIMEOUT = 300; // 5 minutes
    
    // Configuration CSRF
    public static $CSRF_TOKEN_LIFETIME = 3600;
}
?>