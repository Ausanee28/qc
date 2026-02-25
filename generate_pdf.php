<?php
/**
 * PDF Test Certificate Generator (Dompdf)
 * GET ?id=transaction_id → downloads QC_Report_[DMC].pdf
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: report.php');
    exit;
}

// Fetch header
$stmt = $pdo->prepare("
    SELECT TH.transaction_id, TH.dmc, TH.line, TH.receive_date, TH.status,
           EU.external_name AS sender, IU.name AS receiver, E.equipment_name
    FROM Transaction_Header TH
    JOIN External_Users EU ON TH.external_id = EU.external_id
    JOIN Internal_Users IU ON TH.internal_id = IU.user_id
    JOIN Equipments E ON TH.equipment_id = E.equipment_id
    WHERE TH.transaction_id = :id
");
$stmt->execute([':id' => $id]);
$job = $stmt->fetch();

if (!$job) {
    die('Transaction not found.');
}

// Fetch test details
$stmt2 = $pdo->prepare("
    SELECT TD.start_time, TD.end_time, TD.judgement, TD.remark,
           TM.method_name, IU.name AS inspector_name
    FROM Transaction_Detail TD
    JOIN Test_Methods TM ON TD.method_id = TM.method_id
    JOIN Internal_Users IU ON TD.internal_id = IU.user_id
    WHERE TD.transaction_id = :id
    ORDER BY TD.start_time ASC
");
$stmt2->execute([':id' => $id]);
$details = $stmt2->fetchAll();

$overallJudgement = 'OK';
foreach ($details as $d) {
    if ($d['judgement'] === 'NG') {
        $overallJudgement = 'NG';
        break;
    }
}

// Build test rows
$testRows = '';
foreach ($details as $i => $d) {
    $bg = $i % 2 === 0 ? '#f8fafc' : '#ffffff';
    $jColor = $d['judgement'] === 'OK' ? '#059669' : '#dc2626';
    $jBg = $d['judgement'] === 'OK' ? '#ecfdf5' : '#fef2f2';
    $testRows .= "
    <tr style='background:{$bg};'>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;text-align:center;font-size:12px;'>" . ($i + 1) . "</td>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;'>" . htmlspecialchars($d['method_name']) . "</td>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:11px;color:#64748b;'>" . ($d['start_time'] ? date('d/m/Y H:i', strtotime($d['start_time'])) : '-') . "</td>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:11px;color:#64748b;'>" . ($d['end_time'] ? date('d/m/Y H:i', strtotime($d['end_time'])) : '-') . "</td>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;'>" . htmlspecialchars($d['inspector_name']) . "</td>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;text-align:center;'>
            <span style='background:{$jBg};color:{$jColor};padding:3px 14px;border-radius:12px;font-size:11px;font-weight:700;'>" . htmlspecialchars($d['judgement'] ?? 'Pending') . "</span>
        </td>
        <td style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:11px;color:#64748b;max-width:120px;'>" . htmlspecialchars($d['remark'] ?? '') . "</td>
    </tr>";
}

if (empty($details)) {
    $testRows = "<tr><td colspan='7' style='padding:30px;text-align:center;color:#94a3b8;font-size:13px;'>No test results recorded yet.</td></tr>";
}

$overallColor = $overallJudgement === 'OK' ? '#059669' : '#dc2626';
$overallBg = $overallJudgement === 'OK' ? '#ecfdf5' : '#fef2f2';
$overallBorder = $overallJudgement === 'OK' ? '#a7f3d0' : '#fecaca';

$html = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
    @page { margin: 30px 40px; }
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; font-size: 13px; line-height: 1.5; }
    .header-bar { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 25px 30px; color: white; border-radius: 8px; margin-bottom: 25px; }
    .header-bar h1 { font-size: 22px; font-weight: 800; margin: 0 0 3px 0; letter-spacing: 1px; }
    .header-bar p { font-size: 11px; margin: 0; opacity: 0.8; }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .info-table td { padding: 8px 14px; font-size: 12px; border: 1px solid #e2e8f0; }
    .info-label { background: #f1f5f9; font-weight: 700; color: #475569; width: 130px; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
    .info-value { color: #1e293b; font-weight: 500; }
    .section-title { font-size: 14px; font-weight: 700; color: #4f46e5; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; }
    .results-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
    .results-table th { padding: 10px 14px; background: #4f46e5; color: white; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
    .signature-box { display: inline-block; width: 45%; text-align: center; margin-top: 20px; }
    .signature-line { border-top: 1px solid #94a3b8; margin-top: 50px; padding-top: 8px; font-size: 11px; color: #64748b; }
    .footer { border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 30px; font-size: 9px; color: #94a3b8; text-align: center; }
</style></head>
<body>

    <!-- Header -->
    <div class="header-bar">
        <h1>QC LAB TEST CERTIFICATE</h1>
        <p>Official Quality Control Inspection Report — Document #' . $job['transaction_id'] . '</p>
    </div>

    <!-- Job Info -->
    <table class="info-table">
        <tr>
            <td class="info-label">Document No.</td>
            <td class="info-value">QC-' . str_pad($job['transaction_id'], 5, '0', STR_PAD_LEFT) . '</td>
            <td class="info-label">Date Issued</td>
            <td class="info-value">' . date('d/m/Y') . '</td>
        </tr>
        <tr>
            <td class="info-label">DMC Code</td>
            <td class="info-value" style="font-weight:800;font-size:14px;color:#4f46e5;">' . htmlspecialchars($job['dmc'] ?: 'N/A') . '</td>
            <td class="info-label">Production Line</td>
            <td class="info-value">' . htmlspecialchars($job['line'] ?: '-') . '</td>
        </tr>
        <tr>
            <td class="info-label">Equipment</td>
            <td class="info-value">' . htmlspecialchars($job['equipment_name']) . '</td>
            <td class="info-label">Received Date</td>
            <td class="info-value">' . date('d/m/Y H:i', strtotime($job['receive_date'])) . '</td>
        </tr>
        <tr>
            <td class="info-label">Sender</td>
            <td class="info-value">' . htmlspecialchars($job['sender']) . '</td>
            <td class="info-label">Receiver</td>
            <td class="info-value">' . htmlspecialchars($job['receiver']) . '</td>
        </tr>
    </table>

    <!-- Overall Judgement -->
    <div style="background:' . $overallBg . ';border:2px solid ' . $overallBorder . ';border-radius:8px;padding:12px 20px;margin-bottom:20px;text-align:center;">
        <span style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Overall Judgement:</span>
        <span style="font-size:20px;font-weight:800;color:' . $overallColor . ';margin-left:10px;">' . $overallJudgement . '</span>
    </div>

    <!-- Test Results -->
    <div class="section-title">Test Results</div>
    <table class="results-table">
        <thead>
            <tr>
                <th style="width:30px;text-align:center;">#</th>
                <th>Test Method</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Inspector</th>
                <th style="text-align:center;">Result</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>' . $testRows . '</tbody>
    </table>

    <!-- Signatures -->
    <div style="margin-top:40px;">
        <div class="signature-box" style="float:left;">
            <div class="signature-line">Inspected By</div>
            <p style="font-size:10px;color:#94a3b8;margin-top:4px;">Date: _____ / _____ / _____</p>
        </div>
        <div class="signature-box" style="float:right;">
            <div class="signature-line">Approved By</div>
            <p style="font-size:10px;color:#94a3b8;margin-top:4px;">Date: _____ / _____ / _____</p>
        </div>
        <div style="clear:both;"></div>
    </div>

    <!-- Footer -->
    <div class="footer">
        QC Lab Tracking System &bull; Generated on ' . date('d/m/Y H:i') . ' &bull; This is a computer-generated document.
    </div>

</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'QC_Report_' . ($job['dmc'] ?: $job['transaction_id']) . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
