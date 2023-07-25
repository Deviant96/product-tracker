<?php
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

//place this before any script you want to calculate time
$time_start = microtime(true); 

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/common_functions.php';
require_once __DIR__ . '/classes/ProductScrapFromDatabase.php';

$productScraper = new ProductScrapFromDatabase($conn, $client);
// $productScraper->initScrapeNowToHtml();
$productScraper->initScrapeNowToDb();
// var_dump($productScraper->fetchUrlsFromDatabase());

//----------------Execution Timer END-------------------
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
$exec_message = sprintf('Total execution time: %s seconds', $execution_time);
echo $exec_message;
log_to_file($exec_message, "scrape-log");
//------------------------------------------------------
?>