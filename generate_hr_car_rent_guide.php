<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require __DIR__ . '/vendor/autoload.php';

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Car and Rent Management Guide</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin-bottom: 4px; color: #111827; }
        h2 { font-size: 15px; margin-top: 18px; margin-bottom: 6px; color: #111827; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        p { margin: 6px 0; }
        ul, ol { margin: 6px 0 10px 18px; padding: 0; }
        li { margin: 3px 0; }
        .muted { color: #6b7280; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 12px; border-radius: 6px; margin: 8px 0 12px; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
    <h1>HR Car and Rent Management Guide</h1>
    <p class="muted">Module: Human Resources > Car &amp; Rent Management</p>

    <h2>1. Purpose of Each Menu</h2>
    <div class="box">
        <p><strong>Branches</strong>: Register each branch office or proposed branch. This is the starting point for office rent and utility tracking.</p>
        <p><strong>Office Rent Agreements</strong>: Record office rental contracts linked to a branch and landlord.</p>
        <p><strong>Agreement Renewals</strong>: Track renewal decisions for existing office rent agreements.</p>
        <p><strong>Vehicle Service Requests</strong>: Log vehicle repair or maintenance requests.</p>
        <p><strong>Vehicle Maintenance</strong>: Keep the maintenance history of each vehicle.</p>
        <p><strong>Vehicle Bolo Licenses</strong>: Track bolo issue/expiry dates and receipts.</p>
        <p><strong>Vehicle Inspections</strong>: Track technical inspection expiry dates and certificates.</p>
        <p><strong>Branch Utilities</strong>: Register utility accounts for each branch, such as power or water.</p>
        <p><strong>Utility Payments</strong>: Track utility bills and payment status.</p>
    </div>

    <h2>2. Recommended Office Rent Workflow</h2>
    <ol>
        <li>Create the <strong>Branch</strong>.
            Record branch name, code, location, branch type, proposed office, address, and notes.</li>
        <li>Create the <strong>Office Rent Agreement</strong>.
            Link it to the branch, choose the landlord, enter payment cycle, rent amount, dates, and upload the scanned contract.</li>
        <li>Use <strong>Submit Legal</strong>.
            The agreement status changes from <span class="status">Draft</span> to <span class="status">Pending Legal</span>, and the branch becomes <span class="status">Pending Agreement</span>.</li>
        <li>Legal reviews the agreement.
            Use <strong>Legal Approve</strong> or <strong>Legal Reject</strong>.</li>
        <li>If approved, use <strong>Activate</strong>.
            The agreement becomes <span class="status">Active</span> and the branch becomes <span class="status">Active</span>.</li>
        <li>If the contract is ending, create an <strong>Agreement Renewal</strong>.
            Approve or reject it, then use <strong>Apply</strong> to push the renewal decision to the main agreement.</li>
        <li>When a contract ends without renewal, use <strong>Mark Expired</strong> or <strong>Terminate</strong>.</li>
    </ol>

    <h2>3. Office Rent Status Meaning</h2>
    <ul>
        <li><strong>Branch statuses</strong>: Requested, Pending Agreement, Active, Closed.</li>
        <li><strong>Agreement statuses</strong>: Draft, Pending Legal, Approved, Rejected, Active, Expired, Terminated.</li>
        <li><strong>Renewal statuses</strong>: Pending, Approved, Rejected, Applied.</li>
    </ul>

    <h2>4. Recommended Vehicle Workflow</h2>
    <ol>
        <li>Create or confirm the vehicle asset exists in Inventory/Assets and has a vehicle detail record with plate number.</li>
        <li>Create a <strong>Vehicle Service Request</strong>.
            Choose vehicle, service type, urgency, provider, and describe the problem.</li>
        <li>Use the request actions in order:
            <strong>Approve</strong> -> <strong>Start Service</strong> -> <strong>Complete</strong>.</li>
        <li>When <strong>Complete</strong> is used, the system automatically creates a maintenance history record if one does not already exist.</li>
        <li>Review or update the generated record in <strong>Vehicle Maintenance</strong>.
            Add service date, odometer, cost, next service date, next service odometer, report, and notes.</li>
        <li>Maintain compliance records separately in:
            <strong>Vehicle Bolo Licenses</strong> and <strong>Vehicle Inspections</strong>.</li>
    </ol>

    <h2>5. Vehicle Status Meaning</h2>
    <ul>
        <li><strong>Service Request</strong>: Pending, Approved, In Service, Completed, Rejected.</li>
        <li><strong>Bolo License</strong>: Valid, Expiring, Expired.</li>
        <li><strong>Inspection</strong>: Valid, Expiring, Expired.</li>
    </ul>

    <h2>6. Recommended Utility Workflow</h2>
    <ol>
        <li>Create the <strong>Branch Utility</strong>.
            Link it to the branch, choose utility type, provider, account number, payment cycle, estimated amount, and next due date.</li>
        <li>Create <strong>Utility Payments</strong> for billing periods.
            Fill period dates, due date, amount, reference, and notes.</li>
        <li>Update payment status using actions:
            <strong>Mark Paid</strong> or <strong>Mark Overdue</strong>.</li>
        <li>Keep branch utility status as <span class="status">Active</span> while the account is in use, otherwise set it to <span class="status">Inactive</span>.</li>
    </ol>

    <h2>7. Settings Needed Before Use</h2>
    <ul>
        <li>HR Settings > <strong>Car &amp; Rent Dropdowns</strong>: maintain branch type, payment cycles, utility types, service types, urgency, providers, and renewal decisions.</li>
        <li>HR Settings > <strong>Landlords</strong>: maintain landlord master data before entering agreements.</li>
    </ul>

    <h2>8. Practical Start Order for HR Team</h2>
    <ol>
        <li>Fill Car &amp; Rent Dropdowns</li>
        <li>Create Landlords</li>
        <li>Create Branches</li>
        <li>Create Office Rent Agreements</li>
        <li>Create Branch Utilities</li>
        <li>Register Vehicles, Bolo Licenses, and Inspections</li>
        <li>Use Vehicle Service Requests and Maintenance as operations continue</li>
        <li>Use Agreement Renewals and Utility Payments for periodic follow-up</li>
    </ol>

    <h2>9. Short Example</h2>
    <div class="box">
        <p><strong>New branch office</strong>: Create Branch -> Create Office Rent Agreement -> Submit Legal -> Legal Approve -> Activate -> Add Branch Utilities -> Track monthly utility payments.</p>
        <p><strong>Vehicle repair</strong>: Create Service Request -> Approve -> Start Service -> Complete -> Review generated maintenance record -> Keep bolo and inspection dates updated.</p>
    </div>
</body>
</html>
HTML;

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

$outputPath = __DIR__ . '/storage/app/hr-car-rent-management-guide.pdf';

if (! is_dir(dirname($outputPath))) {
    mkdir(dirname($outputPath), 0777, true);
}

file_put_contents($outputPath, $dompdf->output());

echo $outputPath;
