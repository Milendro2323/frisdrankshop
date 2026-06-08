<?php
// pages/admin.php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

// Controle: alleen admins hebben toegang
if (!is_admin()) {
    header("Location: ?page=login");
    exit;
}

// Tabblad selectie (producten of bestellingen)
$tab = $_GET['tab'] ?? 'products';
?>
<div class="box">
  <h2>Admin Dashboard</h2>

  <!-- Tab navigatie -->
  <div style="margin-bottom:1.5rem; border-bottom:2px solid #eee;">
    <a href="?page=admin&tab=products" 
       style="display:inline-block; padding:0.5rem 1rem; text-decoration:none; <?= $tab==='products'?'border-bottom:2px solid #333; font-weight:bold;':'' ?>">
      Producten
    </a>
    <a href="?page=admin&tab=orders" 
       style="display:inline-block; padding:0.5rem 1rem; text-decoration:none; <?= $tab==='orders'?'border-bottom:2px solid #333; font-weight:bold;':'' ?>">
      Bestellingen
    </a>
  </div>

  <?php if ($tab === 'products'): ?>
    <!-- PRODUCTEN OVERZICHT -->
    <?php
    $rows = q($conn, "SELECT * FROM products ORDER BY id DESC");
    ?>
    <h3>Frisdrank Producten</h3>

    <?php if ($rows && $rows->num_rows > 0): ?>
      <table class="table">
        <tr>
          <th>ID</th>
          <th>Naam</th>
          <th>Prijs</th>
          <th>Merk</th>
          <th>Voorraad</th>
        </tr>
          <!--Door alle producten heen lopen-->
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td><!-- Product ID -->
            <td><?php echo htmlspecialchars($r['name'] ?? ''); ?></td><!-- Productnaam veilig weergeven -->
            <!-- Prijs formatteren naar euro-notatie -->
            <td>€<?php
              $price = isset($r['price']) ? (float)$r['price'] : 0;
              echo number_format($price, 2, ',', '.');
            ?></td> 
            <!-- Merk weergeven -->
            <td><?php echo htmlspecialchars($r['brand'] ?? ''); ?></td>
            <td><?php echo (int)$r['stock']; ?></td> <!-- Voorraad tonen -->
          </tr>
        <?php endwhile; ?>
      </table>

    <?php else: ?>
      <p>Er zijn nog helaas geen producten gevonden.</p>
    <?php endif; ?>

  <?php else: ?>
    <!-- BESTELLINGEN OVERZICHT -->
    <?php
    // Haal alle bestellingen op met klantgegevens
    $orders = q($conn, "
      SELECT o.*, u.email as user_email 
      FROM orders o
      LEFT JOIN users u ON o.user_id = u.id
      ORDER BY o.created_at DESC
    ");
    ?>
    <h3>Bestellingen</h3>

    <?php if ($orders && $orders->num_rows > 0): ?>
      <table class="table">
        <tr>
          <th>Bestelnr</th>
          <th>Klant</th>
          <th>Email</th>
          <th>Totaal</th>
          <th>Datum</th>
          <th>Details</th>
        </tr>

        <?php while ($o = $orders->fetch_assoc()): ?> <!-- Door alle bestellingen heen lopen -->
  <tr>
    <td>#<?= (int)$o['id'] ?></td> <!-- Bestelnummer tonen -->
    <td><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?></td> <!-- Naam van klant tonen -->
    <td>
      <?= htmlspecialchars($o['email']) ?> <!-- E-mailadres tonen -->

      <?php if ($o['user_email']): ?> <!-- Controleren of klant een account heeft -->
        <br>
        <small class="muted">
          (Account: <?= htmlspecialchars($o['user_email']) ?>)
        </small> <!-- Account e-mail tonen -->
      <?php endif; ?>
    </td>

    <td>€<?= number_format((float)$o['total'], 2, ',', '.') ?></td> <!-- Totaalbedrag formatteren -->
    <td><?= date('d-m-Y H:i', strtotime($o['created_at'])) ?></td> <!-- Datum en tijd formatteren -->
    <td>
      <button
        class="btn ghost"
        onclick="showOrderDetails(<?= (int)$o['id'] ?>)"; 
        style="padding:0.25rem 0.5rem; font-size:0.9em;">
        Bekijk
      </button>
    </td>
  </tr>
<?php endwhile; ?> <!-- Einde van de bestellingen-lus -->
</table> 

    <?php else: ?>
      <p class="muted">Er zijn nog geen bestellingen geplaatst.</p>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Admin acties -->
  <ul class="admin-actions" style="margin-top:1.5rem; list-style:none; padding-left:0;">
    <li style="display:inline-block; margin-right:1rem;">
      <a href="?page=home">← Terug naar de producten</a>
    </li>
    <li style="display:inline-block;">
      <a href="?page=logout">Logout</a>
    </li>
  </ul>
</div>

<!-- Modal voor order details -->
<div id="orderModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
  <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:2rem; border-radius:8px; max-width:600px; max-height:80vh; overflow-y:auto;">
    <button onclick="closeOrderModal()" style="float:right; background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
    <div id="orderDetails">Laden...</div>
  </div>
</div>

<script>
function showOrderDetails(orderId) { // Orderdetails openen

  document.getElementById('orderModal').style.display = 'block'; // Modal tonen
  document.getElementById('orderDetails').innerHTML = 'Laden...'; // Laadbericht tonen

  fetch('?page=admin&action=get_order_details&order_id=' + orderId) // Ordergegevens ophalen
    .then(r => r.json()) // JSON verwerken
    .then(data => {

      let html = '<h3>Bestelling #' + data.order.id + '</h3>'; // Bestelnummer tonen
      html += '<p><strong>Klant:</strong> ' + data.order.first_name + ' ' + data.order.last_name + '</p>'; // Klantnaam tonen
      html += '<p><strong>Email:</strong> ' + data.order.email + '</p>'; // E-mailadres tonen
      html += '<p><strong>Adres:</strong> ' + data.order.address + ', ' + data.order.postal_code + ' ' + data.order.city + '</p>'; // Adres tonen
      html += '<p><strong>Datum:</strong> ' + data.order.created_at + '</p>'; // Besteldatum tonen

      html += '<h4>Producten:</h4>'; // Koptekst producten
      html += '<table class="table" style="width:100%;">'; // Producttabel starten
      html += '<tr><th>Product</th><th>Aantal</th><th>Prijs</th><th>Totaal</th></tr>'; // Tabelkop maken

      data.items.forEach(item => { // Door alle producten lopen

        html += '<tr>'; // Nieuwe rij starten
        html += '<td>' + item.name + '</td>'; // Productnaam tonen
        html += '<td>' + item.quantity + '</td>'; // Aantal tonen
        html += '<td>€' + item.unit_price + '</td>'; // Prijs tonen
        html += '<td>€' + item.line_total + '</td>'; // Regeltotaal tonen
        html += '</tr>'; // Rij afsluiten

      });
      html += '</table>'; // Tabel afsluiten
      html += '<p style="text-align:right; margin-top:1rem;"><strong>Totaal: €' + data.order.total + '</strong></p>'; // Eindtotaal tonen

      document.getElementById('orderDetails').innerHTML = html; // HTML in modal plaatsen

    })
    .catch(err => { // Fout afhandelen

      document.getElementById('orderDetails').innerHTML =
      'Fout bij laden van details.'; // Foutmelding tonen

    });
}

function closeOrderModal() { // Modal sluiten

  document.getElementById('orderModal').style.display = 'none'; // Modal verbergen

}
</script>

<?php
// API endpoint voor order details ophalen (via AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'get_order_details' && is_admin()) {
    $order_id = (int)($_GET['order_id'] ?? 0);   // Order-ID ophalen
    
    // Database: bestelling ophalen Haal order op
    $order = q($conn, "SELECT * FROM orders WHERE id = ?", [$order_id])->fetch_assoc();
    
    // Database: Haalt orderregels  order items op met product info
    $items_res = q($conn, "
      SELECT oi.*, p.name 
      FROM order_items oi
      JOIN products p ON oi.product_id = p.id
      WHERE oi.order_id = ?
    ", [$order_id]);
    
    $items = [];// Resultaten verwerken
    while ($item = $items_res->fetch_assoc()) {
        $item['unit_price'] = number_format((float)$item['unit_price'], 2, ',', '.');// Prijzen formatteren
        $item['line_total'] = number_format((float)$item['quantity'] * (float)$item['unit_price'], 2, ',', '.'); // Regel totaal berekenen
        $items[] = $item;
    }
    
    $order['total'] = number_format((float)$order['total'], 2, ',', '.');
    // JSON response terugsturen
    header('Content-Type: application/json');
    echo json_encode(['order' => $order, 'items' => $items]);
    exit;
}
?>
