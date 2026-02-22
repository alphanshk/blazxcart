# BlazxCart - Multi-Vendor E-Commerce (Core PHP + MySQL)

BlazxCart is a production-ready **Core PHP** multi-vendor e-commerce platform with three roles:
- **Admin**
- **Seller**
- **User**

It uses one shared login flow, secure session-based authentication, role-based access control, seller approvals, product management, shopping cart + checkout, order history/tracking, category management, and responsive Bootstrap UI.

## Tech Stack
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Backend: Core PHP (no framework)
- Database: MySQL (PDO)

## Project Structure

```
/
|-- config/
|   |-- db.php
|-- auth/
|   |-- login.php
|   |-- register.php
|   |-- logout.php
|-- admin/
|   |-- dashboard.php
|-- seller/
|   |-- dashboard.php
|-- user/
|   |-- home.php
|   |-- cart.php
|   |-- checkout.php
|   |-- orders.php
|   |-- profile.php
|-- includes/
|   |-- bootstrap.php
|   |-- layout.php
|-- assets/
|   |-- css/app.css
|   |-- js/app.js
|-- uploads/
|-- database/
|   |-- ecommerce.sql
|-- index.php
```

## Features by Role

### Admin
- Dashboard statistics (users, sellers, products, orders, revenue)
- Approve / block sellers
- Block / activate users
- Manage categories
- View all orders and update order status

### Seller
- Add / edit / delete products
- Secure image upload (JPG/PNG/WEBP)
- Inventory/stock management
- View orders containing own products

### User
- Browse products
- Search and category filter
- Pagination
- Cart management
- Secure checkout and order creation
- Order history/tracking
- Profile management

## Security Implemented
- Password hashing with `password_hash()`
- Login verification with `password_verify()`
- Session regeneration on login
- Session inactivity timeout auto-logout
- CSRF protection for all POST forms
- PDO prepared statements (SQL injection protection)
- Input validation + output escaping (`htmlspecialchars`)
- Secure upload checks (size + MIME)

## Installation Guide

1. **Create database and tables**
   - Import SQL file:
   ```bash
   mysql -u root -p < database/ecommerce.sql
   ```

2. **Configure database credentials**
   - Edit `config/db.php`:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

3. **Run the application**
   ```bash
   php -S 0.0.0.0:8000
   ```

4. **Open in browser**
   - Common login page: `http://localhost:8000/auth/login.php`

5. **Default admin credentials**
   - Email: `admin@blazxcart.com`
   - Password: `Admin@123`

## Authentication Flow
- All roles login from `/auth/login.php`
- Redirect by role:
  - `admin` → `/admin/dashboard.php`
  - `seller` → `/seller/dashboard.php`
  - `user` → `/user/home.php`

## Notes
- Admin accounts are manually managed via DB seed.
- Seller registrations are created with `pending` status and must be approved by admin.
- Checkout performs stock validation in a DB transaction and updates inventory safely.
