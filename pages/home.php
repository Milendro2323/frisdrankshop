<?php
// Check DB-verbinding ($conn moet mysqli zijn)
if (!isset($conn) || !($conn instanceof mysqli)) { die('DB'); } // Stop als er geen geldige DB-verbinding is

// Lees filters uit querystring en trim spaties
$qs    = trim($_GET['q'] ?? '');     // Zoekterm uit URL
$brand = trim($_GET['brand'] ?? ''); // Merk filter uit URL
$min   = trim($_GET['min'] ?? '');   // Min prijs uit URL
$max   = trim($_GET['max'] ?? '');   // Max prijs uit URL

// Haal lijst met unieke merken (voor het select-filter)
$brands = [];
$res = q($conn, "SELECT DISTINCT brand FROM products WHERE brand<>'' ORDER BY brand"); // Haal alle unieke merken op
while ($row = $res->fetch_assoc()) { $brands[] = $row['brand']; } // Zet merken in array

// Bouw veilige zoekquery met prepared params
$sql = "SELECT id,name,brand,price,flavor,image,stock FROM products WHERE 1=1"; // Basis query
$params = [];
if ($qs !== '')             { $sql .= " AND name LIKE ?"; $params[] = "%$qs%"; }       // zoek op naam
if ($brand !== '')          { $sql .= " AND brand = ?";   $params[] = $brand; }        // filter merk
if ($min !== '' && is_numeric($min)) { $sql .= " AND price >= ?"; $params[] = (float)$min; } // min prijs
if ($max !== '' && is_numeric($max)) { $sql .= " AND price <= ?"; $params[] = (float)$max; } // max prijs
$sql .= " ORDER BY name";

// Uitvoeren met helper q() die prepared statements ondersteunt
$products = q($conn, $sql, $params); // Voer de query uit met de filters
?>
<h2 class="title">Assortiment</h2>

<!-- Filterformulier: zoek, merk, min/max prijs -->
<form class="row mb" method="get" action="index.php">
  <input type="hidden" name="page" value="home">
  <input class="input" name="q" placeholder="Zoek op naam…" value="<?= htmlspecialchars($qs) ?>"> <!-- Zoekbalk -->
  <select class="input" name="brand">
    <option value="">Alle merken</option>
    <?php foreach($brands as $b): ?>
      <option value="<?= htmlspecialchars($b) ?>" <?= $brand===$b?'selected':'' ?>><?= htmlspecialchars($b) ?></option> <!-- Merkopties -->
    <?php endforeach; ?>
  </select>
  <input class="input" type="number" step="0.01" min="0" name="min" placeholder="Min €" value="<?= htmlspecialchars($min) ?>"> <!-- Min prijs -->
  <input class="input" type="number" step="0.01" min="0" name="max" placeholder="Max €" value="<?= htmlspecialchars($max) ?>"> <!-- Max prijs -->
  <button class="btn">Filter</button>
  <a class="btn ghost" href="?page=home">Reset</a> <!-- Reset alle filters -->
</form>

<!-- Productkaarten grid -->
<div class="grid">
<?php while ($p = $products->fetch_assoc()): ?> <!-- Loop door alle producten -->
  <div class="card">
    <!-- Productafbeelding (alt = naam) -->
    <img src="assets/img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
    <div class="p">
      <!-- Merkbadge en titel -->
      <div class="badge"><?= htmlspecialchars($p['brand']) ?></div>
      <h3><?= htmlspecialchars($p['name']) ?></h3>

      <!-- Smaak tonen, fallback '—' -->
      <div class="muted">smaak: <?= htmlspecialchars($p['flavor'] ?: '—') ?></div>

      <!-- Prijs in NL-notatie -->
      <div class="price">€<?= number_format((float)$p['price'], 2, ",", ".") ?></div> <!-- Bijv. 1.234,56 -->

      <!-- Toevoegen aan mand met plus/min knoppen -->
      <form method="post" action="?page=cart">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>"> <!-- Product ID -->
        <div class="row">
          <input id="qty<?= (int)$p['id'] ?>" class="input" type="number" name="qty" value="1" min="1" style="max-width:100px"> <!-- Aantal -->
          <button type="button" class="btn ghost" onclick="dec('qty<?= (int)$p['id'] ?>')">-</button> <!-- Verminder aantal -->
          <button type="button" class="btn ghost" onclick="inc('qty<?= (int)$p['id'] ?>')">+</button> <!-- Verhoog aantal -->
        </div>
        <button class="btn">In mand</button>
      </form>

      <!-- Info-knop opent paneel met details uit data-* attributen -->
      <button
        class="btn ghost mt info-btn"
        data-name="<?= htmlspecialchars($p['name']) ?>"
        data-brand="<?= htmlspecialchars($p['brand']) ?>"
        data-flavor="<?= htmlspecialchars($p['flavor'] ?: '—') ?>"> <!-- Sla productdata op in data-attributen -->
        Info
      </button>
    </div>
  </div>
<?php endwhile; ?>
</div>

<!-- Info-paneel (modal/panel) -->
<div id="info-panel" class="panel" hidden> <!-- Standaard verborgen -->
  <div class="panel__inner">
    <button class="panel__close" type="button" onclick="closePanel()">×</button> <!-- Sluit paneel -->
    <h3 id="info-title">Productinfo</h3>
    <div id="info-body" class="muted"></div> <!-- Productdetails komen hier -->
  </div>
</div>

<script>
// Klein IIFE: regelt info-paneel interactie
(function(){
  const panel = document.getElementById('info-panel');
  const title = document.getElementById('info-title');
  const body  = document.getElementById('info-body');

  // Sluitknop handler
  window.closePanel = ()=>{ panel.hidden=true; }; // Verberg het paneel

  // Klik op Info-knop: vul titel/tekst en toon paneel
  document.addEventListener('click', (e)=>{
    const b = e.target.closest('.info-btn');
    if(!b) return; // Niet op info-knop geklikt, stop
    title.textContent = b.dataset.name; // Zet productnaam als titel
    body.innerHTML = `<p><strong>Merk:</strong> ${b.dataset.brand}</p>
                      <p><strong>Smaak:</strong> ${b.dataset.flavor}</p>
                      <p>Korte beschrijving van de frisdrank. Dit kun je later uitbreiden.</p>`;
    panel.hidden = false; // Toon het paneel
  });
})();
</script>

