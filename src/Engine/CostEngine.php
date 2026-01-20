<?php
class CostEngine {
    public function calculate($ingredientsData, $targetMargin = 30) {
        $totalCost = 0;
        foreach ($ingredientsData as $item) {
            $totalCost += $item['ref_data']['cost_per_unit'] * $item['qty_needed'];
        }

        // Rumus sederhana harga jual
        $sellingPrice = $totalCost + ($totalCost * ($targetMargin / 100));

        return [
            'total_cogs' => $totalCost,
            'suggested_price' => ceil($sellingPrice),
            'margin_percent' => $targetMargin
        ];
    }
}