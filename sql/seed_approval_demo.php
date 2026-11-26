<?php
/**
 * Seed 2 pending Admin approval demos (prospect + onboarding + docs + approval).
 * Run: php sql/seed_approval_demo.php
 */
require_once __DIR__ . '/../includes/functions.php';

ensure_auth_schema();
$pdo = db();
$userId = (int) $pdo->query("SELECT id FROM users WHERE user_id = 'system' LIMIT 1")->fetchColumn();
if (!$userId) {
    $userId = (int) $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
}
if (!$userId) {
    fwrite(STDERR, "No users found. Seed auth first.\n");
    exit(1);
}

$nextId = static function (string $seq) use ($pdo): string {
    $stmt = $pdo->prepare('SELECT prefix, next_number, pad_length FROM id_sequences WHERE seq_name = ? FOR UPDATE');
    $stmt->execute([$seq]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Missing sequence: ' . $seq);
    }
    $code = $row['prefix'] . str_pad((string) (int) $row['next_number'], (int) $row['pad_length'], '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE id_sequences SET next_number = next_number + 1 WHERE seq_name = ?')->execute([$seq]);
    return $code;
};

$demos = [
    [
        'company' => 'CoolTreat Foods Pvt Ltd',
        'brand' => 'CoolTreat',
        'contact' => 'Rahul Mehta',
        'mobile' => '9876543210',
        'email' => 'rahul@cooltreat.demo',
        'business_type' => 'Ice Cream',
        'city' => 'Pune',
        'service' => 'Transportation',
        'temp' => 'Frozen',
        'vehicle' => '14ft',
        'gstin' => '27AABCC1234D1Z5',
        'pan' => 'AABCC1234D',
        'address' => '12 Industrial Area, Hinjewadi, Pune – 411057',
        'payment' => 'Net 30',
        'credit' => 250000,
        'marker' => 'DEMO-APPROVAL-1',
    ],
    [
        'company' => 'FreshBasket Retail India',
        'brand' => 'FreshBasket',
        'contact' => 'Priya Sharma',
        'mobile' => '9123456780',
        'email' => 'priya@freshbasket.demo',
        'business_type' => 'Retail',
        'city' => 'Mumbai',
        'service' => 'Warehousing',
        'temp' => 'Chiller',
        'vehicle' => '10ft',
        'gstin' => '27AADFF5678K1Z2',
        'pan' => 'AADFF5678K',
        'address' => '88 Market Road, Andheri East, Mumbai – 400069',
        'payment' => 'Net 15',
        'credit' => 150000,
        'marker' => 'DEMO-APPROVAL-2',
    ],
];

$docIns = $pdo->prepare(
    "INSERT INTO onboarding_documents
        (onboarding_pk, doc_type, file_name, file_path, file_size, mime_type, status, uploaded_by, uploaded_by_name, remarks)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status), file_name = VALUES(file_name), file_path = VALUES(file_path)"
);

foreach ($demos as $d) {
    $exists = $pdo->prepare('SELECT id FROM onboarding WHERE commercial_reference = ? LIMIT 1');
    $exists->execute([$d['marker']]);
    if ($exists->fetch()) {
        echo "exists {$d['marker']}\n";
        continue;
    }

    $pdo->beginTransaction();
    try {
        $prospectId = $nextId('prospect');
        $insP = $pdo->prepare(
            "INSERT INTO prospects (
                prospect_id, lead_creation_date, company_name, contact_person, designation, mobile, email,
                business_type, industry, city, service_required, temperature_requirement, requirement_details,
                estimated_monthly_business, lead_source, salesperson_id, salesperson_name, pipeline_stage,
                remarks, status, created_by
             ) VALUES (
                ?, CURDATE(), ?, ?, 'Manager', ?, ?,
                ?, 'FMCG', ?, ?, ?, 'Demo lead for Admin approval screen',
                180000, 'Direct', ?, 'Sales Demo', 'Onboarding',
                ?, 'Active', ?
             )"
        );
        $insP->execute([
            $prospectId,
            $d['company'],
            $d['contact'],
            $d['mobile'],
            $d['email'],
            $d['business_type'],
            $d['city'],
            $d['service'],
            $d['temp'],
            $userId,
            $d['marker'],
            $userId,
        ]);
        $prospectPk = (int) $pdo->lastInsertId();

        $onboardingId = $nextId('onboarding');
        $insO = $pdo->prepare(
            "INSERT INTO onboarding (
                onboarding_id, prospect_pk, prospect_id, customer_name, brand_name, business_type,
                contact_person, mobile, email, registered_address, billing_address, operating_address,
                gstin, pan, service_required, temperature_requirement, vehicle_requirement, storage_requirement,
                origin, destination, estimated_monthly_volume, business_start_date, commercial_reference,
                payment_terms, credit_limit, overall_status, created_by
             ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, CURDATE(), ?,
                ?, ?, 'Submitted', ?
             )"
        );
        $insO->execute([
            $onboardingId,
            $prospectPk,
            $prospectId,
            $d['company'],
            $d['brand'],
            $d['business_type'],
            $d['contact'],
            $d['mobile'],
            $d['email'],
            $d['address'],
            $d['address'],
            $d['address'],
            $d['gstin'],
            $d['pan'],
            $d['service'],
            $d['temp'],
            $d['vehicle'],
            $d['temp'],
            $d['city'],
            'Pan India',
            '25 MT / month',
            $d['marker'],
            $d['payment'],
            $d['credit'],
            $userId,
        ]);
        $onboardingPk = (int) $pdo->lastInsertId();

        foreach (onboarding_doc_types() as $type) {
            $safe = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $type));
            $file = $safe . '-demo.pdf';
            $path = 'uploads/onboarding/demo/' . $file;
            $docIns->execute([
                $onboardingPk,
                $type,
                $file,
                $path,
                24576,
                'application/pdf',
                'Received',
                $userId,
                'Sales Demo',
                'Demo document – ready for Admin review',
            ]);
        }

        $pdo->prepare(
            "INSERT INTO onboarding_approvals (onboarding_pk, department, status)
             VALUES (?, 'Admin', 'Pending')"
        )->execute([$onboardingPk]);

        $pdo->commit();
        echo "created {$onboardingId} / {$prospectId} – {$d['company']}\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, 'FAIL ' . $d['company'] . ': ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

$pending = (int) $pdo->query(
    "SELECT COUNT(*) FROM onboarding_approvals a
     JOIN onboarding o ON o.id = a.onboarding_pk
     WHERE a.department='Admin' AND a.status='Pending'
       AND o.overall_status IN ('Submitted','In Approval')"
)->fetchColumn();
echo "pending_admin_approvals={$pending}\n";
echo "OK – login as admin and open Approvals\n";
