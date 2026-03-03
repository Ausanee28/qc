<?php
/**
 * QC Lab Tracking System — Export to Excel (CSV)
 * Generates a CSV with UTF-8 BOM for proper Thai character display in Excel.
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

// Date range
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// The exact query with the exact column mappings
$sql = "
    SELECT 
        TH.line,
        TH.receive_date,
        EU.external_name,
        TH.dmc,
        E.equipment_name,
        TM.method_name,
        IU.name AS inspector_name,
        TD.start_time,
        TD.end_time,
        TD.judgement,
        TD.remark
    FROM Transaction_Detail TD
    JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id
    JOIN External_Users EU ON TH.external_id = EU.external_id
    JOIN Equipments E ON TH.equipment_id = E.equipment_id
    JOIN Test_Methods TM ON TD.method_id = TM.method_id
    JOIN Internal_Users IU ON TD.internal_id = IU.user_id
    WHERE DATE(TH.receive_date) BETWEEN :df AND :dt
    ORDER BY TH.receive_date DESC, TD.start_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':df' => $dateFrom, ':dt' => $dateTo]);
$results = $stmt->fetchAll();

// Set headers for CSV download
$filename = 'QCLab_Report_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Open output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fwrite($output, "\xEF\xBB\xBF");

// Write the exact column headers (Thai as specified)
fputcsv($output, [
    'Line',
    'Date',
    'ผู้ส่ง',
    'DMC',
    'Detail',
    'Inspection Process',
    'ผู้ตรวจสอบ',
    'Start',
    'End',
    'Judgement',
    'หมายเหตุ',
]);

// Write data rows
foreach ($results as $row) {
    fputcsv($output, [
        $row['line'] ?? '',
        $row['receive_date'] ? date('d/m/Y H:i', strtotime($row['receive_date'])) : '',
        $row['external_name'] ?? '',
        $row['dmc'] ?? '',
        $row['equipment_name'] ?? '',
        $row['method_name'] ?? '',
        $row['inspector_name'] ?? '',
        $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : '',
        $row['end_time'] ? date('d/m/Y H:i', strtotime($row['end_time'])) : '',
        $row['judgement'] ?? '',
        $row['remark'] ?? '',
    ]);
}

fclose($output);
exit;
