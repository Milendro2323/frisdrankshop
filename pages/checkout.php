<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';

if (!is_customer_logged_in()) {
    header("Location: ?page=customer_login");
    exit;
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$ids = array_keys($_SESSION['cart']);
$items = [];
$total = 0;

if ($ids) {
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $res = q($conn,"SELECT id,name,price FROM products WHERE id IN ($in)",$ids);

    while ($r = $res->fetch_assoc()) {
        $qty = $_SESSION['cart'][$r['id']];
        $r['qty']=$qty;
        $r['line_total']=$qty*$r['price'];
        $total += $r['line_total'];
        $items[]=$r;
    }
}

$success=false;
$order_id=null;

if ($_SERVER['REQUEST_METHOD']==='POST'){

    $user_id = get_customer_id();

    $first = $_POST['first_name'];
    $last  = $_POST['last_name'];
    $email = $_POST['email'];
    $addr  = $_POST['address'];
    $city  = $_POST['city'];
    $zip   = $_POST['postal_code'];
    $country = $_POST['country'];

    $conn->begin_transaction();

    try{

        q($conn,
        "INSERT INTO orders 
        (user_id,first_name,last_name,email,address,city,postal_code,country,total,created_at)
         VALUES (?,?,?,?,?,?,?,?,?,NOW())",
        [$user_id,$first,$last,$email,$addr,$city,$zip,$country,$total]
        );

        $order_id=$conn->insert_id;

        foreach($items as $it){
            q($conn,
            "INSERT INTO order_items (order_id,product_id,quantity,unit_price)
             VALUES (?,?,?,?)",
            [$order_id,$it['id'],$it['qty'],$it['price']]
            );
        }

        $conn->commit();
        $_SESSION['cart']=[];

        $success=true;

    }catch(Throwable $e){
        $conn->rollback();
        echo $e->getMessage();
    }
}
?>

<style>
.checkout-box{
max-width:700px;
margin:40px auto;
background:#fff;
padding:30px;
border-radius:12px;
box-shadow:0 10px 30px rgba(0,0,0,0.1);
font-family:Arial;
}
.input{
width:100%;
padding:12px;
margin-bottom:12px;
border:1px solid #ddd;
border-radius:8px;
}
.btn{
background:#111;
color:#fff;
padding:14px;
border:none;
border-radius:8px;
cursor:pointer;
width:100%;
font-size:16px;
}
.btn:hover{background:#333;}
.cart-item{
display:flex;
justify-content:space-between;
padding:8px 0;
border-bottom:1px solid #eee;
color: black;
}
.total{
font-size:20px;
font-weight:bold;
margin-top:10px;
color: black;
}
</style>

<div class="checkout-box">
<h2 style="color: black;">Afrekenen</h2>

<?php if($success): ?>
<script>
alert("🎉 Bedankt voor je bestelling! Bestelnummer: <?= $order_id ?>");
window.location="?page=home";
</script>
<?php endif; ?>

<?php foreach($items as $it): ?>
<div class="cart-item">
<span><?= $it['name'] ?> x<?= $it['qty'] ?></span>
<span>€<?= number_format($it['line_total'],2,",",".") ?></span>
</div>
<?php endforeach; ?>

<div class="total">Totaal: €<?= number_format($total,2,",",".") ?></div>

<br>

<form method="post">
<input class="input" name="first_name" placeholder="Voornaam" required>
<input class="input" name="last_name" placeholder="Achternaam" required>
<input class="input" name="email" placeholder="Email" required>
<input class="input" name="address" placeholder="Adres" required>
<input class="input" name="postal_code" placeholder="Postcode" required>
<input class="input" name="city" placeholder="Stad" required>
<input class="input" name="country" placeholder="Land" value="Nederland" required>

<button class="btn">Bestelling afronden</button>
</form>
</div>
