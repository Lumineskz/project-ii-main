# CampusCanteen — College Canteen Pre-Order System

A PHP + MySQL + vanilla HTML/CSS/JS canteen pre-order system with three
roles: **Admin**, **Student**, and **Faculty**.

## 1. Requirements

- PHP 7.4+ (mysqli extension enabled)
- MySQL / MariaDB (phpMyAdmin recommended for import)
- Apache/Nginx (or PHP's built-in server for local testing)

## 2. Setup

1. Copy this whole `canteen-preorder` folder into your server's web root
   (e.g. `htdocs/canteen-preorder` for XAMPP).
2. Open **phpMyAdmin**, create a new database (or let the SQL file do it),
   then import `database.sql`. This creates all tables plus a few sample
   meal schedules and menu items.
3. Open `config/db.php` and update `DB_HOST`, `DB_USER`, `DB_PASS`,
   `DB_NAME` to match your MySQL setup (defaults are `localhost` / `root`
   / empty password / `canteen_preorder`).
4. Make sure the `uploads/menu/` folder is writable by PHP (for menu item
   image uploads).
5. In your browser, visit:
   ```
   http://localhost/canteen-preorder/auth/setup_admin.php
   ```
   and create your first admin account (default suggested email:
   `admin@canteen.com`). **Delete `auth/setup_admin.php` after this step.**
6. Visit `index.php` to see the landing page. Register a student/faculty
   account, or log in as the admin you just created.

## 3. Folder structure

```
canteen-preorder/
├── database.sql                 → import this in phpMyAdmin first
├── index.php                    → public landing page (dynamic header)
├── config/
│   ├── db.php                   → DB credentials (edit this)
│   └── config.php               → session/timezone/base config + auto order finalization
├── includes/                    → shared header, footer, sidebars, topbar, functions
├── assets/
│   ├── css/style.css            → white/blue theme, responsive
│   └── js/main.js               → sidebar toggle, cart AJAX, modals, timers
├── auth/                        → login, register (role-specific fields), logout
├── admin/                       → admin dashboard + all management pages
├── student/                     → shared dashboard for Student & Faculty roles
└── uploads/menu/                → uploaded menu item images land here
```

## 4. How the ordering logic works

1. Admin creates **timing schedules** (e.g. Lunch 11:00–13:30, order
   close 11:00) under **Timing Schedules**.
2. Students/Faculty browse the **Menu** and add items to their **Cart**
   for a specific open schedule — this is a *reservation*, not a
   confirmed order. They cannot check out manually.
3. Every page load runs `finalizeDueSchedules()` (see
   `includes/functions.php`). Once the current time passes a schedule's
   order-close time (and it hasn't already been processed today), the
   system:
   - Groups all cart reservations for that schedule/date by user.
   - Checks each user has enough **balance** and that requested
     quantities don't exceed available **stock**.
   - If both checks pass: creates the order + order items, deducts
     stock, deducts the user's balance, and logs a transaction.
   - If either check fails: the reservation is dropped with no charge
     (so a student is never charged for something that couldn't be
     fulfilled).
4. Admin can track and update live order status (Finalized → Preparing
   → Ready → Completed) from **Live Orders**. Cancelling an order
   automatically refunds the balance and restores stock.
5. **Kitchen Report** aggregates finalized order quantities per item for
   a chosen date/meal — printable for kitchen staff.

> In production you'd normally also run this finalization logic via a
> cron job (e.g. every minute) so it fires even without a page visit —
> the code is structured so you can call `finalizeDueSchedules($conn)`
> from a standalone cron script too.

## 5. Notes

- Fonts/icons load from Font Awesome Kit and Google Fonts via CDN — an
  internet connection is required for the icons/fonts to render.
- Menu images use `https://placehold.co` as a placeholder whenever no
  image has been uploaded.
- The self-recharge and admin-recharge flows use a plain number input,
  as requested — no real payment gateway is wired in.
"# canteen" 
