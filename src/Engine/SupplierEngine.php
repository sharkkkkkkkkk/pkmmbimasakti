<?php
class SupplierEngine {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function checkAvailability($recipeIngredients) {
        $report = [];
        $allAvailable = true;

        foreach ($recipeIngredients as $ing) {
            // Query DB untuk cek stok real-time
            $stmt = $this->conn->prepare("SELECT name, stock_qty, supplier_id FROM ingredients WHERE id = ?");
            $stmt->execute([$ing['id']]);
            $dbItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dbItem['stock_qty'] < $ing['qty']) {
                $allAvailable = false;
                $report[] = "STOCK ALERT: {$dbItem['name']} kurang (Butuh: {$ing['qty']}, Ada: {$dbItem['stock_qty']})";
            }
        }
        return ['status' => $allAvailable, 'issues' => $report];
    }
}