<?php
/**
 * Fill remaining dummy fields + follow-ups + 2 Existing Customers (all columns).
 * Safe to re-run. Run: php sql/seed_dummy_full.php
 */
require_once __DIR__ . '/../includes/functions.php';

ensure_auth_schema();
$pdo = db();
$userId = (int) $pdo->query("SELECT id FROM users WHERE user_id = 'system' LIMIT 1")->fetchColumn();
$userName = (string) $pdo->query("SELECT employee_name FROM users WHERE id = {$userId}")->fetchColumn();
if (!$userId) {
    fwrite(STDERR, "No system user.\n");
    exit(1);
}

// 1) Complete prospect optional fields + stage history + follow-ups
$prospects = $pdo->query(
    "SELECT id, prospect_id, company_name, contact_person, remarks
     FROM prospects
     WHERE remarks IN ('DEMO-APPROVAL-1','DEMO-APPROVAL-2')
     ORDER BY id"
)->fetchAll();

foreach ($prospects as $i => $p) {
    $nextDate = date('Y-m-d', strtotime('+' . (3 + $i) . ' days'));
    $pdo->prepare(
        "UPDATE prospects SET
            next_followup_date = ?,
            requirement_details = ?,
            remarks = ?,
            salesperson_name = ?,
            updated_by = ?
         WHERE id = ?"
    )->execute([
        $nextDate,
        $i === 0
            ? 'Frozen ice-cream distribution lanes Pune–Mumbai, daily dispatch, GPS tracked fleet.'
            : 'Chiller warehouse + last-mile for retail fresh produce, Andheri hub.',
        $i === 0
            ? 'Dummy lead 1 – CoolTreat (all fields filled).'
            : 'Dummy lead 2 – FreshBasket (all fields filled).',
        $userName,
        $userId,
        $p['id'],
    ]);

    $hist = $pdo->prepare('SELECT COUNT(*) FROM prospect_stage_history WHERE prospect_pk = ?');
    $hist->execute([$p['id']]);
    if ((int) $hist->fetchColumn() === 0) {
        $insH = $pdo->prepare(
            'INSERT INTO prospect_stage_history (prospect_pk, from_stage, to_stage, remarks, updated_by, updated_by_name)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insH->execute([$p['id'], null, 'New Prospect', 'Lead created (dummy)', $userId, $userName]);
        $insH->execute([$p['id'], 'New Prospect', 'Calling', 'First call done', $userId, $userName]);
        $insH->execute([$p['id'], 'Calling', 'Meeting', 'Site visit scheduled', $userId, $userName]);
        $insH->execute([$p['id'], 'Meeting', 'Commercial Approved', 'Commercial approved', $userId, $userName]);
        $insH->execute([$p['id'], 'Commercial Approved', 'Onboarding', 'Onboarding initiated', $userId, $userName]);
    }

    $fu = $pdo->prepare('SELECT COUNT(*) FROM followups WHERE prospect_pk = ?');
    $fu->execute([$p['id']]);
    if ((int) $fu->fetchColumn() === 0) {
        $pdo->prepare(
            "INSERT INTO followups (
                prospect_pk, prospect_id, company_name, contact_person, followup_date,
                interaction_type, discussion_summary, next_followup_date, remarks, status,
                created_by, created_by_name
             ) VALUES (?, ?, ?, ?, CURDATE(), 'Phone Call', ?, ?, 'Dummy follow-up – all fields', 'Open', ?, ?)"
        )->execute([
            $p['id'],
            $p['prospect_id'],
            $p['company_name'],
            $p['contact_person'],
            $i === 0
                ? 'Discussed frozen lane rates and vehicle availability.'
                : 'Discussed chiller storage capacity and SLA.',
            $nextDate,
            $userId,
            $userName,
        ]);
        $pdo->prepare(
            "INSERT INTO followups (
                prospect_pk, prospect_id, company_name, contact_person, followup_date,
                interaction_type, discussion_summary, next_followup_date, remarks, status,
                created_by, created_by_name
             ) VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Meeting', ?, NULL, 'Completed dummy meeting', 'Completed', ?, ?)"
        )->execute([
            $p['id'],
            $p['prospect_id'],
            $p['company_name'],
            $p['contact_person'],
            'Face-to-face meeting; shared rate card and sample SLA.',
            $userId,
            $userName,
        ]);
    }
}

// 2) Two Existing Customers with every column filled
$customers = [
    [
        'marker' => 'DEMO-CUS-1',
        'name' => 'Arctic Dairy Distributors',
        'brand' => 'ArcticDairy',
        'business_type' => 'FMCG',
        'contact' => 'Amit Verma',
        'mobile' => '9988776655',
        'email' => 'amit@arcticdairy.demo',
        'gstin' => '27AAECA9012B1Z8',
        'reg' => '45 Cold Chain Park, Nashik – 422001',
        'bill' => '45 Cold Chain Park, Nashik – 422001',
        'ops' => 'Nashik Hub + Pune spoke',
        'services' => 'Transportation, Warehousing',
        'temp' => 'Frozen',
        'commercial' => 'Annual MSA – frozen dairy lanes',
        'payment' => 'Net 45',
        'credit' => 500000,
    ],
    [
        'marker' => 'DEMO-CUS-2',
        'name' => 'GreenLeaf QSR Supplies',
        'brand' => 'GreenLeaf',
        'business_type' => 'QSR',
        'contact' => 'Sneha Patil',
        'mobile' => '9876501234',
        'email' => 'sneha@greenleaf.demo',
        'gstin' => '27AAGGL3344M1Z3',
        'reg' => '9 Food Court Lane, Baner, Pune – 411045',
        'bill' => '9 Food Court Lane, Baner, Pune – 411045',
        'ops' => 'Pune + Mumbai QSR outlets',
        'services' => 'Integrated Supply Chain',
        'temp' => 'Chiller',
        'commercial' => 'Outlet replenishment contract',
        'payment' => 'Net 21',
        'credit' => 320000,
    ],
];

foreach ($customers as $c) {
    $exists = $pdo->prepare('SELECT id FROM customers WHERE commercial_details = ? LIMIT 1');
    $exists->execute([$c['marker']]);
    if ($exists->fetch()) {
        echo "exists customer {$c['marker']}\n";
        continue;
    }

    $code = next_business_id('customer');
    $pdo->prepare(
        "INSERT INTO customers (
            customer_code, source, onboarding_pk, prospect_pk, customer_name, brand_name, business_type,
            contact_person, mobile, email, gstin, registered_address, billing_address, operating_location,
            services, temperature_requirement, commercial_details, payment_terms, credit_limit,
            agreement_start_date, agreement_end_date, account_owner_id, account_owner_name, status, created_by
         ) VALUES (
            ?, 'Existing Customer', NULL, NULL, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?, ?, 'Active', ?
         )"
    )->execute([
        $code,
        $c['name'],
        $c['brand'],
        $c['business_type'],
        $c['contact'],
        $c['mobile'],
        $c['email'],
        $c['gstin'],
        $c['reg'],
        $c['bill'],
        $c['ops'],
        $c['services'],
        $c['temp'],
        $c['marker'] . ' – ' . $c['commercial'],
        $c['payment'],
        $c['credit'],
        $userId,
        $userName,
        $userId,
    ]);
    echo "created customer {$code} – {$c['name']}\n";
}

echo "prospects=" . (int) $pdo->query('SELECT COUNT(*) FROM prospects')->fetchColumn() . PHP_EOL;
echo "onboarding=" . (int) $pdo->query('SELECT COUNT(*) FROM onboarding')->fetchColumn() . PHP_EOL;
echo "customers=" . (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn() . PHP_EOL;
echo "followups=" . (int) $pdo->query('SELECT COUNT(*) FROM followups')->fetchColumn() . PHP_EOL;
echo "OK\n";
