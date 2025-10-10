<?php
// pages/login.php
// Eenvoudige admin-login. Verwacht login_admin($email, $pass) in auth.php.
// Zorg dat session_start() al is aangeroepen in je bootstrap.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees formwaarden; gebruik defaults om notices te voorkomen
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    // Probeer in te loggen; bij succes door naar admin, anders foutmelding
    if (login_admin($email, $pass)) {
        header("Location: ?page=admin");
        exit;
    } else {
        $error = "Onjuiste inloggegevens.";
    }
}
?>
<h2>Inloggen</h2>

<?php if (!empty($error)): ?>
  <!-- Veilige weergave van fouttekst -->
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Simpel loginformulier; required valideert in de browser -->
<form method="post" class="form">
  <label>
    E-mail
    <!-- Behoud ingevulde e-mail bij fout; HTML-escape tegen XSS -->
    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
  </label>
  <br>
  <label>
    Wachtwoord
    <!-- Wachtwoord nooit prefllen -->
    <input type="password" name="password" required>
  </label>
  <br>
  <button type="submit">Inloggen</button>
</form>
