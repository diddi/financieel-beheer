<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../models/Transaction.php';
require_once __DIR__ . '/../../services/OCRService.php';

// Controleer API auth token
$headers = getallheaders();
$authToken = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$authToken || !validateApiToken($authToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Verkrijg gebruikers-ID van token
$userId = getUserIdFromToken($authToken);

// Verwerk Request-methode
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Ophalen van transacties
        $transactionId = isset($_GET['id']) ? $_GET['id'] : null;
        
        if ($transactionId) {
            // Specifieke transactie ophalen
            $transaction = Transaction::getById($transactionId, $userId);
            
            if (!$transaction) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaction not found']);
                exit;
            }
            
            echo json_encode($transaction);
        } else {
            // Lijst van transacties ophalen
            $filters = [];
            
            // Verwerk query parameters voor filters
            $possibleFilters = ['account_id', 'category_id', 'type', 'date_from', 'date_to'];
            foreach ($possibleFilters as $filter) {
                if (isset($_GET[$filter])) {
                    $filters[$filter] = $_GET[$filter];
                }
            }
            
            // Paginatie
            if (isset($_GET['page']) && isset($_GET['limit'])) {
                $page = (int)$_GET['page'];
                $limit = (int)$_GET['limit'];
                $offset = ($page - 1) * $limit;
                
                $filters['limit'] = $limit;
                $filters['offset'] = $offset;
            }
            
            $transactions = Transaction::getAllByUser($userId, $filters);
            echo json_encode($transactions);
        }
        break;
        
    case 'POST':
        // Nieuwe transactie toevoegen
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            // Check voor multipart form-data (voor afbeeldingsupload)
            $data = $_POST;
        }
        
        // Valideer verplichte velden
        $requiredFields = ['account_id', 'amount', 'type', 'date'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                exit;
            }
        }
        
        // Verwerk afbeelding als die is geüpload
        $receiptImage = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/receipts/';
            $fileName = uniqid() . '_' . basename($_FILES['receipt']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadFile)) {
                $receiptImage = $fileName;
                
                // OCR verwerking
                try {
                    $ocrService = new OCRService();
                    $ocrResult = $ocrService->processImage($uploadFile);
                    
                    // Voeg OCR-informatie toe aan reactie
                    $ocrData = [
                        'merchant' => $ocrResult['merchant'] ?? null,
                        'amount' => $ocrResult['amount'] ?? null,
                        'date' => $ocrResult['date'] ?? null
                    ];
                } catch (\Exception $e) {
                    // Log error maar ga door
                    error_log('OCR Error: ' . $e->getMessage());
                }
            }
        }
        
        // Bouw transactiedata
        $transactionData = [
            'user_id' => $userId,
            'account_id' => $data['account_id'],
            'category_id' => $data['category_id'] ?? null,
            'amount' => $data['amount'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'is_recurring' => isset($data['is_recurring']) && $data['is_recurring'] ? 1 : 0,
            'receipt_image' => $receiptImage
        ];
        
        try {
            // Voeg transactie toe
            $transactionId = Transaction::create($transactionData);
            
            // Maak terugkerende transactie indien nodig
            if (isset($data['is_recurring']) && $data['is_recurring']) {
                $recurringData = [
                    'user_id' => $userId,
                    'account_id' => $data['account_id'],
                    'category_id' => $data['category_id'] ?? null,
                    'amount' => $data['amount'],
                    'type' => $data['type'],
                    'description' => $data['description'] ?? null,
                    'frequency' => $data['frequency'] ?? 'monthly',
                    'start_date' => $data['date'],
                    'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
                    'next_due_date' => calculateNextDueDate($data['date'], $data['frequency'] ?? 'monthly')
                ];
                
                // Sla terugkerende transactie op
                $recurringId = (new \App\Models\RecurringTransaction())->create($recurringData);
            }
            
            // Haal de aangemaakte transactie op voor reactie
            $transaction = Transaction::getById($transactionId, $userId);
            
            // Voeg OCR-resultaten toe aan reactie indien beschikbaar
            if (isset($ocrData)) {
                $transaction['ocr_data'] = $ocrData;
            }
            
            http_response_code(201);
            echo json_encode($transaction);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Transactie bijwerken
        $transactionId = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$transactionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID is required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Controleer of transactie bestaat en eigendom is van de gebruiker
        $transaction = Transaction::getById($transactionId, $userId);
        
        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            exit;
        }
        
        // Valideer verplichte velden
        $requiredFields = ['account_id', 'amount', 'type', 'date'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                exit;
            }
        }
        
        // Bouw updatedata
        $updateData = [
            'account_id' => $data['account_id'],
            'category_id' => $data['category_id'] ?? null,
            'amount' => $data['amount'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'is_recurring' => isset($data['is_recurring']) && $data['is_recurring'] ? 1 : 0
        ];
        
        try {
            // Update de transactie
            Transaction::update($transactionId, $updateData, $userId);
            
            // Haal de bijgewerkte transactie op
            $updatedTransaction = Transaction::getById($transactionId, $userId);
            
            echo json_encode($updatedTransaction);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Transactie verwijderen
        $transactionId = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$transactionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID is required']);
            exit;
        }
        
        // Controleer of transactie bestaat en eigendom is van de gebruiker
        $transaction = Transaction::getById($transactionId, $userId);
        
        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            exit;
        }
        
        try {
            // Verwijder de transactie
            Transaction::delete($transactionId, $userId);
            
            http_response_code(204); // No Content
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Helper functies
function validateApiToken($token) {
    // Implementeer token validatie logica
    // Dit kan een controle tegen de database zijn of JWT validatie
    // Dummy implementatie voor het voorbeeld
    return true;
}

function getUserIdFromToken($token) {
    // Haal user ID uit token
    // Dummy implementatie voor het voorbeeld
    return 1;
}

function calculateNextDueDate($startDate, $frequency) {
    $date = new \DateTime($startDate);
    
    switch ($frequency) {
        case 'daily':
            $date->add(new \DateInterval('P1D'));
            break;
        case 'weekly':
            $date->add(new \DateInterval('P1W'));
            break;
        case 'monthly':
            $date->add(new \DateInterval('P1M'));
            break;
        case 'quarterly':
            $date->add(new \DateInterval('P3M'));
            break;
        case 'yearly':
            $date->add(new \DateInterval('P1Y'));
            break;
    }
    
    return $date->format('Y-m-d');
}