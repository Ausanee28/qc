<?php
/**
 * QC Lab Tracking System — Database Connection
 * โหลดค่าจาก config.php (ไม่อยู่ใน git) ถ้าไม่มีใช้ค่า default
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$host = $host ?? 'localhost';
$port = $port ?? 3306;
$db = $db ?? 'qc';
$user = $user ?? 'root';
$pass = $pass ?? '';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
}
catch (PDOException $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Database Error</title></head><body>';
    echo '<div style="text-align:center;margin-top:100px;font-family:sans-serif;">';
    echo '<h1 style="color:#ef4444;">Database Connection Failed</h1>';
    echo '<p>Please ensure MySQL is running in XAMPP and the <code>qc</code> database exists.</p>';
    echo '<p style="color:#6b7280;font-size:0.875rem;">Run <code>setup.sql</code> in phpMyAdmin to create the database.</p>';
    echo '</div></body></html>';
    exit;
}
