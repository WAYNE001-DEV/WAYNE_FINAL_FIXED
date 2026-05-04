<?php
/**
 * createTable.php — PASTIMES
 * Student: [Your Name] | [Student Number]
 * Declaration: This code is my own work where not referenced.
 *
 * This script:
 *  1. Checks if tblUser exists → drops it
 *  2. Re-creates tblUser with the correct schema
 *  3. Loads data from userData.txt into tblUser
 *
 * Run via: http://localhost/WAYNE_FINAL/createTable.php
 */

// ── Include DB connection (DBConn.php embedded as include) ───────────────────
require_once 'DBConn.php';

$messages = [];

// ── 1. Drop tblUser if it exists ──────────────────────────────────────────────
if ($conn->query("DROP TABLE IF EXISTS tblUser")) {
    $messages[] = "✅ tblUser dropped (or did not exist).";
} else {
    $messages[] = "❌ Error dropping tblUser: " . $conn->error;
}

// ── 2. Create tblUser ─────────────────────────────────────────────────────────
$createSQL = "
CREATE TABLE IF NOT EXISTS tblUser (
    userID      INT AUTO_INCREMENT PRIMARY KEY,
    fullName    VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    province    VARCHAR(50)   DEFAULT NULL,
    isVerified  TINYINT(1)    NOT NULL DEFAULT 0,
    status      ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
    role        ENUM('buyer','seller')              NOT NULL DEFAULT 'buyer',
    createdAt   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4;
";

if ($conn->query($createSQL)) {
    $messages[] = "✅ tblUser created successfully.";
} else {
    $messages[] = "❌ Error creating tblUser: " . $conn->error;
    showPage($messages);
    exit;
}

// ── 3. Load data from userData.txt ────────────────────────────────────────────
$filePath = __DIR__ . '/userData.txt';

if (!file_exists($filePath)) {
    $messages[] = "❌ userData.txt not found at: $filePath";
} else {
    $lines    = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $inserted = 0;
    $skipped  = 0;

    // Parameterised INSERT — prevents SQL injection
    $stmt = $conn->prepare(
        "INSERT INTO tblUser (fullName, email, password, province, isVerified, status)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        $messages[] = "❌ Prepare failed: " . $conn->error;
    } else {
        foreach ($lines as $line) {
            // Tab-delimited: fullName  email  password  province  isVerified  status
            $cols = explode("\t", trim($line));
            if (count($cols) < 6) {
                $messages[] = "⚠️  Skipped malformed line: " . htmlspecialchars($line);
                $skipped++;
                continue;
            }

            [$fullName, $email, $password, $province, $isVerified, $status] = $cols;
            $isVerified = (int) trim($isVerified);
            $status     = trim($status);
            $fullName   = trim($fullName);
            $email      = trim($email);
            $password   = trim($password);
            $province   = trim($province);

            $stmt->bind_param("ssssis",
                $fullName, $email, $password, $province, $isVerified, $status
            );

            if ($stmt->execute()) {
                $inserted++;
            } else {
                $messages[] = "⚠️  Could not insert '$email': " . $stmt->error;
                $skipped++;
            }
        }
        $stmt->close();
        $messages[] = "✅ Loaded $inserted row(s) from userData.txt ($skipped skipped).";
    }
}

$conn->close();

// ── 4. Render result page ──────────────────────────────────────────────────────
showPage($messages);

function showPage(array $msgs): void {
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>createTable.php — PASTIMES</title>
<link href='https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&display=swap' rel='stylesheet'>
<style>
  body  { font-family:'DM Sans',sans-serif; background:#0f0f0f; color:#e5e5e5; padding:2.5rem; }
  h1    { color:#c9a86c; margin-bottom:1.5rem; font-size:1.4rem; }
  ul    { list-style:none; padding:0; max-width:600px; }
  li    { background:#1a1a1a; border-left:4px solid #c9a86c; padding:.65rem 1rem;
          margin:.4rem 0; border-radius:4px; font-size:.95rem; }
  .links { margin-top:1.5rem; }
  .links a { color:#c9a86c; margin-right:1.5rem; text-decoration:none; font-size:.9rem; }
</style>
</head>
<body>
<h1>createTable.php — Execution Log</h1>
<ul>";
    foreach ($msgs as $m) {
        echo "<li>" . htmlspecialchars($m) . "</li>\n";
    }
    echo "</ul>
<div class='links'>
  <a href='index.php'>← Home</a>
  <a href='login.php'>Login</a>
</div>
</body></html>";
}
?>
