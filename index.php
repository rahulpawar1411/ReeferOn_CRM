<?php
/**
 * Dashboard (home page after login)
 *
 * Shows:
 * - Global Search (live suggestions while typing)
 * - Management Summary counts (click a count to open that list)
 * - Salesperson-wise active leads
 * - Recently created customers
 *
 * Follow-up / approval counts live here (not a separate Live Alerts page).
 */
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/includes/functions.php';

// Live counts for the Management Summary cards
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
$searchQ = trim($_GET['q'] ?? '');
$searchResults = null;

try {
    $pdo = db();

    // --- Count cards ---
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

    // Top salespeople by active prospect count
    $bySales = $pdo->query(
        "SELECT salesperson_name, COUNT(*) AS cnt
         FROM prospects WHERE status = 'Active'
         GROUP BY salesperson_name ORDER BY cnt DESC LIMIT 10"
    )->fetchAll();

    // Latest customers for the bottom table
    $recentCustomers = $pdo->query(
        'SELECT id, customer_code, customer_name, status, created_at FROM customers ORDER BY id DESC LIMIT 8'
    )->fetchAll();

    // Full search results after clicking Search (suggestions use a separate API)
    if ($searchQ !== '') {
        $searchResults = global_search($searchQ, 15);
    }
} catch (Throwable $e) {
    // DB/schema may not be ready yet — show empty dashboard instead of crashing
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Global Search</h2>
    <p class="step-note">Search by Prospect ID, Customer Code, company/customer name, phone, or GSTIN.</p>
    <form class="global-search-form js-no-loader" method="get" action="<?= e(base_url('index.php')) ?>" autocomplete="off">
        <div class="global-search-row">
            <div class="global-search-wrap" data-suggest-url="<?= e(base_url('api/search_suggest.php')) ?>">
                <input type="text" name="q" id="global-search-input" class="global-search-input"
                       value="<?= e($searchQ) ?>"
                       placeholder="e.g. REO-LEAD-0001, company name, phone, GSTIN…"
                       autocomplete="off" autofocus
                       role="combobox" aria-autocomplete="list" aria-expanded="false"
                       aria-controls="global-search-suggest">
                <div id="global-search-suggest" class="global-search-suggest" role="listbox" hidden></div>
            </div>
            <button type="submit" class="btn">Search</button>
            <?php if ($searchQ !== ''): ?>
                <a class="btn btn-secondary" href="<?= e(base_url('index.php')) ?>">Clear</a>
            <?php endif; ?>
        </div>
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
        <a class="stat" href="<?= e(base_url('modules/prospects/index.php')) ?>">
            <div class="stat-n"><?= (int) $counts['total'] ?></div>
            <div class="stat-l">Total Prospects</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/prospects/index.php?stage=' . rawurlencode('New Prospect'))) ?>">
            <div class="stat-n"><?= (int) $counts['new'] ?></div>
            <div class="stat-l">New Leads</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/prospects/index.php')) ?>">
            <div class="stat-n"><?= (int) $counts['active_pipeline'] ?></div>
            <div class="stat-l">Active Pipeline</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/prospects/index.php?stage=' . rawurlencode('Commercial Approved'))) ?>">
            <div class="stat-n"><?= (int) $counts['commercial_approved'] ?></div>
            <div class="stat-l">Commercial Approved</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/onboarding/index.php')) ?>">
            <div class="stat-n"><?= (int) $counts['onboarding_pending'] ?></div>
            <div class="stat-l">Onboarding Pending</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/approvals/index.php?tab=pending')) ?>">
            <div class="stat-n"><?= (int) $counts['pending_approvals'] ?></div>
            <div class="stat-l">Pending Admin Approvals</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/customers/index.php?status=Active')) ?>">
            <div class="stat-n"><?= (int) $counts['active_customers'] ?></div>
            <div class="stat-l">Active Customers</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/prospects/index.php?stage=Lost')) ?>">
            <div class="stat-n"><?= (int) $counts['lost'] ?></div>
            <div class="stat-l">Lost Leads</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/followups/index.php?tab=today')) ?>">
            <div class="stat-n"><?= (int) $counts['fu_today'] ?></div>
            <div class="stat-l">Follow-ups Today</div>
        </a>
        <a class="stat" href="<?= e(base_url('modules/followups/index.php?tab=overdue')) ?>">
            <div class="stat-n"><?= (int) $counts['fu_overdue'] ?></div>
            <div class="stat-l">Overdue Follow-ups</div>
        </a>
    </div>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
