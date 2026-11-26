<?php
/**
 * Help guide
 *
 * In-app how-to for Prospect → Onboarding → Approval → Customer flow.
 */
$pageTitle = 'Help – How to use';
$currentPage = 'help';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('help.view');
require_once __DIR__ . '/../../includes/header.php';

$step = $_GET['step'] ?? 'overview';
$allowed = ['overview', 'flow', 'codes', 'health', '1', '2', '3', '4', '5', '6', 'existing', 'menus', 'tips'];
if (!in_array($step, $allowed, true)) {
    $step = 'overview';
}
$rulesDocUrl = base_url('modules/help/download_rules.php');
?>

<div class="card">
    <h2>Help Menu</h2>
    <p class="step-note">
        ReeferON CRM is one <strong>connected</strong> system:
        <strong>Sales Prospect → Onboarding → Admin Approval → Customer Master</strong>.
        Initial sales data is reused — you do not retype the same company details.
    </p>
    <p>
        <a class="btn" href="<?= e($rulesDocUrl) ?>">Download Rules (Word)</a>
        <span class="step-note" style="margin-left:8px">Full rules + how the app works — opens in Microsoft Word.</span>
    </p>

    <div class="tabs">
        <a class="<?= $step === 'overview' ? 'active' : '' ?>" href="?step=overview">Overview</a>
        <a class="<?= $step === 'flow' ? 'active' : '' ?>" href="?step=flow">How data flows</a>
        <a class="<?= $step === 'codes' ? 'active' : '' ?>" href="?step=codes">Auto codes</a>
        <a class="<?= $step === 'health' ? 'active' : '' ?>" href="?step=health">Health URL</a>
        <a class="<?= $step === 'menus' ? 'active' : '' ?>" href="?step=menus">Menu guide</a>
        <a class="<?= $step === '1' ? 'active' : '' ?>" href="?step=1">1. Prospect</a>
        <a class="<?= $step === '2' ? 'active' : '' ?>" href="?step=2">2. Pipeline</a>
        <a class="<?= $step === '3' ? 'active' : '' ?>" href="?step=3">3. Onboarding</a>
        <a class="<?= $step === '4' ? 'active' : '' ?>" href="?step=4">4. Approval</a>
        <a class="<?= $step === '5' ? 'active' : '' ?>" href="?step=5">5. Customer</a>
        <a class="<?= $step === '6' ? 'active' : '' ?>" href="?step=6">6. Reports</a>
        <a class="<?= $step === 'existing' ? 'active' : '' ?>" href="?step=existing">Existing Customer</a>
        <a class="<?= $step === 'tips' ? 'active' : '' ?>" href="?step=tips">Tips</a>
    </div>
</div>

<?php if ($step === 'overview'): ?>
<div class="card">
    <h2>Full journey (simple)</h2>
    <ol class="help-steps">
        <li><strong>New lead</strong> → Create Prospect → ID <code>REO-LEAD-xxxx</code></li>
        <li><strong>Sales process</strong> → Change Stage + Follow-ups (calls / meetings)</li>
        <li><strong>Deal accepted</strong> → Stage = <em>Commercial Approved</em></li>
        <li><strong>Paperwork</strong> → Initiate Onboarding (sales data auto-copied) → extra details + documents</li>
        <li><strong>Submit</strong> → Admin Approve / Reject</li>
        <li><strong>Approve</strong> → Customer Code <code>REO-CUS-xxxx</code> → Customer Master → Activated</li>
    </ol>
    <div class="help-next">
        <strong>After each step → what next?</strong>
        <p style="margin:8px 0 0">Use the buttons on the screen or the left menu. The Help tabs below explain each step.</p>
    </div>
    <p>
        <a class="btn" href="?step=flow">How sales data becomes a customer →</a>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/create.php')) ?>">Create Prospect now</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === 'flow'): ?>
<div class="card">
    <h2>How sales data becomes a customer</h2>
    <p>
        Yes — when a customer comes from <strong>Sales</strong> with initial data, that <strong>same data</strong> moves into
        <strong>Onboarding</strong>. After extra documents and verification, the record becomes an official
        <strong>Customer</strong>. You do <em>not</em> create a separate customer form for new deals.
    </p>

    <h3>1. Sales captures initial data (Prospect)</h3>
    <ul>
        <li>Company name, contact person, mobile, email, business type, service, temperature, etc.</li>
        <li>System creates Prospect ID: <code>REO-LEAD-xxxx</code></li>
        <li>Sales moves the lead through the pipeline and logs follow-ups</li>
    </ul>

    <h3>2. Commercial Approved → Onboarding starts</h3>
    <ul>
        <li>When rates are accepted, set stage to <strong>Commercial Approved</strong></li>
        <li>Click <strong>Initiate Customer Onboarding</strong></li>
        <li>CRM <strong>auto-copies</strong> the prospect’s initial data into onboarding</li>
        <li>Onboarding ID: <code>REO-ONB-xxxx</code></li>
    </ul>

    <h3>3. Extra details + documents + verification</h3>
    <ul>
        <li>Add only the extra fields: GSTIN, PAN, addresses, vehicle, route, payment terms, etc.</li>
        <li>Upload documents (GST, PAN, KYC, Agreement, PO/WO, Commercial Approval)</li>
        <li>Each document must be <strong>Received</strong> (file uploaded) or <strong>Not Applicable</strong></li>
        <li>Click <strong>Submit for Admin Approval</strong></li>
        <li>Admin <strong>Approves</strong> or <strong>Rejects</strong> (with remarks). Reject → fix → resubmit</li>
    </ul>

    <h3>4. Approved → official Customer</h3>
    <ul>
        <li>System generates Customer Code <code>REO-CUS-xxxx</code></li>
        <li>Record appears in <strong>Customer Master</strong> (source = New Onboarding)</li>
        <li>Prospect stage becomes <strong>Customer Activated</strong></li>
    </ul>

    <div class="help-next">
        <strong>Important</strong>
        <ul>
            <li>One connected path — no duplicate re-entry of company details</li>
            <li>Onboarding cannot start before Commercial Approved</li>
            <li>Customer code is created only after Admin Approve</li>
            <li>Old customers already doing business can use <em>Add Existing Customer</em> (skip pipeline)</li>
        </ul>
    </div>

    <p>
        <a class="btn" href="?step=1">Start Step 1 – Prospect</a>
        <a class="btn btn-secondary" href="?step=existing">Existing customer shortcut</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === 'codes'): ?>
<div class="card">
    <h2>Auto-generated codes – when &amp; where</h2>
    <p class="step-note">
        All IDs are created by the system from the <code>id_sequences</code> table.
        Users cannot edit these codes.
    </p>
    <table class="data">
        <thead>
        <tr>
            <th>Code</th>
            <th>When</th>
            <th>Where (in the app)</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>REO-LEAD-xxxx</code></td>
            <td>When you save a new prospect</td>
            <td><strong>New Prospect</strong> form</td>
        </tr>
        <tr>
            <td><code>REO-ONB-xxxx</code></td>
            <td>When you start onboarding after Commercial Approved</td>
            <td>Prospect View → <strong>Initiate Customer Onboarding</strong></td>
        </tr>
        <tr>
            <td><code>REO-CUS-xxxx</code></td>
            <td>When Admin Approves onboarding</td>
            <td><strong>Approvals</strong> → Decide → Approve</td>
        </tr>
        <tr>
            <td><code>REO-CUS-xxxx</code></td>
            <td>When adding an existing customer and code field is left blank</td>
            <td><strong>Add Existing Customer</strong> form</td>
        </tr>
        </tbody>
    </table>

    <h3>Sequence setup (database)</h3>
    <ul>
        <li><code>prospect</code> → prefix <code>REO-LEAD-</code></li>
        <li><code>onboarding</code> → prefix <code>REO-ONB-</code></li>
        <li><code>customer</code> → prefix <code>REO-CUS-</code></li>
    </ul>
    <p>Numbers are padded to 4 digits (example: <code>REO-LEAD-0001</code>). Generation is safe under parallel users (row lock on the sequence).</p>

    <div class="help-next">
        <strong>Notes</strong>
        <ul>
            <li>For existing customers you may enter an old known ReeferON code instead of auto-generate</li>
            <li>Customer code from Admin Approve is always system-generated — never typed manually</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="?step=flow">How data flows</a>
        <a class="btn btn-secondary" href="?step=overview">Overview</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === 'health'): ?>
<div class="card">
    <h2>System Health URL</h2>
    <p class="step-note">
        JSON health endpoint — confirms PHP, database, tables, auto-code sequences, and upload folder.
    </p>
    <ul>
        <li><strong>URL:</strong> <a href="<?= e(base_url('health.php')) ?>" target="_blank" rel="noopener"><code><?= e(base_url('health.php')) ?></code></a></li>
    </ul>

    <h3>What it checks</h3>
    <table class="data">
        <thead>
        <tr>
            <th>Group</th>
            <th>Checks</th>
        </tr>
        </thead>
        <tbody>
        <tr><td><strong>Runtime</strong></td><td>PHP version, PDO MySQL, GD, file uploads, timezone, temp user note</td></tr>
        <tr><td><strong>Files</strong></td><td>uploads/documents writable, logo file, schema.sql present</td></tr>
        <tr><td><strong>Database</strong></td><td>MySQL connection + version</td></tr>
        <tr><td><strong>Tables</strong></td><td>users, prospects, followups, onboarding, documents, approvals, customers, audit_logs, notifications, sequences — with row counts</td></tr>
        <tr><td><strong>Sequences</strong></td><td>Next REO-LEAD / REO-ONB / REO-CUS values</td></tr>
        <tr><td><strong>Auth prep</strong></td><td>Temp System User row exists (login comes later)</td></tr>
        </tbody>
    </table>

    <h3>Status meanings</h3>
    <ul>
        <li><strong>OK</strong> — healthy</li>
        <li><strong>WARN</strong> — works but note (e.g. auth not enabled yet)</li>
        <li><strong>FAIL</strong> — broken; fix before using that feature (HTTP 503 on JSON)</li>
        <li>Overall: <strong>OK</strong> / <strong>DEGRADED</strong> (warnings only) / <strong>FAIL</strong></li>
    </ul>

    <div class="help-next">
        <strong>If Database FAIL</strong>
        <ul>
            <li>Start WAMP (green icon)</li>
            <li>Open <code>setup_install.php</code> or import <code>sql/schema.sql</code> in phpMyAdmin</li>
            <li>Refresh Health</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('health.php')) ?>" target="_blank" rel="noopener">Open Health JSON</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === 'menus'): ?>
<div class="card">
    <h2>Left menu – what each item is for</h2>
    <table class="data">
        <thead>
        <tr>
            <th>Menu</th>
            <th>Usage</th>
        </tr>
        </thead>
        <tbody>
        <tr><td><strong>Dashboard</strong></td><td>Live counts + global search (Prospect ID, Customer Code, name, mobile, GSTIN)</td></tr>
        <tr><td><strong>Prospects</strong></td><td>List, search, filter, view and edit all sales leads</td></tr>
        <tr><td><strong>New Prospect</strong></td><td>Create a new lead with initial sales data</td></tr>
        <tr><td><strong>Follow-ups</strong></td><td>Track calls/meetings — Today / Upcoming / Overdue / Completed</td></tr>
        <tr><td><strong>Onboarding</strong></td><td>Paperwork after Commercial Approved — extra details + documents</td></tr>
        <tr><td><strong>Approvals</strong></td><td>Admin Approve / Reject → creates customer code</td></tr>
        <tr><td><strong>Customers</strong></td><td>Official Customer Master — view / edit / status</td></tr>
        <tr><td><strong>Add Existing Customer</strong></td><td>Add old customers without the prospect pipeline</td></tr>
        <tr><td><strong>Reports</strong></td><td>View reports and download Excel (CSV)</td></tr>
        <tr><td><strong>Notifications</strong></td><td>Alerts: follow-up due, approval pending, code generated, etc.</td></tr>
        <tr><td><strong>Logs</strong></td><td>Full activity log — every create/update/stage/doc/approval/customer action (35 rows per page)</td></tr>
        <tr><td><strong>Help</strong></td><td>This guide</td></tr>
        <tr><td><strong>Roles</strong></td><td>Checkbox access — Admin / Salesperson / Management / Viewer defaults (fully customizable)</td></tr>
        <tr><td><strong>Users</strong></td><td>Employee logins + assign role</td></tr>
        <tr><td><strong>Health JSON</strong></td><td><code>health.php</code> — DB, tables, sequences, uploads status (JSON only)</td></tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($step === '1'): ?>
<div class="card">
    <h2>Step 1 – Create Prospect (New Lead)</h2>
    <p><strong>What to do:</strong> Enter company name, contact, mobile, business type, service, temperature, lead source, then save.</p>
    <p><strong>What the system does:</strong> Creates auto ID <code>REO-LEAD-xxxx</code>. Starting stage = New Prospect.</p>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>Open the prospect <em>View</em> page</li>
            <li>Use <strong>Change Stage</strong> to move the pipeline (Calling / Meeting…)</li>
            <li>Use <strong>Add Follow-up</strong> to set the next call/meeting date</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('modules/prospects/create.php')) ?>">Open: New Prospect</a>
        <a class="btn btn-secondary" href="?step=2">Next: Pipeline help →</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === '2'): ?>
<div class="card">
    <h2>Step 2 – Pipeline + Follow-ups</h2>
    <p><strong>What to do:</strong> Move the lead through stages and record every call/meeting.</p>
    <p><strong>Stage order:</strong> New Prospect → Calling → Meeting → Proposal Sent → Negotiation → Commercial Sent → <strong>Commercial Approved</strong> → Onboarding → Customer Activated</p>
    <p>(Also available: Lost / On Hold / Cancelled.)</p>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>When the customer accepts rates → set stage to <strong>Commercial Approved</strong></li>
            <li>On Prospect View, the button <strong>Initiate Customer Onboarding</strong> appears</li>
            <li>Onboarding cannot start before Commercial Approved</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('modules/prospects/index.php')) ?>">Open: Prospects</a>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/followups/index.php')) ?>">Open: Follow-ups</a>
        <a class="btn btn-secondary" href="?step=3">Next: Onboarding help →</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === '3'): ?>
<div class="card">
    <h2>Step 3 – Onboarding + Documents</h2>
    <p><strong>What to do:</strong></p>
    <ol>
        <li>On a Commercial Approved prospect, click <strong>Initiate Customer Onboarding</strong></li>
        <li>Name/contact/service data is already copied from Sales</li>
        <li><strong>Edit Details</strong> — fill GSTIN, PAN, addresses, vehicle, route, payment terms, etc.</li>
        <li>Documents: each type must be <strong>Received</strong> (file uploaded) or <strong>Not Applicable</strong></li>
        <li>Click <strong>Submit for Admin Approval</strong></li>
    </ol>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>Go to sidebar → <strong>Approvals</strong> (Pending)</li>
            <li>Admin Approves or Rejects</li>
            <li>If Rejected → fix details/docs → Submit again</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('modules/onboarding/index.php')) ?>">Open: Onboarding</a>
        <a class="btn btn-secondary" href="?step=4">Next: Approval help →</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === '4'): ?>
<div class="card">
    <h2>Step 4 – Admin Approval + Customer Code</h2>
    <p><strong>What to do:</strong> Open a pending item in Approvals → enter remarks → <strong>Approve</strong> or <strong>Reject</strong>.</p>
    <p><strong>On Approve, the system:</strong></p>
    <ul>
        <li>Creates Customer Code <code>REO-CUS-xxxx</code></li>
        <li>Creates the Customer Master record (from onboarding data)</li>
        <li>Sets prospect stage → <strong>Customer Activated</strong></li>
    </ul>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>Sidebar → <strong>Customers</strong> — the new code appears</li>
            <li>Edit status/details if needed (Active / Inactive / On Hold)</li>
            <li>Customer code can never be edited</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('modules/approvals/index.php')) ?>">Open: Approvals</a>
        <a class="btn btn-secondary" href="?step=5">Next: Customer help →</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === '5'): ?>
<div class="card">
    <h2>Step 5 – Customer Master</h2>
    <p><strong>Official customers live here</strong> — search, filter, view, edit.</p>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>Check numbers on Dashboard / Reports</li>
            <li>Review Notifications for alerts</li>
            <li>For old customers (skip pipeline), use <strong>Add Existing Customer</strong></li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('modules/customers/index.php')) ?>">Open: Customers</a>
        <a class="btn btn-secondary" href="?step=existing">Existing customer shortcut →</a>
        <a class="btn btn-secondary" href="?step=6">Next: Reports help →</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === '6'): ?>
<div class="card">
    <h2>Step 6 – Dashboard, Reports, Notifications</h2>
    <ul>
        <li><strong>Dashboard</strong> — live counts + global search</li>
        <li><strong>Reports</strong> — open on screen or download Excel (CSV)</li>
        <li><strong>Notifications</strong> — follow-up due, approval pending, customer code generated, document rejected</li>
        <li><strong>Logs</strong> — full activity audit trail (filter + Excel export)</li>
    </ul>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>Daily: check Follow-ups Today / Overdue</li>
            <li>Clear Pending Approvals</li>
            <li>Login + roles come in the next phase (currently temp System User)</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('index.php')) ?>">Open: Dashboard</a>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/reports/index.php')) ?>">Open: Reports</a>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/notifications/index.php')) ?>">Open: Notifications</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === 'existing'): ?>
<div class="card">
    <h2>Shortcut – Add Existing Customer</h2>
    <p>For customers already doing business, you can skip the prospect pipeline.</p>
    <ol>
        <li>Open <strong>Add Existing Customer</strong></li>
        <li>Enter the old ReeferON code if known; leave blank to auto-generate <code>REO-CUS-xxxx</code></li>
        <li>Same GSTIN / name shows possible duplicates — review and confirm to continue</li>
        <li>Save → appears in Customer Master (Source = Existing Customer)</li>
    </ol>
    <div class="help-next">
        <strong>After this step → what next?</strong>
        <ul>
            <li>Verify in the Customers list</li>
            <li>Set status Active / Inactive / On Hold if needed</li>
        </ul>
    </div>
    <p>
        <a class="btn" href="<?= e(base_url('modules/customers/create.php')) ?>">Open: Add Existing Customer</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === 'tips'): ?>
<div class="card">
    <h2>Quick tips</h2>
    <ul>
        <li>IDs (<code>REO-LEAD</code> / <code>REO-ONB</code> / <code>REO-CUS</code>) cannot be edited by users — see <a href="?step=codes">Auto codes</a></li>
        <li>No permanent delete — use status (Inactive / Cancelled / On Hold)</li>
        <li>Onboarding only after <strong>Commercial Approved</strong></li>
        <li>Before Submit, all documents must be Received or Not Applicable</li>
        <li>Customer code only after <strong>Admin Approve</strong></li>
        <li>Do not retype the same data — modules are connected</li>
        <li>List pages show <strong>25 rows</strong> first (Logs <strong>35</strong>), then <strong>lazy load</strong> more on scroll or <em>Load more</em> — same Loading… UI everywhere</li>
        <li>Login is required — Roles use <strong>checkbox access</strong> (Admin / Salesperson / Management / Viewer defaults under Roles menu)</li>
    </ul>
    <h3>Stuck? Check this order</h3>
    <ol class="help-steps">
        <li>Prospect created?</li>
        <li>Stage = Commercial Approved?</li>
        <li>Onboarding initiated + details + docs + Submit?</li>
        <li>Approvals → Admin Decide?</li>
        <li>Customer code visible under Customers?</li>
    </ol>
    <p><a class="btn" href="?step=overview">Back to Overview</a></p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
