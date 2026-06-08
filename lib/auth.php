<?php
// Start een sessie zodat gebruikersgegevens kunnen worden opgeslagen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//
// Genereert een unieke CSRF-token en slaat deze op in de sessie
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Controleert of de ontvangen CSRF-token geldig is
function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}



//Functies voor het inloggen, controleren en uitloggen van admins.
// Controleert of een admin is ingelogd
function is_admin(): bool
{
    return isset($_SESSION["admin"]) && $_SESSION["admin"] === true;
}

/*
| Hardcoded admin-account voor development.
| In een echte webshop worden gegevens uit de database gehaald.
*/
function login_admin(string $email, string $pass): bool
{
    $admin_email = "admin@shop.local";

    // Versleuteld wachtwoord
    $admin_hash = password_hash("adminmil", PASSWORD_BCRYPT);

    // Controleert e-mail en wachtwoord
    if ($email === $admin_email && password_verify($pass, $admin_hash)) {

        // Nieuwe sessie-ID voorkomt sessie-fixatie
        session_regenerate_id(true);

        $_SESSION["admin"] = true;
        $_SESSION["admin_email"] = $email;

        return true;
    }

    return false;
}

// Logt de admin uit en verwijdert sessiegegevens
function logout_admin(): void
{
    unset(
        $_SESSION["admin"],
        $_SESSION["admin_id"],
        $_SESSION["admin_email"]
    );

    session_regenerate_id(true);
}


// Functies voor klanten die inloggen in de webshop.
// Controleert of een klant is ingelogd
function is_customer_logged_in(): bool
{
    return isset($_SESSION["user_id"]) && $_SESSION["user_id"] > 0;
}

// Geeft het ID van de ingelogde klant terug
function get_customer_id(): ?int
{
    return $_SESSION["user_id"] ?? null;
}

/*
| Hardcoded testaccounts voor development.
| In productie worden klanten uit de database opgehaald.
*/
function login_customer(string $email, string $pass): bool
{
    $test_accounts = [

        // Testaccount Jan
        'jan@example.com' => [
            'password_hash' => password_hash('123', PASSWORD_BCRYPT),
            'id' => 1001,
            'name' => 'Jan'
        ],

        // Testaccount Lisa
        'lisa@example.com' => [
            'password_hash' => password_hash('klant123', PASSWORD_BCRYPT),
            'id' => 1002,
            'name' => 'Lisa'
        ]
    ];

    // Controleert of het account bestaat
    if (isset($test_accounts[$email])) {

        $account = $test_accounts[$email];

        // Controleert of het wachtwoord klopt
        if (password_verify($pass, $account['password_hash'])) {

            // Nieuwe sessie-ID voor extra beveiliging
            session_regenerate_id(true);

            $_SESSION["user_id"] = $account['id'];
            $_SESSION["user_email"] = $email;
            $_SESSION["user_name"] = $account['name'];

            return true;
        }
    }

    return false;
}

// Logt de klant uit en verwijdert sessiegegevens
function logout_customer(): void
{
    unset(
        $_SESSION["user_id"],
        $_SESSION["user_email"],
        $_SESSION["user_name"]
    );

    session_regenerate_id(true);
}