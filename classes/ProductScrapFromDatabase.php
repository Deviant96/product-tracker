<?php

// use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;

class ProductScrapFromDatabase
{
    private $client;
    private $conn;

    public function __construct($conn, $client)
    {
        $this->client = $client;
        $this->conn = $conn;
    }

    public function fetchUrlsFromDatabase()
    {
        $urlList = [];

        try {
            print_r('Fetching products and URLs from db..');
            $stmt = $this->conn->prepare("SELECT a.product_id, b.url_id, b.product_url FROM product AS a INNER JOIN urls AS b ON a.url_id = b.url_id");
            $stmt->execute();
            $urlList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }

        return $urlList;
        /* Example return
            { 
                [0]=> array(3) { 
                    ["product_id"]=> string(1) "1" 
                    ["url_id"]=> string(1) "1" 
                    ["product_url"]=> string(108) "https%3A%2F%2Fwww.tokopedia.com%2Fiblink%2Fkonektor-sambungan-kabel-lan-rj45-barel-utp-rj45-barrel-lan-bagus" 
                } 
                [1]=> array(3) { 
                    ["product_id"]=> string(1) "2" 
                    ["url_id"]=> string(1) "2" 
                    ["product_url"]=> string(113) "https%3A%2F%2Fwww.tokopedia.com%2Fenterkomputer%2Famd-ryzen-7-5800x3d-3-4ghz-up-to-4-5ghz-cache-96mb-105w-am4-box" 
                } 
                [2]=> array(3) { 
                    ["product_id"]=> string(1) "3" 
                    ["url_id"]=> string(1) "4" 
                    ["product_url"]=> string(113) "https%3A%2F%2Fwww.tokopedia.com%2Fenterkomputer%2Famd-ryzen-7-7700x-4-5ghz-up-to-5-4ghz-cache-32mb-am5-box-8-core" 
                } 
            }
        */
    }

    public function scrapeProductDataAsync($url, $productId, $urlId)
    {
        return $this->client->requestAsync('GET', $url)
            ->then(function (ResponseInterface $response) use ($productId, $urlId) {
                $html = $response->getBody()->getContents();
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                $dom->loadHTML($html);
                $xpath = new DOMXPath($dom);

                // Check if the XPath query for product name returns a valid result
                $productNameNode = $xpath->query('//div[@id="pdp_comp-product_content"]/div/h1')->item(0);
                $productName = $productNameNode ? $productNameNode->nodeValue : 'N/A';

                // Check if the XPath query for product price returns a valid result
                $productPriceNode = $xpath->query('//div[@class="css-chstwd"]//div[@class="price"]')->item(0);
                $productPrice = $productPriceNode ? $productPriceNode->nodeValue : 'N/A';

                // Check if the XPath query for product stock returns a valid result
                $productStockNode = $xpath->query('//input[@id="qty-editor-atc"]/@aria-valuemax')->item(0);
                $productStock = $productStockNode ? $productStockNode->nodeValue : 'N/A';

                $productData = [
                    'product_id' => $productId,
                    'url_id' => $urlId,
                    'name' => $productName,
                    'price' => $productPrice,
                    'stock' => $productStock,
                    // Add more data points to the array
                ];

                return $productData;
            })
            ->otherwise(function (Exception $exception) use ($url) {
                throw new Exception('Error scraping product from URL: ' . $url . PHP_EOL .
                    'Error message: ' . $exception->getMessage());
            });
    }


    public function processScrapedData($results)
    {
        foreach ($results as $url => $result) {
            $url = urldecode($url);
            echo '<hr>';
            if ($result['state'] === Promise\PromiseInterface::FULFILLED) {
                $productData = $result['value'];
                if ($productData !== null) {
                    echo "<pre>";
                    print_r($productData);
                    echo "</pre>";
                    echo 'Product URL: <a href="' . $url . '" target="_blank">' . $url . '</a>' . PHP_EOL . '<br>';
                    echo 'Product Name: ' . $productData['name'] . PHP_EOL . '<br>';
                    echo 'Product Price: ' . $productData['price'] . PHP_EOL . '<br>';
                    echo 'Product Stock: ' . $productData['stock'] . PHP_EOL . '<br>';
                    // Output or process other scraped data
                    
                    $scrap_message = sprintf(
                                            "Stored to database with details: Product name: %s, Price: %s, Stock: %d", 
                                            $productData['name'], 
                                            $productData['price'], 
                                            $productData['stock']
                                        );
                    echo $scrap_message;
                    log_to_file($scrap_message, "scrape-log");
                } else {
                    echo "Error scraping $url: No result was returned.";
                }
            } else {
                echo 'Error scraping ' . $url . ': ' . $result['reason']->getMessage() . PHP_EOL;
            }

            echo '<hr>';
        }
    }

    public function scrapeProducts()
    {
        $urlList = $this->fetchUrlsFromDatabase();
        $promises = [];

        foreach ($urlList as $url) {
            $decodedUrl = urldecode($url['product_url']);
            $productId = $url['product_id'];
            $urlId = $url['url_id'];
            $promises[$decodedUrl] = $this->scrapeProductDataAsync($decodedUrl, $productId, $urlId);
        }
        $results = Promise\Utils::settle($promises)->wait();

        return $results;
    }

    public function initScrapeNowToDb() {
        $results = $this->scrapeProducts();
        $this->storeScrapedDataToDatabase($results);
    }

    public function initScrapeNowToHtml() {
        $results = $this->scrapeProducts();
        $this->processScrapedData($results);
    }

    public function storeScrapedDataToDatabase($results) {
        foreach ($results as $url => $result) {
            if ($result['state'] === Promise\PromiseInterface::FULFILLED) {
                $productData = $result['value'];
                if ($productData !== null) {
                    $productId = $productData['product_id'];
                    $urlId = $productData['url_id'];
                    var_dump($urlId);
                    $stock = $productData['stock'];
                    $price = convertHtmlPriceToDigit($productData['price']);

                    $product_log_id = $this->storeProductLogToDb($productId, $urlId);
                    $this->storeProductPriceToDb($productId, $product_log_id, $price);
                    $this->storeProductStockToDb($productId, $product_log_id, $stock);
                    $scrap_message = sprintf("Stored to database with details: Product ID: %d, Price: %d, Stock: %d<br>", $productId, $price, $stock);
                    echo $scrap_message;
                    log_to_file($scrap_message, "scrape-log");
                } else {
                    echo "Error scraping $url: No result was returned.";
                }
            } else {
                echo 'Error scraping ' . $url . ': ' . $result['reason']->getMessage() . PHP_EOL;
            }
        }
    }

    public function storeProductLogToDb($productId, $urlId) {
        // var_dump($productId);
        if(!empty($productId)) {
            try {
                $stmt_insert_log = $this->conn->prepare("INSERT INTO product_log (product_id, url_id) 
                    VALUES (:product_id, :url_id)");
                $stmt_insert_log->bindParam(':product_id', $productId);
                $stmt_insert_log->bindParam(':url_id', $urlId);
                $stmt_insert_log->execute();

                return $this->conn->lastInsertId();
            } catch (PDOException $e) {
                error_log("Connection failed: " . $e->getMessage());
            }
        } else {
            error_log("Product ID is required. Please provide a valid product ID.");
            return null;
        }
    }

    public function storeProductPriceToDb($productId, $product_log_id, $price) {
        if($productId && $product_log_id) {
            try {
                $stmt_insert_price = $this->conn->prepare("INSERT INTO price (product_id, product_log_id, price) 
                    VALUES (:product_id, :product_log_id, :price)");
                $stmt_insert_price->bindParam(':product_id', $productId);
                $stmt_insert_price->bindParam(':product_log_id', $product_log_id);
                $stmt_insert_price->bindParam(':price', $price);
                $stmt_insert_price->execute();
            } catch (PDOException $e) {
                error_log("Connection failed: " . $e->getMessage());
            }
        } else {
            error_log("Product ID and Product log ID are required. Please provide the valid one.");
            return null;
        }
    }

    public function storeProductStockToDb($productId, $product_log_id, $stock) {
        if($productId && $product_log_id) {
            try {
                $stmt_insert_stock = $this->conn->prepare("INSERT INTO stock (product_id, product_log_id, stock_available) 
                    VALUES (:product_id, :product_log_id, :stock_available)");
                $stmt_insert_stock->bindParam(':product_id', $productId);
                $stmt_insert_stock->bindParam(':product_log_id', $product_log_id);
                $stmt_insert_stock->bindParam(':stock_available', $stock);
                $stmt_insert_stock->execute();
            } catch (PDOException $e) {
                error_log("Connection failed: " . $e->getMessage());
            }
        } else {
            error_log("Product ID and Product log ID are required. Please provide the valid one.");
            return null;
        }
    }


}