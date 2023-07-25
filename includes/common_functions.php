<?php
/**
 * Get a string that is between two other strings.
 */
function get_string_between($str, $start, $end) {
    $start_position = strpos($str, $start) + strlen($start);
    $end_position = strpos($str, $end, $start_position);
    return substr($str, $start_position, $end_position - $start_position); 
}


/**
 * Get execution time of code in seconds.
 * @param mixed $code The code to be measured.
 * @param string $timerTitle Title for the timer.
 * @return string
 */
function calculateExecutionTime($code, $timerTitle) {
    $time_start = microtime(true);

    $code();

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    return print_r($timerTitle . ' execution time: '.$execution_time.' seconds<br>');
}


/**
 * Log a message with specified category into db
 */
function log_to_db(string $message, string $category) {
    date_default_timezone_set('Asia/Jakarta');
    $datetime = date('Y-m-d H:i:s');
    global $conn;
    if($conn) {
        try {
            $stmt = $conn->prepare("INSERT INTO logging (date_time, mssg, category) VALUES (:date_time, :mssg, :category)");
            $stmt->bindParam(':date_time', $datetime);
            $stmt->bindParam(':mssg', $message);
            $stmt->bindParam(':category', $category);
            
            $stmt->execute();
          
        } catch(PDOException $e) {
            echo "Logging failed: " . $e->getMessage();
        }
    } else {
        if (function_exists('log_to_file')) {
            log_to_file($message, $category);
        }
    }
}


/**
 * Log a message with specified category into log file
 */
function log_to_file(string $message, string $category): void {
    date_default_timezone_set('Asia/Jakarta');
    $datetime = date('Y-m-d H:i:s');
    $log = sprintf("[%s][%s] Message: %s\n", $datetime, $category, $message);
    file_put_contents('./log/message_log.log', $log, FILE_APPEND);
}


function getDomainFromUrl($url) {
    $parsedUrl = parse_url($url);
    return $parsedUrl['host'];
}


// Extract the desired portion until the first slash after 'https://'
function getStoreUrl($url) {
    preg_match('#^https://([^/]+/[^/]+)#', $url, $matches);

    if (isset($matches[1])) {
        $desiredUrl = 'https://' . $matches[1];
        return $desiredUrl;
    } else {
        return "URL extraction failed.";
    }
}


function convertHtmlPriceToDigit($htmlPrice) {
    return preg_replace('/[^0-9]/', '', $htmlPrice);
}