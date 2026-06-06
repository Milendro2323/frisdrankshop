<?php
function login_log($msg)
{
    $file = __DIR__ . '/../pages/login.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($file, "[$time] $msg\n", FILE_APPEND);
}

login_log("Login pagina geladen");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    login_log("Login poging: $email");

    // 🔴 Eerst admin check
    if (login_admin($email, $pass)) {
        login_log("ADMIN LOGIN SUCCESS");
        header("Location: ?page=admin");
        exit;
    }

    // 🔵 Dan customer check
    if (login_customer($email, $pass)) {
        login_log("CUSTOMER LOGIN SUCCESS");
        header("Location: ?page=account");
        exit;
    }

    login_log("LOGIN FAILED");
    $error = "Onjuiste inloggegevens.";
}
?>

<h2>Inloggen</h2>

<?php if (!empty($error)): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
  <input type="email" name="email" required placeholder="email">
  <br>
  <input type="password" name="password" required placeholder="wachtwoord">
  <br>
  <button type="submit">Login</button>
</form>
