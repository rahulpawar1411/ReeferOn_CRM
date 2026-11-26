<?php
/**
 * Seed demo notifications for Notifications menu.
 * Run: php sql/seed_notifications_demo.php
 */
require_once __DIR__ . '/../includes/functions.php';

ensure_auth_schema();

$demos = [
    [
        'event' => 'Onboarding Submitted',
        'title' => 'New approval request',
        'message' => 'REO-ONB-0001 – CoolTreat Foods Pvt Ltd submitted for Admin approval.',
        'link' => base_url('modules/approvals/index.php?tab=pending'),
        'module' => 'onboarding',
        'related' => 'REO-ONB-0001',
        'marker' => 'DEMO-NOTIF-1',
    ],
    [
        'event' => 'Onboarding Submitted',
        'title' => 'New approval request',
        'message' => 'REO-ONB-0002 – FreshBasket Retail India submitted for Admin approval.',
        'link' => base_url('modules/approvals/index.php?tab=pending'),
        'module' => 'onboarding',
        'related' => 'REO-ONB-0002',
        'marker' => 'DEMO-NOTIF-2',
    ],
    [
        'event' => 'Document Uploaded',
        'title' => 'GST Certificate received',
        'message' => 'GST Certificate uploaded for CoolTreat Foods (demo).',
        'link' => base_url('modules/onboarding/index.php'),
        'module' => 'onboarding',
        'related' => 'DEMO-DOC-GST',
        'marker' => 'DEMO-NOTIF-3',
    ],
    [
        'event' => 'Follow-up Reminder',
        'title' => 'Follow-ups due today',
        'message' => 'Check Today tab in Follow-ups for open calls/meetings (demo).',
        'link' => base_url('modules/followups/index.php?tab=today'),
        'module' => 'followups',
        'related' => 'DEMO-FU-TODAY',
        'marker' => 'DEMO-NOTIF-4',
    ],
    [
        'event' => 'Customer Activated',
        'title' => 'Customer code created (demo sample)',
        'message' => 'Sample: after Admin Approve, customer gets REO-CUS-xxxx in Customer Master.',
        'link' => base_url('modules/customers/index.php'),
        'module' => 'customers',
        'related' => 'DEMO-CUS',
        'marker' => 'DEMO-NOTIF-5',
    ],
];

$chk = db()->prepare('SELECT id FROM notifications WHERE related_id = ? LIMIT 1');
$ins = db()->prepare(
    'INSERT INTO notifications (user_id, event_type, title, message, link_url, related_module, related_id, is_read)
     VALUES (NULL, ?, ?, ?, ?, ?, ?, 0)'
);

$created = 0;
foreach ($demos as $d) {
    $chk->execute([$d['marker']]);
    if ($chk->fetch()) {
        echo "exists {$d['marker']}\n";
        continue;
    }
    $ins->execute([
        $d['event'],
        $d['title'],
        $d['message'],
        $d['link'],
        $d['module'],
        $d['marker'],
    ]);
    $created++;
    echo "created {$d['marker']} – {$d['title']}\n";
}

$unread = (int) db()->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
echo "created_new={$created} unread_total={$unread}\n";
echo "OK – open Notifications menu\n";
