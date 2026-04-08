<<<<<<< HEAD
# GoodFinds — Second-Hand Marketplace

![GoodFinds](img/favicon.jpg)

> A full-stack PHP/MySQL web application for buying and selling second-hand goods.  
> Built as a term project for the Internet Tools course.

---

## Student Information

| Field          | Details                        |
|----------------|-------------------------------|
| **Name**       | Bikramjit Singh Gill           |
| **Student ID** | 5147298                        |
| **Course**     | Internet Tools                 |
| **Project**    | GoodFinds — Term Project       |

---

## Project Description

**GoodFinds** is a full-featured second-hand marketplace web application where users can register, browse listings, buy products, and sell their own items. Think of it as a simplified Kijiji or Facebook Marketplace built from scratch using PHP, MySQL, Bootstrap, and SCSS.

### Core Features

- 🔐 **User Authentication** — Register, login, logout with PHP sessions and bcrypt password hashing
- 🛍️ **Product Listings** — Browse, search, and filter products by category and condition
- 📦 **Product Detail Page** — Image gallery, seller info, stock count, condition badge, related items
- 🛒 **Shopping Cart** — Add/remove items, adjust quantity, persistent per session
- 💳 **Checkout & Orders** — Shipping details form, order confirmation, order history dashboard
- 🏪 **Seller Dashboard** — Create, edit, and delete your own listings with image upload
- 🔑 **Admin Panel** — Manage all users and listings site-wide

### Bonus Features

- 🖼️ **Multiple Image Upload** — Sellers can upload 2–5 images per listing with thumbnail gallery
- ✉️ **Messaging System** — Buyers can message sellers directly about a product, with unread badges
- ⭐ **Reviews & Ratings** — Buyers can leave star ratings and comments; average shown on product page

---

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | PHP 8.x (procedural + OOP mysqli)   |
| Database    | MySQL 8.x via phpMyAdmin            |
| Frontend    | Bootstrap 5, SCSS (compiled to CSS) |
| Server      | Apache via XAMPP (localhost)        |
| Version Control | Git / GitHub                    |

---

## Project Structure

```
Goodfinds/
├── db/
│   └── db.php                  # Database connection + constants
├── includes/
│   ├── header.php              # Navbar, session, Bootstrap head
│   ├── footer.php              # Footer + Bootstrap JS
│   └── product_card.php        # Reusable product card component
├── php/
│   ├── login.php               # Login form + handler
│   ├── register.php            # Registration form + handler
│   ├── logout.php              # Session destroy
│   ├── products.php            # Browse + search + filter listings
│   ├── product.php             # Single product detail + reviews
│   ├── cart.php                # Shopping cart view
│   ├── cart_add.php            # Add to cart handler
│   ├── checkout.php            # Checkout form
│   ├── dashboard.php           # Buyer order history
│   ├── messages.php            # Messaging inbox + thread view
│   ├── message_send.php        # Send message handler
│   └── review_save.php         # Submit/update review handler
├── seller/
│   ├── dashboard.php           # Seller stats overview
│   └── listings.php            # Create / edit / delete listings
├── admin/
│   └── dashboard.php           # Admin panel (users + all listings)
├── scss/
│   └── style.scss              # Custom styles (compiled to css/style.css)
├── css/
│   ├── bootstrap.css
│   └── style.css               # Compiled from SCSS
├── js/
│   ├── bootstrap.bundle.min.js
│   └── validation.js
├── uploads/                    # Product images (uploaded + seeded)
├── img/
│   └── favicon.jpg             # Site logo / favicon
└── index.php                   # Homepage / landing page
```

---

## Setup Instructions

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- A web browser
- Git (optional)

### Step 1 — Clone or copy the project

```bash
git clone https://github.com/YOUR_USERNAME/goodfinds.git
```
Or copy the `Goodfinds/` folder manually into your XAMPP `htdocs` directory:
```
C:/xampp/htdocs/Goodfinds/
```

### Step 2 — Start XAMPP

Open XAMPP Control Panel and start both:
- ✅ **Apache**
- ✅ **MySQL**

### Step 3 — Create the database

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** → name it `goodfinds_db` → click **Create**
3. Select `goodfinds_db` → click the **SQL** tab
4. Paste and run `database/01_create_tables.sql`
5. Paste and run `database/02_seed_data.sql`

### Step 4 — Configure database connection

Open `db/db.php` and verify these match your XAMPP setup:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // blank by default in XAMPP
define('DB_NAME', 'goodfinds_db');
```

### Step 5 — Download placeholder images

Copy `seed_images.php` to the project root, then visit:
```
http://localhost/Goodfinds/seed_images.php
```
Wait for all images to download, then **delete** `seed_images.php`.

### Step 6 — Launch the app

Visit: [http://localhost/Goodfinds](http://localhost/Goodfinds)

---

## Demo Accounts

| Role    | Email                        | Password   |
|---------|------------------------------|------------|
| Admin   | admin@goodfinds.ca           | password   |
| Seller  | liam.thompson@gmail.com      | password   |
| Buyer   | sophia.chen@gmail.com        | password   |

---

## Database Summary

| Table           | Rows | Description                        |
|-----------------|------|------------------------------------|
| users           | 22   | 1 admin, 7 sellers, 14 buyers      |
| products        | 50   | 6 categories, realistic listings   |
| product_images  | 132  | 2–3 images per product             |
| orders          | 12   | Various statuses across Canada     |
| order_items     | 14   | Line items tied to orders          |
| cart            | 8    | Active cart items                  |
| messages        | 29   | 7 buyer↔seller conversations       |
| reviews         | 47   | Star ratings with comments         |

---

## License

This project was created for academic purposes as part of the Internet Tools course.  
© 2026 Bikramjit Singh Gill — All rights reserved.
=======
# Goodfinds
>>>>>>> 7cfdb34a3f49ae8f1a20a7a2d841269f5345f2cd
# Goodfinds
