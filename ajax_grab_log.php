
<?php

// configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/LogLoader.php';
error_reporting(E_ALL ^ E_WARNING);

$row_result = $_POST['row'];
$logs_per__page = 10;

$logLoader = new LogLoader($conn, $logs_per__page, $row_result);
$result = $logLoader->loadLogs();
$html = $result['html'];
echo $html;
$totalRecord = $result['totalRecord'];