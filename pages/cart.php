<?php
// Start mand in sessie als map: [product_id => qty]
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = []; // Winkelmand initialiseren

// Verwerk POST-acties: add / set / clear
if ($_SERVER['REQUEST_METHOD']==='POST') { // Controleren op formulierverzending
    $action = $_POST['action'] ?? ''; // Actie ophalen
    $id  = (int)($_POST['id'] ?? 0); // Product-ID ophalen

    // Toevoegen: qty min. 1, daarna redirect (PRG-patroon)
    if ($action==='add' && $id>0) {
        $qty = max(1, (int)($_POST['qty'] ?? 1)); // Minimum aantal bepalen
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty; // Product toevoegen
        header("Location: ?page=cart"); exit; // Redirect uitvoeren

    // Aantal instellen: 0 verwijdert item
    } elseif ($action==='set' && $id>0) {
        $qty = max(0, (int)($_POST['qty'] ?? 0)); // Nieuw aantal ophalen
        if ($qty<=0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id] = $qty; // Product verwijderen of updaten
        header("Location: ?page=cart"); exit; // Redirect uitvoeren

    // Hele mand leegmaken
    } elseif ($action==='clear') {
        $_SESSION['cart'] = []; // Winkelmand leegmaken
        header("Location: ?page=cart"); exit; // Redirect uitvoeren
    }
}

// Mand-items ophalen uit DB voor weergave
$ids = array_keys($_SESSION['cart']); // Product-ID's ophalen
$items = []; // Producten opslaan
$total = 0.0; // Totaalbedrag initialiseren

if ($ids) {
    // Veilige IN-lijst via placeholders
    $in  = implode(',', array_fill(0,count($ids),'?')); // SQL placeholders maken
    $res = q($conn, "SELECT id,name,brand,price,image FROM products WHERE id IN ($in)", $ids); // Producten ophalen

    // Voor elk product: qty + regelbedrag berekenen en totaal ophogen
    while ($r = $res->fetch_assoc()) {
        $qty = (int)($_SESSION['cart'][$r['id']] ?? 0); // Aantal ophalen
        $r['qty'] = $qty; // Aantal opslaan
        $r['line_total'] = $qty * (float)$r['price']; // Regelbedrag berekenen
        $total += $r['line_total']; // Totaal verhogen
        $items[] = $r; // Product toevoegen aan array
    }
}
?>

<h2 class="title">Winkelmand</h2> <!-- Titel tonen -->

<?php if (!$items): ?>
  <!-- Lege staat -->
  <p class="muted">Je mand is leeg.</p> <!-- Lege winkelmand melding -->

<?php else: ?>
  <!-- Lijst met mandregels -->
  <div class="cart">
  <?php foreach($items as $it): ?> <!-- Door alle producten lopen -->
    <div class="cart-row">

      <img src="assets/img/<?= htmlspecialchars($it['image']) ?>" alt=""> <!-- Productafbeelding -->

      <div class="grow">
        <div class="bold"><?= htmlspecialchars($it['name']) ?></div> <!-- Productnaam -->
        <div class="muted"><?= htmlspecialchars($it['brand']) ?></div> <!-- Merknaam -->
        <div class="muted">€<?= number_format((float)$it['price'],2,",",".") ?> / stuk</div> <!-- Prijs per stuk -->
      </div>

      <form method="post" class="row"> <!-- Formulier voor update -->
        <input type="hidden" name="action" value="set"> <!-- Actie instellen -->
        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>"> <!-- Product-ID meesturen -->
        <input class="input" type="number" name="qty" min="0" value="<?= (int)$it['qty'] ?>" style="max-width:100px"> <!-- Nieuw aantal -->
        <button class="btn ghost">Update</button> <!-- Update knop -->
      </form>

      <div class="price">€<?= number_format((float)$it['line_total'],2,",",".") ?></div> <!-- Regelbedrag -->
    </div>
  <?php endforeach; ?> <!-- Einde productlus -->
  </div>

  <div class="total">Totaal: <strong>€<?= number_format((float)$total,2,",",".") ?></strong></div> <!-- Totaalbedrag -->

  <div class="row mt">
    <form method="post">
      <input type="hidden" name="action" value="clear"> <!-- Winkelmand leegmaken -->
      <button class="btn ghost">Leeg mand</button>
    </form>

    <a class="btn" href="?page=checkout">Afrekenen</a> <!-- Naar checkout -->
  </div>
<?php endif; ?>