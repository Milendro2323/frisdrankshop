<?php
function login_log(string $msg): void
{
    // ✅ Buiten de publieke map
    $file = __DIR__ . '/../pages/login.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($file, "[$time] $msg\n", FILE_APPEND);
}

// ✅ Brute force bescherming
function check_rate_limit(string $email): bool
{
    $key   = 'login_attempts_' . md5($email);
    $max   = 5;
    $decay = 300;

    $attempts = $_SESSION[$key]['count'] ?? 0;
    $since    = $_SESSION[$key]['since'] ?? time();

    if (time() - $since > $decay) {
        $_SESSION[$key] = ['count' => 0, 'since' => time()];
        return true;
    }

    return $attempts < $max;
}

function increment_attempts(string $email): void
{
    $key = 'login_attempts_' . md5($email);
    $_SESSION[$key]['count'] = ($_SESSION[$key]['count'] ?? 0) + 1;
    $_SESSION[$key]['since'] ??= time();
}

function reset_attempts(string $email): void
{
    unset($_SESSION['login_attempts_' . md5($email)]);
}

login_log("Login pagina geladen");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ CSRF check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        login_log("CSRF check mislukt");
        $error = "Ongeldig verzoek.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        // ✅ Nooit wachtwoord loggen, e-mail geanonimiseerd
        login_log("Login poging: " . substr($email, 0, 3) . "***");

        if (!check_rate_limit($email)) {
            login_log("RATE LIMIT: geblokkeerd");
            $error = "Te veel pogingen. Probeer het later opnieuw.";
        } elseif (login_admin($email, $pass)) {
            reset_attempts($email);
            login_log("ADMIN LOGIN SUCCESS");
            header("Location: ?page=admin");
            exit;
        } elseif (login_customer($email, $pass)) {
            reset_attempts($email);
            login_log("CUSTOMER LOGIN SUCCESS");
            header("Location: ?page=account");
            exit;
        } else {
            increment_attempts($email);
            login_log("LOGIN FAILED");
            $error = "Onjuiste inloggegevens.";
        }
    }
}
?>

<h2>Inloggen</h2>

<?php if (!empty($error)): ?>
    <div><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
    <!-- ✅ CSRF-token -->
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars(generate_csrf_token()) ?>">

    <input type="email" name="email" required placeholder="Email">
    <br>
    <input type="password" name="password" required placeholder="Wachtwoord">
    <br>
    <button type="submit">Login</button>
</form>
