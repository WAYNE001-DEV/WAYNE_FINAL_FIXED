<?php
/**
 * checkout.php
 * Processes the session cart into tblOrder rows.
 * User must be logged in. Cart must not be empty.
 */

session_start();
require_once 'DBConn.php';

// Must be logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

// Cart must have items
if (empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit;
}

$userID  = $_SESSION['userID'];
$cart    = $_SESSION['cart'];
$errors  = [];
$success = false;

// ── Handle order submission ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = trim($_POST['deliveryAddress'] ?? '');

    if (empty($deliveryAddress)) {
        $errors[] = "Please enter a delivery address.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO tblOrder (userID, clothesID, quantity, totalAmount, deliveryAddress, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );
        
        $updateStmt = $conn->prepare("UPDATE tblClothes SET status = 'sold' WHERE clothesID = ?");

        $conn->begin_transaction();
        try {
            foreach ($cart as $item) {
                $clothesID   = (int)   $item['clothesID'];
                $qty         = (int)   $item['qty'];
                $totalAmount = (float) ($item['price'] * $qty);

                $stmt->bind_param("iiids", $userID, $clothesID, $qty, $totalAmount, $deliveryAddress);
                $stmt->execute();
                
                // Mark item as sold
                $updateStmt->bind_param("i", $clothesID);
                $updateStmt->execute();
            }
            $conn->commit();
            $lastOrderID = $conn->insert_id;          // capture reference number
            $stmt->close();
            $updateStmt->close();

            // Clear cart
            unset($_SESSION['cart']);
            $success    = true;
            $orderRef   = 'ORD-' . str_pad($lastOrderID, 6, '0', STR_PAD_LEFT);
            $sessionRef = strtoupper(substr(session_id(), 0, 8));

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Order failed. Please try again.";
        }
    }
}

// ── Compute totals ────────────────────────────────────────────────────────────
$cartTotal = array_sum(array_map(function($i) { return $i['price'] * $i['qty']; }, $cart));
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — DISCOVER AND RE-WIND</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0c0c0c; --card:#161616; --gold:#c9a86c; --gold2:#e8c98a; --text:#e5e5e5; --muted:#888; --err:#e05252; --ok:#5cb85c; --border:#2a2a2a; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
  nav {
    background:#111; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; padding:.9rem 2rem;
  }
  .logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.3rem; text-decoration:none; }
  .nav-links a { color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem; }
  .nav-links a:hover { color:var(--gold); }

  .container { max-width:900px; margin:2rem auto; padding:0 1.5rem; display:grid; grid-template-columns:1fr 340px; gap:2rem; }
  @media(max-width:700px){ .container{grid-template-columns:1fr;} }

  .section-title { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.2rem; margin-bottom:1rem; }

  .alert { padding:.85rem 1rem; border-radius:var(--radius); margin-bottom:1.2rem; font-size:.9rem; }
  .alert-err { background:#2a1212; border-left:4px solid var(--err); color:#ffaaaa; }
  .alert-ok  { background:#122a12; border-left:4px solid var(--ok); color:#aaffaa; }

  .form-group { margin-bottom:1.2rem; }
  label { display:block; font-size:.82rem; color:var(--muted); margin-bottom:.4rem; letter-spacing:.04em; text-transform:uppercase; }
  input, textarea {
    width:100%; padding:.65rem .9rem;
    background:#1f1f1f; border:1px solid var(--border);
    color:var(--text); border-radius:var(--radius);
    font-family:'DM Sans',sans-serif; font-size:.95rem;
  }
  input:focus, textarea:focus { outline:none; border-color:var(--gold); }
  textarea { resize:vertical; min-height:90px; }

  .btn {
    width:100%; padding:.75rem;
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    color:#111; font-weight:600; border:none; border-radius:var(--radius);
    cursor:pointer; font-size:1rem;
  }
  .btn:hover { opacity:.9; }
  .btn-back { display:inline-block; color:var(--muted); font-size:.88rem; text-decoration:none; margin-bottom:1.5rem; }
  .btn-back:hover { color:var(--gold); }

  /* Order summary */
  .summary-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.25rem; position:sticky; top:1rem; }
  .summary-card h3 { font-family:'Playfair Display',serif; color:var(--gold); margin-bottom:1rem; font-size:1.1rem; }
  .order-item { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid var(--border); font-size:.88rem; }
  .order-item:last-of-type { border-bottom:none; }
  .item-name { color:var(--text); }
  .item-qty  { color:var(--muted); font-size:.8rem; }
  .item-price { color:var(--gold); font-weight:600; }
  .order-total { display:flex; justify-content:space-between; padding:.75rem 0 0; font-weight:600; font-size:1rem; color:var(--gold); border-top:1px solid var(--border); margin-top:.5rem; }

  /* Success screen */
  .success-wrap { text-align:center; padding:3rem 2rem; }
  .success-icon { font-size:3rem; margin-bottom:1rem; }
  .success-wrap h2 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.8rem; margin-bottom:.5rem; }
  .success-wrap p { color:var(--muted); margin-bottom:2rem; }
  .success-wrap a { background:var(--gold); color:#111; padding:.7rem 2rem; border-radius:var(--radius); text-decoration:none; font-weight:600; display:inline-block; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">DISCOVER AND RE-WIND</a>
  <div class="nav-links">
    <a href="shop.php">Shop</a>
    <a href="dashboard.php">My Account</a>
    <a href="logout.php">Logout</a>
  </div>
</nav>

<?php if ($success): ?>
  <!-- ── Success screen ─────────────────────────────────────── -->
  <div style="max-width:600px;margin:4rem auto;padding:0 1.5rem;">
    <div class="success-wrap">
      <div class="success-icon">✅</div>
      <h2>Order Placed!</h2>
      <p>Thank you, <?= htmlspecialchars($_SESSION['fullName'] ?? 'Customer') ?>.<br>
         Your order has been received and is being processed.</p>
      <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;
                  padding:1rem 1.5rem;margin:1.25rem auto;max-width:320px;text-align:left;">
        <div style="font-size:.78rem;color:#888;text-transform:uppercase;
                    letter-spacing:.05em;margin-bottom:.5rem;">Reference Numbers</div>
        <div style="font-size:1.1rem;color:#c9a86c;font-weight:600;font-family:monospace;">
          <?= htmlspecialchars($orderRef ?? 'ORD-000001') ?>
        </div>
        <div style="font-size:.82rem;color:#666;margin-top:.3rem;">
          Session: <?= htmlspecialchars($sessionRef ?? 'N/A') ?>
        </div>
      </div>
      <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="shop.php" style="background:transparent;border:1px solid #c9a86c;
           color:#c9a86c;padding:.65rem 1.5rem;border-radius:8px;
           text-decoration:none;font-weight:600;">Continue Shopping →</a>
        <a href="dashboard.php" style="background:#c9a86c;color:#111;
           padding:.65rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;">
          View My Orders</a>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- ── Checkout form ──────────────────────────────────────── -->
  <div class="container">
    <div>
      <a href="shop.php" class="btn-back">← Back to Shop</a>
      <div class="section-title">Delivery Details</div>

      <?php foreach ($errors as $e): ?>
        <div class="alert alert-err"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="checkout.php">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="<?= htmlspecialchars($_SESSION['fullName'] ?? '') ?>" readonly>
        </div>
        <div class="form-group">
          <label>Delivery Address</label>
          <textarea name="deliveryAddress" placeholder="Street address, City, Province, Postal code" required><?= htmlspecialchars($_POST['deliveryAddress'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">Place Order — R <?= number_format($cartTotal, 2) ?> →</button>
      </form>
    </div>

    <!-- Order summary sidebar -->
    <div class="summary-card">
      <h3>Order Summary</h3>
      <?php foreach ($cart as $item): ?>
        <div class="order-item">
          <div>
            <div class="item-name"><?= htmlspecialchars($item['title']) ?></div>
            <div class="item-qty">Qty: <?= $item['qty'] ?></div>
          </div>
          <div class="item-price">R <?= number_format($item['price'] * $item['qty'], 2) ?></div>
        </div>
      <?php endforeach; ?>
      <div class="order-total">
        <span>Total</span>
        <span>R <?= number_format($cartTotal, 2) ?></span>
      </div>
    </div>
  </div>
<?php endif; ?>
</body>
</html>
