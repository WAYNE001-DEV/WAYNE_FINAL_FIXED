<?php
/**
 * seller-products.php
 * Seller product management — add/edit/view products for sale
 */

session_start();
require_once 'DBConn.php';

// Must be logged in as seller
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['userID'];

// Verify user is a seller
$stmt = $conn->prepare("SELECT role FROM tblUser WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($result['role'] !== 'seller') {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$errors = [];

// Handle product upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addProduct'])) {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $size        = trim($_POST['size'] ?? '');
    $colour      = trim($_POST['colour'] ?? '');
    $condition   = trim($_POST['condition'] ?? 'Good');
    $sellPrice   = floatval($_POST['sellPrice'] ?? 0);
    $retailPrice = floatval($_POST['retailPrice'] ?? 0);

    if (empty($title))       $errors[] = "Product title is required.";
    if (empty($category))    $errors[] = "Category is required.";
    if ($sellPrice <= 0)     $errors[] = "Selling price must be greater than 0.";
    if ($retailPrice < 0)    $errors[] = "Retail price cannot be negative.";

    // Handle image upload
    $imageFile = 'placeholder.jpg';
    if (!empty($_FILES['productImage']['name'])) {
        $uploadDir = 'images/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileName = basename($_FILES['productImage']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExt, $allowedExt)) {
            $errors[] = "Invalid image format. Allowed: JPG, PNG, GIF.";
        } else {
            $newFileName = uniqid('product_') . '.' . $fileExt;
            $uploadPath = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadPath)) {
                $imageFile = $newFileName;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO tblClothes (sellerID, title, category, brand, size, colour, condition_, sellPrice, retailPrice, imageFile, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $status = 'active';
            $stmt->bind_param("issssssddss", $userID, $title, $category, $brand, $size, $colour, $condition, $sellPrice, $retailPrice, $imageFile, $status);
            if ($stmt->execute()) {
                $message = "✅ Product added successfully!";
                $title = $category = $brand = $size = $colour = '';
                $sellPrice = $retailPrice = 0;
                $condition = 'Good';
            } else {
                $errors[] = "Failed to add product. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Fetch seller's products with order/sales information
$pStmt = $conn->prepare(
    "SELECT c.clothesID, c.title, c.category, c.sellPrice, c.status, c.createdAt,
            c.imageFile,
            COUNT(o.orderID) as totalSales, SUM(o.totalAmount) as totalRevenue
     FROM tblClothes c
     LEFT JOIN tblOrder o ON c.clothesID = o.clothesID
     WHERE c.sellerID = ?
     GROUP BY c.clothesID
     ORDER BY c.createdAt DESC"
);
if ($pStmt) {
    $pStmt->bind_param("i", $userID);
    $pStmt->execute();
    $products = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pStmt->close();
} else {
    $products = [];
}

$conn->close();

$categories = ['Tops', 'Bottoms', 'Dresses', 'Jackets', 'Shoes', 'Accessories', 'Activewear', 'Outerwear'];
$conditions = ['Mint', 'Good', 'Fair', 'Well-Loved'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Products — PASTIMES</title>
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
  
  h1 { font-family:'Playfair Display',serif; font-size:2rem; margin-bottom:1rem; color:var(--gold); }
  .subtitle { color:var(--muted); margin-bottom:2rem; font-size:.95rem; }
  
  .alert { padding:1rem; border-radius:var(--radius); margin-bottom:1.5rem; font-size:.9rem; }
  .alert-err { background:#2a1212; border-left:4px solid #e05252; color:#ffaaaa; }
  .alert-ok { background:#122a12; border-left:4px solid #5cb85c; color:#aaffaa; }

  .form-card {
    background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
    padding:2rem; margin-bottom:2rem;
  }

  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; margin-bottom:1.2rem; }
  .form-row.full { grid-template-columns:1fr; }

  label { display:block; font-size:.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:.4rem; }
  input, select, textarea {
    width:100%; padding:.65rem .9rem; background:#191919; border:1px solid var(--border2);
    color:var(--text); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.95rem;
    transition:border-color .2s;
  }
  input:focus, select:focus, textarea:focus { outline:none; border-color:var(--gold); }

  .btn {
    background:linear-gradient(135deg,#8b6914,var(--gold)); color:#fff; border:none;
    padding:.8rem 1.5rem; border-radius:var(--radius); cursor:pointer; font-size:.95rem;
    font-weight:600; letter-spacing:.02em; margin-top:1rem;
  }
  .btn:hover { opacity:.9; }

  .products-title { font-size:1.3rem; margin:2rem 0 1rem; color:var(--gold); }
  
  .product-grid {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1.5rem; margin-top:1rem;
  }

  .product-card {
    background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
    overflow:hidden; transition:transform .2s;
  }
  .product-card:hover { transform:translateY(-2px); }

  .product-img {
    width:100%; height:150px; background:#1f1f1f; display:flex; align-items:center;
    justify-content:center; color:var(--muted); font-size:2rem;
  }

  .product-info { padding:1rem; }
  .product-title { font-weight:600; font-size:.95rem; margin-bottom:.3rem; }
  .product-meta { font-size:.8rem; color:var(--muted); margin-bottom:.5rem; }
  .product-price { font-weight:600; color:var(--gold); }
  .product-status { font-size:.75rem; padding:.2rem .4rem; border-radius:4px; margin-top:.5rem; display:inline-block; }
  .status-active { background:#122a12; color:#5cb85c; }
  .status-sold { background:#2a1212; color:#e05252; }

  .empty { text-align:center; padding:2rem; color:var(--muted); }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">PASTIMES</a>
  <div class="nav-links">
    <a href="shop.php">Shop</a>
    <a href="seller-products.php" class="active">My Products</a>
    <a href="dashboard.php">My Account</a>
    <a href="logout.php" style="color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem;">Logout</a>
  </div>
</nav>

<div class="container">
  <h1>📦 My Products</h1>
  <p class="subtitle">Manage your product listings and uploads</p>

  <?php if ($message): ?>
    <div class="alert alert-ok"><?= $message ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-err">
      <?php foreach($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ── Upload Form ────────────────────────────────────────── -->
  <div class="form-card">
    <h2 style="font-size:1.2rem; margin-bottom:1.5rem; color:var(--gold);">➕ Add New Product</h2>
    <form method="POST" action="seller-products.php" enctype="multipart/form-data" novalidate>
      
      <div class="form-row">
        <div>
          <label>Product Title *</label>
          <input type="text" name="title" placeholder="e.g., Vintage Denim Jacket" value="<?= htmlspecialchars($title ?? '') ?>" required>
        </div>
        <div>
          <label>Category *</label>
          <select name="category" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= ($category ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Brand</label>
          <input type="text" name="brand" placeholder="e.g., Levi's" value="<?= htmlspecialchars($brand ?? '') ?>">
        </div>
        <div>
          <label>Size</label>
          <input type="text" name="size" placeholder="e.g., M, L, 32" value="<?= htmlspecialchars($size ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Colour</label>
          <input type="text" name="colour" placeholder="e.g., Blue, Red" value="<?= htmlspecialchars($colour ?? '') ?>">
        </div>
        <div>
          <label>Condition *</label>
          <select name="condition" required>
            <?php foreach ($conditions as $cond): ?>
              <option value="<?= $cond ?>" <?= ($condition ?? 'Good') === $cond ? 'selected' : '' ?>><?= $cond ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Selling Price (R) *</label>
          <input type="number" name="sellPrice" step="0.01" placeholder="250.00" value="<?= htmlspecialchars($sellPrice ?? '') ?>" required>
        </div>
        <div>
          <label>Retail Price (R)</label>
          <input type="number" name="retailPrice" step="0.01" placeholder="800.00" value="<?= htmlspecialchars($retailPrice ?? '') ?>">
        </div>
      </div>

      <div class="form-row full">
        <div>
          <label>Product Image (JPG, PNG, GIF)</label>
          <input type="file" name="productImage" accept="image/jpeg,image/png,image/gif">
          <small style="color:var(--muted); display:block; margin-top:.3rem;">Max 5MB. Leave empty for placeholder.</small>
        </div>
      </div>

      <button type="submit" name="addProduct" class="btn">Add Product →</button>
    </form>
  </div>

  <!-- ── Product List ───────────────────────────────────────── -->
  <div class="products-title">📋 Your Products (<?= count($products) ?>)</div>
  <?php if (empty($products)): ?>
    <p class="empty">No products yet. Create your first listing above!</p>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:1.5rem; margin-top:1rem;">
      <?php foreach ($products as $p): ?>
        <div style="background:var(--card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; transition:transform .2s;">
          <!-- Image -->
          <div style="width:100%; height:150px; background:#1f1f1f; display:flex; align-items:center; justify-content:center; overflow:hidden;">
            <img src="images/<?= htmlspecialchars($p['imageFile']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"
              style="width:100%; height:100%; object-fit:cover;"
              onerror="this.style.display='none'; this.parentElement.innerHTML='🧥 <?= htmlspecialchars($p['category']) ?>';">

          </div>
          <!-- Info -->
          <div style="padding:1rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:.3rem; word-break:break-word;"><?= htmlspecialchars($p['title']) ?></div>
            <div style="font-size:.8rem; color:var(--muted); margin-bottom:.5rem;"><?= htmlspecialchars($p['category']) ?></div>
            <div style="font-weight:600; color:var(--gold); margin-bottom:.5rem;">R <?= number_format($p['sellPrice'], 2) ?></div>
            
            <!-- Status Badge -->
            <div style="margin-bottom:.5rem;">
              <span style="padding:.2rem .4rem; border-radius:4px; font-size:.75rem; 
                background:<?= $p['status'] === 'active' ? '#122a12' : ($p['status'] === 'sold' ? '#2a1212' : '#2a2a2a') ?>; 
                color:<?= $p['status'] === 'active' ? '#5cb85c' : ($p['status'] === 'sold' ? '#e05252' : '#888') ?>;">
                <?= ucfirst($p['status']) ?>
              </span>
            </div>
            
            <!-- Sales Info -->
            <div style="font-size:.8rem; color:var(--muted); margin-bottom:.3rem;">
              <strong style="color:var(--text);"><?= (int)$p['totalSales'] ?></strong> sale<?= $p['totalSales'] != 1 ? 's' : '' ?>
            </div>
            <div style="font-size:.8rem; color:var(--muted);">
              Revenue: <strong style="color:var(--gold);">R <?= number_format($p['totalRevenue'] ?? 0, 2) ?></strong>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Sales Summary ──────────────────────────────────────── -->
    <?php
      $totalProducts = count($products);
      $activeListed = count(array_filter($products, function($p) { return $p['status'] === 'active'; }));
      $totalSalesMade = array_sum(array_map(function($p) { return (int)$p['totalSales']; }, $products));
      $totalRevenueEarned = array_sum(array_map(function($p) { return (float)($p['totalRevenue'] ?? 0); }, $products));
    ?>
    <div style="background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-top:2rem; display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem;">
      <div style="text-align:center; border-right:1px solid var(--border);">
        <div style="font-size:1.8rem; color:var(--gold); font-weight:bold;"><?= $totalProducts ?></div>
        <div style="font-size:.8rem; color:var(--muted); margin-top:.3rem;">Total Products</div>
      </div>
      <div style="text-align:center; border-right:1px solid var(--border);">
        <div style="font-size:1.8rem; color:#5cb85c; font-weight:bold;"><?= $activeListed ?></div>
        <div style="font-size:.8rem; color:var(--muted); margin-top:.3rem;">Active Listings</div>
      </div>
      <div style="text-align:center; border-right:1px solid var(--border);">
        <div style="font-size:1.8rem; color:#c9a86c; font-weight:bold;"><?= $totalSalesMade ?></div>
        <div style="font-size:.8rem; color:var(--muted); margin-top:.3rem;">Total Sales</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:1.8rem; color:var(--gold); font-weight:bold;">R <?= number_format($totalRevenueEarned, 2) ?></div>
        <div style="font-size:.8rem; color:var(--muted); margin-top:.3rem;">Total Revenue</div>
      </div>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
