<?php
require_once __DIR__ . '/../includes/common_functions.php';

class ProductScraper {
    private $client;
    private $url;
    private $conn;

    public function __construct($conn, $client, $url) {
        $this->client = $client;
        $this->url = $url;
        $this->conn = $conn;
    }

    public function scrapeNewProductFromUrl(string $url) {
        try {
            $response = $this->client->request('GET', $url);
            
            $html = $response->getBody()->getContents();
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // Query the product name
            $productNameNode = $xpath->query('//div[@id="pdp_comp-product_content"]/div/h1')->item(0);
            $productName = $productNameNode ? $productNameNode->nodeValue : null;

            // Query the product price
            // $productPriceNode = $xpath->query('//div[@class="css-chstwd"]//div[@class="price"]')->item(0);
            // $productPrice = $productPriceNode ? $productPriceNode->nodeValue : null;

            // Query the store name
            $get_shop_name = $xpath->query("//title")->item(0);
            $storeName = $get_shop_name ? $get_shop_name->nodeValue : null;
            $shop_name = get_string_between($storeName, ' -  - ', ' | Tokopedia');
            $site = getDomainFromUrl($url);

            $productData = [
                'name' => $productName,
                // 'price' => $productPrice,
                'store_name' => $shop_name,
                'site' => $site,
                // Add more data points to the array
            ];

            return $productData;
        } catch (Exception $exception) {
            echo 'Error scraping product from URL: ' . $url . PHP_EOL;
            echo 'Error message: ' . $exception->getMessage() . PHP_EOL;
            return null;
        }
    }

    public function scrapeProduct() {
        try {
            calculateExecutionTime(function() {
                $scrapedData = $this->scrapeNewProductFromUrl($this->url);
                print_r($scrapedData);
            }, "Scrape");
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function storeScrapedNewProductToDatabase() {
        $scrapedData = $this->scrapeNewProductFromUrl($this->url);
        // var_dump($scrapedData);
        $product_name = $scrapedData['name'];
        $store_name = $scrapedData['store_name'];
        $site_url = $scrapedData['site'];
        $store_url = getStoreUrl($this->url);
        $product_url = urlencode($this->url);

        try {
            $urlId = $this->storeNewUrlToDb($product_url);
            if($urlId) {
                $site_id = $this->storeNewSiteToDb($site_url);
                $store_id = $this->storeNewStoreToDb($store_name, $store_url, $site_id);
                $this->storeNewProductToDb($product_url, $urlId, $site_id, $store_id, $product_name);
            } else {
                echo "A product with this URL already exist<br>";
            }
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    }

    private function storeNewUrlToDb(string $product_url) {
        $store_url = urlencode($product_url);
        try {
            print_r('Fetching URL details..<br>');
            $stmt_store = $this->conn->prepare("SELECT url_id FROM urls WHERE product_url LIKE :product_url");
            $stmt_store->bindParam(':product_url', $product_url);
            $stmt_store->execute();
            $get_url = $stmt_store->fetch();
            if($get_url){
                print_r('URL exist<br>');
                return false;
            } else {
                print_r('URL doesn\'t exist. Inserting new URL..<br>');

                $stmt = $this->conn->prepare("INSERT INTO urls (product_url) 
                VALUES (:product_url)");
                $stmt->bindValue(':product_url', $product_url, PDO::PARAM_STR);
                $stmt->execute();

                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    }

    private function storeNewProductToDb(string $product_url, int $urlId, int $site_id, int $store_id, string $product_name) {
        try {
            echo 'Fetching a product..<br>';
            $product_url = urlencode($product_url);
            echo $product_url;
    
            $stmt_url = $this->conn->prepare("SELECT url_id FROM urls AS a INNER JOIN product AS b ON a.url_id = b.url_id WHERE a.product_url = :product_url");
            $stmt_url->bindValue(':product_url', $product_url, PDO::PARAM_STR);
            $get_url = $stmt_url->fetch();
    
            if($get_url){
                echo 'Product exist<br>';
            } else {
                echo 'Product doesn\'t exist. Inserting new product..<br>';
    
                $stmt = $this->conn->prepare("INSERT INTO product (url_id, site_id, store_id, product_name) 
                        VALUES (:url_id, :site_id, :store_id, :product_name)");
                $stmt->bindParam(':url_id', $urlId);
                $stmt->bindParam(':site_id', $site_id);
                $stmt->bindParam(':store_id', $store_id);
                $stmt->bindParam(':product_name', $product_name);
    
                $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    }

    private function storeNewSiteToDb(string $site_url) {
        $site_url = urlencode($site_url);
        try {
            print_r('Fetching a site..<br>');
            $stmt_site = $this->conn->prepare("SELECT site_id FROM site WHERE site_url = :site_url");
            $stmt_site->bindParam(':site_url', $site_url);
            $stmt_site->execute();
            $get_site = $stmt_site->fetch();
            if($get_site){
                print_r('Site exist<br>');
                return intval($get_site['site_id']);
            } else {
                print_r('Site doesn\'t exist. Inserting new site..<br>');

                $stmt_insert_site = $this->conn->prepare("INSERT INTO site (site_url) 
                VALUES (:site_url)");
                $stmt_insert_site->bindParam(':site_url', $site_url);
                $stmt_insert_site->execute();

                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    }

    private function storeNewStoreToDb(string $store_name, string $store_url, int $site_id) {
        $store_url = urlencode($store_url);
        try {
            print_r('Fetching the store details..<br>');
            $stmt_store = $this->conn->prepare("SELECT store_id FROM store WHERE store_name LIKE :store_name");
            $stmt_store->bindParam(':store_name', $store_name);
            $stmt_store->execute();
            $get_site = $stmt_store->fetch();
            var_dump($get_site);
            if($get_site){
                print_r('Store exist<br>');
                return intval($get_site['store_id']);
            } else {
                print_r('Store doesn\'t exist. Inserting new store..<br>');

                $stmt_insert_store = $this->conn->prepare("INSERT INTO store (site_id, store_name, store_url) 
                VALUES (:site_id, :store_name, :store_url)");
                $stmt_insert_store->bindParam(':site_id', $site_id);
                $stmt_insert_store->bindParam(':store_name', $store_name);
                $stmt_insert_store->bindParam(':store_url', $store_url);
                $stmt_insert_store->execute();

                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    }
}