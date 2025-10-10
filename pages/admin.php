<?php
// pages/admin.php

// 1) Benodigdheden inladen:
//    - db.php: maakt $conn (mysqli-verbinding) beschikbaar
//    - auth.php: levert is_admin() (en evt. andere auth-functies)
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

// 2) Toegangscontrole: alleen admins mogen deze pagina zien.
//    Zo niet → doorsturen naar login.
if (!is_admin()) {
    header("Location: ?page=login");
    exit;
}

// 3) Mini-queryhelper q():
//    Bestaat q() nog niet (bijv. elders al gedefinieerd)? Dan hier een simpele variant.
//    Doel: wat kortere code bij eenvoudige queries.
//    Let op: deze versie ondersteunt GEEN parameters (dus niet voor user input!).
if (!function_exists('q')) {
    /**
     * Kleine wrapper om een query uit te voeren.
     * @param mysqli $conn  Actieve MySQLi-verbinding
     * @param string $sql   Volledige SQL-string (zonder parameters)
     * @return mysqli_result|false
     *
     * Voor param-gebonden queries (veilig tegen SQL-injectie) gebruik liever
     * een prepared-statement of je uitgebreidere q()-helper met bind_param.
     */
    function q(mysqli $conn, string $sql): mysqli_result|false {
        return $conn->query($sql);
    }
}

// 4) Data ophalen voor het overzicht:
//    We tonen de producten (nieuwste eerst op id dalend).
//    NB: Omdat we hier geen user-input in de SQL stoppen is dit veilig,
//    maar in het algemeen: gebruik prepared statements bij filters/zoektermen.
$rows = q($conn, "SELECT * FROM products ORDER BY id DESC");
?>
<div class="box">
  <!-- 5) Koptekst van het admin-overzicht -->
  <h2>Frisdrank Producten</h2>

  <?php if ($rows && $rows->num_rows > 0): ?>
    <!-- 6) Tabel met productregels (alleen als er resultaten zijn) -->
    <table class="table">
      <tr>
        <th>ID</th>
        <th>Naam</th>
        <th>Prijs</th>
        <th>Merk</th>
      </tr>

      <?php while ($r = $rows->fetch_assoc()): ?>
        <tr>
          <!-- ID: als integer casten voor zekerheid -->
          <td><?php echo (int)$r['id']; ?></td>

          <!-- Naam: HTML-escapen om XSS te voorkomen -->
          <td><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>

          <!-- Prijs: netjes opmaken met 2 decimalen, komma's volgens NL notatie -->
          <td>€<?php
            $price = isset($r['price']) ? (float)$r['price'] : 0;
            echo number_format($price, 2, ',', '.');
          ?></td>

          <!-- Merk: ook escapen -->
          <td><?php echo htmlspecialchars($r['brand'] ?? ''); ?></td>
        </tr>
      <?php endwhile; ?>
    </table>

  <?php else: ?>
    <!-- 7) Lege-staat bericht als er (nog) geen producten zijn -->
    <p>Er zijn nog helaas geen producten gevonden.</p>
  <?php endif; ?>

  <!-- 8) Snelle admin-acties (navigatie terug en uitloggen) -->
  <ul class="admin-actions" style="margin-top:1rem; list-style:none; padding-left:0;">
    <li style="display:inline-block; margin-right:1rem;">
      <a href="?page=home">← Terug naar de producten</a>
    </li>
    <li style="display:inline-block;">
      <a href="?page=logout">Logout</a>
    </li>
  </ul>
</div>

