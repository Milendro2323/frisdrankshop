<?php
// Start winkelmand in sessie als associatieve array: [product_id => qty]
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Verwerk POST-acties: add, set (aantal bijwerken), clear (leegmaken)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);

    // Product toevoegen (minimaal 1), daarna redirect om herpost te voorkomen
    if ($action==='add' && $id>0) {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
        header("Location: ?page=cart"); exit;

    // Aantal instellen (0 = verwijderen), daarna redirect
    } elseif ($action==='set' && $id>0) {
        $qty = max(0, (int)($_POST['qty'] ?? 0));
        if ($qty<=0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id] = $qty;
        header("Location: ?page=cart"); exit;

    // Hele mand leegmaken
    } elseif ($action==='clear') {
        $_SESSION['cart'] = [];
        header("Location: ?page=cart"); exit;
    }
}

// Productregels voor de mand ophalen
$ids = array_keys($_SESSION['cart']);
$items = [];
$total = 0.0;

if ($ids) {
    // Veilige IN-lijst met placeholders
    $in  = implode(',', array_fill(0,count($ids),'?'));
    // Haal alleen nodige velden op
    $res = q($conn, "SELECT id,name,brand,price,image FROM products WHERE id IN ($in)", $ids);

    // Bereken per regel: qty en regel-totaal; tel op bij totaal
    while ($r = $res->fetch_assoc()) {
        $qty = (int)($_SESSION['cart'][$r['id']] ?? 0);
        $r['qty'] = $qty;
        $r['line_total'] = $qty * (float)$r['price'];
        $total += $r['line_total'];
        $items[] = $r;
    }
}
?>
<h2 class="title">Winkelmand</h2>

<?php if (!$items): ?>
  <!-- Lege mand melding -->
  <p class="muted">Je mand is leeg.</p>

<?php else: ?>
  <!-- Overzicht met regels: afbeelding, naam/merk/prijs, qty-form, regelprijs -->
  <div class="cart">
  <?php foreach($items as $it): ?>
    <div class="cart-row">
      <img src="assets/img/<?= htmlspecialchars($it['image']) ?>" alt="">
      <div class="grow">
        <div class="bold"><?= htmlspecialchars($it['name']) ?></div>
        <div class="muted"><?= htmlspecialchars($it['brand']) ?></div>
        <div class="muted">€<?= number_format((float)$it['price'],2,",",".") ?> / stuk</div>
      </div>

      <!-- Aantal bijwerken: min=0 laat verwijderen toe -->
      <form method="post" class="row">
        <input type="hidden" name="action" value="set">
        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
        <input class="input" type="number" name="qty" min="0" value="<?= (int)$it['qty'] ?>" style="max-width:100px">
        <button class="btn ghost">Update</button>
      </form>

      <!-- Regel-totaal -->
      <div class="price">€<?= number_format((float)$it['line_total'],2,",",".") ?></div>
    </div>
  <?php endforeach; ?>
  </div>

  <!-- Eindtotaal + acties: mand leegmaken en naar checkout -->
  <div class="total">Totaal: <strong>€<?= number_format((float)$total,2,",",".") ?></strong></div>
  <div class="row mt">
    <form method="post">
      <input type="hidden" name="action" value="clear">
      <button class="btn ghost">Leeg mand</button>
    </form>
    <a class="btn" href="?page=checkout">Afrekenen</a>
  </div>
<?php endif; ?>
