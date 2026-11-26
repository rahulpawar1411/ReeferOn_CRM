# ReeferON CRM – Development Phases Plan

**Stack:** PHP 8 + MySQL 8 + HTML + CSS + JavaScript  
**Rule:** Modules stay connected (Prospect → Onboarding → Customer Master).  
**Official requirements:** See `PLANNING.txt` (full business rules, fields, workflow, milestones).  
**Structure + DB relationships (for review):** See `STRUCTURE_AND_DB_PLAN.txt`.  
**Auth:** Login is a milestone target in PLANNING.txt; until wired, use temporary fixed user.

---

## Phase Overview

| Phase | Name | Target | Status |
|-------|------|--------|--------|
| 0 | Foundation (folder + DB design) | Before coding | DONE (simple UI) |
| 1 | Sales Prospect Management | 11 Dec 2026 | DONE (create/list/view/edit) |
| 2 | Pipeline + Follow-up | Mid Dec 2026 | DONE (stage change + follow-ups) |
| 3 | Customer Onboarding + Documents | 18 Dec 2026 | DONE |
| 4 | Admin Approval + Customer Code | 21 Dec 2026 | DONE (direct Admin only) |
| 5 | Existing Customer Master | 18–21 Dec 2026 | DONE (list/view/edit + direct add + dup check) |
| 6 | Dashboard + Reports + Notifications | 28 Dec 2026 | DONE |
| 7 | Authentication + Roles (checkbox access) | Before go-live | DONE |
| 8 | Testing + Training + Go-Live | 28 Dec 2026 – 1 Jan 2027 | Pending |

---

## Phase 0 – Foundation (do this first)

**Goal:** Project shell + database plan so every later module connects correctly.

### Deliverables
- Project folder structure
- MySQL database `reeferon_crm`
- Core tables (empty structure)
- Config file (DB connection)
- Temporary user constant (e.g. `TEMP_USER_ID = 1`, name = "System User") until auth phase
- Basic layout (header, sidebar, white / navy / grey theme)

### Folder structure
```
CRM/
├── config/
│   └── database.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── modules/
│   ├── prospects/
│   ├── followups/
│   ├── onboarding/
│   ├── customers/
│   ├── dashboard/
│   ├── reports/
│   └── users/          (auth – last)
├── uploads/
│   └── documents/
├── sql/
│   └── schema.sql
├── index.php
└── PHASES.md
```

### Core database tables (relationships)
```
users (created early, auth wired later)
   ↑
prospects ──→ prospect_stage_history
   │       └──→ followups
   ↓
onboarding ──→ onboarding_documents
   │       └──→ onboarding_approvals
   ↓
customers (Existing Customer Master)

audit_logs / notifications (support tables)
```

**IDs:**
- Prospect: `REO-LEAD-0001`
- Onboarding: `REO-ONB-0001`
- Customer: `REO-CUS-0001`

**Exit Phase 0 when:** DB connects, blank layout opens in browser, schema created.

---

## Phase 1 – Sales Prospect Management ⭐ BUILD NEXT

**Goal:** Salesperson can create and manage leads.

### Build
1. Create Prospect form (all required fields)
2. Auto Prospect ID (`REO-LEAD-0001`…)
3. Prospect list (table)
4. Search + filters (company, city, stage, business type, date)
5. View / Edit prospect
6. Soft status only (no hard delete)
7. Save `created_by`, `updated_by`, dates (use temp user)

### Exit checklist
- [ ] New prospect saves to MySQL
- [ ] ID auto-generates and cannot be edited
- [ ] List / search / filter work
- [ ] Edit updates record + audit fields

---

## Phase 2 – Sales Pipeline + Follow-up

**Goal:** Move leads through stages and track calls/meetings.

### Pipeline stages
New Prospect → Calling → Meeting → Proposal Sent → Negotiation → Commercial Sent → Commercial Approved → Onboarding → Customer Activated  
+ Lost / On Hold / Cancelled

### Build
1. Change stage on prospect (with remarks)
2. Stage history (date, user, remarks)
3. Next follow-up date on prospect
4. Follow-up module: create / list
5. Follow-up dashboard: Today / Upcoming / Overdue / Completed
6. Interaction types: Phone / Email / Meeting / WhatsApp / Other

### Exit checklist
- [ ] Stage change writes history
- [ ] Onboarding button only when stage = Commercial Approved
- [ ] Today / overdue follow-ups show correctly

---

## Phase 3 – New Customer Onboarding + Documents

**Goal:** After Commercial Approved, start onboarding without retyping prospect data.

### Build
1. "Initiate Customer Onboarding" button
2. Auto-copy prospect fields into onboarding
3. Extra fields: addresses, GSTIN, PAN, vehicle, storage, origin/destination, etc.
4. Auto Onboarding ID
5. Document upload (GST, PAN, KYC, Agreement, PO/WO, Commercial)
6. Document status: Pending / Received / Rejected / N/A
7. Link: `prospect_id` → `onboarding_id`

### Exit checklist
- [ ] Prospect data pre-filled
- [ ] Cannot start onboarding before Commercial Approved
- [ ] Files upload + list + replace rejected

---

## Phase 4 – Admin Approval + Customer Code

**Goal:** After details + documents are complete, Admin approves once → unique customer code.

### Locked decision
Direct **Admin** approval only (Finance / Ops / Commercial steps skipped).  
Reason: all details are filled before submit, so one Admin gate is enough.

### Approval flow
1. Sales submits onboarding (details + docs ready)
2. Admin Approve / Reject + remarks
3. On Approve → auto generate `REO-CUS-0001`
4. Transfer to Customer Master (no duplicate entry)
5. Prospect stage → Customer Activated  
Reject → correct → resubmit → Admin again

### Build
1. Approvals queue (Pending / Done)
2. Approve / Reject screen + remarks
3. Resubmit after rejection (resets Admin to Pending)
4. Safe customer-code sequence (FOR UPDATE on `id_sequences`)
5. Customer Master list/view for generated codes

### Exit checklist
- [x] Code only after Admin Approve
- [x] One unique code per approved onboarding
- [x] Prospect stage moves to Customer Activated

---

## Phase 5 – Existing Customer Master

**Goal:** Official customer list for ReeferON.

### Build
1. Auto-create customer from approved onboarding
2. Customer list / view / edit (permitted fields)
3. Status: Active / Inactive / On Hold
4. Direct "Add Existing Customer" (skip prospect pipeline)
5. Duplicate check: GSTIN + customer name (show matches, no auto-merge)
6. Keep existing ReeferON code if already known; else Admin generates new
7. Source flag: `New Onboarding` vs `Existing Customer`

### Exit checklist
- [x] Approved onboarding appears once in Customer Master
- [x] Direct add works with duplicate warning (GSTIN + name)
- [x] No permanent delete for normal users (Active / Inactive / On Hold)

---

## Phase 6 – Dashboard + Reports + Notifications

**Goal:** Live numbers and Excel export.

### Build
1. Management / Sales / Finance / Ops summary cards
2. Global search (Prospect ID, Customer Code, name, mobile, GSTIN…)
3. Reports + Excel export
4. In-app notifications (follow-up due, approval pending, code generated, etc.)
5. Activity / audit trail views

### Exit checklist
- [x] Dashboard counts match real DB rows
- [x] Export works (CSV / Excel)
- [x] Notifications + activity trail (no placeholder buttons)

---

## Phase 7 – Authentication + Access (LAST)

**Goal:** Replace temporary user with real login and roles.

### Build
1. Login / logout / session
2. Password hashing (not visible to Admin)
3. User management (create, edit, activate / deactivate)
4. Roles: Super Admin, Admin, Salesperson, Finance, Operations, Approver, Management, Viewer
5. Enforce permissions on server (not only hide buttons)
6. Salesperson sees own prospects; Management sees all
7. Wire `created_by` / `updated_by` to real logged-in user

### Exit checklist
- [ ] Unauthorised URL access blocked
- [ ] Role dashboards show correct data only
- [ ] Temp user removed

---

## Phase 8 – Testing + Go-Live

### Test end-to-end
1. Create prospect → pipeline → Commercial Approved
2. Onboarding → documents → approvals → reject/resubmit
3. Final approval → customer code → Customer Master → Activate
4. Add existing customer + duplicate GSTIN check
5. Follow-ups, reports, export
6. Each role permission

### Targets
- Testing & training: **28 Dec 2026**
- Final approval: **30 Dec 2026**
- Go-live: **1 Jan 2027**

---

## Connected workflow (never break this)

```
Sales login (later)
    → Create Prospect (REO-LEAD-xxxx)
    → Pipeline updates + Follow-ups
    → Commercial Approved
    → Initiate Onboarding (auto-copy data)
    → Documents
    → Admin Approval
    → Customer Code (REO-CUS-xxxx)
    → Existing Customer Master
    → Customer Activated
```

---

## What we build RIGHT NOW

**Phase 0–7 done. Next = Phase 8 (Testing + Training + Go-Live).**

Phase 7 delivered:
1. Login / logout / session
2. Roles with **checkbox permissions** + Admin / Salesperson / Management / Viewer defaults (customizable)
3. Users management (assign role)
4. Menu + server `require_perm` checks
5. Demo logins: `system` / `sales` / `account`

Authentication is live — temp-only browsing removed.

---

## Decisions locked for development

| Topic | Decision |
|-------|----------|
| Languages | PHP + MySQL + HTML + CSS + JS |
| Auth timing | Last phase |
| Temp user until auth | Yes |
| Design | White background, navy + grey, corporate |
| Modules | Connected, no duplicate re-entry |
| Delete policy | Soft status only (Inactive / Cancelled) |
| Customer code | Only after Admin approval |
| Approval style | Direct Admin only (no Finance/Ops/Commercial steps) |
