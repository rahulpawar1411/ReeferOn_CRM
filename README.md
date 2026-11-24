# ReeferON CRM

PHP + MySQL CRM for ReeferON (Prospect → Onboarding → Customer Master).

## Planning docs

- **`PLANNING.txt`** – full requirements & business workflow (source of truth)
- **`STRUCTURE_AND_DB_PLAN.txt`** – menu structure + database relationships (review this next)
- **`PHASES.md`** – build order + what is done / next

## Current build

- Phase 0: Foundation (done)
- Phase 1: Sales Prospect Management (done – simple UI)
- Phase 2: Pipeline stage change + Follow-ups (done)
- Phase 3: Customer Onboarding + Documents (done)
- Phase 4: Admin Approval + Customer Code (done – direct Admin only)
- Phase 5: Customer Master edit + direct existing add + GSTIN/name duplicate check (done)
- Phase 6: Dashboard / Reports / Notifications / Activity (done)
- Next: Phase 7 Login + role-based access
- Auth: later (temp user = System User)
- UI/UX polish: later

## How to open (WAMP)

1. Start **WAMP** (green icon).
2. First time only: open [http://localhost/CRM/setup_install.php](http://localhost/CRM/setup_install.php) to create the database.
3. Open: [http://localhost/CRM/](http://localhost/CRM/)
4. Create prospect: [http://localhost/CRM/modules/prospects/create.php](http://localhost/CRM/modules/prospects/create.php)
5. Follow-ups: [http://localhost/CRM/modules/followups/index.php](http://localhost/CRM/modules/followups/index.php)
6. Approvals: [http://localhost/CRM/modules/approvals/index.php](http://localhost/CRM/modules/approvals/index.php)
7. Customers: [http://localhost/CRM/modules/customers/index.php](http://localhost/CRM/modules/customers/index.php)
8. Reports: [http://localhost/CRM/modules/reports/index.php](http://localhost/CRM/modules/reports/index.php)
9. Notifications: [http://localhost/CRM/modules/notifications/index.php](http://localhost/CRM/modules/notifications/index.php)
10. Logs (activity): [http://localhost/CRM/modules/logs/index.php](http://localhost/CRM/modules/logs/index.php)
11. Help guide: [http://localhost/CRM/modules/help/index.php](http://localhost/CRM/modules/help/index.php)
12. Health JSON: [http://localhost/CRM/health.php](http://localhost/CRM/health.php)

If you already installed earlier (multi-dept approvals), import `sql/migrate_admin_only_approval.sql` once in phpMyAdmin.  
If Notifications page errors, import `sql/schema_phase6.sql` once.

Project folder: `C:\wamp64\www\CRM`.

## Database

- Database name: `reeferon_crm`
- Schema file: `sql/schema.sql`
- Default MySQL user: `root` / empty password (WAMP default)

If connection fails, import `sql/schema.sql` again in phpMyAdmin.

## Config

Edit `config/database.php` if your MySQL password is not empty.
