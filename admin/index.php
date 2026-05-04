<?php
/**
 * admin/index.php
 * Admin dashboard:
 * - Verify new customer registrations (approve / reject)
 * - Add, update, delete customers
 */

session_start();
require_once '../DBConn.php';

// Guard: must be admin
if (!isset($_SESSION['adminID'])) {
    header("Location: login.php");
    exit;
}

$msg = '';

// ── Handle actions ────────────────────────────────────────────────────────────

// Verify (approve) a user
if (isset($_GET['verify'])) {
    $uid = (int) $_GET['verify'];
    $stmt = $conn->prepare("UPDATE tblUser SET isVerified=1, status='active' WHERE userID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute(); $stmt->close();
    $msg = "✅ User verified and activated.";
}

// Reject / deactivate a user
if (isset($_GET['reject'])) {
    $uid = (int) $_GET['reject'];
    $stmt = $conn->prepare("UPDATE tblUser SET isVerified=0, status='inactive' WHERE userID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute(); $stmt->close();
    $msg = "⚠️  User has been rejected/deactivated.";
}

// Delete a user
if (isset($_GET['delete'])) {
    $uid = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tblUser WHERE userID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute(); $stmt->close();
    $msg = "🗑️  User deleted.";
}

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $fn = trim($_POST['fullName'] ?? '');
    $em = trim($_POST['email']    ?? '');
    $pw = trim($_POST['password'] ?? '');
    $pr = trim($_POST['province'] ?? '');
    $iv = (int)($_POST['isVerified'] ?? 0);

    if ($fn && $em && $pw && $pr) {
        $hashed = md5($pw);
        $status = $iv ? 'active' : 'pending';
        $stmt = $conn->prepare(
            "INSERT INTO tblUser (fullName,email,password,province,isVerified,status) VALUES(?,?,?,?,?,?)"
        );
        $stmt->bind_param("ssssis", $fn, $em, $hashed, $pr, $iv, $status);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ New user '$fn' added.";
    } else {
        $msg = "❌ All fields are required to add a user.";
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $uid = (int)($_POST['userID']     ?? 0);
    $fn  = trim($_POST['fullName']    ?? '');
    $em  = trim($_POST['email']       ?? '');
    $pr  = trim($_POST['province']    ?? '');
    $iv  = (int)($_POST['isVerified'] ?? 0);
    $st  = trim($_POST['status']      ?? 'pending');

    if ($uid && $fn && $em) {
        $stmt = $conn->prepare(
            "UPDATE tblUser SET fullName=?,email=?,province=?,isVerified=?,status=? WHERE userID=?"
        );
        $stmt->bind_param("sssisi", $fn, $em, $pr, $iv, $st, $uid);
        $stmt->execute(); $stmt->close();
        $msg = "✅ User #$uid updated.";
    }
}

// ── Fetch users ───────────────────────────────────────────────────────────────
$users = $conn->query(
    "SELECT userID, fullName, email, province, isVerified, status, createdAt
     FROM tblUser ORDER BY createdAt DESC"
)->fetch_all(MYSQLI_ASSOC);

// ── Stats ─────────────────────────────────────────────────────────────────────
$totalUsers   = count($users);
$pending      = count(array_filter($users, function($u) { return $u['status'] === 'pending'; }));
$active       = count(array_filter($users, function($u) { return $u['status'] === 'active'; }));

$conn->close();

$provinces = ['Eastern Cape','Free State','Gauteng','KwaZulu-Natal',
              'Limpopo','Mpumalanga','North West','Northern Cape','Western Cape'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#07070a; --card:#111318; --gold:#c9a86c; --text:#e5e5e5; --muted:#666; --border:#1e1e28; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

  /* Sidebar */
  aside { width:220px; background:#0d0d12; border-right:1px solid var(--border); padding:1.5rem 1rem; flex-shrink:0; }
  .sidebar-logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.1rem; margin-bottom:2rem; display:block; }
  .nav-item { display:block; padding:.6rem .8rem; color:var(--muted); text-decoration:none; border-radius:var(--radius); font-size:.9rem; margin-bottom:.25rem; }
  .nav-item:hover, .nav-item.active { background:#1a1a24; color:var(--text); }
  .nav-item.danger { color:#e05252; }

  /* Main */
  main { flex:1; padding:2rem; overflow-y:auto; }
  h1 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.6rem; margin-bottom:.25rem; }
  .admin-sub { color:var(--muted); font-size:.88rem; margin-bottom:2rem; }

  /* Stats */
  .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:2rem; }
  .stat-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.2rem; text-align:center; }
  .stat-num { font-family:'Playfair Display',serif; color:var(--gold); font-size:2rem; }
  .stat-lbl { color:var(--muted); font-size:.8rem; margin-top:.25rem; }

  /* Message */
  .msg { background:#1a2a1a; border-left:4px solid var(--gold); padding:.75rem 1rem; border-radius:var(--radius); margin-bottom:1.5rem; font-size:.9rem; }

  /* Tables */
  .section-title { font-family:'Playfair Display',serif; color:var(--text); font-size:1.1rem; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:.5rem; }
  table { width:100%; border-collapse:collapse; margin-bottom:2.5rem; font-size:.88rem; }
  th { background:#14141c; padding:.65rem .9rem; text-align:left; color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--border); }
  td { padding:.65rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
  tr:last-child td { border-bottom:none; }

  .badge { display:inline-block; padding:.2rem .6rem; border-radius:20px; font-size:.72rem; font-weight:600; }
  .b-active   { background:#122a12; color:#5cb85c; }
  .b-pending  { background:#2a2510; color:#c9a86c; }
  .b-inactive { background:#2a1212; color:#e05252; }

  .action-btn {
    padding:.3rem .75rem; border:none; border-radius:var(--radius);
    cursor:pointer; font-size:.78rem; font-weight:600; margin-right:.3rem; text-decoration:none; display:inline-block;
  }
  .btn-verify  { background:#122a12; color:#5cb85c; }
  .btn-reject  { background:#2a1a00; color:#ff9800; }
  .btn-delete  { background:#2a1212; color:#e05252; }
  .btn-edit    { background:#1a1a2a; color:#8888ff; }

  /* Add/Edit form */
  .form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:2.5rem; }
  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
  @media(max-width:700px){ .form-grid{grid-template-columns:1fr;} }
  .form-group { display:flex; flex-direction:column; gap:.3rem; }
  label { font-size:.78rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
  input, select { background:#191924; border:1px solid var(--border); color:var(--text); padding:.6rem .8rem; border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.9rem; }
  input:focus, select:focus { outline:none; border-color:var(--gold); }
  select option { background:#191924; }
  .submit-btn { margin-top:1rem; padding:.65rem 1.5rem; background:var(--gold); color:#111; border:none; border-radius:var(--radius); cursor:pointer; font-weight:700; font-size:.9rem; }

  /* Edit modal */
  .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1000; align-items:center; justify-content:center; }
  .modal-overlay.show { display:flex; }
  .modal { background:#111318; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; width:100%; max-width:500px; }
  .modal h3 { color:var(--gold); font-family:'Playfair Display',serif; margin-bottom:1.25rem; }
  .modal-close { float:right; background:transparent; border:none; color:var(--muted); font-size:1.2rem; cursor:pointer; }
</style>
</head>
<body>

<aside>
  <span class="sidebar-logo">CS Admin</span>
  <a href="index.php" class="nav-item active">👥 Users</a>
  <a href="../shop.php" class="nav-item">🛍️ Shop</a>
  <a href="logout.php" class="nav-item danger">🚪 Logout</a>
</aside>

<main>
  <h1>Admin Panel</h1>
  <p class="admin-sub">Logged in as <?= htmlspecialchars($_SESSION['adminName']) ?></p>

  <?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card"><div class="stat-num"><?= $totalUsers ?></div><div class="stat-lbl">Total Users</div></div>
    <div class="stat-card"><div class="stat-num"><?= $active ?></div><div class="stat-lbl">Active</div></div>
    <div class="stat-card"><div class="stat-num"><?= $pending ?></div><div class="stat-lbl">Pending Verification</div></div>
  </div>

  <!-- ── Add New User ──────────────────────────────────────────── -->
  <div class="section-title">➕ Add New Customer</div>
  <div class="form-card">
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>Full Name</label><input type="text" name="fullName" placeholder="John Doe" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="user@example.co.za" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Min 8 chars" required></div>
        <div class="form-group">
          <label>Province</label>
          <select name="province" required>
            <option value="">Select…</option>
            <?php foreach ($provinces as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Verified?</label>
          <select name="isVerified">
            <option value="0">No (Pending)</option>
            <option value="1">Yes (Active)</option>
          </select>
        </div>
      </div>
      <button type="submit" class="submit-btn">Add User</button>
    </form>
  </div>

  <!-- ── Pending Users ────────────────────────────────────────── -->
  <?php $pendingUsers = array_filter($users, function($u) { return $u['status'] === 'pending'; }); ?>
  <div class="section-title">⏳ Pending Verification (<?= count($pendingUsers) ?>)</div>
  <?php if (empty($pendingUsers)): ?>
    <p style="color:var(--muted);margin-bottom:2rem">No pending users.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Province</th><th>Registered</th><th>Actions</th></tr>
      <?php foreach ($pendingUsers as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['fullName']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['province'] ?? '—') ?></td>
        <td><?= date('d M Y', strtotime($u['createdAt'])) ?></td>
        <td>
          <a href="?verify=<?= $u['userID'] ?>" class="action-btn btn-verify" onclick="return confirm('Verify this user?')">✅ Verify</a>
          <a href="?reject=<?= $u['userID'] ?>" class="action-btn btn-reject" onclick="return confirm('Reject this user?')">❌ Reject</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <!-- ── All Users ─────────────────────────────────────────────── -->
  <div class="section-title">👥 All Customers</div>
  <table>
    <tr><th>#</th><th>Name</th><th>Email</th><th>Province</th><th>Verified</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['userID'] ?></td>
      <td><?= htmlspecialchars($u['fullName']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['province'] ?? '—') ?></td>
      <td><?= $u['isVerified'] ? '✅' : '⏳' ?></td>
      <td><span class="badge b-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
      <td><?= date('d M Y', strtotime($u['createdAt'])) ?></td>
      <td>
        <?php if ($u['status'] !== 'active'): ?>
          <a href="?verify=<?= $u['userID'] ?>" class="action-btn btn-verify">✅</a>
        <?php endif; ?>
        <button class="action-btn btn-edit" onclick="openEdit(
          <?= $u['userID'] ?>,
          '<?= addslashes($u['fullName']) ?>',
          '<?= addslashes($u['email']) ?>',
          '<?= addslashes($u['province'] ?? '') ?>',
          <?= $u['isVerified'] ?>,
          '<?= $u['status'] ?>'
        )">✏️ Edit</button>
        <a href="?delete=<?= $u['userID'] ?>" class="action-btn btn-delete"
           onclick="return confirm('Permanently delete this user?')">🗑️</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</main>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('show')">✕</button>
    <h3>Edit Customer</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="userID" id="editUID">
      <div class="form-grid">
        <div class="form-group"><label>Full Name</label><input type="text" name="fullName" id="editName" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail" required></div>
        <div class="form-group">
          <label>Province</label>
          <select name="province" id="editProvince">
            <?php foreach ($provinces as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Verified</label>
          <select name="isVerified" id="editVerified">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="editStatus">
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <button type="submit" class="submit-btn">Save Changes</button>
    </form>
  </div>
</div>

<script>
function openEdit(uid, name, email, province, verified, status) {
  document.getElementById('editUID').value      = uid;
  document.getElementById('editName').value     = name;
  document.getElementById('editEmail').value    = email;
  document.getElementById('editProvince').value = province;
  document.getElementById('editVerified').value = verified;
  document.getElementById('editStatus').value   = status;
  document.getElementById('editModal').classList.add('show');
}
</script>
</body>
</html>
