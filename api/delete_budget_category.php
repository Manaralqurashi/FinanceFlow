<?php
// Database configuration
include "database_config.php";

// Get JSON input data
$data = json_decode(file_get_contents('php://input'), true);
$allocation_id = isset($data['allocation_id']) ? $data['allocation_id'] : null;

if (!$allocation_id) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'Missing allocation_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify the allocation belongs to the user
    $stmt = $pdo->prepare("
        SELECT ba.allocation_id 
        FROM budget_allocations ba
        JOIN budgets b ON ba.budget_id = b.budget_id
        WHERE ba.allocation_id = ?
    ");
    $stmt->execute([$allocation_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Budget allocation not found or unauthorized');
    }

    // Delete the budget allocation
    $stmt = $pdo->prepare("DELETE FROM budget_allocations WHERE allocation_id = ?");
    $stmt->execute([$allocation_id]);

    $pdo->commit();
    echo json_encode(['valid' => true, 'message' => 'Category allocation deleted successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => $e->getMessage()]);
}
?>
