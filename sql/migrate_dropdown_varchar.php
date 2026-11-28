<?php
/**
 * Allow custom dropdown values (from + Other).
 * Converts key ENUM columns to VARCHAR. Safe to re-run.
 * Run: php sql/migrate_dropdown_varchar.php
 */
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$alters = [
    "ALTER TABLE prospects MODIFY business_type VARCHAR(100) NOT NULL",
    "ALTER TABLE prospects MODIFY service_required VARCHAR(100) NOT NULL",
    "ALTER TABLE prospects MODIFY temperature_requirement VARCHAR(50) NOT NULL",
    "ALTER TABLE prospects MODIFY lead_source VARCHAR(100) NOT NULL",
    "ALTER TABLE customers MODIFY business_type VARCHAR(100) NOT NULL",
    "ALTER TABLE customers MODIFY temperature_requirement VARCHAR(50) NULL",
    "ALTER TABLE onboarding MODIFY business_type VARCHAR(100) NOT NULL",
    "ALTER TABLE onboarding MODIFY service_required VARCHAR(100) NOT NULL",
    "ALTER TABLE onboarding MODIFY temperature_requirement VARCHAR(50) NOT NULL",
    "ALTER TABLE onboarding MODIFY vehicle_requirement VARCHAR(50) NULL",
    "ALTER TABLE onboarding MODIFY storage_requirement VARCHAR(50) NULL",
    "ALTER TABLE followups MODIFY interaction_type VARCHAR(100) NOT NULL",
];

foreach ($alters as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK  {$sql}\n";
    } catch (Throwable $e) {
        echo "SKIP {$sql}\n  → " . $e->getMessage() . "\n";
    }
}
echo "DONE\n";
