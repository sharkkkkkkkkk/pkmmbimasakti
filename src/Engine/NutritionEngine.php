<?php
class NutritionEngine {
    public function calculate($ingredientsData) {
        $total = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0];
        
        foreach ($ingredientsData as $item) {
            // Asumsi input qty dikalikan data nutrisi db
            $qty = $item['qty_needed']; 
            $total['calories'] += $item['ref_data']['calories'] * $qty;
            $total['protein']  += $item['ref_data']['protein'] * $qty;
            $total['carbs']    += $item['ref_data']['carbs'] * $qty;
            $total['fats']     += $item['ref_data']['fats'] * $qty;
        }
        return $total;
    }
}