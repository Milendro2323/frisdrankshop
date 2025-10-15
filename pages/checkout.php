<?php
// checkout.php

// Vereist: session_start(), $conn (mysqli), q() helper met prepared statements

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Eenvoudig CSRF-token (tegen vervalste formulieren)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// Mand ophalen en totaal berekenen
$ids    = array_keys($_SESSION['cart']);
$items  = [];
$total  = 0.0;

if ($ids) {
    // IN-lijst bouwen met placeholders, veilig via prepared statement
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $res = q($conn, "SELECT id,name,price,image FROM products WHERE id IN ($in)", $ids);

    // Voor elk product: aantal en regeltotaal bepalen
    while ($r = $res->fetch_assoc()) {
        $qty = (int)($_SESSION['cart'][$r['id']] ?? 0);
        if ($qty <= 0) continue;
        $r['qty'] = $qty;
        $r['line_total'] = $qty * (float)$r['price'];
        $total += $r['line_total'];
        $items[] = $r;
    }
}

// Afhandelen “Bestelling plaatsen”
$success = null; $error = null; $order_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place') {
    // 1) Basis checks: CSRF en niet-lege mand
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = "Beveiligingsfout. Probeer opnieuw.";
    } elseif (!$items) {
        $error = "Je mand is leeg.";
    } else {
        // 2) Velden lezen en simpel valideren
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $addr       = trim($_POST['address']    ?? '');
        $zip        = trim($_POST['postal_code']?? '');
        $city       = trim($_POST['city']       ?? '');
        $country    = trim($_POST['country']    ?? 'Nederland');

        // Vereiste velden + geldig e-mailadres
        if ($first_name==='' || $last_name==='' || $email==='' || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $addr==='' || $zip==='' || $city==='') {
            $error = "Vul alle velden correct in.";
        } else {
            // 3) Transactie: order + items + voorraad (alles of niets)
            $conn->begin_transaction();
            try {
                // Order opslaan
                q(
                    $conn,
                    "INSERT INTO orders (first_name,last_name,email,address,postal_code,city,country,total,created_at)
                     VALUES (?,?,?,?,?,?,?,?,NOW())",
                    [$first_name,$last_name,$email,$addr,$zip,$city,$country,$total]
                );
                $order_id = $conn->insert_id;

                // Orderregels opslaan en voorraad bijwerken
                foreach ($items as $it) {
                    // Eén rij per besteld product
                    q(
                        $conn,
                        "INSERT INTO order_items (order_id,product_id,quantity,unit_price)
                         VALUES (?,?,?,?)",
                        [$order_id,$it['id'],$it['qty'],(float)$it['price']]
                    );
                    // Voorraad verminderen, maar nooit onder 0
                    q(
                        $conn,
                        "UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?",
                        [$it['qty'],$it['id']]
                    );
                }

                // Alles gelukt: vastleggen, mand leegmaken, melding tonen
                $conn->commit();
                $_SESSION['cart'] = [];
                $success = "Bedankt voor je bestelling.";
            } catch (Throwable $e) {
                // Fout: alles terugdraaien en foutmelding tonen
                $conn->rollback();
                $error = "Er ging iets mis bij het afronden.";
            }
        }
    }
}
?>

<h2 class="title">Afrekenen</h2>

<?php if ($success): ?>
  <!-- Succesmelding en knop om verder te winkelen -->
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <br>
  <p><a class="btn" href="?page=home">Verder winkelen</a></p>

<?php else: ?>

  <?php if ($error): ?>
    <!-- Foutmelding bij validatie/CSRF/DB-fout -->
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$items): ?>
    <!-- Lege mand -->
    <p class="muted">Je mand is leeg.</p>
    <p><a class="btn" href="?page=home">Terug naar producten</a></p>

  <?php else: ?>
    <!-- Samenvatting van de bestelling -->
    <div class="cart">
      <?php foreach ($items as $it): ?>
        <div class="cart-row">
          <img src="assets/img/<?= htmlspecialchars($it['image']) ?>" alt="">
          <div class="grow">
            <div class="bold"><?= htmlspecialchars($it['name']) ?></div>
            <div class="muted">€<?= number_format((float)$it['price'],2,",",".") ?> / stuk</div>
            <div class="muted">Aantal: <?= (int)$it['qty'] ?></div>
          </div>
          <div class="price">€<?= number_format((float)$it['line_total'],2,",",".") ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="total">Totaal: <strong>€<?= number_format((float)$total,2,",",".") ?></strong></div>

    <!-- Afrekenformulier met vereiste klantgegevens -->
    <h3 class="mt">Gegevens</h3>
    <form method="post" class="form">
      <!-- Actie + CSRF-token verplicht meesturen -->
      <input type="hidden" name="action" value="place">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

      <div class="row">
        <label style="flex:1">Voornaam
          <input class="input" type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
        </label>
        <label style="flex:1">Achternaam
          <input class="input" type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
        </label>
      </div>

      <label>E-mail
        <input class="input" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </label>
      <label>Adres
        <input class="input" type="text" name="address" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
      </label>
      <div class="row">
        <label style="flex:1">Postcode
          <input class="input" type="text" name="postal_code" required value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
        </label>
        <label style="flex:2">Plaats
          <input class="input" type="text" name="city" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </label>
      </div>
      <label>Land
        <input class="input" type="text" name="country" required value="<?= htmlspecialchars($_POST['country'] ?? '') ?>">
      </label>

      <div class="row mt">
        <!-- Terugknop naar mand + verzendknop -->
        <a class="btn ghost" href="?page=cart">← Terug naar mand</a>
        <button class="btn">Bestelling afronden</button>
      </div>
    </form>
  <?php endif; ?>

<?php endif; ?>

