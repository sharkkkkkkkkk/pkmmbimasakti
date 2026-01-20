<?php
require_once '../src/Engine/AIEngine.php';

class MenuGeneratorService {
    private $db;
    private $aiEngine;

    public function __construct($db, $geminiKey) {
        $this->db = $db;
        $this->aiEngine = new AIEngine($geminiKey);
    }

    public function generateRecommendation($input) {
        // Ambil Data Stok DB
        $stmt = $this->db->query("SELECT name, stock_qty as qty, unit FROM ingredients");
        $dbStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gabung Stok Manual
        $combinedStock = array_merge($dbStock, $input['manual_stock'] ?? []);

        // Panggil AI
        $menu = $this->aiEngine->generateSingleMenu(
            $input['budget'], 
            $input['pax'], 
            $input['preferences'], 
            $combinedStock,
            $input['nutrition_target']
        );

        if (!$menu) throw new Exception("AI mengembalikan data kosong.");

        // Hitung Biaya Real
        $totalCost = 0;
        $processedIng = [];

        foreach ($menu['ingredients'] as $ing) {
            $price = $ing['market_price_estimate'];
            $status = "BELI (Pasar)";
            
            // Cek Ketersediaan Stok (Case Insensitive)
            foreach ($combinedStock as $stock) {
                if (stripos($stock['name'], $ing['name']) !== false) {
                    if ($stock['qty'] >= $ing['qty']) {
                        $status = "GUDANG ({$stock['qty']} {$stock['unit']})";
                    } else {
                        $status = "KURANG (Ada {$stock['qty']})";
                    }
                    break;
                }
            }

            $lineCost = $price * $ing['qty'];
            $totalCost += $lineCost;

            $processedIng[] = [
                'name' => $ing['name'],
                'qty' => $ing['qty'],
                'unit' => $ing['unit'],
                'price' => $price,
                'total' => $lineCost,
                'status' => $status
            ];
        }

        return [
            'menu_name' => $menu['menu_name'],
            'description' => $menu['description'],
            'nutrition' => $menu['nutrition_estimate'],
            'total_cost' => $totalCost,
            'cost_per_pax' => ceil($totalCost / $input['pax']),
            'ingredients' => $processedIng,
            'steps' => $menu['cooking_steps']
        ];
    }
}