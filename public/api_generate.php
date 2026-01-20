<?php
// SETTING PENTING AGAR TIDAK TIMEOUT
set_time_limit(300); // 5 Menit
ini_set('display_errors', 0); // Matikan print error ke layar (biar JSON bersih)
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

try {
    // 1. Validasi File
    if (!file_exists('../config/database.php')) throw new Exception("Config database tidak ditemukan.");
    if (!file_exists('../src/Service/MenuGeneratorService.php')) throw new Exception("Service file tidak ditemukan.");

    require_once '../config/database.php';
    require_once '../src/Service/MenuGeneratorService.php';

    // 2. API Key & DB
    $GEMINI_API_KEY = ""; // <--- ISI KEY
    
    $database = new Database();
    $db = $database->getConnection();
    if(!$db) throw new Exception("Koneksi Database Gagal.");

    $service = new MenuGeneratorService($db, $GEMINI_API_KEY);

    // 3. Input
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (!$input) throw new Exception("Input kosong.");

    // 4. Proses
    $params = [
        'pax' => (int) ($input['pax'] ?? 50),
        'budget' => (int) ($input['budget'] ?? 15000),
        'preferences' => $input['preferences'] ?? "-",
        'nutrition_target' => [
            'calories' => $input['nutrition']['calories'] ?? 650,
            'protein' => $input['nutrition']['protein'] ?? 25
        ],
        'manual_stock' => $input['manual_stock'] ?? []
    ];

    $result = $service->generateRecommendation($params);
    
    echo json_encode(["status" => "success", "result" => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}