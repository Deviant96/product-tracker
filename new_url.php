<?php
$time_start = microtime(true); 

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once 'includes/common_functions.php';
require_once 'classes/ProductScraper.php';

//----------------Execution Timer END-------------------
$time_first = microtime(true);
$first_execution_time = ($time_first - $time_start);
echo 'Init execution time: '.$first_execution_time.' seconds<br>';
//------------------------------------------------------

$listurl = $_POST['add_url'];
if($listurl) {
    $productScraper = new ProductScraper($conn, $client, $listurl);
    $productScraper->storeScrapedNewProductToDatabase();
} else {
    echo "No URL was given!";
}

//----------------Execution Timer END-------------------
$time_second = microtime(true);
$second_execution_time = ($time_second - $time_start);
echo 'Functions execution time: '.$second_execution_time.' seconds<br>';
//------------------------------------------------------