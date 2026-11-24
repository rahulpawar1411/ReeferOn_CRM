<?php
require_once __DIR__ . '/../config/database.php';

/**
 * App base URL path (works from any module depth under /CRM or /reeferon-crm).
 */
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

        if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        } else {
            $base = '';
        }
        $base = rtrim(str_replace('\\', '/', $base), '/');
    }

    $path = ltrim($path, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $base . ($path !== '' ? '/' . $path : '');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

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

function current_user(): array
{
    return [
        'id'   => TEMP_USER_ID,
        'name' => TEMP_USER_NAME,
    ];
}

/**
 * Generate next business ID using id_sequences table (safe under concurrency).
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

function format_money($amount): string
{
    if ($amount === null || $amount === '') {
        return '-';
    }
    return '₹' . number_format((float) $amount, 2);
}

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

function interaction_types(): array
{
    return ['Phone Call', 'Email', 'Meeting', 'WhatsApp', 'Other'];
}

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
 * Docs ready for Admin submit: each type must be Received (with file) or Not Applicable.
 * Pending / Rejected / missing file blocks submit.
 *
 * @return string[] list of blocking messages (empty = OK)
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

/**
 * Possible duplicates by GSTIN and/or customer name (show matches; never auto-merge).
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
 * Decide Admin approval. On Approve → generate customer code + Customer Master.
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
 * Reset approvals to Pending when resubmitting a rejected onboarding.
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
 * Change prospect pipeline stage + write history + audit.
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

/**
 * In-app notification (user_id NULL = visible to all until auth roles).
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

/**
 * Stream CSV download (Excel-friendly).
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
 * Global search across prospects / onboarding / customers.
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

/**
 * Render Prev / page numbers / Next. Keeps current query string except page.
 */
function render_pagination(array $pg, array $keepQuery = []): void
{
    if (($pg['pages'] ?? 1) <= 1 && ($pg['total'] ?? 0) === 0) {
        return;
    }

    $keepQuery = $keepQuery ?: $_GET;
    unset($keepQuery['page']);

    $url = static function (int $page) use ($keepQuery): string {
        $q = $keepQuery;
        $q['page'] = $page;
        return '?' . http_build_query($q);
    };

    $page = (int) $pg['page'];
    $pages = (int) $pg['pages'];
    $from = (int) $pg['from'];
    $to = (int) $pg['to'];
    $total = (int) $pg['total'];
    $per = (int) $pg['per_page'];
    ?>
    <div class="pagination">
        <div class="pagination-info">
            Showing <?= $from ?>–<?= $to ?> of <?= $total ?>
            (<?= $per ?> per page)
        </div>
        <?php if ($pages > 1): ?>
        <div class="pagination-links">
            <?php if ($page > 1): ?>
                <a class="btn btn-secondary" href="<?= e($url($page - 1)) ?>">Prev</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($pages, $page + 2);
            if ($start > 1) {
                echo '<a href="' . e($url(1)) . '">1</a>';
                if ($start > 2) {
                    echo '<span>…</span>';
                }
            }
            for ($i = $start; $i <= $end; $i++) {
                if ($i === $page) {
                    echo '<span class="pagination-current">' . $i . '</span>';
                } else {
                    echo '<a href="' . e($url($i)) . '">' . $i . '</a>';
                }
            }
            if ($end < $pages) {
                if ($end < $pages - 1) {
                    echo '<span>…</span>';
                }
                echo '<a href="' . e($url($pages)) . '">' . $pages . '</a>';
            }
            ?>
            <?php if ($page < $pages): ?>
                <a class="btn btn-secondary" href="<?= e($url($page + 1)) ?>">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
