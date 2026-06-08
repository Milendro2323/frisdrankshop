<?php
function login_log(string $msg): void
{
    // Schrijft berichten weg naar een logbestand
    $file = __DIR__ . '/../pages/login.log'; // Pad naar logbestand
    $time = date('Y-m-d H:i:s'); // Huidige datum en tijd
    file_put_contents($file, "[$time] $msg\n", FILE_APPEND); // Bericht toevoegen aan log
}

// ✅ Brute force bescherming
function check_rate_limit(string $email): bool
{
    $key   = 'login_attempts_' . md5($email); // Unieke sessiesleutel per e-mail
    $max   = 5; // Maximaal 5 pogingen
    $decay = 300; // Reset na 300 seconden (5 minuten)

    $attempts = $_SESSION[$key]['count'] ?? 0; // Aantal pogingen ophalen
    $since    = $_SESSION[$key]['since'] ?? time(); // Tijd van eerste poging

    if (time() - $since > $decay) {
        $_SESSION[$key] = ['count' => 0, 'since' => time()]; // Teller resetten
        return true; // Opnieuw proberen toegestaan
    }

    return $attempts < $max; // Controle of limiet bereikt is
}

function increment_attempts(string $email): void
{
    $key = 'login_attempts_' . md5($email); // Unieke sleutel maken
    $_SESSION[$key]['count'] = ($_SESSION[$key]['count'] ?? 0) + 1; // Poging optellen
    $_SESSION[$key]['since'] ??= time(); // Eerste poging opslaan
}

function reset_attempts(string $email): void
{
    unset($_SESSION['login_attempts_' . md5($email)]); // Pogingen verwijderen
}

login_log("Login pagina geladen"); // Vastleggen dat pagina bezocht is

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Alleen uitvoeren bij formulierverzending

    // ✅ CSRF check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        login_log("CSRF check mislukt"); // Foute token loggen
        $error = "Ongeldig verzoek."; // Foutmelding tonen
    } else {
        $email = trim($_POST['email'] ?? ''); // E-mail ophalen
        $pass  = $_POST['password'] ?? ''; // Wachtwoord ophalen

        // ✅ Nooit wachtwoord loggen, e-mail geanonimiseerd
        login_log("Login poging: " . substr($email, 0, 3) . "***"); // Loginpoging registreren

        if (!check_rate_limit($email)) {
            login_log("RATE LIMIT: geblokkeerd"); // Te veel pogingen loggen
            $error = "Te veel pogingen. Probeer het later opnieuw."; // Foutmelding
        } elseif (login_admin($email, $pass)) {
            reset_attempts($email); // Pogingen resetten
            login_log("ADMIN LOGIN SUCCESS"); // Succesvolle admin-login loggen
            header("Location: ?page=admin"); // Doorsturen naar adminpagina
            exit;
        } elseif (login_customer($email, $pass)) {
            reset_attempts($email); // Pogingen resetten
            login_log("CUSTOMER LOGIN SUCCESS"); // Succesvolle klant-login loggen
            header("Location: ?page=account"); // Doorsturen naar accountpagina
            exit;
        } else {
            increment_attempts($email); // Mislukte poging tellen
            login_log("LOGIN FAILED"); // Mislukte login loggen
            $error = "Onjuiste inloggegevens."; // Foutmelding tonen
        }
    }
}
?>

<h2>Inloggen</h2>

<?php if (!empty($error)): ?> <!-- Als $error niet leeg is -->
    <div><?= htmlspecialchars($error) ?></div>  <!-- Toon de foutmelding veilig -->
<?php endif; ?> <!-- Einde van de if -->

<form method="post">
    <!-- ✅ CSRF-token Verborgen invoerveld met de naam csrf_token-->  
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars(generate_csrf_token()) ?>"><!-- Veilige CSRF-token als waarde -->

    <input type="email" name="email" required placeholder="Email">
    <br>
    <input type="password" name="password" required placeholder="Wachtwoord">
    <br>
    <button type="submit">Login</button>
</form>
