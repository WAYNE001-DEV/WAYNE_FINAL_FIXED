# DISCOVER AND RE-WIND — ClothingStore
### WEDE6021 POE — Part 2 Prototype
### Setup: WAMP Server + phpMyAdmin + VS Code

---

## Changes Made in This Version (vs previous zip)

| # | File | Change | Why (Rubric Requirement) |
|---|------|--------|--------------------------|
| 0 | `images/` (all 30) | **ADDED** — 30 real product photos matched to all DB clothing items | Images now display in shop for all 30 products |
| 0b | `shop.php` | **FIXED** image display — shows real photo, falls back to placeholder if missing | Seller-uploaded images also display correctly |
| 0c | `seller-products.php` | **FIXED** product list images — same fallback added | Consistent image handling |
| 1 | `userData.txt` | **ADDED** — 5 tab-delimited users with MD5 passwords | Part 2 §2: text file with 5 fictitious entries |
| 2 | `createTable.php` | **ADDED** — drops tblUser, recreates schema, loads userData.txt | Part 2 §2: createTable.php with DBConn include |
| 3 | `images/jacket1.jpg` | **ADDED** — product image | Part 2 rubric: 5 JPG files in images/ |
| 4 | `images/tshirt1.jpg` | **ADDED** — product image | Part 2 rubric: 5 JPG files in images/ |
| 5 | `images/dress1.jpg` | **ADDED** — product image | Part 2 rubric: 5 JPG files in images/ |
| 6 | `images/jeans1.jpg` | **ADDED** — product image | Part 2 rubric: 5 JPG files in images/ |
| 7 | `images/shoes1.jpg` | **ADDED** — product image | Part 2 rubric: 5 JPG files in images/ |
| 8 | `shop.php` | **ADDED** ShowCart button in nav bar | Part 2 rubric: ShowCart button required |
| 9 | `shop.php` | **CHANGED** AddToCart to SVG picture/icon button | Part 2 rubric: picture button required |
| 10 | `checkout.php` | **ADDED** order reference number (ORD-XXXXXX) on success | Part 2 rubric: orderNum reference shown |
| 11 | `checkout.php` | **ADDED** session ID shown on success screen | Part 2 rubric: sessionId shown at checkout |
| 12 | `checkout.php` | **ADDED** "Continue Shopping" button after checkout | Part 2 rubric: continue shopping option |
| 13 | `dashboard.php` | **ADDED** seller shortcut card with "My Products" button | Seller UX — clear path to their listings |
| 14 | `login.php` | **FIXED** sellers redirect to seller-products.php on login | Correct role-based routing |
| 15 | `dashboard.php` | **FIXED** PHP operator precedence bug in seller nav link | Bug: `?? null === 'seller'` was always false |

---

## WAMP Setup Instructions

### 1 — Copy project
```
C:\wamp64\www\WAYNE_FINAL\
```

### 2 — Start WAMP
Tray icon must be **green** before continuing.

### 3 — Import database
`http://localhost/phpmyadmin` → Import → choose `myClothingStore.sql` → Go

### 4 — Open site
```
http://localhost/WAYNE_FINAL/
```

---

## Login Credentials

### Buyers (Active)
| Email | Password |
|-------|----------|
| j.doe@abc.co.za | password1 |
| j.smith@xyz.co.za | password2 |
| t.nkosi@mail.co.za | password3 |
| n.khumalo@wear.co.za | password7 |

### Sellers (Active)
| Email | Password |
|-------|----------|
| l.dlamini@shop.co.za | password5 |

### Pending (need admin approval first)
| Email | Password | Role |
|-------|----------|------|
| a.maseko@web.co.za | password4 | Seller |
| s.mthembu@clothe.co.za | password6 | Buyer |
| d.vanwyk@store.co.za | password8 | Seller |

### Admin Accounts
| Email | Password |
|-------|----------|
| admin@clothingstore.co.za | admin123 |
| manager@clothingstore.co.za | manager123 |
| support@clothingstore.co.za | support123 |
| content@clothingstore.co.za | content123 |
| finance@clothingstore.co.za | finance123 |

**Admin panel:** `http://localhost/WAYNE_FINAL/admin/login.php`
**Admin invite code:** `Ayanda_8`

---

## All Pages

| URL | Description |
|-----|-------------|
| `/index.php` | Home page |
| `/register.php` | Register as Buyer or Seller |
| `/login.php` | Customer login — sellers go to seller-products, buyers to dashboard |
| `/dashboard.php` | Account + order history + seller shortcut |
| `/shop.php` | Browse, AddToCart (picture button), ShowCart, SellPrice popup |
| `/checkout.php` | Place order — shows ORD-XXXXXX + session ID + Continue Shopping |
| `/seller-products.php` | Upload and manage listings (sellers only) |
| `/admin/login.php` | Admin login |
| `/admin/register.php` | Register new admin (invite code: Ayanda_8) |
| `/admin/index.php` | Admin panel: verify / add / update / delete users |

---

## Part 2 Special Scripts

**createTable.php** — drops and rebuilds tblUser from userData.txt:
```
http://localhost/WAYNE_FINAL/createTable.php
```

**loadClothingStore.php** — drops ALL tables and rebuilds everything:
```
http://localhost/WAYNE_FINAL/loadClothingStore.php?token=setup_DR2025
```
⚠️ Wipes all data. Use only to reset to defaults.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Blank page | WAMP tray must be green |
| Connection failed | Check DBConn.php — DB_PASS should be empty for WAMP |
| Page not found | Folder must be inside `C:\wamp64\www\` |
| Can't login | Import myClothingStore.sql in phpMyAdmin first |
| Pending users can't login | Admin must click Verify in admin panel |
