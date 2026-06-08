<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';

if (!is_customer_logged_in()) {
    header("Location: ?page=customer_login");
    exit;
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// CSRF-token aanmaken als die er nog niet is
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$ids   = array_keys($_SESSION['cart']);
$items = [];
$total = 0.0;

if ($ids) {
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $res = q($conn, "SELECT id,name,price FROM products WHERE id IN ($in)", $ids);

    while ($r = $res->fetch_assoc()) {
        $qty = (int)($_SESSION['cart'][$r['id']] ?? 0);
        if ($qty <= 0) continue;
        $r['qty']        = $qty;
        $r['line_total'] = $qty * (float)$r['price'];
        $total          += $r['line_total'];
        $items[]         = $r;
    }
}

$success  = false;
$order_id = null;
$error    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) CSRF-controle
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = "Beveiligingsfout. Probeer opnieuw.";

    // 2) Lege mand
    } elseif (!$items) {
        $error = "Je mand is leeg.";

    } else {
        // 3) Velden lezen
        $first   = trim($_POST['first_name']   ?? '');
        $last    = trim($_POST['last_name']    ?? '');
        $email   = trim($_POST['email']        ?? '');
        $addr    = trim($_POST['address']      ?? '');
        $zip     = trim($_POST['postal_code']  ?? '');
        $city    = trim($_POST['city']         ?? '');
        $country = trim($_POST['country']      ?? 'Nederland');

        // 4) Validatie: vereiste velden + geldig e-mailadres
        if ($first === '' || $last === '' || $email === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $addr === '' || $zip === '' || $city === '') {
            $error = "Vul alle velden correct in.";

        } else {
            $user_id = get_customer_id();

            // 5) Transactie: order + items + voorraad (alles of niets)
            $conn->begin_transaction();
            try {
                q(
                    $conn,
                    "INSERT INTO orders
                     (user_id,first_name,last_name,email,address,city,postal_code,country,total,created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,NOW())",
                    [$user_id, $first, $last, $email, $addr, $city, $zip, $country, $total]
                );
                $order_id = $conn->insert_id;

                foreach ($items as $it) {
                    q(
                        $conn,
                        "INSERT INTO order_items (order_id,product_id,quantity,unit_price)
                         VALUES (?,?,?,?)",
                        [$order_id, $it['id'], $it['qty'], (float)$it['price']]
                    );
                    // Voorraad verminderen, maar nooit onder 0
                    q(
                        $conn,
                        "UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?",
                        [$it['qty'], $it['id']]
                    );
                }

                $conn->commit();
                $_SESSION['cart'] = [];
                $success = true;

            } catch (Throwable $e) {
                $conn->rollback();
                $error = "Er ging iets mis bij het afronden.";
            }
        }
    }
}
?>

<style>
.checkout-box {
    max-width: 700px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    font-family: Arial;
}
.input {
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-sizing: border-box;
}
.btn {
    background: #111;
    color: #fff;
    padding: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.btn:hover { background: #333; }
.btn.ghost {
    background: transparent;
    color: #111;
    border: 1px solid #111;
}
.cart-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    color: black;
}
.total {
    font-size: 20px;
    font-weight: bold;
    margin-top: 10px;
    color: black;
}
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.alert-error   { background: #fde8e8; color: #c0392b; }
.alert-success { background: #e8f8e8; color: #27ae60; }
.row { display: flex; gap: 12px; }
.mt  { margin-top: 16px; }
</style>

<div class="checkout-box">
<h2 style="color: black;">Afrekenen</h2>

<?php if ($success): ?>
    <div class="alert alert-success">
        🎉 Bedankt voor je bestelling! Bestelnummer: <?= (int)$order_id ?>
    </div>
    <p><a class="btn" href="?page=home">Verder winkelen</a></p>

<?php else: ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$items): ?>
        <p style="color:#666;">Je mand is leeg.</p>
        <p><a class="btn" href="?page=home">Terug naar producten</a></p>

    <?php else: ?>

        <?php foreach ($items as $it): ?>
            <div class="cart-item">
                <span><?= htmlspecialchars($it['name']) ?> x<?= (int)$it['qty'] ?></span>
                <span>€<?= number_format((float)$it['line_total'], 2, ',', '.') ?></span>
            </div>
        <?php endforeach; ?>

        <div class="total">Totaal: €<?= number_format((float)$total, 2, ',', '.') ?></div>
        <br>

        <form method="post">
            <!-- CSRF-token meesturen -->
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

            <div class="row">
                <input class="input" name="first_name" placeholder="Voornaam" required
                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                <input class="input" name="last_name" placeholder="Achternaam" required
                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>
            <input class="input" type="email" name="email" placeholder="E-mail" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <input class="input" name="address" placeholder="Adres" required
                   value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            <div class="row">
                <input class="input" name="postal_code" placeholder="Postcode" required
                       value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                <input class="input" name="city" placeholder="Stad" required
                       value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>
            <input class="input" name="country" placeholder="Land" required
                   value="<?= htmlspecialchars($_POST['country'] ?? 'Nederland') ?>">

            <div class="row mt">
                <a class="btn ghost" href="?page=cart">← Terug naar mand</a>
                <button class="btn">Bestelling afronden</button>
            </div>
        </form>

    <?php endif; ?>

<?php endif; ?>
</div>