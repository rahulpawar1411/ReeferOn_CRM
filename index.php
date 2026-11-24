<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/includes/functions.php';

$counts = [
    'total' => 0,
    'new' => 0,
    'active_pipeline' => 0,
    'commercial_approved' => 0,
    'onboarding_pending' => 0,
    'active_customers' => 0,
    'lost' => 0,
    'fu_today' => 0,
    'fu_overdue' => 0,
    'pending_approvals' => 0,
];
$bySales = [];
$recentCustomers = [];
$recentActivity = [];
$searchQ = trim($_GET['q'] ?? '');
$searchResults = null;

try {
    $pdo = db();
    $counts['total'] = (int) $pdo->query("SELECT COUNT(*) FROM prospects WHERE status = 'Active'")->fetchColumn();
    $counts['new'] = (int) $pdo->query("SELECT COUNT(*) FROM prospects WHERE pipeline_stage = 'New Prospect' AND status = 'Active'")->fetchColumn();
    $counts['active_pipeline'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM prospects
         WHERE status = 'Active'
           AND pipeline_stage NOT IN ('Lost','On Hold','Cancelled','Customer Activated')"
    )->fetchColumn();
    $counts['commercial_approved'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM prospects WHERE pipeline_stage = 'Commercial Approved' AND status = 'Active'"
    )->fetchColumn();
    $counts['onboarding_pending'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM onboarding WHERE overall_status IN ('Draft','Submitted','In Approval','Rejected')"
    )->fetchColumn();
    $counts['active_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'Active'")->fetchColumn();
    $counts['lost'] = (int) $pdo->query("SELECT COUNT(*) FROM prospects WHERE pipeline_stage = 'Lost'")->fetchColumn();
    $counts['fu_today'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM followups WHERE status = 'Open' AND followup_date = CURDATE()"
    )->fetchColumn();
    $counts['fu_overdue'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM followups WHERE status = 'Open' AND followup_date < CURDATE()"
    )->fetchColumn();
    $counts['pending_approvals'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM onboarding_approvals a
         JOIN onboarding o ON o.id = a.onboarding_pk
         WHERE a.department = 'Admin' AND a.status = 'Pending'
           AND o.overall_status IN ('Submitted','In Approval')"
    )->fetchColumn();

    $bySales = $pdo->query(
        "SELECT salesperson_name, COUNT(*) AS cnt
         FROM prospects WHERE status = 'Active'
         GROUP BY salesperson_name ORDER BY cnt DESC LIMIT 10"
    )->fetchAll();

    $recentCustomers = $pdo->query(
        'SELECT id, customer_code, customer_name, status, created_at FROM customers ORDER BY id DESC LIMIT 8'
    )->fetchAll();

    $recentActivity = $pdo->query(
        'SELECT module, record_id, action, old_value, new_value, performed_by_name, created_at
         FROM audit_logs ORDER BY id DESC LIMIT 12'
    )->fetchAll();

    if ($searchQ !== '') {
        $searchResults = global_search($searchQ, 15);
    }
} catch (Throwable $e) {
    // Schema may not be imported yet
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Global Search</h2>
    <form class="toolbar" method="get" action="">
        <div class="field" style="min-width:280px;flex:1">
            <label>Prospect ID / Customer Code / Name / Mobile / GSTIN</label>
            <input type="text" name="q" value="<?= e($searchQ) ?>" placeholder="Search…">
        </div>
        <button type="submit" class="btn">Search</button>
        <?php if ($searchQ !== ''): ?>
            <a class="btn btn-secondary" href="<?= e(base_url('index.php')) ?>">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($searchResults !== null): ?>
<div class="card">
    <h2>Search results for “<?= e($searchQ) ?>”</h2>
    <?php
    $any = $searchResults['prospects'] || $searchResults['onboarding'] || $searchResults['customers'];
    if (!$any): ?>
        <div class="empty">No matches.</div>
    <?php else: ?>
        <?php if ($searchResults['prospects']): ?>
            <h3>Prospects</h3>
            <table class="data">
                <tr><th>ID</th><th>Company</th><th>Contact</th><th>Stage</th><th></th></tr>
                <?php foreach ($searchResults['prospects'] as $r): ?>
                    <tr>
                        <td><?= e($r['prospect_id']) ?></td>
                        <td><?= e($r['company_name']) ?></td>
                        <td><?= e($r['contact_person']) ?></td>
                        <td><?= e($r['pipeline_stage']) ?></td>
                        <td><a href="<?= e(base_url('modules/prospects/view.php?id=' . $r['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php if ($searchResults['onboarding']): ?>
            <h3>Onboarding</h3>
            <table class="data">
                <tr><th>ID</th><th>Customer</th><th>GSTIN</th><th>Status</th><th></th></tr>
                <?php foreach ($searchResults['onboarding'] as $r): ?>
                    <tr>
                        <td><?= e($r['onboarding_id']) ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['gstin'] ?? '-') ?></td>
                        <td><?= e($r['overall_status']) ?></td>
                        <td><a href="<?= e(base_url('modules/onboarding/view.php?id=' . $r['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php if ($searchResults['customers']): ?>
            <h3>Customers</h3>
            <table class="data">
                <tr><th>Code</th><th>Name</th><th>Mobile</th><th>Status</th><th></th></tr>
                <?php foreach ($searchResults['customers'] as $r): ?>
                    <tr>
                        <td><?= e($r['customer_code']) ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['mobile']) ?></td>
                        <td><?= e($r['status']) ?></td>
                        <td><a href="<?= e(base_url('modules/customers/view.php?id=' . $r['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <h2>Management Summary</h2>
    <p class="step-note"><strong>Live counts</strong> from MySQL. Flow: Prospect → Commercial Approved → Onboarding → Admin Approval → Customer Code → Customer Master.</p>
    <div class="stat-grid">
        <div class="stat"><div class="stat-n"><?= (int) $counts['total'] ?></div><div class="stat-l">Total Prospects</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['new'] ?></div><div class="stat-l">New Leads</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['active_pipeline'] ?></div><div class="stat-l">Active Pipeline</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['commercial_approved'] ?></div><div class="stat-l">Commercial Approved</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['onboarding_pending'] ?></div><div class="stat-l">Onboarding Pending</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['pending_approvals'] ?></div><div class="stat-l">Pending Admin Approvals</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['active_customers'] ?></div><div class="stat-l">Active Customers</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['lost'] ?></div><div class="stat-l">Lost Leads</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['fu_today'] ?></div><div class="stat-l">Follow-ups Today</div></div>
        <div class="stat"><div class="stat-n"><?= (int) $counts['fu_overdue'] ?></div><div class="stat-l">Overdue Follow-ups</div></div>
    </div>
</div>

<div class="card">
    <h2>Actions</h2>
    <a class="btn" href="<?= e(base_url('modules/prospects/create.php')) ?>">Create New Prospect</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/followups/index.php')) ?>">Follow-ups</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/approvals/index.php')) ?>">Approvals</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/reports/index.php')) ?>">Reports</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/notifications/index.php')) ?>">Notifications</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/customers/index.php')) ?>">Customers</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/help/index.php')) ?>">Help – How it works</a>
</div>

<div class="card">
    <h2>Salesperson-wise Leads (Active)</h2>
    <?php if (!$bySales): ?>
        <div class="empty">No prospect data yet.</div>
    <?php else: ?>
        <table class="data">
            <tr><th>Salesperson</th><th>Active Prospects</th></tr>
            <?php foreach ($bySales as $r): ?>
                <tr>
                    <td><?= e($r['salesperson_name'] ?: '-') ?></td>
                    <td><?= (int) $r['cnt'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Recently Created Customers</h2>
    <?php if (!$recentCustomers): ?>
        <div class="empty">No customers yet.</div>
    <?php else: ?>
        <table class="data">
            <tr><th>Code</th><th>Name</th><th>Status</th><th>Created</th><th></th></tr>
            <?php foreach ($recentCustomers as $r): ?>
                <tr>
                    <td><?= e($r['customer_code']) ?></td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><span class="badge"><?= e($r['status']) ?></span></td>
                    <td><?= e($r['created_at']) ?></td>
                    <td><a href="<?= e(base_url('modules/customers/view.php?id=' . $r['id'])) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Recent Activity</h2>
    <p><a href="<?= e(base_url('modules/activity/index.php')) ?>">View full audit trail</a></p>
    <?php if (!$recentActivity): ?>
        <div class="empty">No activity yet.</div>
    <?php else: ?>
        <table class="data">
            <tr><th>When</th><th>Module</th><th>Record</th><th>Action</th><th>Change</th><th>By</th></tr>
            <?php foreach ($recentActivity as $r): ?>
                <tr>
                    <td><?= e($r['created_at']) ?></td>
                    <td><?= e($r['module']) ?></td>
                    <td><?= e($r['record_id']) ?></td>
                    <td><?= e($r['action']) ?></td>
                    <td><?= e(($r['old_value'] ?? '-') . ' → ' . ($r['new_value'] ?? '-')) ?></td>
                    <td><?= e($r['performed_by_name']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
