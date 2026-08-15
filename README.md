# StockWise V2

A modular, object-oriented (MVC) PHP 8.2+ web application for managing inventory,
purchase requests (RIS / PPMP / PPE / BS), multi-stage approvals with digital
signatures, barcode generation, and webcam barcode scanning.


## Default logins

Seed accounts use the password `Admin@123`:

| Role | Email |
|------|-------|
| Admin | `admin@stockwise.local` |
| Requestor | `` |
| Budget Head | `` |
| Procurement Head | `` |
| VP Finance | `` |
| Supply Personnel | `` |
<Sequential Approval for Procurement not yet done>
---

## Key features & how they map to files

| Feature | Location |
|---------|----------|
| Router / front controller | `public/index.php`, `public/.htaccess` |
| Login / registration / reset | `controllers/AuthController.php`, `views/auth/*` |
| Database (PDO) & secure session | `config/database.php`, `config/session.php`, `config/env.php` |
| Security (CSRF / XSS) | `helpers/Security.php` |
| Auth middleware | `middleware/AuthMiddleware.php` |
| Barcode generation (GD) | `helpers/BarcodeGenerator.php`, `controllers/ItemController.php` |
| Digital signatures (canvas → base64) | `views/approvals/view.php`, `controllers/ApprovalController.php` |
| Webcam scanning (html5-qrcode) | `views/transactions/stock_in.php`, `stock_out.php`, `public/assets/js/app.js` |
| Stock transactions (in/out/adjust/transfer) | `controllers/TransactionController.php`, `views/transactions/*` |
| Notifications | `helpers/NotificationHelper.php`, `models/Notification.php` |
| Role dashboards | `controllers/DashboardController.php`, `views/dashboard/*` |
| Admin (users/categories/locations/limits) | `controllers/AdminController.php`, `views/admin/*` |
| Office Limits + consumption report | `models/OfficeLimit.php`, `views/admin/limits.php` |
| Archive / audit trail (admin) | `controllers/ArchiveController.php`, `models/Archive.php`, `views/admin/archived.php`, `controllers/ItemController.php` (archive/restore) |
| Settings (admin) | `controllers/SettingController.php`, `models/Setting.php`, `views/settings/index.php` |
| Reports | `controllers/ReportController.php`, `views/reports/dashboard.php` |

### Accounts
- Users can **self-register** as a Requestor via the "Register" link on the login page (creates a requestor account and notifies admins).
- Admins create accounts for the other roles in **Users** management.

### Workflow
1. **Requestor** creates a request (RIS / PPMP / PPE / BS) → saved as *draft*.
2. Requestor **submits** → office limits are applied, status → *pending_budget*.
   - RIS submission requires an approved (fulfilled) PPMP for the office/year.
3. **Budget Head** approves (signs) → *pending_procurement*.
4. **Procurement Head** approves (signs) → *pending_vp*.
5. **VP Finance** approves (signs) → *pending_fulfillment*.
6. **Supply Personnel** fulfills → deducts stock, logs a stock-out transaction,
   status → *fulfilled* (or *partially_fulfilled* if stock shortage).
7. Rejections set status to *denied*; requestor is notified at each step.

---

## Security notes

- All SQL uses **PDO prepared statements** (no string concatenation).
- Every POST form contains a hidden **CSRF token** validated on the server.
- Passwords use **bcrypt** via `password_hash()`.
- Sessions regenerate the ID on login and set **HttpOnly** + **SameSite=Lax**.
- All dynamic output passes through `Security::e()` (htmlspecialchars).
- `public/uploads/` denies direct PHP execution via `.htaccess`.


---

## Settings & administration (admin only)

The **Settings** page (`/settings`) lets an administrator tune the application
**without editing code or restarting Apache**:

- **System Name** — the brand title shown in the navbar, footer, and page
  titles (defaults to `StockWise`).
- **Timezone** — applied globally on every request via `date_default_timezone_set()`
  so all timestamps (transactions, approvals, audit) use the configured zone.
- **Notifications** — toggle low-stock alerts and new-registration alerts
  (`notify_low_stock`, `notify_on_register`) independently.
- **Pagination** / **default reorder point** for new items.

Settings are stored in the `settings` key/value table and read live by the
`Setting` model (used by `NotificationHelper`, `AuthController`, the layout,
and the front controller timezone bootstrap).

Additional admin features:

- **Archive** (`/admin/archived`) — soft-archive items instead of deleting them,
  with a full audit trail in the `archives` table; items can be restored.
  Only Admins can archive, view, or purge archives.
- **Stock Adjustment** (`/transactions/adjust`) — record a `+`/`-` quantity change
  with a mandatory reason (e.g. damaged goods, cycle-count correction).
- **Stock Transfer** (`/transactions/transfer`) — move stock between locations;
  updates the item's net location and logs `TRF-YYYY-####` reference.
- **Office Consumption Limits** (`/admin/limits`) — per-office / per-item / per-year
  caps that are reserved for the full approval cycle, plus a live consumption
  report (max / used / remaining / progress bar).

---

## Directory structure

```
StockwiseV2/
├── config/          constants.php, database.php, env.php, session.php
├── controllers/     Auth, Dashboard, Item, Transaction, Request, Approval, Admin, Archive, Setting, Notification, Base
├── models/          User, Item, Request, Notification, OfficeLimit, Archive, Setting
├── views/           layouts, auth, dashboard, items, transactions, requests, approvals, reports, notifications, admin, settings
├── public/          index.php (router), .htaccess, assets/, uploads/
├── middleware/      AuthMiddleware.php
├── helpers/         Security.php, BarcodeGenerator.php, NotificationHelper.php
├── database/        schema.sql, setup.php, migrate.php
└── .env             environment config
```
