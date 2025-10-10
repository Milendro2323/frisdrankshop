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

// Log de admin in met vaste gegevens
function login_admin($email, $pass)
{
    if ($email === "admin@shop.local" && $pass === "adminmil") {
        $_SESSION["admin"] = true;
        return true;
    }
    return false;
}

// Log de admin uit
function logout_admin()
{
    unset($_SESSION["admin"]);
}

