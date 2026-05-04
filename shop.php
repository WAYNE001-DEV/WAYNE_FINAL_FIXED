<?php
/**
 * shop.php
 * Displays tblClothes items with:
 * - Product image, title, category, condition badge
 * - Add to Cart button
 * - SellPrice shown in popup on click
 * - Line item display (cart summary)
 */

session_start();
require_once 'DBConn.php';

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addToCart'])) {
    $clothesID = (int) $_POST['clothesID'];

    // Fetch item
    $s = $conn->prepare("SELECT clothesID, title, sellPrice FROM tblClothes WHERE clothesID = ? AND status = 'active'");
    if ($s) {
        $s->bind_param("i", $clothesID);
        $s->execute();
        $item = $s->get_result()->fetch_assoc();
        $s->close();
    } else {
        $item = null;
    }

    if ($item) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $id = $item['clothesID'];
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty']++;
        } else {
            $_SESSION['cart'][$id] = [
                'clothesID' => $id,
                'title'     => $item['title'],
                'price'     => $item['sellPrice'],
                'qty'       => 1,
            ];
        }
    }
    header("Location: shop.php");
    exit;
}

// Remove from cart
if (isset($_GET['remove'])) {
    $rid = (int) $_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header("Location: shop.php");
    exit;
}

// Fetch all active clothes (associative)
$result = $conn->query(
    "SELECT clothesID, title, category, brand, size, colour, condition_,
            sellPrice, retailPrice, imageFile
     FROM tblClothes WHERE status = 'active' ORDER BY createdAt DESC"
);
$clothes = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

$cart     = $_SESSION['cart'] ?? [];
$cartTotal = array_sum(array_map(function($i) { return $i['price'] * $i['qty']; }, $cart));

// Condition badge colours
$condColor = [
    'Mint'      => '#4caf50',
    'Good'      => '#2196f3',
    'Fair'      => '#ff9800',
    'Well-Loved'=> '#e05252',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0c0c0c; --card:#161616; --gold:#c9a86c; --text:#e5e5e5; --muted:#888; --border:#2a2a2a; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); }
  nav {
    background:#111; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; padding:.9rem 2rem;
  }
  .logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.3rem; text-decoration:none; }
  .nav-links a { color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem; }
  .nav-links a:hover { color:var(--gold); }
  .btn-showcart {
    background: transparent; border: 1px solid var(--gold); color: var(--gold);
    padding: .35rem .9rem; border-radius: var(--radius); font-size: .82rem;
    text-decoration: none; font-weight: 500; margin-left: .5rem;
    display: inline-block;
  }
  .btn-showcart:hover { background: var(--gold); color: #111; }
  .cart-badge {
    background:var(--gold); color:#111; font-size:.7rem; font-weight:700;
    border-radius:50%; padding:.15rem .4rem; margin-left:.3rem;
  }

  .layout { display:grid; grid-template-columns:1fr 300px; gap:2rem; max-width:1200px; margin:2rem auto; padding:0 1.5rem; }

  /* Product grid */
  .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1.25rem; }
  @media(max-width:900px){ .grid{grid-template-columns:repeat(2,1fr);} .layout{grid-template-columns:1fr;} }
  @media(max-width:600px){ .grid{grid-template-columns:1fr;} }

  .product-card {
    background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
    overflow:hidden; transition:transform .2s, border-color .2s;
  }
  .product-card:hover { transform:translateY(-3px); border-color:#3a3a3a; }

  .product-img {
    width:100%; height:200px; object-fit:cover; background:#1f1f1f;
    display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:.8rem;
  }
  .product-img img { width:100%; height:100%; object-fit:cover; }

  .product-body { padding:1rem; }
  .product-title { font-weight:500; margin-bottom:.3rem; font-size:.95rem; }
  .product-meta  { font-size:.8rem; color:var(--muted); margin-bottom:.6rem; }
  .cond-badge {
    display:inline-block; font-size:.7rem; padding:.15rem .5rem;
    border-radius:20px; font-weight:600; margin-bottom:.6rem; color:#fff;
  }
  .price-area { display:flex; align-items:center; gap:.6rem; margin-bottom:.8rem; }
  .sell-price { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.1rem; font-weight:600; }
  .retail-price { font-size:.8rem; color:var(--muted); text-decoration:line-through; }

  .btn-cart {
    width:100%; padding:.55rem; background:var(--gold); color:#111;
    border:none; border-radius:var(--radius); cursor:pointer; font-weight:600; font-size:.9rem;
    display:flex; align-items:center; justify-content:center; gap:.4rem;
  }
  .btn-cart:hover { background:#e8c98a; }

  /* Price popup */
  .price-popup {
    display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
    background:#1a1a1a; border:1px solid var(--gold); border-radius:var(--radius);
    padding:2rem; z-index:999; min-width:280px; text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,.8);
  }
  .price-popup.show { display:block; }
  .price-popup h3 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.3rem; margin-bottom:.5rem; }
  .price-popup .big-price { font-size:2.5rem; font-weight:700; color:#fff; margin:.5rem 0; }
  .price-popup .retail    { color:var(--muted); font-size:.9rem; text-decoration:line-through; }
  .price-popup .savings   { color:#5cb85c; font-size:.9rem; margin-top:.3rem; }
  .price-popup button     { margin-top:1.2rem; background:var(--gold); color:#111; border:none; padding:.5rem 1.5rem; border-radius:var(--radius); cursor:pointer; font-weight:600; }
  .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:998; }
  .overlay.show { display:block; }

  /* Cart sidebar */
  .cart-panel {
    background:var(--card); border:1px solid var(--border);
    border-radius:var(--radius); padding:1.25rem; position:sticky; top:1rem; max-height:80vh; overflow-y:auto;
  }
  .cart-panel h3 { font-family:'Playfair Display',serif; color:var(--gold); margin-bottom:1rem; font-size:1.1rem; }
  .cart-item { display:flex; justify-content:space-between; align-items:flex-start; padding:.65rem 0; border-bottom:1px solid var(--border); font-size:.88rem; }
  .cart-item-title { max-width:170px; }
  .cart-item-right { text-align:right; }
  .cart-item-price { color:var(--gold); font-weight:600; }
  .remove-link { color:#e05252; font-size:.75rem; text-decoration:none; }
  .cart-total { padding:.75rem 0 0; font-weight:600; display:flex; justify-content:space-between; color:var(--gold); }
  .checkout-btn {
    width:100%; margin-top:1rem; padding:.65rem;
    background:var(--gold); color:#111; border:none; border-radius:var(--radius);
    cursor:pointer; font-weight:700; font-size:.95rem;
  }
  .empty-cart { color:var(--muted); font-size:.88rem; text-align:center; padding:1rem 0; }

  h2.page-title { font-family:'Playfair Display',serif; color:var(--text); font-size:1.8rem; margin-bottom:1.5rem; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">PASTIMES</a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="shop.php">Shop</a>
    <?php if (isset($_SESSION['userID'])): ?>
      <a href="dashboard.php">My Account</a>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
    <a href="shop.php">🛒<?php if ($cart): ?><span class="cart-badge"><?= count($cart) ?></span><?php endif; ?></a>
    <?php if (!empty($cart)): ?>
      <a href="#cart-section" class="btn-showcart" id="showCartBtn">ShowCart (<?= count($cart) ?>)</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Price popup -->
<div class="overlay" id="overlay" onclick="closePopup()"></div>
<div class="price-popup" id="pricePopup">
  <h3 id="popupTitle"></h3>
  <div class="big-price" id="popupPrice"></div>
  <div class="retail" id="popupRetail"></div>
  <div class="savings" id="popupSavings"></div>
  <button onclick="closePopup()">Close</button>
</div>

<div class="layout">
  <div>
    <h2 class="page-title">Browse All Items</h2>
    <div class="grid">
      <?php foreach ($clothes as $item): ?>
        <?php
          $condColor = $condColor[$item['condition_']] ?? '#888';
          $savings   = $item['retailPrice'] ? ($item['retailPrice'] - $item['sellPrice']) : 0;
        ?>
        <div class="product-card">
          <div class="product-img">
            <img src="images/<?= htmlspecialchars($item['imageFile']) ?>"
                 alt="<?= htmlspecialchars($item['title']) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div style="display:none;width:100%;height:100%;background:#1f1f1f;align-items:center;justify-content:center;color:var(--muted);">
              🧥 <?= htmlspecialchars($item['category']) ?>
            </div>
          </div>
          <div class="product-body">
            <div class="cond-badge" style="background:<?= $condColor ?>">
              <?= htmlspecialchars($item['condition_']) ?>
            </div>
            <div class="product-title"><?= htmlspecialchars($item['title']) ?></div>
            <div class="product-meta">
              <?= htmlspecialchars($item['brand'] ?? '') ?>
              <?= $item['size'] ? '· Size ' . htmlspecialchars($item['size']) : '' ?>
              <?= $item['colour'] ? '· ' . htmlspecialchars($item['colour']) : '' ?>
            </div>
            <div class="price-area">
              <span class="sell-price">R <?= number_format($item['sellPrice'], 2) ?></span>
              <?php if ($item['retailPrice']): ?>
                <span class="retail-price">R <?= number_format($item['retailPrice'], 2) ?></span>
              <?php endif; ?>
            </div>

            <!-- Price popup trigger button -->
            <button type="button" class="btn-cart" style="background:#1f1f1f;color:var(--gold);border:1px solid var(--border);margin-bottom:.5rem;"
              onclick="showPrice(
                '<?= addslashes(htmlspecialchars($item['title'])) ?>',
                '<?= number_format($item['sellPrice'],2) ?>',
                '<?= $item['retailPrice'] ? 'R '.number_format($item['retailPrice'],2) : '' ?>',
                '<?= $savings > 0 ? 'Save R '.number_format($savings,2) : '' ?>'
              )">
              💰 View Price
            </button>

            <!-- Add to Cart -->
            <form method="POST" action="shop.php">
              <input type="hidden" name="clothesID" value="<?= $item['clothesID'] ?>">
              <button type="submit" name="addToCart" class="btn-cart" title="Add to Cart">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23111' stroke-width='2.5'%3E%3Ccircle cx='9' cy='21' r='1'/%3E%3Ccircle cx='20' cy='21' r='1'/%3E%3Cpath d='M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6'/%3E%3C/svg%3E" alt="cart" width="16" height="16" style="vertical-align:middle;margin-right:5px;">
                Add to Cart
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Cart sidebar -->
  <div class="cart-panel">
    <h3>🛒 Cart (<?= count($cart) ?>)</h3>
    <?php if (empty($cart)): ?>
      <div class="empty-cart">Your cart is empty</div>
    <?php else: ?>
      <?php foreach ($cart as $ci): ?>
        <div class="cart-item">
          <div class="cart-item-title"><?= htmlspecialchars($ci['title']) ?>
            <div style="color:var(--muted);font-size:.75rem">Qty: <?= $ci['qty'] ?></div>
            <a href="shop.php?remove=<?= $ci['clothesID'] ?>" class="remove-link">Remove</a>
          </div>
          <div class="cart-item-right">
            <div class="cart-item-price">R <?= number_format($ci['price'] * $ci['qty'], 2) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="cart-total">
        <span>Total</span>
        <span>R <?= number_format($cartTotal, 2) ?></span>
      </div>
      <?php if (isset($_SESSION['userID'])): ?>
        <a href="checkout.php" class="checkout-btn" style="display:block;text-align:center;text-decoration:none;">Checkout →</a>
      <?php else: ?>
        <a href="login.php" style="display:block;text-align:center;margin-top:1rem;color:var(--gold);font-size:.85rem;">Login to checkout</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
function showPrice(title, price, retail, savings) {
  document.getElementById('popupTitle').textContent   = title;
  document.getElementById('popupPrice').textContent   = 'R ' + price;
  document.getElementById('popupRetail').textContent  = retail ? 'Retail: ' + retail : '';
  document.getElementById('popupSavings').textContent = savings || '';
  document.getElementById('pricePopup').classList.add('show');
  document.getElementById('overlay').classList.add('show');
}
function closePopup() {
  document.getElementById('pricePopup').classList.remove('show');
  document.getElementById('overlay').classList.remove('show');
}
</script>
</body>
</html>
