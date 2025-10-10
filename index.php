<?php
// Basis includes:
// - config.php: algemene instellingen (paths, constants)
// - lib/db.php: maakt DB-verbinding ($conn) beschikbaar
// - lib/auth.php: auth helpers (is_admin(), login_admin(), logout_admin(), etc.)
require __DIR__ . "/config.php";
require __DIR__ . "/lib/db.php";
require __DIR__ . "/lib/auth.php";

// Eenvoudige router via ?page=... met allowlist ter beveiliging
$page = $_GET['page'] ?? 'home';
$allowed = ['home','cart','checkout','login','logout','admin'];
if (!in_array($page, $allowed)) { $page = 'home'; }
?>
<!doctype html>
<html lang="nl">
<head>
  <!-- Charset + viewport voor juiste weergave -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Frisdrankshop</title>
  <!-- Externe stylesheet (zorg dat assets/css/style.css bestaat) -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="hdr">
  <div class="wrap">
    <!-- Logo linkt naar productoverzicht -->
    <a class="logo" href="?page=home">Frisdrankshop</a>

    <!-- Navigatie: toon Admin/Logout alleen als ingelogd als admin -->
    <nav>
      <a href="?page=home">Producten</a>
      <a href="?page=cart">Winkelmand</a>
      <?php if (is_admin()): ?>
        <a href="?page=admin">Admin</a>
        <a href="?page=logout">Logout</a>
      <?php else: ?>
        <a href="?page=login">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- Hero/banners-sectie met korte uitleg -->
<section class="hero">
  <div class="wrap">
    <h1>Frisdranken</h1>
    <p>Zoek op merk en prijs. Voeg toe aan je mand en bestel eenvoudig.</p>
  </div>
</section>

<main class="wrap">
  <?php
  // Laad de paginacontent uit /pages/*.php
  // NB: $page is al gevalideerd via $allowed om LFI te voorkomen
  include __DIR__ . "/pages/" . $page . ".php";
  ?>
</main>

<!-- Footer met jaartal/credit -->
<footer class="ftr"><div class="wrap">© Frisdrankshop Milendro 2025</div></footer>

<!-- Optionele JS (bijv. qty +/-, info-panel) -->
<script src="assets/script.js"></script>
</body>
</html>

