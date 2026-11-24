<?php
$pageTitle = 'Help – How to use';
$currentPage = 'help';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/header.php';

$step = $_GET['step'] ?? 'overview';
$allowed = ['overview', '1', '2', '3', '4', '5', '6', 'existing', 'tips'];
if (!in_array($step, $allowed, true)) {
    $step = 'overview';
}
?>

<div class="card">
    <h2>Help Menu</h2>
    <p class="step-note">
        ReeferON CRM ek connected system hai: <strong>Prospect → Onboarding → Admin Approval → Customer</strong>.
        Neeche har step ke baad <em>kya karna hai</em> clearly likha hai.
    </p>

    <div class="tabs">
        <a class="<?= $step === 'overview' ? 'active' : '' ?>" href="?step=overview">Overview</a>
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
        <li><strong>New lead</strong> → Create Prospect → ID milta hai <code>REO-LEAD-xxxx</code></li>
        <li><strong>Sales process</strong> → Change Stage + Follow-ups (calls / meetings)</li>
        <li><strong>Deal yes</strong> → Stage = <em>Commercial Approved</em></li>
        <li><strong>Paperwork</strong> → Initiate Onboarding → fill details + upload docs</li>
        <li><strong>Submit</strong> → Admin Approve / Reject</li>
        <li><strong>Approve</strong> → Customer Code <code>REO-CUS-xxxx</code> → Customer Master → Activated</li>
    </ol>
    <p><strong>After each step → next step automatically clear hota hai</strong> (button / menu se).</p>
    <p>
        <a class="btn" href="?step=1">Start from Step 1</a>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/create.php')) ?>">Create Prospect now</a>
    </p>
</div>
<?php endif; ?>

<?php if ($step === '1'): ?>
<div class="card">
    <h2>Step 1 – Create Prospect (New Lead)</h2>
    <p><strong>Kya karna hai:</strong> Company name, contact, mobile, business type, service, temperature, lead source fill karke save karo.</p>
    <p><strong>System kya karta hai:</strong> Auto ID <code>REO-LEAD-xxxx</code> banata hai. Stage start = New Prospect.</p>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Prospect <em>View</em> kholo</li>
            <li><strong>Change Stage</strong> se pipeline aage badhao (Calling / Meeting…)</li>
            <li><strong>Add Follow-up</strong> se next call/meeting date set karo</li>
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
    <p><strong>Kya karna hai:</strong> Lead ko stages me move karo aur har call/meeting record karo.</p>
    <p><strong>Stages order:</strong> New Prospect → Calling → Meeting → Proposal Sent → Negotiation → Commercial Sent → <strong>Commercial Approved</strong> → Onboarding → Customer Activated</p>
    <p>(Lost / On Hold / Cancelled bhi available hain.)</p>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Jab customer rates pe agree kare → stage set karo <strong>Commercial Approved</strong></li>
            <li>Uske baad Prospect View pe button aayega: <strong>Initiate Customer Onboarding</strong></li>
            <li>Usse pehle onboarding start nahi hoga</li>
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
    <p><strong>Kya karna hai:</strong></p>
    <ol>
        <li>Commercial Approved prospect pe <strong>Initiate Customer Onboarding</strong> click karo</li>
        <li>Name/contact already copy ho chuka hota hai</li>
        <li><strong>Edit Details</strong> – GSTIN, addresses, vehicle, route, payment terms etc. fill karo</li>
        <li>Documents upload karo (GST, PAN, KYC, Agreement, PO, Commercial)</li>
        <li><strong>Submit for Admin Approval</strong> dabao</li>
    </ol>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Sidebar → <strong>Approvals</strong> (Pending)</li>
            <li>Admin Approve / Reject karega</li>
            <li>Reject hua to details/docs fix karke phir se Submit</li>
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
    <p><strong>Kya karna hai:</strong> Approvals list se pending item open karo → remarks likho → <strong>Approve</strong> ya <strong>Reject</strong>.</p>
    <p><strong>Approve pe system kya karta hai:</strong></p>
    <ul>
        <li>Customer Code banata hai <code>REO-CUS-xxxx</code></li>
        <li>Customer Master me record create</li>
        <li>Prospect stage → <strong>Customer Activated</strong></li>
    </ul>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Sidebar → <strong>Customers</strong> → naya code dikhega</li>
            <li>Zarurat ho to Edit se status / details update karo (Active / Inactive / On Hold)</li>
            <li>Code kabhi edit nahi hota</li>
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
    <p><strong>Yahan official customers rehte hain</strong> — search, filter, view, edit.</p>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Dashboard / Reports se numbers check karo</li>
            <li>Notifications me alerts dekho</li>
            <li>Purane customers ke liye pipeline skip karke <strong>Add Existing Customer</strong> use karo</li>
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
        <li><strong>Dashboard</strong> – live counts + global search (Prospect ID / Customer Code / name / mobile / GSTIN)</li>
        <li><strong>Reports</strong> – open on screen ya Excel (CSV) download</li>
        <li><strong>Notifications</strong> – follow-up due, approval pending, customer code generated</li>
        <li><strong>Activity</strong> – kaun kab kya change kiya (audit trail)</li>
    </ul>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Daily: Follow-ups Today / Overdue check karo</li>
            <li>Pending Approvals clear karo</li>
            <li>Login + roles abhi next phase mein aayenge (abhi temp System User)</li>
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
    <p>Jo customers pehle se business kar rahe hain, unke liye prospect pipeline skip kar sakte ho.</p>
    <ol>
        <li><strong>Add Existing Customer</strong> form kholo</li>
        <li>Purana ReeferON code ho to likho; warna blank chhodo → auto <code>REO-CUS-xxxx</code></li>
        <li>Same GSTIN / name pe possible duplicates dikhenge — review karke confirm tick</li>
        <li>Save → Customer Master me aa jayega (Source = Existing Customer)</li>
    </ol>
    <div class="help-next">
        <strong>Is step ke baad kya karein?</strong>
        <ul>
            <li>Customer list me verify karo</li>
            <li>Status Active / Inactive / On Hold set kar sakte ho</li>
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
        <li>IDs (<code>REO-LEAD</code> / <code>REO-ONB</code> / <code>REO-CUS</code>) user edit nahi kar sakta</li>
        <li>Delete permanent nahi — status use karo (Inactive / Cancelled / On Hold)</li>
        <li>Onboarding sirf <strong>Commercial Approved</strong> ke baad</li>
        <li>Customer code sirf <strong>Admin Approve</strong> ke baad</li>
        <li>Same data dobara type mat karo — modules connected hain</li>
        <li>Abhi login nahi hai — sab <strong>System User</strong> se chal raha hai; roles baad mein</li>
    </ul>
    <h3>Stuck? Check this order</h3>
    <ol class="help-steps">
        <li>Prospect banaya?</li>
        <li>Stage Commercial Approved hai?</li>
        <li>Onboarding initiate + details + docs + Submit?</li>
        <li>Approvals → Admin Decide?</li>
        <li>Customers me code dikh raha hai?</li>
    </ol>
    <p><a class="btn" href="?step=overview">Back to Overview</a></p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
