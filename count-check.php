<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$headers = DB::table('Transaction_Header')->count();
$details = DB::table('Transaction_Detail')->count();
$open = DB::table('Transaction_Header')->whereNull('return_date')->count();

echo json_encode([
  'transaction_header_count' => $headers,
  'transaction_detail_count' => $details,
  'open_jobs_count' => $open,
], JSON_PRETTY_PRINT), PHP_EOL;
