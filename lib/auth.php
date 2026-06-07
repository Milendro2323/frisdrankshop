<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ CSRF-token genereren
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ✅ CSRF-token valideren
function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Check of admin is ingelogd
function is_admin(): bool
{
    return isset($_SESSION["admin"]) && $_SESSION["admin"] === true;
}

// ⚠️ Hardcoded admin - alleen voor development!
// In productie: gebruik een database met password_hash()
function login_admin(string $email, string $pass): bool
{
    $admin_email = "admin@shop.local";
    $admin_hash  = password_hash("adminmil", PASSWORD_BCRYPT); // eenmalig genereren en opslaan

    if ($email === $admin_email && password_verify($pass, $admin_hash)) {
        session_regenerate_id(true); // ✅ voorkomt sessie-fixatie
        $_SESSION["admin"]       = true;
        $_SESSION["admin_email"] = $email;
        return true;
    }
    return false;
}

// Log de admin uit
function logout_admin(): void
{
    unset($_SESSION["admin"], $_SESSION["admin_id"], $_SESSION["admin_email"]);
    session_regenerate_id(true);
}

// Check of klant is ingelogd
function is_customer_logged_in(): bool
{
    return isset($_SESSION["user_id"]) && $_SESSION["user_id"] > 0;
}

// Haal huidige klant ID op
function get_customer_id(): ?int
{
    return $_SESSION["user_id"] ?? null;
}

// ⚠️ Hardcoded accounts - alleen voor development!
// In productie: gebruik een database met password_hash()
function login_customer(string $email, string $pass): bool
{
    $test_accounts = [
        'jan@example.com' => [
            'password_hash' => password_hash('123', PASSWORD_BCRYPT),
            'id'   => 1001,
            'name' => 'Jan'
        ],
        'lisa@example.com' => [
            'password_hash' => password_hash('klant123', PASSWORD_BCRYPT),
            'id'   => 1002,
            'name' => 'Lisa'
        ]
    ];

    if (isset($test_accounts[$email])) {
        $account = $test_accounts[$email];

        if (password_verify($pass, $account['password_hash'])) {
            session_regenerate_id(true); // ✅ voorkomt sessie-fixatie
            $_SESSION["user_id"]    = $account['id'];
            $_SESSION["user_email"] = $email;
            $_SESSION["user_name"]  = $account['name'];
            return true;
        }
    }

    return false;
}

// Log klant uit
function logout_customer(): void
{
    unset($_SESSION["user_id"], $_SESSION["user_email"], $_SESSION["user_name"]);
    session_regenerate_id(true);
}