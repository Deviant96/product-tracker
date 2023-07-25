<?php

class LogLoader
{
    private $conn;
    private $logsPerPage;
    private $rowResult;

    public function __construct($conn, $logsPerPage = 10, $rowResult = null)
    {
        $this->conn = $conn;
        $this->logsPerPage = $logsPerPage;
        $this->rowResult = $rowResult;
    }

    public function loadLogs()
    {
        try {
            // Get the total number of logs
            $logsTotalRecord = $this->getLogsTotalRecord();

            if(is_null($this->rowResult)) {
                $logs = $this->getLogsFromDatabase();
            } else {
                $logs = $this->getAjaxLogsFromDatabase();
            }

            // Render the logs
            $html = $this->renderLogs($logs);

            return [
                'html' => $html,
                'totalRecord' => $logsTotalRecord
            ];
        } catch (PDOException $e) {
            return "Connection failed: " . $e->getMessage();
        }
    }

    private function getLogsTotalRecord()
    {
        $sql = "SELECT COUNT(*) as total FROM product_log";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }

    private function getLogsFromDatabase()
    {
        $sql = "SELECT a.product_log_id, a.log_date, b.product_name, c.price, d.stock_available 
                FROM product_log AS a 
                INNER JOIN product AS b ON a.product_id = b.product_id 
                INNER JOIN price AS c ON c.product_id = b.product_id
                INNER JOIN stock AS d ON d.product_id = b.product_id
                GROUP BY a.product_log_id, b.product_name, c.price, d.stock_available
                ORDER BY a.product_log_id DESC
                LIMIT 0, :logsPerPage";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':logsPerPage', $this->logsPerPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAjaxLogsFromDatabase()
    {
        $sql = "SELECT a.product_log_id, a.log_date, b.product_name, c.price, d.stock_available 
                FROM product_log AS a 
                INNER JOIN product AS b ON a.product_id = b.product_id 
                INNER JOIN price AS c ON c.product_id = b.product_id
                INNER JOIN stock AS d ON d.product_id = b.product_id
                GROUP BY a.product_log_id, b.product_name, c.price, d.stock_available
                ORDER BY a.product_log_id DESC
                LIMIT :rowResult, :logsPerPage";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':rowResult', $this->rowResult, PDO::PARAM_INT);
        $stmt->bindParam(':logsPerPage', $this->logsPerPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function renderLogs($logs)
    {
        $html = '';

        foreach ($logs as $row) {
            $id = $row['product_log_id'];
            $logDate = $row['log_date'];
            $productName = $row['product_name'];
            $price = $row['price'];
            $stock = $row['stock_available'];
            $html .= '<tr class="post" id="post_' . $id . '">';
            $html .= '<th class="col-2"><small>' . $logDate . '</small></th>';
            $html .= '<td class="col-6">' . $productName . '</td>';
            $html .= '<td class="col-2">' . $price . '</td>';
            $html .= '<td class="col-2">' . $stock . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }
}