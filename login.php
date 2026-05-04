<?php
/**
 * login.php
 * Authenticates users against tblUser.
 * - Compares MD5 hash
 * - Checks isVerified = 1 before allowing login
 * - Sticky form on failure
 */

session_start();
require_once 'DBConn.php';

// Already logged in?
if (isset($_SESSION['userID'])) {
    if (($_SESSION['role'] ?? null) === 'seller') {
        header("Location: seller-products.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error    = '';
$stickyEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $stickyEmail = $email;   // sticky — keep email visible on error

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } else {
        $hashed = md5($password);

        // Associative read using column names
        $stmt = $conn->prepare(
            "SELECT userID, fullName, email, province, isVerified, status, role
             FROM tblUser
             WHERE email = ? AND password = ?"
        );
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $email, $hashed);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error = "Incorrect email or password. Please try again.";
            } else {
                $user = $result->fetch_assoc();   // associative read

                if ($user['isVerified'] != 1 || $user['status'] !== 'active') {
                    $error = "Your account is pending administrator verification. Please check back later.";
                } else {
                    // ── Success: store session ────────────────────────────────────
                    $_SESSION['userID']   = $user['userID'];
                    $_SESSION['fullName'] = $user['fullName'];
                    $_SESSION['email']    = $user['email'];
                    $_SESSION['role']     = $user['role'];

                    if ($user['role'] === 'seller') {
                        header("Location: seller-products.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;
                }
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
<title>Login — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:   #0c0c0c; --card:#161616; --gold:#c9a86c; --gold2:#e8c98a;
    --text: #e5e5e5; --muted:#888;   --err:#e05252;  --border:#2a2a2a;
    --radius:8px;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body {
    font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text);
    min-height:100vh; display:flex; flex-direction:column;
    align-items:center; padding:2rem 1rem;
  }
  nav {
    width:100%; max-width:900px; display:flex; justify-content:space-between;
    align-items:center; padding:.75rem 0; border-bottom:1px solid var(--border);
    margin-bottom:2.5rem;
  }
  .logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.4rem; text-decoration:none; }
  .nav-links a { color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem; }
  .nav-links a:hover { color:var(--gold); }

  .card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:2.5rem; width:100%; max-width:420px; }
  h1 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.8rem; margin-bottom:.25rem; }
  .subtitle { color:var(--muted); font-size:.9rem; margin-bottom:2rem; }
  .alert { padding:.9rem 1rem; border-radius:var(--radius); margin-bottom:1.2rem; font-size:.9rem; background:#2a1212; border-left:4px solid var(--err); color:#ffaaaa; }
  .form-group { margin-bottom:1.25rem; }
  label { display:block; font-size:.82rem; color:var(--muted); margin-bottom:.4rem; letter-spacing:.04em; text-transform:uppercase; }
  input {
    width:100%; padding:.65rem .9rem; background:#1f1f1f;
    border:1px solid var(--border); color:var(--text);
    border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.95rem;
  }
  input:focus { outline:none; border-color:var(--gold); }
  .btn {
    width:100%; padding:.75rem;
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    color:#111; font-weight:600; border:none; border-radius:var(--radius);
    cursor:pointer; font-size:1rem;
  }
  .btn:hover { opacity:.9; }
  .form-footer { text-align:center; margin-top:1.5rem; font-size:.88rem; color:var(--muted); }
  .form-footer a { color:var(--gold); text-decoration:none; }
  .admin-link { text-align:center; margin-top:1rem; }
  .admin-link a { color:var(--muted); font-size:.85rem; text-decoration:none; border:1px solid var(--border); padding:.4rem .9rem; border-radius:var(--radius); }
  .admin-link a:hover { border-color:var(--gold); color:var(--gold); }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">PASTIMES</a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="shop.php">Shop</a>
    <a href="register.php">Register</a>
  </div>
</nav>

<div class="card">
  <h1>Welcome Back</h1>
  <p class="subtitle">Login to your PASTIMES account</p>

  <?php if ($error): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-group">
      <label for="email">Email Address</label>
      <!-- Sticky: pre-fills email on failed login -->
      <input type="email" id="email" name="email"
             value="<?= htmlspecialchars($stickyEmail) ?>"
             placeholder="you@example.co.za" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password"
             placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">Login</button>
  </form>

  <div class="form-footer">
    Don't have an account? <a href="register.php">Register here</a>
  </div>

  <!-- Admin login button as required -->
  <div class="admin-link">
    <a href="admin/login.php">🔐 Admin Login</a>
  </div>
</div>
</body>
</html>
