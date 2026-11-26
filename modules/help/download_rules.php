<?php
/**
 * Download ReeferON CRM Rules & User Guide as a Word (.doc) file.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('help.view');

$filename = 'ReeferON_CRM_Rules_and_User_Guide_' . date('Ymd') . '.doc';
$generated = date('d M Y, h:i A') . ' IST';

$html = <<<'HTML'
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<title>ReeferON CRM – Rules & User Guide</title>
<!--[if gte mso 9]>
<xml>
  <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
  </w:WordDocument>
</xml>
<![endif]-->
<style>
  body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #222; line-height: 1.45; }
  h1 { font-size: 22pt; color: #0b3d5c; border-bottom: 2px solid #0b3d5c; padding-bottom: 6px; }
  h2 { font-size: 14pt; color: #0b3d5c; margin-top: 22px; border-bottom: 1px solid #ccd8e0; padding-bottom: 4px; }
  h3 { font-size: 12pt; color: #1a5674; margin-top: 14px; }
  p, li { font-size: 11pt; }
  .meta { color: #666; font-size: 10pt; margin-bottom: 18px; }
  .box { background: #f3f7fa; border: 1px solid #c5d6e2; padding: 10px 12px; margin: 10px 0 14px; }
  .rule { background: #fff8e8; border-left: 4px solid #e0a800; padding: 8px 12px; margin: 8px 0; }
  table { border-collapse: collapse; width: 100%; margin: 10px 0 16px; }
  th, td { border: 1px solid #99aab5; padding: 6px 8px; text-align: left; vertical-align: top; }
  th { background: #0b3d5c; color: #fff; }
  code { font-family: Consolas, monospace; background: #eef2f5; padding: 1px 4px; }
  ol.steps li { margin-bottom: 6px; }
</style>
</head>
<body>

<h1>ReeferON CRM – Rules &amp; User Guide</h1>
<p class="meta">
  Official guide for how the application works.<br>
  Generated: __GENERATED__
</p>

<div class="box">
  <strong>One connected journey:</strong>
  Sales Prospect → Pipeline &amp; Follow-ups → Commercial Approved →
  Onboarding + Documents → Admin Approval → Customer Master.
  Sales data is reused — do not retype the same company details.
</div>

<h2>1. Purpose of this CRM</h2>
<p>
  ReeferON CRM manages cold-chain / logistics sales leads from first contact
  until they become official customers. It keeps Prospect, Onboarding, Approvals,
  Customers, Follow-ups, Reports, Notifications, Users, and Logs in one system.
</p>

<h2>2. Important rules (must follow)</h2>

<div class="rule"><strong>Rule 1 – Login &amp; access:</strong> Every user must log in.
Access is controlled by Role checkboxes (Admin, Salesperson, Management, Viewer, or custom).
If a menu or button is missing, your role does not have that permission.</div>

<div class="rule"><strong>Rule 2 – No permanent delete:</strong> Records are not erased.
Use <em>Inactive</em> (or Cancelled / On Hold where available). History and codes stay safe.
Admins can set status back to <em>Active</em>.</div>

<div class="rule"><strong>Rule 3 – Auto IDs cannot be edited:</strong>
Prospect = <code>REO-LEAD-xxxx</code>, Onboarding = <code>REO-ONB-xxxx</code>,
Customer = <code>REO-CUS-xxxx</code>. The system creates them.</div>

<div class="rule"><strong>Rule 4 – Onboarding only after Commercial Approved:</strong>
You cannot start paperwork before the deal stage is Commercial Approved.</div>

<div class="rule"><strong>Rule 5 – Documents before Submit:</strong>
Every required document must be <em>Received</em> (file uploaded) or <em>Not Applicable</em>
before Submit for Admin Approval.</div>

<div class="rule"><strong>Rule 6 – Customer code only after Admin Approve:</strong>
Official Customer Master entry and <code>REO-CUS-xxxx</code> are created when Admin Approves.
Do not try to invent a customer code for new sales deals.</div>

<div class="rule"><strong>Rule 7 – Approval needs dates:</strong>
For Excel (CSV) export on list pages, select <em>From</em> and <em>To</em> dates in Filters,
Apply Filters, then click Excel (CSV).</div>

<div class="rule"><strong>Rule 8 – Approvals are separate:</strong>
Users cannot set onboarding status to Approved / Rejected from the Edit form.
Only the Approvals screen can decide.</div>

<h2>3. How the application works (simple flow)</h2>
<ol class="steps">
  <li><strong>Create Prospect</strong> – Enter company, contact, mobile, business type, service, temperature, lead source. System gives <code>REO-LEAD-xxxx</code>. Stage starts at New Prospect.</li>
  <li><strong>Pipeline + Follow-ups</strong> – Move stages (Calling → Meeting → Proposal → Negotiation → Commercial Sent → Commercial Approved). Log every call/meeting in Follow-ups.</li>
  <li><strong>Commercial Approved</strong> – When rates are accepted, set this stage. Button <em>Initiate Customer Onboarding</em> appears on Prospect View.</li>
  <li><strong>Onboarding</strong> – Sales data is auto-copied. Add GSTIN, PAN, addresses, vehicle, route, payment terms. Upload documents. Submit for Admin Approval.</li>
  <li><strong>Admin Approval</strong> – Admin Approves or Rejects with remarks. Reject → fix → resubmit.</li>
  <li><strong>Customer Master</strong> – On Approve, system creates <code>REO-CUS-xxxx</code>, opens Customer Master (source = New Onboarding), and sets prospect stage to Customer Activated.</li>
</ol>

<h2>4. Pipeline stages</h2>
<p>
  New Prospect → Calling → Meeting → Proposal Sent → Negotiation → Commercial Sent →
  <strong>Commercial Approved</strong> → Onboarding → Customer Activated
</p>
<p>Also available: Lost, On Hold, Cancelled.</p>

<h2>5. Auto-generated codes</h2>
<table>
  <tr><th>Code</th><th>When it is created</th><th>Where</th></tr>
  <tr><td><code>REO-LEAD-xxxx</code></td><td>New prospect saved</td><td>New Prospect form</td></tr>
  <tr><td><code>REO-ONB-xxxx</code></td><td>Onboarding started after Commercial Approved</td><td>Prospect View → Initiate Customer Onboarding</td></tr>
  <tr><td><code>REO-CUS-xxxx</code></td><td>Admin Approves onboarding</td><td>Approvals → Decide → Approve</td></tr>
  <tr><td><code>REO-CUS-xxxx</code></td><td>Add Existing Customer (blank code = auto)</td><td>Add Existing Customer form</td></tr>
</table>

<h2>6. Left menu guide</h2>
<table>
  <tr><th>Menu</th><th>What it is for</th></tr>
  <tr><td>Dashboard</td><td>Live counts and global search (ID, name, mobile, GSTIN)</td></tr>
  <tr><td>Prospects / New Prospect</td><td>All sales leads — list, filter, create, view, edit</td></tr>
  <tr><td>Follow-ups</td><td>Calls and meetings — Today / Upcoming / Overdue / Completed</td></tr>
  <tr><td>Onboarding</td><td>Paperwork + documents after Commercial Approved</td></tr>
  <tr><td>Approvals</td><td>Admin Approve / Reject → creates customer code</td></tr>
  <tr><td>Customers / Add Existing</td><td>Official Customer Master; shortcut for old customers</td></tr>
  <tr><td>Reports</td><td>View reports or download Excel (CSV)</td></tr>
  <tr><td>Notifications</td><td>Alerts (follow-up due, approval pending, code created)</td></tr>
  <tr><td>Logs</td><td>Full activity audit trail + Excel export</td></tr>
  <tr><td>Roles / Users</td><td>Permission checkboxes and employee logins</td></tr>
  <tr><td>Help</td><td>In-app guide + this Word download</td></tr>
</table>

<h2>7. Existing customers (shortcut)</h2>
<p>
  If a customer already does business with ReeferON, skip the prospect pipeline:
</p>
<ol>
  <li>Open <strong>Add Existing Customer</strong></li>
  <li>Enter an old known code, or leave blank to auto-generate <code>REO-CUS-xxxx</code></li>
  <li>Save → appears in Customer Master with Source = Existing Customer</li>
</ol>

<h2>8. Excel export</h2>
<ol>
  <li>Open the list (Prospects / Customers / Onboarding / Logs)</li>
  <li>Open <strong>Filters</strong></li>
  <li>Select <strong>From date</strong> and <strong>To date</strong></li>
  <li>Click <strong>Apply Filters</strong></li>
  <li>Click <strong>Excel (CSV)</strong> — file opens in Microsoft Excel</li>
</ol>
<p>Export respects current filters and is limited to 5,000 rows for safety.</p>

<h2>9. Inactive vs Active</h2>
<ul>
  <li><strong>Inactive</strong> = soft close. Record stays in database with history.</li>
  <li>Users with delete permission see Inactive / Active links.</li>
  <li>Confirm by typing the word shown in the popup.</li>
  <li>You cannot permanently erase prospects, customers, or users from normal screens.</li>
</ul>

<h2>10. Documents</h2>
<p>Required document types in onboarding:</p>
<ul>
  <li>GST Certificate</li>
  <li>PAN Card</li>
  <li>Company KYC</li>
  <li>Customer Agreement</li>
  <li>Purchase Order / Work Order</li>
  <li>Commercial Approval</li>
</ul>
<p>
  After upload, use <strong>View</strong> or <strong>Download</strong> on the onboarding screen.
  Clearing a file (Admin) sets the document back to Pending.
</p>

<h2>11. Daily checklist</h2>
<ol>
  <li>Login</li>
  <li>Check Follow-ups → Today / Overdue</li>
  <li>Move open prospects in the pipeline</li>
  <li>Complete pending onboarding documents and Submit</li>
  <li>Admin: clear Pending Approvals</li>
  <li>Check Notifications</li>
  <li>Use Reports / Excel when management needs numbers</li>
</ol>

<h2>12. If something looks stuck</h2>
<ol>
  <li>Is the Prospect created?</li>
  <li>Is stage = Commercial Approved?</li>
  <li>Was Onboarding initiated + details + docs + Submit done?</li>
  <li>Did Admin Decide on Approvals?</li>
  <li>Is the customer code visible under Customers?</li>
  <li>Still unsure? Open Help in the CRM, or check Health JSON for database status.</li>
</ol>

<h2>13. Login tip (demo / first install)</h2>
<p>
  Default system account (change after go-live): user ID <code>system</code>, password <code>password</code>.
  Create real employee users under <strong>Users</strong> and assign Roles.
</p>

<div class="box">
  <strong>End of guide.</strong><br>
  This file matches the in-app Help pages. Keep it for training new users.
  For live screens, open Help inside ReeferON CRM.
</div>

</body>
</html>
HTML;

$html = str_replace('__GENERATED__', e($generated), $html);

header('Content-Type: application/msword; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo "\xEF\xBB\xBF"; // UTF-8 BOM for Word
echo $html;
exit;
