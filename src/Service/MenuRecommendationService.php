<?php
require_once '../src/Engine/AIEngine.php';

class MenuGeneratorService {
    private $db;
    private $aiEngine;

    public function __construct($db, $geminiKey) {
        $this->db = $db;
        $this->aiEngine = new AIEngine($geminiKey);
    }

    public function generateRecommendation($budget, $pax, $note) {
        // 1. Ambil Data Stock Gudang (Untuk dikirim ke AI)
        $stmt = $this->db->query("SELECT * FROM ingredients");
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buat map biar gampang search by ID nanti
        $inventoryMap = [];
        foreach ($inventory as $inv) {
            $inventoryMap[$inv['id']] = $inv;
        }

        // 2. Minta AI Generate Menu
        $aiResult = $this->aiEngine->generateMenuFromBudget($budget, $pax, $note, $inventory);

        if (!$aiResult) {
            throw new Exception("AI gagal memberikan rekomendasi.");
        }

        // 3. Proses Hasil AI (Cek Stock & Hitung Biaya Real)
        $processedIngredients = [];
        $totalCost = 0;
        $stockNotes = [];

        foreach ($aiResult['ingredients'] as $ing) {
            $matchedDb = null;
            $status = "BELI BARU"; // Default
            $cost = 0; // Kalau beli baru di pasar, harga asumsi AI (atau kita set 0 dulu kalau tidak tahu)

            // Jika AI memberikan ID yang valid dari inventory kita
            if (!empty($ing['id']) && isset($inventoryMap[$ing['id']])) {
                $matchedDb = $inventoryMap[$ing['id']];
                
                // Hitung Cost Real berdasarkan data DB
                $cost = $matchedDb['cost_per_unit'] * $ing['qty'];
                
                // Cek Ketersediaan
                if ($matchedDb['stock_qty'] >= $ing['qty']) {
                    $status = "TERSEDIA DI GUDANG";
                    $stockNotes[] = "Gunakan stok {$matchedDb['name']} ({$ing['qty']} {$ing['unit']}) dari gudang.";
                } else {
                    $diff = $ing['qty'] - $matchedDb['stock_qty'];
                    $status = "STOK KURANG (Kurang $diff)";
                }
            } else {
                // Barang tidak ada di database
                $stockNotes[] = "Beli {$ing['name']} di pasar (Tidak ada di database).";
            }

            $totalCost += $cost;

            $processedIngredients[] = [
                'name' => $ing['name'],
                'qty_needed' => $ing['qty'],
                'unit' => $ing['unit'],
                'status' => $status,
                'estimated_cost' => $cost
            ];
        }

        // 4. Finalisasi Output
        return [
            'menu_name' => $aiResult['menu_name'],
            'description' => $aiResult['description'],
            'pax' => $pax,
            'budget_per_pax' => $budget,
            'total_estimated_cost' => $totalCost,
            'is_within_budget' => $totalCost <= ($budget * $pax),
            'ingredients' => $processedIngredients,
            'stock_usage_summary' => $stockNotes, // Ini yang diminta user
            'cooking_steps' => $aiResult['cooking_steps']
        ];
    }
}