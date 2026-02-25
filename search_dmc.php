<?php
/**
 * Quick DMC Search API
 * GET ?dmc=xxx → JSON response
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$dmc = trim($_GET['dmc'] ?? '');

if ($dmc === '') {
    echo json_encode(['success' => false, 'message' => 'DMC is required']);
    exit;
}

$sql = "SELECT 
            TH.transaction_id,
            TH.dmc,
            TH.line,
            TH.receive_date,
            TH.status AS job_status,
            EU.external_name AS sender,
            E.equipment_name,
            IU.name AS inspector_name,
            TM.method_name,
            TD.judgement,
            TD.start_time,
            TD.end_time,
            TD.remark
        FROM Transaction_Header TH
        JOIN External_Users EU ON TH.external_id = EU.external_id
        JOIN Equipments E ON TH.equipment_id = E.equipment_id
        JOIN Internal_Users IU ON TH.internal_id = IU.user_id
        LEFT JOIN Transaction_Detail TD ON TH.transaction_id = TD.transaction_id
        LEFT JOIN Test_Methods TM ON TD.method_id = TM.method_id
        WHERE TH.dmc LIKE :dmc
        ORDER BY TH.receive_date DESC, TD.start_time DESC
        LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute([':dmc' => '%' . $dmc . '%']);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'No results found for "' . $dmc . '"']);
    exit;
}

echo json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]);
