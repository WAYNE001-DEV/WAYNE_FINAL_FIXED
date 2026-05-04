<?php
/**
 * register.php - PASTIMES
 * User chooses Buyer or Seller role at registration.
 * Status = 'pending' until admin approves.
 */
session_start();
require_once 'DBConn.php';

$errors  = [];
$success = '';
$fullName = $email = $province = '';
$role     = $_POST['role'] ?? 'buyer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = trim($_POST['password']  ?? '');
    $confirm  = trim($_POST['confirm']   ?? '');
    $province = trim($_POST['province']  ?? '');
    $role     = in_array($_POST['role'] ?? '', ['buyer','seller']) ? $_POST['role'] : 'buyer';

    if (empty($fullName))  $errors[] = "Full name is required.";
    if (empty($email))     $errors[] = "Email address is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($password))  $errors[] = "Password is required.";
    elseif (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    if (empty($province))  $errors[] = "Please select your province.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT userID FROM tblUser WHERE email = ?");
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "An account with that email already exists.";
            $stmt->close();
        }
    }

    if (empty($errors)) {
        $hashed = md5($password);
        $stmt = $conn->prepare(
            "INSERT INTO tblUser (fullName, email, password, province, isVerified, status, role)
             VALUES (?, ?, ?, ?, 0, 'pending', ?)"
        );
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("sssss", $fullName, $email, $hashed, $province, $role);
            if ($stmt->execute()) {
                $roleLabel = $role === 'seller' ? 'Seller' : 'Buyer';
                $success   = "Registration successful! Your <strong>$roleLabel</strong> account is pending admin verification.";
                $fullName  = $email = $province = '';
                $role      = 'buyer';
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
$conn->close();

$provinces = ['Eastern Cape','Free State','Gauteng','KwaZulu-Natal',
              'Limpopo','Mpumalanga','North West','Northern Cape','Western Cape'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root{--bg:#0c0c0c;--card:#161616;--gold:#c9a86c;--gold2:#e8c98a;--text:#e5e5e5;--muted:#888;--err:#e05252;--ok:#5cb85c;--border:#2a2a2a;--r:8px;--buyer:#4a90d9;--seller:#c9a86c;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem;}
  nav{width:100%;max-width:960px;display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--border);margin-bottom:2.5rem;}
  .logo{font-family:'Playfair Display',serif;color:var(--gold);font-size:1.25rem;text-decoration:none;line-height:1.2;}
  .logo span{display:block;font-style:italic;font-weight:400;font-size:.78rem;color:var(--muted);}
  .nav-links a{color:var(--muted);text-decoration:none;margin-left:1.5rem;font-size:.9rem;}
  .nav-links a:hover{color:var(--gold);}
  .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2.5rem;width:100%;max-width:500px;}
  h1{font-family:'Playfair Display',serif;color:var(--gold);font-size:1.8rem;margin-bottom:.25rem;}
  .subtitle{color:var(--muted);font-size:.9rem;margin-bottom:1.75rem;}
  /* Role toggle */
  .role-title{font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.65rem;display:block;}
  .role-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:1.5rem;}
  .role-opt{position:relative;}
  .role-opt input{position:absolute;opacity:0;width:0;height:0;}
  .role-card{display:flex;flex-direction:column;align-items:center;gap:6px;padding:1rem .75rem;border-radius:10px;border:2px solid var(--border);background:#1a1a1a;cursor:pointer;transition:all .2s;text-align:center;}
  .role-card .ico{font-size:1.7rem;}
  .role-card .rname{font-size:.92rem;font-weight:500;}
  .role-card .rdesc{font-size:.76rem;color:var(--muted);line-height:1.35;}
  .role-opt input[value="buyer"]:checked~.role-card{border-color:var(--buyer);background:rgba(74,144,217,.09);box-shadow:0 0 0 1px var(--buyer);}
  .role-opt input[value="buyer"]:checked~.role-card .rname{color:var(--buyer);}
  .role-opt input[value="seller"]:checked~.role-card{border-color:var(--seller);background:rgba(201,168,108,.09);box-shadow:0 0 0 1px var(--seller);}
  .role-opt input[value="seller"]:checked~.role-card .rname{color:var(--seller);}
  .role-card:hover{border-color:var(--muted);}
  hr{border:none;border-top:1px solid var(--border);margin:0 0 1.4rem;}
  /* Alerts */
  .alert{padding:.9rem 1rem;border-radius:var(--r);margin-bottom:1.2rem;font-size:.9rem;}
  .alert-err{background:#2a1212;border-left:4px solid var(--err);color:#ffaaaa;}
  .alert-ok{background:#122a12;border-left:4px solid var(--ok);color:#aaffaa;}
  /* Fields */
  .fg{margin-bottom:1.2rem;}
  label{display:block;font-size:.8rem;color:var(--muted);margin-bottom:.4rem;letter-spacing:.04em;text-transform:uppercase;}
  input,select{width:100%;padding:.65rem .9rem;background:#1f1f1f;border:1px solid var(--border);color:var(--text);border-radius:var(--r);font-family:'DM Sans',sans-serif;font-size:.95rem;transition:border-color .2s;}
  input:focus,select:focus{outline:none;border-color:var(--gold);}
  select option{background:#1f1f1f;}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .btn{width:100%;padding:.8rem;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#111;font-weight:600;border:none;border-radius:var(--r);cursor:pointer;font-size:1rem;letter-spacing:.03em;}
  .btn:hover{opacity:.9;}
  .footer{text-align:center;margin-top:1.4rem;font-size:.88rem;color:var(--muted);}
  .footer a{color:var(--gold);text-decoration:none;}
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">
    PASTIMES
    <span>Pre-loved fashion, rediscovered</span>
  </a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="shop.php">Shop</a>
    <a href="login.php">Login</a>
  </div>
</nav>

<div class="card">
  <h1>Create Account</h1>
  <p class="subtitle">Join the PASTIMES community</p>

  <?php if ($success): ?><div class="alert alert-ok"><?= $success ?></div><?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-err"><?php foreach($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" novalidate>

    <!-- Role selection -->
    <span class="role-title">I want to…</span>
    <div class="role-grid">
      <label class="role-opt">
        <input type="radio" name="role" value="buyer" <?= $role==='buyer'?'checked':'' ?>>
        <div class="role-card">
          <div class="ico">🛍️</div>
          <div class="rname">Shop as Buyer</div>
          <div class="rdesc">Browse &amp; buy pre-loved clothing</div>
        </div>
      </label>
      <label class="role-opt">
        <input type="radio" name="role" value="seller" <?= $role==='seller'?'checked':'' ?>>
        <div class="role-card">
          <div class="ico">🏷️</div>
          <div class="rname">List as Seller</div>
          <div class="rdesc">Sell your pre-loved clothes to others</div>
        </div>
      </label>
    </div>

    <hr>

    <div class="fg">
      <label for="fullName">Full Name</label>
      <input type="text" id="fullName" name="fullName" value="<?= htmlspecialchars($fullName) ?>" placeholder="Your full name" required>
    </div>
    <div class="fg">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="you@example.co.za" required>
    </div>
    <div class="two">
      <div class="fg">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Min 8 characters" required>
      </div>
      <div class="fg">
        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm" placeholder="Repeat password" required>
      </div>
    </div>
    <div class="fg">
      <label for="province">Province</label>
      <select id="province" name="province" required>
        <option value="">Select your province…</option>
        <?php foreach($provinces as $p): ?>
          <option value="<?= $p ?>" <?= $province===$p?'selected':'' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn">Create My Account →</button>
  </form>

  <div class="footer">Already have an account? <a href="login.php">Login here</a></div>
</div>
</body>
</html>
