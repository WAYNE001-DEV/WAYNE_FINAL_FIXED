# 🛍️ PASTIMES — Online Clothing Store

> A full-stack second-hand clothing marketplace built with PHP, MySQL, and WAMP.  
> Browse, buy, and sell pre-loved clothing with ease.

---

## 🎬 Video Demonstration

[![Watch the Demo](https://img.shields.io/badge/▶%20Watch%20Demo-YouTube-red?style=for-the-badge&logo=youtube)](https://youtu.be/l_cxsmPfGqs)

**👉 https://youtu.be/l_cxsmPfGqs**

---

## 💻 GitHub Repository

[![GitHub](https://img.shields.io/badge/GitHub-Ayanda001%2FWAYNE__FINAL__FIXED-black?style=for-the-badge&logo=github)](https://github.com/Ayanda001/WAYNE_FINAL_FIXED)

**👉 https://github.com/Ayanda001/WAYNE_FINAL_FIXED**

---

## 👨‍💻 Developers

| Name | Role | GitHub |
|------|------|--------|
| **Ayanda Maseko** | Software Developer | [@Ayanda001](https://github.com/Ayanda001) |
| **Sabelo Dlomo** | Software Developer |  dlomosabelo977@gmail.com |

> This project was designed and developed by Ayanda Maseko and Sabelo Dlomo as part of their software development coursework (WEDE6021 POE — Part 2 Prototype).

---

## 📋 Project Overview

**PASTIMES** is a second-hand clothing e-commerce platform that allows:

- 🛒 **Buyers** to browse listings, add items to cart, and place orders
- 🏷️ **Sellers** to list clothing items with images, prices, and condition ratings
- 🔐 **Admins** to manage users, verify accounts, and monitor the platform

**Tech Stack:**
- **Backend:** PHP (MySQLi)
- **Database:** MySQL via phpMyAdmin
- **Frontend:** HTML, CSS, JavaScript
- **Server:** WAMP (Windows Apache MySQL PHP)
- **Version Control:** Git + GitHub

---

## ⚙️ Setup Instructions (WAMP)

### Step 1 — Install WAMP
Download and install WAMP from [wampserver.com](https://www.wampserver.com).  
Make sure the **tray icon is green** before proceeding.

### Step 2 — Copy the Project
Place the project folder inside your WAMP web root:
```
C:\wamp64\www\WAYNE_FINAL_FIXED\
```

### Step 3 — Import the Database
1. Open your browser and go to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click **Import** in the top menu
3. Click **Choose File** and select `myClothingStore.sql` from the project folder
4. Click **Go** to import

> ✅ This creates the `ClothingStore` database with all tables and seed data.

### Step 4 — Check Database Connection
Open `DBConn.php` and confirm these settings match your WAMP setup:
```php
$host = 'localhost';
$dbname = 'ClothingStore';
$user = 'root';
$pass = '';   // Leave empty for default WAMP
```

### Step 5 — Open the Site
```
http://localhost/WAYNE_FINAL_FIXED/
```

---

## 🔑 Login Credentials

### 🛒 Buyer Accounts (Active)
| Full Name | Email | Password |
|-----------|-------|----------|
| John Doe | j.doe@abc.co.za | password1 |
| Jane Smith | j.smith@xyz.co.za | password2 |
| Naledi Khumalo | n.khumalo@wear.co.za | password7 |

### 🏷️ Seller Accounts (Active)
| Full Name | Email | Password |
|-----------|-------|----------|
| Thabo Nkosi | t.nkosi@mail.co.za | password3 |
| Lerato Dlamini | l.dlamini@shop.co.za | password5 |
| Naledi Khumalo | n.khumalo@wear.co.za | password7 |

### ⏳ Pending Accounts (Admin must verify first)
| Full Name | Email | Password | Role |
|-----------|-------|----------|------|
| Ayanda Maseko | a.maseko@web.co.za | password4 | Seller |
| Sipho Mthembu | s.mthembu@clothe.co.za | password6 | Buyer |
| David van Wyk | d.vanwyk@store.co.za | password8 | Seller |

### 🔐 Admin Accounts
| Email | Password |
|-------|----------|
| admin@clothingstore.co.za | admin123 |
| manager@clothingstore.co.za | manager123 |
| support@clothingstore.co.za | support123 |
| content@clothingstore.co.za | content123 |
| finance@clothingstore.co.za | finance123 |

**Admin Panel URL:** `http://localhost/WAYNE_FINAL_FIXED/admin/login.php`  
**Admin Invite Code:** `Ayanda_8`

---

## 🗂️ Pages & Features

| Page | URL | Description |
|------|-----|-------------|
| Home | `/index.php` | Landing page with featured categories |
| Register | `/register.php` | Sign up as a Buyer or Seller |
| Login | `/login.php` | Role-based login — sellers go to their listings, buyers to dashboard |
| Dashboard | `/dashboard.php` | View account info, order history, and seller shortcut |
| Shop | `/shop.php` | Browse all listings, add to cart, view price popup |
| Checkout | `/checkout.php` | Place order — shows order reference number and session ID |
| Seller Products | `/seller-products.php` | Upload new items with images, manage existing listings |
| Admin Login | `/admin/login.php` | Admin-only login |
| Admin Panel | `/admin/index.php` | Verify users, manage accounts, view platform stats |

---

## 🗃️ Database Structure

```
ClothingStore
├── tblUser       — Buyers and Sellers (role, status, province)
├── tblAdmin      — Admin accounts
├── tblClothes    — Product listings (title, price, image, condition)
└── tblOrder      — Orders placed by buyers
```

**Foreign Key Order (important for seeding):**
```
tblAdmin → tblUser → tblClothes → tblOrder
```

---

## 🔧 Special Setup Scripts

### Reset Everything (Full Rebuild)
Drops all tables and re-seeds from scratch:
```
http://localhost/WAYNE_FINAL_FIXED/loadClothingStore.php?token=setup_DR2025
```
⚠️ **Warning:** This wipes all data including orders and user accounts.

### Rebuild Users Only
Drops and rebuilds `tblUser` from `userData.txt`:
```
http://localhost/WAYNE_FINAL_FIXED/createTable.php
```

---

## 🖼️ Product Images

All product images are stored in the `/images/` folder and are named to match the database `imageFile` column. When a seller uploads a new product image it is saved automatically to `/images/` with a unique filename and linked to that listing in the database.

If an image file is missing, the shop displays a fallback category emoji instead of a broken image.

---

## 🚀 Running With Git (VS Code)

Clone the repo and run locally:
```bash
git clone https://github.com/Ayanda001/WAYNE_FINAL_FIXED.git
cd WAYNE_FINAL_FIXED
```

To push future changes:
```bash
git add .
git commit -m "Your commit message"
git push origin main
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank white page | WAMP tray icon must be green (all services running) |
| "Connection failed" error | Check `DBConn.php` — password should be empty for default WAMP |
| "Page not found" (404) | Ensure the folder is inside `C:\wamp64\www\` |
| Can't log in | Import `myClothingStore.sql` into phpMyAdmin first |
| Pending users can't log in | Admin must click **Verify** in the admin panel |
| FK constraint error on SQL import | Always import the full `myClothingStore.sql` — do not run partial INSERTs |
| Images not showing | Check that the `images/` folder exists and contains the image files |

---

## 📄 License

This project was built for educational purposes as part of the WEDE6021 module.  
© 2025 Ayanda Maseko & Sabelo Dlomo — All rights reserved.