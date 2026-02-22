# BlazxCart - Full Stack PHP + MySQL E-Commerce

BlazxCart is a production-ready **Core PHP** e-commerce system with three roles:
- **Admin**
- **Seller**
- **User**

It includes one common authentication page, role-based dashboards, seller approvals, product/catalog management, cart + checkout, order history/tracking, pagination, filters, secure uploads, and core security hardening.

## Stack
- Frontend: HTML, CSS, JavaScript, Bootstrap 5
- Backend: Core PHP (no framework)
- DB: MySQL + PDO prepared statements

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
```

## Setup Guide
1. Create database/tables:
   - Import `database/ecommerce.sql` into MySQL.
2. Update DB credentials in `config/db.php`.
3. Run PHP server from project root:
   ```bash
   php -S 0.0.0.0:8000
   ```
4. Open:
   - `http://localhost:8000/auth/login.php`
5. Default admin login:
   - Email: `admin@blazxcart.com`
   - Password: `Admin@123`

## Security Implemented
- Password hashing: `password_hash()`
- Password verify: `password_verify()`
- Session auth + `session_regenerate_id()`
- Role-based access control checks
- CSRF token validation on POST forms
- Input validation + output escaping (`htmlspecialchars`)
- PDO prepared statements
- File upload validation (size + MIME checks)

## Role Routing
After common login (`/auth/login.php`):
- admin → `/admin/dashboard.php`
- seller → `/seller/dashboard.php`
- user → `/user/home.php`

## Feature Notes
- Seller registration defaults to `pending` and needs Admin approval.
- Admin can approve/block sellers and block users.
- Seller can CRUD products and upload validated images.
- User can browse, filter/search, add to cart, checkout, and track orders.

