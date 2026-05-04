<?php
/**
 * admin/register.php — PASTIMES
 * Registers a new admin account.
 * Requires a secret admin invite code to prevent public access.
 */

session_start();
require_once '../DBConn.php';

if (isset($_SESSION['adminID'])) {
    header("Location: index.php"); exit;
}

define('ADMIN_INVITE_CODE', 'Ayanda_8');

$errors   = [];
$success  = '';
$fullName = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName   = trim($_POST['fullName']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = trim($_POST['password']   ?? '');
    $confirm    = trim($_POST['confirm']    ?? '');
    $inviteCode = trim($_POST['inviteCode'] ?? '');

    if (empty($fullName))  $errors[] = "Full name is required.";
    if (empty($email))     $errors[] = "Email address is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($password))  $errors[] = "Password is required.";
    elseif (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    if ($inviteCode !== ADMIN_INVITE_CODE) $errors[] = "Invalid admin invite code.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT adminID FROM tblAdmin WHERE email = ?");
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute(); $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "An admin account with that email already exists.";
            $stmt->close();
        }
    }

    if (empty($errors)) {
        $hashed = md5($password);
        $stmt = $conn->prepare("INSERT INTO tblAdmin (fullName, email, password) VALUES (?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("sss", $fullName, $email, $hashed);
            if ($stmt->execute()) {
                $success  = "Admin account created! You can now <a href='login.php'>login here</a>.";
                $fullName = $email = '';
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Register — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root{--bg:#070708;--card:#111;--gold:#c9a86c;--gold2:#e8c98a;--text:#e5e5e5;--muted:#666;--muted2:#999;--err:#e05252;--ok:#5cb85c;--border:#222;--border2:#2a2a2a;--r:8px;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;}
  .wrap{width:100%;max-width:460px;}
  .logo-row{text-align:center;margin-bottom:2rem;}
  .logo{font-family:'Playfair Display',serif;color:var(--gold);font-size:1.3rem;text-decoration:none;line-height:1.2;}
  .logo span{display:block;font-style:italic;font-weight:400;font-size:.75rem;color:var(--muted);margin-top:2px;}
  .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2.25rem;}
  .admin-badge{display:inline-block;background:#1a1507;border:1px solid var(--gold);color:var(--gold);font-size:.72rem;padding:.25rem .75rem;border-radius:20px;letter-spacing:.08em;margin-bottom:1.2rem;}
  h1{font-family:'Playfair Display',serif;color:var(--text);font-size:1.65rem;margin-bottom:.25rem;}
  .subtitle{color:var(--muted2);font-size:.88rem;margin-bottom:1.75rem;line-height:1.4;}
  .alert{padding:.85rem 1rem;border-radius:var(--r);margin-bottom:1.2rem;font-size:.9rem;}
  .alert-err{background:#2a1212;border-left:4px solid var(--err);color:#ffaaaa;}
  .alert-ok{background:#122a12;border-left:4px solid var(--ok);color:#aaffaa;}
  .alert-ok a{color:#7fdc7f;}
  .fg{margin-bottom:1.15rem;}
  label{display:block;font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;}
  input{width:100%;padding:.65rem .9rem;background:#191919;border:1px solid var(--border2);color:var(--text);border-radius:var(--r);font-family:'DM Sans',sans-serif;font-size:.95rem;transition:border-color .2s;}
  input:focus{outline:none;border-color:var(--gold);}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .invite-box{background:#1a1507;border:1px solid #3a2e12;border-radius:var(--r);padding:1rem 1.1rem;margin-bottom:1.25rem;}
  .invite-label{font-size:.8rem;color:var(--gold);font-weight:500;margin-bottom:.5rem;}
  .invite-box input{background:#120f04;border-color:#3a2e12;}
  .invite-box input:focus{border-color:var(--gold);}
  .invite-hint{font-size:.76rem;color:var(--muted);margin-top:.4rem;}
  hr{border:none;border-top:1px solid var(--border2);margin:1.25rem 0;}
  .btn{width:100%;padding:.8rem;background:linear-gradient(135deg,#8b6914,var(--gold));color:#fff;border:none;border-radius:var(--r);cursor:pointer;font-size:1rem;font-weight:600;letter-spacing:.02em;}
  .btn:hover{opacity:.9;}
  .footer{text-align:center;margin-top:1.3rem;font-size:.86rem;color:var(--muted);}
  .footer a{color:var(--gold);text-decoration:none;}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo-row">
    <a href="../index.php" class="logo">
      PASTIMES
      <span>Administration Portal</span>
    </a>
  </div>
  <div class="card">
    <div class="admin-badge">🔐 ADMIN REGISTRATION</div>
    <h1>Create Admin Account</h1>
    <p class="subtitle">Restricted access — an invite code is required to register.</p>

    <?php if ($success): ?>
      <div class="alert alert-ok"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-err">
        <?php foreach($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php" novalidate>
      <div class="fg">
        <label>Full Name</label>
        <input type="text" name="fullName" value="<?= htmlspecialchars($fullName) ?>" placeholder="Your full name" required>
      </div>
      <div class="fg">
        <label>Email Address</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="admin@discoverandrewind.co.za" required>
      </div>
      <div class="two">
        <div class="fg">
          <label>Password</label>
          <input type="password" name="password" placeholder="Min 8 characters" required>
        </div>
        <div class="fg">
          <label>Confirm Password</label>
          <input type="password" name="confirm" placeholder="Repeat password" required>
        </div>
      </div>
      <hr>
      <div class="invite-box">
        <div class="invite-label">🔑 Admin Invite Code</div>
        <input type="password" name="inviteCode" placeholder="Enter your admin invite code" required>
        <div class="invite-hint">Contact the store owner to get this code. Current code: <strong style="color:var(--gold)">Ayanda_8</strong></div>
      </div>
      <button type="submit" class="btn">Register Admin Account →</button>
    </form>

    <div class="footer">
      Already have an account? <a href="login.php">Login here</a>
      &nbsp;|&nbsp; <a href="../index.php">Back to Store</a>
    </div>
  </div>
</div>
</body>
</html>
