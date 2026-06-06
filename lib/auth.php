<?php
// Start de sessie als die nog niet loopt
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check of admin is ingelogd
function is_admin()
{
    return isset($_SESSION["admin"]) && $_SESSION["admin"] === true;
}

// Log de admin in met vaste gegevens (hardcoded fallback)
function login_admin($email, $pass)
{
    if ($email === "admin@shop.local" && $pass === "adminmil") {
        $_SESSION["admin"] = true;
        $_SESSION["admin_email"] = $email;
        return true;
    }
    return false;
}

// Log de admin uit
function logout_admin()
{
    unset($_SESSION["admin"]);
    unset($_SESSION["admin_id"]);
    unset($_SESSION["admin_email"]);
}

// Check of klant is ingelogd
function is_customer_logged_in()
{
    return isset($_SESSION["user_id"]) && $_SESSION["user_id"] > 0;
}

// Haal huidige klant ID op
function get_customer_id()
{
    return $_SESSION["user_id"] ?? null;
}

// Log klant in met vaste gegevens (hardcoded - werkt altijd!)
function login_customer($email, $pass)
{
    // Hardcoded test accounts - werken ALTIJD, ook zonder database
    $test_accounts = [
        'jan@example.com' => [
            'password' => '123',
            'id' => 1001,
            'name' => 'Jan'
        ],
        'lisa@example.com' => [
            'password' => 'klant123',
            'id' => 1002,
            'name' => 'Lisa'
        ]
    ];
    
    // Check of het een test account is
    if (isset($test_accounts[$email])) {
        $account = $test_accounts[$email];
        
        // Verificeer wachtwoord
        if ($pass === $account['password']) {
            $_SESSION["user_id"] = $account['id'];
            $_SESSION["user_email"] = $email;
            $_SESSION["user_name"] = $account['name'];
            return true;
        }
    }
    
    return false;
}

// Log klant uit
function logout_customer()
{
    unset($_SESSION["user_id"]);
    unset($_SESSION["user_email"]);
    unset($_SESSION["user_name"]);
}