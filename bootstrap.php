<?php
/**
 * The code that includes the autoloader and sets up the HTTP client, often referred to as a bootstrap file or initialization script. 
 * This file is responsible for setting up the necessary dependencies and configurations before the application can start using them.
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

// Set up the HTTP client
$client = new Client([
    'connect_timeout' => 5,
    'timeout'         => 5.00,
    'headers' => [
        'Host'=> 'tokopedia.com',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
        'Accept'=> '*/*',
        'Accept-Language'=> 'en-US,en;q=0.5',
        'Accept-Encoding'=> 'gzip, deflate, br',
        'Connection'=> 'keep-alive'
    ]
]);