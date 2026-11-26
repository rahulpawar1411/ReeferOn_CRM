<?php
/**
 * ReeferON CRM – Shared helper functions
 *
 * This file is used by almost every page.
 * It gives you: URLs, phone helpers, flash messages, IDs, audit logs,
 * pipeline/onboarding helpers, search, pagination, and Excel export.
 *
 * Tip for developers: search for "SECTION:" to jump between parts.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// =============================================================================
// SECTION: URLs and safe HTML
// =============================================================================

/**
 * Build a full app path, e.g. base_url('login.php') → /CRM/login.php
 * Works even when the page is deep inside /modules/...
 */
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');

        if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        } else {
            // CLI / fallback for WAMP folder name
            $base = '/CRM';
        }
        $base = rtrim(str_replace('\\', '/', $base), '/');
        if ($base === '') {
            $base = '/CRM';
        }
    }

    $path = ltrim($path, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $base . ($path !== '' ? '/' . $path : '');
}

/**
 * Fix stored links so they always open under /CRM.
 * Older demo seeds sometimes saved paths without the /CRM folder.
 */
function app_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return base_url('index.php');
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if (str_starts_with($url, '/CRM/') || $url === '/CRM') {
        return $url;
    }
    if (str_starts_with($url, '/modules/') || str_starts_with($url, 'modules/')) {
        return base_url(ltrim($url, '/'));
    }
    if (str_starts_with($url, '/')) {
        return base_url(ltrim($url, '/'));
    }
    return base_url($url);
}

/** Escape text before printing in HTML (stops XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// =============================================================================
// SECTION: Phone helpers (+91 India, exactly 10 digits)
// =============================================================================

/** Keep only digits; remove leading 91 or 0; max 10 digits. */
function phone_digits(?string $raw): string
{
    $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';
    if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0') && strlen($digits) === 11) {
        $digits = substr($digits, 1);
    }
    return substr($digits, 0, 10);
}

/** Store as +91XXXXXXXXXX (or null if empty) */
function phone_store(?string $raw): ?string
{
    $digits = phone_digits($raw);
    return $digits === '' ? null : ('+91' . $digits);
}

/** Error message or null if OK */
function phone_error(?string $raw, bool $required = false): ?string
{
    $digits = phone_digits($raw);
    if ($digits === '') {
        return $required ? 'Phone number is required.' : null;
    }
    if (!preg_match('/^\d{10}$/', $digits)) {
        return 'Phone number must be exactly 10 digits.';
    }
    return null;
}

/** Render +91 phone input (10 digits only) */
function render_phone_input(string $name, ?string $value, bool $required = false, string $placeholder = '10-digit number'): void
{
    $digits = phone_digits($value);
    $req = $required ? ' required' : '';
    echo '<div class="phone-field">';
    echo '<span class="phone-code">+91</span>';
    echo '<input type="tel" class="js-phone-10" name="' . e($name) . '" value="' . e($digits) . '"'
        . $req . ' maxlength="10" inputmode="numeric" pattern="[0-9]{10}"'
        . ' placeholder="' . e($placeholder) . '" autocomplete="tel-national">';
    echo '</div>';
}

// =============================================================================
// SECTION: Right-side filter drawer (Filters button on list pages)
// =============================================================================

/** Start the filter drawer HTML. Put form fields next, then call filter_flip_end(). */
function filter_flip_start(string $title = 'Filters', bool $active = false, string $resetUrl = '', string $leadingHtml = ''): void
{
    static $n = 0;
    $n++;
    $id = 'filterFlip' . $n;
    $activeClass = $active ? ' has-active' : '';
    echo '<div class="filter-flip-bar">';
    echo '<div class="filter-flip-bar-left">' . $leadingHtml . '</div>';
    echo '<div class="filter-flip-bar-right">';
    echo '<button type="button" class="btn btn-secondary js-filter-open' . $activeClass . '" data-filter-target="' . e($id) . '">';
    echo 'Filters' . ($active ? ' •' : '');
    echo '</button>';
    if ($resetUrl !== '') {
        echo ' <a class="btn btn-secondary" href="' . e($resetUrl) . '">Reset</a>';
    }
    echo '</div></div>';
    echo '<div class="filter-flip-backdrop js-filter-backdrop" data-filter-target="' . e($id) . '" hidden></div>';
    echo '<aside class="filter-flip" id="' . e($id) . '" aria-hidden="true">';
    echo '<div class="filter-flip-head">';
    echo '<h3>' . e($title) . '</h3>';
    echo '<button type="button" class="filter-flip-x js-filter-close" data-filter-target="' . e($id) . '" aria-label="Close">&times;</button>';
    echo '</div>';
    echo '<div class="filter-flip-body">';
}

/** Close filter flip panel wrapper */
function filter_flip_end(): void
{
    echo '</div></aside>';
}

// =============================================================================
// SECTION: Redirect, flash messages, current logged-in user
// =============================================================================

/** Send the browser to another page and stop this script. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Save a one-time message for the next page.
 *
 * $action (optional): 'create' | 'edit' | 'delete'
 * Success + action → top-right toast with Undo (acknowledgement only).
 */
function flash(string $type, string $message, ?string $action = null): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $action = $action !== null ? strtolower(trim($action)) : null;
    if ($action !== null && !in_array($action, ['create', 'edit', 'delete'], true)) {
        $action = null;
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
        'action' => $action,
    ];
}

/** Read and clear the flash message (show once, then remove). */
function get_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Return the logged-in user as a simple array (or Guest if not logged in). */
function current_user(): array
{
    $u = auth_user();
    if ($u) {
        return [
            'id' => (int) $u['id'],
            'name' => (string) $u['employee_name'],
            'user_id' => (string) $u['user_id'],
            'role_name' => (string) ($u['role_name'] ?? ''),
            'department' => (string) ($u['department'] ?? ''),
            'permissions' => $u['permissions'] ?? [],
        ];
    }

    // Not logged in – guest (pages should call require_login)
    return [
        'id' => 0,
        'name' => 'Guest',
        'user_id' => '',
        'role_name' => '',
        'department' => '',
        'permissions' => [],
    ];
}

// =============================================================================
// SECTION: Business IDs and audit log
// =============================================================================

/**
 * Make the next ID like REO-LEAD-0001 or REO-CUS-0001.
 * Uses a DB lock so two users cannot get the same number at the same time.
 */
function next_business_id(string $seqName): string
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT prefix, next_number, pad_length FROM id_sequences WHERE seq_name = ? FOR UPDATE');
        $stmt->execute([$seqName]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Sequence not found: ' . $seqName);
        }

        $number = (int) $row['next_number'];
        $code = $row['prefix'] . str_pad((string) $number, (int) $row['pad_length'], '0', STR_PAD_LEFT);

        $upd = $pdo->prepare('UPDATE id_sequences SET next_number = next_number + 1 WHERE seq_name = ?');
        $upd->execute([$seqName]);

        $pdo->commit();
        return $code;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Save who did what (shown later in Masters → Logs). */
function write_audit(string $module, string $recordId, string $action, ?string $oldValue, ?string $newValue): void
{
    $user = current_user();
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (module, record_id, action, old_value, new_value, performed_by, performed_by_name)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $module,
        $recordId,
        $action,
        $oldValue,
        $newValue,
        $user['id'],
        $user['name'],
    ]);
}

// =============================================================================
// SECTION: Dropdown lists (stages, docs, statuses)
// =============================================================================

/** Format amount as Indian Rupees, e.g. ₹1,250.00 */
function format_money($amount): string
{
    if ($amount === null || $amount === '') {
        return '-';
    }
    return '₹' . number_format((float) $amount, 2);
}

/** Sales pipeline stages – order matters for the sales journey. */
function pipeline_stages(): array
{
    return [
        'New Prospect',
        'Calling',
        'Meeting',
        'Proposal Sent',
        'Negotiation',
        'Commercial Sent',
        'Commercial Approved',
        'Onboarding',
        'Customer Activated',
        'Lost',
        'On Hold',
        'Cancelled',
    ];
}

/** How the salesperson contacted the prospect. */
function interaction_types(): array
{
    return ['Phone Call', 'Email', 'Meeting', 'WhatsApp', 'Other'];
}

/** Documents needed before Admin can approve onboarding. */
function onboarding_doc_types(): array
{
    return [
        'GST Certificate',
        'PAN Card',
        'Company KYC',
        'Customer Agreement',
        'Purchase Order / Work Order',
        'Commercial Approval',
    ];
}

/**
 * Check if all onboarding docs are ready to submit to Admin.
 * Each doc must be "Received" (with file) or "Not Applicable".
 * Returns a list of problems; empty list means OK to submit.
 */
function onboarding_doc_submit_errors(int $onboardingPk): array
{
    $errors = [];
    $stmt = db()->prepare(
        'SELECT doc_type, status, file_path FROM onboarding_documents WHERE onboarding_pk = ? ORDER BY id'
    );
    $stmt->execute([$onboardingPk]);
    $docs = $stmt->fetchAll();

    if (!$docs) {
        return ['Upload required documents before submit (or mark Not Applicable).'];
    }

    $found = [];
    foreach ($docs as $d) {
        $found[$d['doc_type']] = true;
        $type = $d['doc_type'];
        $status = $d['status'];
        $hasFile = !empty($d['file_path']);

        if ($status === 'Not Applicable') {
            continue;
        }
        if ($status === 'Rejected') {
            $errors[] = $type . ' is Rejected — replace the file and set status to Received.';
            continue;
        }
        if ($status === 'Pending' || !$hasFile) {
            $errors[] = $type . ' must be Received (file uploaded) or Not Applicable.';
            continue;
        }
        if ($status === 'Received' && !$hasFile) {
            $errors[] = $type . ' is Received but file is missing — upload again.';
        }
    }

    foreach (onboarding_doc_types() as $requiredType) {
        if (empty($found[$requiredType])) {
            $errors[] = $requiredType . ' row missing — open Documents and refresh onboarding.';
        }
    }

    return $errors;
}

function vehicle_requirements(): array
{
    return ['8ft', '10ft', '14ft', '20ft', 'Other'];
}

function storage_requirements(): array
{
    return ['Ambient', 'Chiller', 'Frozen'];
}

function onboarding_statuses(): array
{
    return ['Draft', 'Submitted', 'In Approval', 'Approved', 'Rejected', 'Cancelled'];
}

function doc_statuses(): array
{
    return ['Pending', 'Received', 'Rejected', 'Not Applicable'];
}

function approval_departments(): array
{
    // Locked decision: direct Admin approval only (details already filled before submit).
    return ['Admin'];
}

function customer_statuses(): array
{
    return ['Active', 'Inactive', 'On Hold'];
}

// =============================================================================
// SECTION: Customer duplicates, Admin approval, pipeline stage change
// =============================================================================

/**
 * Find customers that may already exist (same GSTIN or same name).
 * We only warn the user — we never auto-merge records.
 */
function find_customer_duplicates(string $customerName, ?string $gstin, ?int $excludeId = null): array
{
    $customerName = trim($customerName);
    $gstin = $gstin !== null ? strtoupper(trim($gstin)) : '';
    if ($customerName === '' && $gstin === '') {
        return [];
    }

    $sql = 'SELECT id, customer_code, customer_name, gstin, mobile, status, source
            FROM customers WHERE (';
    $params = [];
    $parts = [];

    if ($customerName !== '') {
        $parts[] = 'customer_name = ?';
        $params[] = $customerName;
    }
    if ($gstin !== '') {
        $parts[] = 'gstin = ?';
        $params[] = $gstin;
    }
    $sql .= implode(' OR ', $parts) . ')';

    if ($excludeId) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }
    $sql .= ' ORDER BY id DESC LIMIT 20';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Admin Approve or Reject one onboarding approval row.
 * On Approve: create Customer Code + Customer Master + activate prospect.
 * On Reject: mark onboarding Rejected so sales can fix and resubmit.
 */
function decide_onboarding_approval(int $approvalId, string $decision, string $remarks): array
{
    if (!in_array($decision, ['Approved', 'Rejected'], true)) {
        throw new InvalidArgumentException('Decision must be Approved or Rejected.');
    }
    if ($remarks === '') {
        throw new InvalidArgumentException('Remarks are required.');
    }

    $pdo = db();
    $user = current_user();

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, o.id AS onb_id, o.onboarding_id, o.overall_status, o.prospect_pk, o.prospect_id
             FROM onboarding_approvals a
             JOIN onboarding o ON o.id = a.onboarding_pk
             WHERE a.id = ? FOR UPDATE'
        );
        $stmt->execute([$approvalId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Approval row not found.');
        }

        if ($row['department'] !== 'Admin') {
            throw new RuntimeException('Only Admin approval is used. Finance / Ops / Commercial steps are disabled.');
        }

        if (!in_array($row['overall_status'], ['Submitted', 'In Approval'], true)) {
            throw new RuntimeException('Onboarding must be Submitted or In Approval. Current: ' . $row['overall_status']);
        }

        if ($row['status'] !== 'Pending') {
            throw new RuntimeException('This approval is already ' . $row['status'] . '.');
        }

        $upd = $pdo->prepare(
            "UPDATE onboarding_approvals SET
                status = ?, remarks = ?, approver_id = ?, approver_name = ?, decided_at = NOW()
             WHERE id = ?"
        );
        $upd->execute([$decision, $remarks, $user['id'], $user['name'], $approvalId]);

        $onboardingPk = (int) $row['onboarding_pk'];
        $result = [
            'onboarding_pk' => $onboardingPk,
            'onboarding_id' => $row['onboarding_id'],
            'customer_code' => null,
            'message' => '',
        ];

        if ($decision === 'Rejected') {
            $pdo->prepare("UPDATE onboarding SET overall_status = 'Rejected', updated_by = ? WHERE id = ?")
                ->execute([$user['id'], $onboardingPk]);
            write_audit('onboarding', $row['onboarding_id'], 'REJECTED', 'Admin', $remarks);
            $pdo->commit();
            create_notification(
                'Onboarding Rejected',
                'Onboarding Rejected',
                $row['onboarding_id'] . ' was rejected by Admin.',
                base_url('modules/onboarding/view.php?id=' . $onboardingPk),
                'onboarding',
                $row['onboarding_id']
            );
            $result['message'] = 'Admin rejected. Correct details/documents and resubmit.';
            return $result;
        }

        // Admin approved → customer code + Customer Master
        $seq = $pdo->prepare('SELECT prefix, next_number, pad_length FROM id_sequences WHERE seq_name = ? FOR UPDATE');
        $seq->execute(['customer']);
        $s = $seq->fetch();
        if (!$s) {
            throw new RuntimeException('Customer sequence missing.');
        }
        $customerCode = $s['prefix'] . str_pad((string) (int) $s['next_number'], (int) $s['pad_length'], '0', STR_PAD_LEFT);
        $pdo->prepare('UPDATE id_sequences SET next_number = next_number + 1 WHERE seq_name = ?')
            ->execute(['customer']);

        $onbStmt = $pdo->prepare('SELECT * FROM onboarding WHERE id = ?');
        $onbStmt->execute([$onboardingPk]);
        $o = $onbStmt->fetch();

        $dup = $pdo->prepare('SELECT customer_code FROM customers WHERE onboarding_pk = ?');
        $dup->execute([$onboardingPk]);
        if ($dup->fetch()) {
            throw new RuntimeException('Customer already created for this onboarding.');
        }

        $ins = $pdo->prepare(
            "INSERT INTO customers (
                customer_code, source, onboarding_pk, prospect_pk,
                customer_name, brand_name, business_type, contact_person, mobile, email, gstin,
                registered_address, billing_address, operating_location, services, temperature_requirement,
                commercial_details, payment_terms, credit_limit, agreement_start_date,
                account_owner_id, account_owner_name, status, created_by
             ) VALUES (
                ?, 'New Onboarding', ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, 'Active', ?
             )"
        );
        $ins->execute([
            $customerCode,
            $onboardingPk,
            $o['prospect_pk'],
            $o['customer_name'],
            $o['brand_name'],
            $o['business_type'],
            $o['contact_person'],
            $o['mobile'],
            $o['email'],
            $o['gstin'],
            $o['registered_address'],
            $o['billing_address'],
            $o['operating_address'],
            $o['service_required'],
            $o['temperature_requirement'],
            $o['commercial_reference'],
            $o['payment_terms'],
            $o['credit_limit'],
            $o['business_start_date'],
            $user['id'],
            $user['name'],
            $user['id'],
        ]);

        $pdo->prepare("UPDATE onboarding SET overall_status = 'Approved', updated_by = ? WHERE id = ?")
            ->execute([$user['id'], $onboardingPk]);

        write_audit('onboarding', $row['onboarding_id'], 'APPROVED', 'Admin', $remarks);
        write_audit('customers', $customerCode, 'CREATE', $row['onboarding_id'], 'From Admin approval');

        $pdo->commit();

        change_prospect_stage(
            (int) $o['prospect_pk'],
            'Customer Activated',
            'Customer code generated: ' . $customerCode
        );

        create_notification(
            'Customer Code Generated',
            'Customer Code Generated',
            $customerCode . ' created from ' . $row['onboarding_id'],
            base_url('modules/customers/index.php?q=' . urlencode($customerCode)),
            'customers',
            $customerCode
        );
        create_notification(
            'Customer Activated',
            'Customer Activated',
            $o['customer_name'] . ' is now Active (' . $customerCode . ').',
            base_url('modules/customers/index.php?q=' . urlencode($customerCode)),
            'customers',
            $customerCode
        );

        $result['customer_code'] = $customerCode;
        $result['message'] = 'Admin approved. Customer code: ' . $customerCode;
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Put approval rows back to Pending when a rejected onboarding is sent again.
 */
function reset_onboarding_approvals(int $onboardingPk): void
{
    $pdo = db();
    $pdo->prepare(
        "UPDATE onboarding_approvals SET
            status = 'Pending', remarks = NULL, approver_id = NULL, approver_name = NULL, decided_at = NULL
         WHERE onboarding_pk = ?"
    )->execute([$onboardingPk]);
}

/**
 * True only when user typed exactly "delete" in the confirm popup.
 */
function delete_confirm_ok(?string $typed): bool
{
    return strtolower(trim((string) $typed)) === 'delete';
}

/**
 * Centered delete confirmation popup (type "delete" to enable the button).
 *
 * @param array{title?:string,message?:string,item_label?:string,cancel_url?:string,hidden?:array<string,string|int>} $opts
 */
function render_delete_confirm_modal(array $opts = []): void
{
    $title = $opts['title'] ?? 'Confirm delete';
    $message = $opts['message'] ?? 'Are you sure you want to delete this?';
    $item = $opts['item_label'] ?? '';
    $cancelUrl = $opts['cancel_url'] ?? '';
    $hidden = $opts['hidden'] ?? [];
    ?>
<div class="delete-modal-backdrop js-delete-modal"
     aria-hidden="false"
     data-cancel-url="<?= e($cancelUrl) ?>">
    <div class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="delete-modal-head">
            <h2 id="deleteModalTitle"><?= e($title) ?></h2>
            <button type="button" class="delete-modal-x js-delete-modal-close" aria-label="Close">&times;</button>
        </div>
        <p class="delete-modal-msg"><?= e($message) ?></p>
        <?php if ($item !== ''): ?>
            <p class="delete-modal-item"><?= e($item) ?></p>
        <?php endif; ?>
        <form method="post" action="" class="js-delete-confirm-form js-no-loader">
            <?php foreach ($hidden as $name => $value): ?>
                <input type="hidden" name="<?= e((string) $name) ?>" value="<?= e((string) $value) ?>">
            <?php endforeach; ?>
            <div class="form-group">
                <label>Type <strong>delete</strong> to confirm</label>
                <input type="text" name="confirm_text" class="js-delete-confirm-input" autocomplete="off"
                       placeholder="delete" autofocus>
            </div>
            <div class="form-group">
                <label>Reason (optional)</label>
                <textarea name="reason" rows="2" placeholder="Optional note"></textarea>
            </div>
            <div class="delete-modal-actions">
                <button type="submit" class="btn btn-danger js-delete-confirm-btn" disabled>Delete</button>
                <?php if ($cancelUrl !== ''): ?>
                    <a class="btn btn-secondary js-delete-modal-close" href="<?= e($cancelUrl) ?>">Cancel</a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary js-delete-modal-close">Cancel</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
    <?php
}

/**
 * Soft-delete a prospect: set status = Inactive (row stays for history/logs).
 * Does not permanently remove the record.
 */
function soft_delete_prospect(int $prospectPk, string $reason = ''): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, prospect_id, status FROM prospects WHERE id = ?');
    $stmt->execute([$prospectPk]);
    $p = $stmt->fetch();
    if (!$p) {
        throw new RuntimeException('Prospect not found.');
    }
    if ($p['status'] === 'Inactive') {
        throw new RuntimeException('Prospect is already Inactive.');
    }
    $pdo->prepare('UPDATE prospects SET status = ?, updated_by = ? WHERE id = ?')
        ->execute(['Inactive', current_user()['id'], $prospectPk]);
    write_audit('prospects', $p['prospect_id'], 'SOFT_DELETE', 'Active', $reason !== '' ? $reason : 'Inactive');
}

/**
 * Soft-delete a customer: set status = Inactive (keeps Customer Code + history).
 */
function soft_delete_customer(int $customerPk, string $reason = ''): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, customer_code, status FROM customers WHERE id = ?');
    $stmt->execute([$customerPk]);
    $c = $stmt->fetch();
    if (!$c) {
        throw new RuntimeException('Customer not found.');
    }
    if ($c['status'] === 'Inactive') {
        throw new RuntimeException('Customer is already Inactive.');
    }
    $pdo->prepare('UPDATE customers SET status = ?, updated_by = ? WHERE id = ?')
        ->execute(['Inactive', current_user()['id'], $customerPk]);
    write_audit('customers', $c['customer_code'], 'SOFT_DELETE', $c['status'], $reason !== '' ? $reason : 'Inactive');
}

/**
 * Soft-delete (deactivate) a user login. Cannot deactivate yourself.
 */
function soft_delete_user(int $userPk, string $reason = ''): void
{
    $pdo = db();
    $me = current_user();
    if ((int) $me['id'] === $userPk) {
        throw new RuntimeException('You cannot deactivate your own login.');
    }
    $stmt = $pdo->prepare('SELECT id, user_id, employee_name, status FROM users WHERE id = ?');
    $stmt->execute([$userPk]);
    $u = $stmt->fetch();
    if (!$u) {
        throw new RuntimeException('User not found.');
    }
    if ($u['status'] === 'Inactive') {
        throw new RuntimeException('User is already Inactive.');
    }
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute(['Inactive', $userPk]);
    write_audit('users', $u['user_id'], 'SOFT_DELETE', 'Active', $reason !== '' ? $reason : ('Deactivated ' . $u['employee_name']));
}

/**
 * Remove an uploaded document file and reset row to Pending (not a hard DB delete).
 */
function clear_onboarding_document(int $docId): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT d.*, o.onboarding_id, o.overall_status
         FROM onboarding_documents d
         JOIN onboarding o ON o.id = d.onboarding_pk
         WHERE d.id = ?'
    );
    $stmt->execute([$docId]);
    $d = $stmt->fetch();
    if (!$d) {
        throw new RuntimeException('Document not found.');
    }
    if (in_array($d['overall_status'], ['Approved'], true)) {
        throw new RuntimeException('Cannot clear documents after onboarding is Approved.');
    }

    if (!empty($d['file_path'])) {
        $appRoot = realpath(__DIR__ . '/..');
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        $rel = ltrim(str_replace('\\', '/', (string) $d['file_path']), '/');
        $candidate = $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $full = realpath($candidate);
        $norm = static fn (?string $p): string => strtolower(str_replace('\\', '/', (string) $p));
        if (
            $full
            && $uploadsRoot
            && is_file($full)
            && str_starts_with($norm($full), $norm($uploadsRoot) . '/')
        ) {
            @unlink($full);
        }
    }

    $pdo->prepare(
        "UPDATE onboarding_documents SET
            file_name = NULL, file_path = NULL, file_size = NULL, mime_type = NULL,
            status = 'Pending', remarks = NULL, uploaded_by = NULL, uploaded_by_name = NULL
         WHERE id = ?"
    )->execute([$docId]);

    write_audit('onboarding_documents', $d['onboarding_id'], 'CLEAR', $d['doc_type'], $d['file_name'] ?? '');
}

/**
 * Move a prospect to a new pipeline stage.
 * Also writes stage history, audit log, and a notification.
 */
function change_prospect_stage(int $prospectPk, string $toStage, string $remarks, ?string $nextFollowupDate = null): void
{
    if (!in_array($toStage, pipeline_stages(), true)) {
        throw new InvalidArgumentException('Invalid pipeline stage.');
    }

    $pdo = db();
    $user = current_user();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT * FROM prospects WHERE id = ? FOR UPDATE');
        $stmt->execute([$prospectPk]);
        $p = $stmt->fetch();
        if (!$p) {
            throw new RuntimeException('Prospect not found.');
        }

        $fromStage = $p['pipeline_stage'];

        $upd = $pdo->prepare(
            'UPDATE prospects SET pipeline_stage = ?, next_followup_date = COALESCE(?, next_followup_date), updated_by = ? WHERE id = ?'
        );
        $upd->execute([
            $toStage,
            ($nextFollowupDate !== null && $nextFollowupDate !== '') ? $nextFollowupDate : null,
            $user['id'],
            $prospectPk,
        ]);

        if ($fromStage !== $toStage) {
            $hist = $pdo->prepare(
                'INSERT INTO prospect_stage_history
                 (prospect_pk, from_stage, to_stage, remarks, updated_by, updated_by_name)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $hist->execute([
                $prospectPk,
                $fromStage,
                $toStage,
                $remarks !== '' ? $remarks : null,
                $user['id'],
                $user['name'],
            ]);

            write_audit('prospects', $p['prospect_id'], 'STAGE_CHANGE', $fromStage, $toStage);
        }

        $pdo->commit();

        if ($fromStage !== $toStage && $toStage === 'Commercial Approved') {
            create_notification(
                'Commercial Approved',
                'Commercial Approved',
                $p['prospect_id'] . ' – ' . $p['company_name'] . ' ready for onboarding.',
                base_url('modules/prospects/view.php?id=' . $prospectPk),
                'prospects',
                $p['prospect_id']
            );
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// =============================================================================
// SECTION: In-app notifications
// =============================================================================

/**
 * Create a bell/notification item.
 * user_id NULL means any logged-in user can see it.
 */
function create_notification(
    string $eventType,
    string $title,
    string $message,
    ?string $linkUrl = null,
    ?string $relatedModule = null,
    ?string $relatedId = null,
    ?int $userId = null
): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO notifications
             (user_id, event_type, title, message, link_url, related_module, related_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $eventType,
            $title,
            $message,
            $linkUrl,
            $relatedModule,
            $relatedId,
        ]);
    } catch (Throwable $e) {
        // Do not break main flow if notifications table missing
    }
}

function unread_notifications_count(?int $userId = null): int
{
    try {
        if ($userId) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id IS NULL OR user_id = ?)');
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        }
        return (int) db()->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** How many Admin approvals are still waiting (red dot on menu). */
function pending_approvals_count(): int
{
    try {
        return (int) db()->query(
            "SELECT COUNT(*) FROM onboarding_approvals a
             JOIN onboarding o ON o.id = a.onboarding_pk
             WHERE a.department = 'Admin'
               AND a.status = 'Pending'
               AND o.overall_status IN ('Submitted','In Approval')"
        )->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// =============================================================================
// SECTION: Excel/CSV export and Global Search
// =============================================================================

/**
 * Download rows as a CSV file that Excel can open.
 */
function export_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

/**
 * Search prospects, onboarding, and customers by ID, name, phone, or GSTIN.
 * Used by Dashboard search and live suggestions.
 */
function global_search(string $q, int $limit = 40): array
{
    $q = trim($q);
    if ($q === '') {
        return ['prospects' => [], 'onboarding' => [], 'customers' => []];
    }
    $like = '%' . $q . '%';
    $pdo = db();

    $p = $pdo->prepare(
        "SELECT id, prospect_id, company_name, contact_person, mobile, pipeline_stage, status
         FROM prospects
         WHERE prospect_id LIKE ? OR company_name LIKE ? OR contact_person LIKE ?
            OR mobile LIKE ? OR email LIKE ?
         ORDER BY id DESC LIMIT ?"
    );
    $p->bindValue(1, $like);
    $p->bindValue(2, $like);
    $p->bindValue(3, $like);
    $p->bindValue(4, $like);
    $p->bindValue(5, $like);
    $p->bindValue(6, $limit, PDO::PARAM_INT);
    $p->execute();

    $o = $pdo->prepare(
        "SELECT id, onboarding_id, prospect_id, customer_name, mobile, gstin, overall_status
         FROM onboarding
         WHERE onboarding_id LIKE ? OR prospect_id LIKE ? OR customer_name LIKE ?
            OR mobile LIKE ? OR gstin LIKE ?
         ORDER BY id DESC LIMIT ?"
    );
    $o->bindValue(1, $like);
    $o->bindValue(2, $like);
    $o->bindValue(3, $like);
    $o->bindValue(4, $like);
    $o->bindValue(5, $like);
    $o->bindValue(6, $limit, PDO::PARAM_INT);
    $o->execute();

    $c = $pdo->prepare(
        "SELECT id, customer_code, customer_name, contact_person, mobile, gstin, status, source
         FROM customers
         WHERE customer_code LIKE ? OR customer_name LIKE ? OR contact_person LIKE ?
            OR mobile LIKE ? OR email LIKE ? OR gstin LIKE ?
         ORDER BY id DESC LIMIT ?"
    );
    $c->bindValue(1, $like);
    $c->bindValue(2, $like);
    $c->bindValue(3, $like);
    $c->bindValue(4, $like);
    $c->bindValue(5, $like);
    $c->bindValue(6, $like);
    $c->bindValue(7, $limit, PDO::PARAM_INT);
    $c->execute();

    return [
        'prospects' => $p->fetchAll(),
        'onboarding' => $o->fetchAll(),
        'customers' => $c->fetchAll(),
    ];
}

// =============================================================================
// SECTION: AJAX helpers and pagination (Load more / page links)
// =============================================================================

/** True when the page was called with ?ajax=1 (used by lazy tables). */
function wants_ajax(): bool
{
    return isset($_GET['ajax']) && (string) $_GET['ajax'] === '1';
}

/** Send JSON to the browser and stop (for API / ajax calls). */
function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Default list page size (hub tables). Logs use 35. */
function page_size_default(): int
{
    return 25;
}

function page_size_logs(): int
{
    return 35;
}

/**
 * Build pagination meta from total rows.
 *
 * @return array{page:int,per_page:int,total:int,pages:int,offset:int,from:int,to:int}
 */
function make_pagination(int $total, int $perPage = 25, ?int $page = null): array
{
    $perPage = max(1, $perPage);
    $pages = max(1, (int) ceil($total / $perPage));
    if ($page === null) {
        $page = (int) ($_GET['page'] ?? 1);
    }
    $page = max(1, min($pages, $page));
    $offset = ($page - 1) * $perPage;
    $from = $total === 0 ? 0 : $offset + 1;
    $to = min($total, $offset + $perPage);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'pages' => $pages,
        'offset' => $offset,
        'from' => $from,
        'to' => $to,
    ];
}

/**
 * Run COUNT then SELECT with LIMIT/OFFSET.
 *
 * @return array{0: array<int,array>, 1: array}
 */
function paginate_sql(string $countSql, string $dataSql, array $params, int $perPage = 25): array
{
    $countStmt = db()->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pg = make_pagination($total, $perPage);

    $dataSql .= ' LIMIT ' . (int) $pg['per_page'] . ' OFFSET ' . (int) $pg['offset'];
    $stmt = db()->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [$rows, $pg];
}

function pagination_next_url(array $pg, array $keepQuery = []): string
{
    if ((int) ($pg['page'] ?? 1) >= (int) ($pg['pages'] ?? 1)) {
        return '';
    }
    $keepQuery = $keepQuery ?: $_GET;
    unset($keepQuery['page'], $keepQuery['ajax']);
    $keepQuery['page'] = (int) $pg['page'] + 1;
    $keepQuery['ajax'] = '1';
    return '?' . http_build_query($keepQuery);
}

function lazy_ajax_payload(array $pg, string $rowsHtml, array $keepQuery = []): array
{
    $page = (int) $pg['page'];
    $pages = (int) $pg['pages'];
    return [
        'ok' => true,
        'rows_html' => $rowsHtml,
        'page' => $page,
        'pages' => $pages,
        'total' => (int) $pg['total'],
        'per_page' => (int) $pg['per_page'],
        'from' => (int) $pg['from'],
        'to' => (int) $pg['to'],
        'has_more' => $page < $pages,
        'next_url' => pagination_next_url($pg, $keepQuery),
    ];
}

/**
 * Render lazy-load footer (Load more + scroll sentinel) using shared loader UI in JS.
 */
function render_pagination(array $pg, array $keepQuery = []): void
{
    if (($pg['total'] ?? 0) === 0) {
        return;
    }

    $keepQuery = $keepQuery ?: $_GET;
    unset($keepQuery['page'], $keepQuery['ajax']);

    $page = (int) $pg['page'];
    $pages = (int) $pg['pages'];
    $from = (int) $pg['from'];
    $to = (int) $pg['to'];
    $total = (int) $pg['total'];
    $per = (int) $pg['per_page'];
    $hasMore = $page < $pages;

    $nextQuery = $keepQuery;
    $nextQuery['page'] = $page + 1;
    $nextQuery['ajax'] = '1';
    $nextUrl = '?' . http_build_query($nextQuery);
    ?>
    <div class="pagination js-lazy-pager"
         data-page="<?= (int) $page ?>"
         data-pages="<?= (int) $pages ?>"
         data-total="<?= (int) $total ?>"
         data-per-page="<?= (int) $per ?>"
         data-next-url="<?= $hasMore ? e($nextUrl) : '' ?>">
        <div class="pagination-info js-lazy-info">
            Showing <span class="js-lazy-from"><?= $from ?></span>–<span class="js-lazy-to"><?= $to ?></span>
            of <span class="js-lazy-total"><?= $total ?></span>
            (<?= $per ?> per page<?= $hasMore ? ' · scroll or Load more' : '' ?>)
        </div>
        <?php if ($hasMore): ?>
        <div class="pagination-links">
            <button type="button" class="btn js-lazy-more">Load more</button>
        </div>
        <div class="lazy-sentinel js-lazy-sentinel" aria-hidden="true"></div>
        <?php endif; ?>
    </div>
    <?php
}
