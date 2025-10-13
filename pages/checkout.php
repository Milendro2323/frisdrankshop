<?php
// checkout.php

// Vereist: session_start(), $conn (mysqli), q() helper met prepared statements
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// CSRF-token (eenvoudig)
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

// Mand ophalen
$ids    = array_keys($_SESSION['cart']);
$items  = [];
$total  = 0.0;

if ($ids) {
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $res = q($conn, "SELECT id,name,price,image FROM products WHERE id IN ($in)", $ids);
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
    // Basis checks: CSRF + niet lege mand
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = "Beveiligingsfout. Probeer opnieuw.";
    } elseif (!$items) {
        $error = "Je mand is leeg.";
    } else {
        // Velden lezen + simpele validatie
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $addr  = trim($_POST['address'] ?? '');
        $zip   = trim($_POST['postal_code'] ?? '');
        $city  = trim($_POST['city'] ?? '');
        if ($name==='' || $email==='' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $addr==='' || $zip==='' || $city==='') {
            $error = "Vul alle velden correct in.";
        } else {
            // Transactie: order + items + voorraad
            $conn->begin_transaction();
            try {
                // Order opslaan
                q($conn,
                  "INSERT INTO orders (customer_name,email,address,postal_code,city,total,created_at)
                   VALUES (?,?,?,?,?,?,NOW())",
                  [$name,$email,$addr,$zip,$city,$total]
                );
                $order_id = $conn->insert_id;

                // Items opslaan + voorraad verlagen
                foreach ($items as $it) {
                    q($conn,
                      "INSERT INTO order_items (order_id,product_id,name,unit_price,qty,line_total)
                       VALUES (?,?,?,?,?,?)",
                      [$order_id,$it['id'],$it['name'],(float)$it['price'],$it['qty'],$it['line_total']]
                    );
                    q($conn,
                      "UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?",
                      [$it['qty'],$it['id']]
                    );
                }

                $conn->commit();
                $_SESSION['cart'] = [];    // mand leeg
                $success = "Bedankt! Je bestelling (#{$order_id}) is geplaatst.";
            } catch (Throwable $e) {
                $conn->rollback();
                $error = "Er ging iets mis bij het afronden.";
            }
        }
    }
}
?>

<h2 class="title">Afrekenen</h2>

<?php if ($success): ?>
  <!-- Bevestiging -->
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <p>Je ontvangt een bevestiging op e-mail: <?= htmlspecialchars($_POST['email'] ?? '') ?>.</p>
  <p><a class="btn" href="?page=home">Verder winkelen</a></p>

<?php else: ?>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$items): ?>
    <!-- Geen items -->
    <p class="muted">Je mand is leeg.</p>
    <p><a class="btn" href="?page=home">Terug naar producten</a></p>

  <?php else: ?>
    <!-- Samenvatting bestelling -->
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

    <!-- Afrekenformulier: klantgegevens -->
    <h3 class="mt">Gegevens</h3>
    <form method="post" class="form">
      <input type="hidden" name="action" value="place">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

      <!-- Korte labels/inputs; required voor basisvalidatie in browser -->
      <label>Naam
        <input class="input" type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </label>
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

      <div class="row mt">
        <a class="btn ghost" href="?page=cart">← Terug naar mand</a>
        <button class="btn">Bestelling afronden</button>
      </div>
    </form>
  <?php endif; ?>

<?php endif; ?>
