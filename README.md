# StockWise V2

A modular, object-oriented (MVC) PHP 8.2+ web application for managing inventory,
purchase requests (RIS / PPMP / PPE / ARE / BS), multi-stage approvals with digital
signatures, delegation, barcode generation, and webcam barcode scanning.


## Default logins

Seed accounts use the password `Admin@123`:

| Role | Email |
|------|-------|
| Supply Administrator (Administrator) | `admin@stockwise.local` |
| Requestor (1 per office) | `requestor@stockwise.local` |
| Budget Head | `budget@stockwise.local` |
| Procurement Head | `procurement@stockwise.local` |
| VP Finance | `vp@stockwise.local` |
| Supply Personnel | `supply@stockwise.local` |
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

### Request & Approval workflow
1. **Requestor** creates a request (RIS / PPMP / PPE / ARE / BS) → saved as *draft*.
2. Requestor **submits (signature required)** → office limits are applied, the
   form's fixed approval chain is built, status → *in_review* (pending the
   first approver).
   - RIS submission requires an approved PPMP for the current year.
   - The Supply Administrator step is **auto-delegated to Supply Personnel**
     whenever delegation is enabled or no active Supply Administrator exists;
     the delegation is dated and logged.
3. Approvers act strictly in their fixed sequence. **Approve requires a digital
   signature**; **both approve and reject require remarks**, and every action is
   timestamped:
   - PPMP / PPE : Supply Administrator → Budget Head → Procurement Head → VP
   - RIS / ARE  : Supply Administrator → VP
   - BS         : Supply Administrator
4. A **rejection stops the flow and returns the request to the requester**
   together with the approver's remarks. The requester can then edit and
   **resubmit**; resubmission restarts the chain from the beginning and is
   logged as a *Resubmitted* event.
5. After the final signature the request becomes **Approved / Done**; Supply
   Personnel can then issue stock on the transactions screen.
6. The request page shows a **horizontal timeline** (current step, completed
   steps with signature + date/time, pending steps, returned/rejected steps),
   the request-level status label, all enforced timestamps (Needed-by,
   Submitted, each action, delegation), and the full status history / audit
   log. Every event also appears in the notification bell/page.

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
├── database/        schema.sql
└── .env             environment config
```
