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

// Log klant in via database check
function login_customer($conn, $email, $pass)
{
    // Haal gebruiker op uit database
    $stmt = $conn->prepare("SELECT id, password_hash, role FROM users WHERE email = ? AND role = 'customer'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Verificeer wachtwoord
        if (password_verify($pass, $row['password_hash'])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["user_email"] = $email;
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
}
