<?php
/**
 * admin/login.php
 * Admin login — validates against tblAdmin using MD5 hash.
 * Username must be admin email address.
 */

session_start();
require_once '../DBConn.php';

// Already logged in as admin?
if (isset($_SESSION['adminID'])) {
    header("Location: index.php");
    exit;
}

$error       = '';
$stickyEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $stickyEmail = $email;

    if (empty($email) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $hashed = md5($password);

        $stmt = $conn->prepare(
            "SELECT adminID, fullName, email FROM tblAdmin WHERE email = ? AND password = ?"
        );
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $email, $hashed);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error = "Invalid admin credentials.";
            } else {
                $admin = $result->fetch_assoc();
                $_SESSION['adminID']   = $admin['adminID'];
                $_SESSION['adminName'] = $admin['fullName'];
                $_SESSION['adminEmail']= $admin['email'];
                $_SESSION['role']      = 'admin';

                header("Location: index.php");
                exit;
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
<title>Admin Login — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#070708; --card:#111; --gold:#c9a86c; --text:#e5e5e5; --muted:#666; --err:#e05252; --border:#222; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
  .card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:2.5rem; width:100%; max-width:400px; }
  .admin-badge { display:inline-block; background:#1a1507; border:1px solid var(--gold); color:var(--gold); font-size:.75rem; padding:.25rem .75rem; border-radius:20px; letter-spacing:.08em; margin-bottom:1.25rem; }
  h1 { font-family:'Playfair Display',serif; color:var(--text); font-size:1.7rem; margin-bottom:.25rem; }
  .subtitle { color:var(--muted); font-size:.88rem; margin-bottom:2rem; }
  .alert { background:#2a1212; border-left:4px solid var(--err); padding:.8rem 1rem; border-radius:var(--radius); margin-bottom:1.2rem; color:#ffaaaa; font-size:.9rem; }
  .form-group { margin-bottom:1.2rem; }
  label { display:block; font-size:.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:.4rem; }
  input { width:100%; background:#191919; border:1px solid var(--border); color:var(--text); padding:.65rem .9rem; border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.95rem; }
  input:focus { outline:none; border-color:var(--gold); }
  .btn { width:100%; padding:.75rem; background:linear-gradient(135deg,#8b6914,var(--gold)); color:#fff; border:none; border-radius:var(--radius); cursor:pointer; font-size:1rem; font-weight:600; }
  .btn:hover { opacity:.9; }
  .back { text-align:center; margin-top:1.5rem; font-size:.85rem; color:var(--muted); }
  .back a { color:var(--gold); text-decoration:none; }
</style>
</head>
<body>
<div class="card">
  <div class="admin-badge">🔐 ADMIN ACCESS</div>
  <h1>Admin Login</h1>
  <p class="subtitle">PASTIMES Administration Panel</p>

  <?php if ($error): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Admin Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($stickyEmail) ?>"
             placeholder="admin@pastimes.co.za" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">Login to Admin Panel</button>
  </form>

  <div class="back"><a href="../login.php">← User Login</a> &nbsp;|&nbsp; <a href="register.php">Register as Admin</a></div>
</div>
</body>
</html>
