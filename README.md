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
- Next: Phase 3 Onboarding
- Auth: later (temp user = System User)
- UI/UX polish: later

## How to open (WAMP)

1. Start **WAMP** (green icon).
2. First time only: open [http://localhost/CRM/setup_install.php](http://localhost/CRM/setup_install.php) to create the database.
3. Open: [http://localhost/CRM/](http://localhost/CRM/)
4. Create prospect: [http://localhost/CRM/modules/prospects/create.php](http://localhost/CRM/modules/prospects/create.php)
5. Follow-ups: [http://localhost/CRM/modules/followups/index.php](http://localhost/CRM/modules/followups/index.php)

Project folder: `C:\wamp64\www\CRM`.

## Database

- Database name: `reeferon_crm`
- Schema file: `sql/schema.sql`
- Default MySQL user: `root` / empty password (WAMP default)

If connection fails, import `sql/schema.sql` again in phpMyAdmin.

## Config

Edit `config/database.php` if your MySQL password is not empty.
