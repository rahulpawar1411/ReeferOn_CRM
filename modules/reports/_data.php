<?php
/**
 * Shared report query builder. Returns [headers, rows, title].
 */
function report_dataset(string $type): array
{
    $pdo = db();

    switch ($type) {
        case 'prospects':
            $rows = $pdo->query(
                "SELECT prospect_id, company_name, contact_person, mobile, city, business_type,
                        service_required, pipeline_stage, salesperson_name, status, lead_creation_date
                 FROM prospects ORDER BY id DESC"
            )->fetchAll(PDO::FETCH_NUM);
            return [
                ['Prospect ID', 'Company', 'Contact', 'Mobile', 'City', 'Business Type', 'Service', 'Stage', 'Salesperson', 'Status', 'Created'],
                $rows,
                'Prospect Report',
            ];

        case 'pipeline':
            $rows = $pdo->query(
                "SELECT pipeline_stage, COUNT(*) AS cnt
                 FROM prospects WHERE status = 'Active'
                 GROUP BY pipeline_stage ORDER BY cnt DESC"
            )->fetchAll(PDO::FETCH_NUM);
            return [['Pipeline Stage', 'Count'], $rows, 'Sales Pipeline Report'];

        case 'salesperson':
            $rows = $pdo->query(
                "SELECT salesperson_name,
                        COUNT(*) AS total,
                        SUM(pipeline_stage = 'Customer Activated') AS converted,
                        SUM(pipeline_stage = 'Lost') AS lost,
                        SUM(pipeline_stage = 'Commercial Approved') AS commercial_approved
                 FROM prospects
                 GROUP BY salesperson_name
                 ORDER BY total DESC"
            )->fetchAll(PDO::FETCH_NUM);
            return [
                ['Salesperson', 'Total Prospects', 'Converted', 'Lost', 'Commercial Approved'],
                $rows,
                'Salesperson Performance Report',
            ];

        case 'followups':
            $rows = $pdo->query(
                "SELECT f.followup_date, f.interaction_type, f.status, f.discussion_summary,
                        f.prospect_id, f.company_name, f.created_by_name
                 FROM followups f
                 ORDER BY f.followup_date DESC, f.id DESC"
            )->fetchAll(PDO::FETCH_NUM);
            return [
                ['Follow-up Date', 'Type', 'Status', 'Discussion', 'Prospect ID', 'Company', 'Created By'],
                $rows,
                'Follow-up Report',
            ];

        case 'onboarding':
            $rows = $pdo->query(
                "SELECT onboarding_id, prospect_id, customer_name, gstin, overall_status,
                        service_required, created_at
                 FROM onboarding ORDER BY id DESC"
            )->fetchAll(PDO::FETCH_NUM);
            return [
                ['Onboarding ID', 'Prospect ID', 'Customer', 'GSTIN', 'Status', 'Service', 'Created'],
                $rows,
                'Onboarding Report',
            ];

        case 'pending_approvals':
            $rows = $pdo->query(
                "SELECT o.onboarding_id, o.customer_name, o.gstin, o.overall_status,
                        a.department, a.status, a.created_at
                 FROM onboarding_approvals a
                 JOIN onboarding o ON o.id = a.onboarding_pk
                 WHERE a.status = 'Pending' AND o.overall_status IN ('Submitted','In Approval')
                 ORDER BY a.id"
            )->fetchAll(PDO::FETCH_NUM);
            return [
                ['Onboarding ID', 'Customer', 'GSTIN', 'Onboarding Status', 'Department', 'Approval', 'Created'],
                $rows,
                'Pending Approval Report',
            ];

        case 'customers':
            $rows = $pdo->query(
                "SELECT customer_code, customer_name, contact_person, mobile, gstin,
                        business_type, source, status, created_at
                 FROM customers ORDER BY id DESC"
            )->fetchAll(PDO::FETCH_NUM);
            return [
                ['Customer Code', 'Name', 'Contact', 'Mobile', 'GSTIN', 'Business Type', 'Source', 'Status', 'Created'],
                $rows,
                'Customer Master Report',
            ];

        case 'customers_status':
            $rows = $pdo->query(
                "SELECT status, COUNT(*) AS cnt FROM customers GROUP BY status ORDER BY status"
            )->fetchAll(PDO::FETCH_NUM);
            $detail = $pdo->query(
                "SELECT customer_code, customer_name, status, source, mobile
                 FROM customers ORDER BY status, customer_name"
            )->fetchAll(PDO::FETCH_NUM);
            // Prefer detail sheet for export usefulness
            return [
                ['Customer Code', 'Name', 'Status', 'Source', 'Mobile'],
                $detail,
                'Active / Inactive Customer Report',
            ];

        default:
            throw new InvalidArgumentException('Unknown report type.');
    }
}

function report_types(): array
{
    return [
        'prospects',
        'pipeline',
        'salesperson',
        'followups',
        'onboarding',
        'pending_approvals',
        'customers',
        'customers_status',
    ];
}
