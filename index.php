<?php
/**
 * index.php — PASTIMES Home
 */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PASTIMES — Vintage Finds, Verified Sellers</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0c0c0c; --gold:#c9a86c; --gold2:#e8c98a; --text:#e5e5e5; --muted:#888; --border:#2a2a2a; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); }
  nav {
    background:#111; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; padding:.9rem 2.5rem;
    position:sticky; top:0; z-index:100;
  }
  .logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.4rem; text-decoration:none; }
  .nav-links a { color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem; transition:color .2s; }
  .nav-links a:hover { color:var(--gold); }
  .btn-nav { background:var(--gold); color:#111; padding:.4rem 1rem; border-radius:var(--radius); font-weight:600; }

  /* Hero */
  .hero {
    padding:6rem 2.5rem;
    background: radial-gradient(ellipse at 70% 40%, #2a1e0a 0%, #0c0c0c 70%);
    text-align:center;
  }
  .hero h1 { font-family:'Playfair Display',serif; font-size:clamp(2.5rem,6vw,4.5rem); color:var(--text); line-height:1.1; }
  .hero h1 span { color:var(--gold); font-style:italic; }
  .hero p { color:var(--muted); font-size:1.1rem; max-width:560px; margin:1.25rem auto 2.5rem; }
  .hero-btns { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; }
  .btn-primary { background:var(--gold); color:#111; padding:.75rem 2rem; border-radius:var(--radius); font-weight:600; text-decoration:none; font-size:1rem; }
  .btn-secondary { border:1px solid var(--border); color:var(--text); padding:.75rem 2rem; border-radius:var(--radius); text-decoration:none; font-size:1rem; }
  .btn-primary:hover { background:var(--gold2); }
  .btn-secondary:hover { border-color:var(--gold); color:var(--gold); }

  /* Features */
  .features { display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem; max-width:900px; margin:4rem auto; padding:0 2rem; }
  @media(max-width:700px){ .features{grid-template-columns:1fr;} }
  .feat { text-align:center; padding:1.5rem; background:#111; border:1px solid var(--border); border-radius:var(--radius); }
  .feat-icon { font-size:2rem; margin-bottom:.75rem; }
  .feat h3 { color:var(--gold); font-family:'Playfair Display',serif; margin-bottom:.5rem; }
  .feat p  { color:var(--muted); font-size:.9rem; }

  footer { text-align:center; padding:2rem; border-top:1px solid var(--border); color:var(--muted); font-size:.85rem; margin-top:4rem; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">PASTIMES</a>
  <div class="nav-links">
    <a href="shop.php">Shop</a>
    <?php if (isset($_SESSION['userID'])): ?>
      <a href="dashboard.php">My Account</a>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php" class="btn-nav">Register</a>
    <?php endif; ?>
  </div>
</nav>

<section class="hero">
  <h1>Quality Clothing,<br><span>Pre-Loved Stories</span></h1>
  <p>Discover unique pieces from verified sellers. Sustainable fashion at unbeatable prices.</p>
  <div class="hero-btns">
    <a href="shop.php" class="btn-primary">Browse Shop →</a>
    <a href="register.php" class="btn-secondary">Join Free</a>
  </div>
</section>

<div class="features">
  <div class="feat">
    <div class="feat-icon">✅</div>
    <h3>Verified Sellers</h3>
    <p>Every account is admin-reviewed before selling or buying.</p>
  </div>
  <div class="feat">
    <div class="feat-icon">♻️</div>
    <h3>Sustainable Fashion</h3>
    <p>Pre-loved items — reducing waste, one garment at a time.</p>
  </div>
  <div class="feat">
    <div class="feat-icon">💰</div>
    <h3>Great Prices</h3>
    <p>Save up to 70% off retail price on top brands.</p>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> PASTIMES — WEDE6021 POE Project
  &nbsp;|&nbsp; <a href="admin/login.php" style="color:var(--muted)">Admin</a>
</footer>
</body>
</html>
