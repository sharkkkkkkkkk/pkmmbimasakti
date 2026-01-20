<?php
class AIEngine {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function generateSingleMenu($budgetPerPax, $pax, $preferences, $inventoryList, $nutritionTargets) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->apiKey;

        // 1. Format Inventory
        $inventoryText = "";
        if (!empty($inventoryList)) {
            foreach ($inventoryList as $item) {
                $qty = $item['qty'] ?? $item['stock_qty'] ?? 0;
                $unit = $item['unit'] ?? 'pcs';
                $inventoryText .= "- {$item['name']} (Ada: {$qty} {$unit})\n";
            }
        } else {
            $inventoryText = "Tidak ada data stok (Kosong).";
        }

        $totalBudget = $budgetPerPax * $pax;

        // 2. PROMPT 1 MENU
        $promptText = "
            Role: Chef & Ahli Gizi.
            Tugas: Buat 1 (SATU) REKOMENDASI MENU MAKAN SIANG untuk $pax orang.
            Budget Total: Rp $totalBudget.
            Stok Gudang: \n$inventoryText
            Target Nutrisi: {$nutritionTargets['calories']} kkal, {$nutritionTargets['protein']}g protein.
            Catatan User: $preferences

            Instruksi:
            1. Prioritaskan bahan di gudang untuk hemat biaya.
            2. Berikan estimasi harga pasar (market_price_estimate) IDR per unit untuk setiap bahan.
            3. OUTPUT WAJIB JSON OBJECT.

            OUTPUT JSON:
            {
                \"menu_name\": \"Nama Menu\",
                \"description\": \"Penjelasan singkat...\",
                \"nutrition_estimate\": { \"calories\": 0, \"protein\": 0 },
                \"ingredients\": [
                    { \"name\": \"Bahan A\", \"qty\": 1, \"unit\": \"kg\", \"market_price_estimate\": 15000 }
                ],
                \"cooking_steps\": [\"Step 1\", \"Step 2\"]
            }
        ";

        $data = [
            "contents" => [ ["parts" => [["text" => $promptText]]] ],
            "safetySettings" => [
                ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE"],
                ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE"],
                ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE"],
                ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE"]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 8192 // Limit Token Tinggi
            ]
        ];

        // 3. CURL SETUP
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Timeout 2 Menit
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) throw new Exception("Koneksi Gemini Error: " . $error);

        $result = json_decode($response, true);

        // 4. PARSING
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'];
            $cleanJson = $this->extractJsonObject($rawText);
            
            $parsed = json_decode($cleanJson, true);
            if (!$parsed) throw new Exception("Format JSON AI Rusak. Raw: " . substr($cleanJson, 0, 100));
            return $parsed;
        }
        
        // Debug Error
        $msg = isset($result['error']) ? $result['error']['message'] : "Unknown Error";
        throw new Exception("AI Gagal: " . $msg);
    }

    private function extractJsonObject($text) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false) {
            return substr($text, $start, $end - $start + 1);
        }
        return $text;
    }
}