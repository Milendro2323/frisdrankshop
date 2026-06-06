<?php
// pages/customer_login.php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

// Als al ingelogd, doorsturen naar home
if (is_customer_logged_in()) {
    header("Location: ?page=home");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    if (login_customer($conn, $email, $pass)) {
        // Success: doorsturen naar checkout of home
        $redirect = $_GET['redirect'] ?? 'home';
        header("Location: ?page=" . urlencode($redirect));
        exit;
    } else {
        $error = "Onjuiste inloggegevens.";
    }
}
?>

<div class="box">
  <h2>Inloggen als klant</h2>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="form">
    <label>
      E-mail
      <input class="input" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label>

    <label>
      Wachtwoord
      <input class="input" type="password" name="password" required>
    </label>

    <button class="btn" type="submit">Inloggen</button>
  </form>

  <div style="margin-top:1rem;">
    <p class="muted">Test accounts:</p>
    <ul class="muted" style="font-size:0.9em;">
      <li>jan@example.com / klant123</li>
      <li>lisa@example.com / klant123</li>
    </ul>
  </div>

  <div style="margin-top:1rem;">
    <a href="?page=home">← Terug naar shop</a>
  </div>
</div>