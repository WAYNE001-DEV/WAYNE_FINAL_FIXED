<?php
/**
 * dashboard.php
 * User dashboard — displays user data using associative read (column names).
 * Shows: "User John Doe is logged in"
 */

session_start();
require_once 'DBConn.php';

// Must be logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['userID'];

// ── Fetch user data using associative read ────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT userID, fullName, email, province, isVerified, status, role, createdAt
     FROM tblUser WHERE userID = ?"
);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();   // Associative read by column names
$stmt->close();

// ── Fetch user's orders ────────────────────────────────────────────────────────
$oStmt = $conn->prepare(
    "SELECT o.orderID, c.title, c.category, c.sellPrice, o.quantity,
            o.totalAmount, o.status, o.createdAt
     FROM tblOrder o
     JOIN tblClothes c ON o.clothesID = c.clothesID
     WHERE o.userID = ?
     ORDER BY o.createdAt DESC"
);
$oStmt->bind_param("i", $userID);
$oStmt->execute();
$orders = $oStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$oStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0c0c0c; --card:#161616; --gold:#c9a86c; --text:#e5e5e5; --muted:#888; --border:#2a2a2a; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
  nav {
    background:#111; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    padding:.9rem 2rem;
  }
  .logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.3rem; text-decoration:none; }
  .nav-links a { color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem; }
  .nav-links a:hover, .nav-links a.active { color:var(--gold); }

  .container { max-width:1000px; margin:0 auto; padding:2rem; }

  /* Banner */
  .welcome-banner {
    background:linear-gradient(135deg,#1a1507,#2a1e0a);
    border:1px solid #3a2d10; border-radius:var(--radius);
    padding:1.5rem 2rem; margin-bottom:2rem;
  }
  .welcome-banner h2 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.4rem; }
  .welcome-banner p  { color:var(--muted); font-size:.9rem; margin-top:.25rem; }

  /* User info table */
  .section-title { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.1rem; margin-bottom:1rem; }
  .info-table { width:100%; border-collapse:collapse; margin-bottom:2.5rem; }
  .info-table th {
    background:#1f1f1f; padding:.7rem 1rem; text-align:left;
    font-size:.8rem; color:var(--muted); text-transform:uppercase;
    letter-spacing:.05em; border-bottom:1px solid var(--border);
  }
  .info-table td { padding:.7rem 1rem; border-bottom:1px solid var(--border); font-size:.92rem; }
  .info-table tr:last-child td { border-bottom:none; }

  /* Orders table */
  .orders-table { width:100%; border-collapse:collapse; }
  .orders-table th {
    background:#1f1f1f; padding:.7rem 1rem;
    font-size:.8rem; color:var(--muted); text-transform:uppercase; text-align:left;
    border-bottom:1px solid var(--border);
  }
  .orders-table td { padding:.65rem 1rem; border-bottom:1px solid var(--border); font-size:.9rem; }
  .badge {
    display:inline-block; padding:.2rem .6rem; border-radius:20px; font-size:.75rem; font-weight:600;
  }
  .badge-pending  { background:#2a2510; color:#c9a86c; }
  .badge-active   { background:#122a12; color:#5cb85c; }
  .badge-inactive { background:#2a1212; color:#e05252; }

  .empty { text-align:center; padding:2rem; color:var(--muted); font-style:italic; }

  .btn-logout {
    background:transparent; border:1px solid var(--border); color:var(--muted);
    padding:.4rem .9rem; border-radius:var(--radius); cursor:pointer; font-size:.85rem;
    text-decoration:none;
  }
  .btn-logout:hover { border-color:#e05252; color:#e05252; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">PASTIMES</a>
  <div class="nav-links">
    <a href="shop.php">Shop</a>
    <?php if (($_SESSION['role'] ?? null) === 'seller'): ?>
      <a href="seller-products.php">My Products</a>
    <?php endif; ?>
    <a href="dashboard.php" class="active">My Account</a>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</nav>

<div class="container">

  <!-- ── Identity banner ─────────────────────────────────────── -->
  <div class="welcome-banner">
    <h2><?= strtoupper($user['role']) ?> — <?= htmlspecialchars($user['fullName']) ?> is logged in</h2>
    <p>Welcome back! Here is your account overview.</p>
  </div>

  <!-- ── User data table (associative column read) ──────────── -->
  <div class="section-title">Account Details</div>
  <table class="info-table">
    <tr>
      <th>Field</th>
      <th>Value</th>
    </tr>
    <tr><td>User ID</td>    <td><?= htmlspecialchars($user['userID'])   ?></td></tr>
    <tr><td>Full Name</td>  <td><?= htmlspecialchars($user['fullName']) ?></td></tr>
    <tr><td>Email</td>      <td><?= htmlspecialchars($user['email'])    ?></td></tr>
    <tr><td>Province</td>   <td><?= htmlspecialchars($user['province'] ?? 'N/A') ?></td></tr>
    <tr>
      <td>Account Status</td>
      <td>
        <?php
          $s = $user['status'];
          $cls = $s === 'active' ? 'badge-active' : ($s === 'pending' ? 'badge-pending' : 'badge-inactive');
        ?>
        <span class="badge <?= $cls ?>"><?= ucfirst($s) ?></span>
      </td>
    </tr>
    <tr><td>Verified</td>   <td><?= $user['isVerified'] ? '✅ Yes' : '⏳ Pending' ?></td></tr>
    <tr><td>Role</td>        <td><strong><?= ucfirst($user['role']) ?></strong></td></tr>
    <tr><td>Member Since</td><td><?= date('d M Y', strtotime($user['createdAt'])) ?></td></tr>
  </table>

  <!-- ── Seller shortcut ───────────────────────────────────────── -->
  <?php if (($_SESSION['role'] ?? null) === 'seller'): ?>
  <div style="background:linear-gradient(135deg,#1a1507,#2a1e0a);border:1px solid #3a2d10;
              border-radius:var(--radius);padding:1.25rem 2rem;margin-bottom:2rem;
              display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div style="font-family:'Playfair Display',serif;color:var(--gold);font-size:1rem;margin-bottom:.25rem;">
        🏷️ Seller Dashboard
      </div>
      <div style="color:var(--muted);font-size:.85rem;">
        Manage your listings, upload new items and track your sales.
      </div>
    </div>
    <a href="seller-products.php"
       style="background:var(--gold);color:#111;padding:.6rem 1.5rem;border-radius:var(--radius);
              text-decoration:none;font-weight:600;font-size:.9rem;white-space:nowrap;">
      My Products →
    </a>
  </div>
  <?php endif; ?>

  <!-- ── Orders ─────────────────────────────────────────────── -->
  <div class="section-title">My Orders</div>
  <?php if (empty($orders)): ?>
    <p class="empty">No orders yet. <a href="shop.php" style="color:var(--gold)">Browse the shop →</a></p>
  <?php else: ?>
    <table class="orders-table">
      <tr>
        <th>Order #</th><th>Item</th><th>Category</th>
        <th>Qty</th><th>Total (R)</th><th>Status</th><th>Date</th>
      </tr>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= $o['orderID'] ?></td>
        <td><?= htmlspecialchars($o['title']) ?></td>
        <td><?= htmlspecialchars($o['category']) ?></td>
        <td><?= $o['quantity'] ?></td>
        <td>R <?= number_format($o['totalAmount'], 2) ?></td>
        <td><span class="badge badge-pending"><?= ucfirst($o['status']) ?></span></td>
        <td><?= date('d M Y', strtotime($o['createdAt'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

</div>
</body>
</html>
