<?php
// Configuration for database connection
include "database_config.php";

// Function to check if the token is valid
function isValidToken($pdo, $token)
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE auth_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle POST request to get allocated percentages for categories and total budget
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the Authorization token from the headers
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Authorization header missing']);
        exit();
    }

    // Extract the token
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    // Check if the token is valid
    $user = isValidToken($pdo, $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid or expired token']);
        exit();
    }

    try {
        // Retrieve the user's active budget (if it exists)
        $stmt = $pdo->prepare("
            SELECT b.budget_id, b.total_budget
            FROM budgets b
            WHERE b.id = ? AND NOW() BETWEEN b.start_date AND b.end_date
        ");
        $stmt->execute([$user['id']]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            http_response_code(200);
            echo json_encode(['message' => 'No active budget found for the user']);
            exit();
        }

        // Retrieve categories and their allocated percentages for this budget
        $stmt = $pdo->prepare("
            SELECT c.category_name, ba.allocated_percentage
            FROM budget_allocations ba
            JOIN categories c ON ba.category_id = c.category_id
            WHERE ba.budget_id = ? AND c.id = ?
        ");
        $stmt->execute([$budget['budget_id'], $user['id']]);
        $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($allocations)) {
            http_response_code(200);
            echo json_encode(['message' => 'No budget allocations found for this user']);
            exit();
        }

        // Return the total budget and the list of categories and their percentages
        http_response_code(200);
        echo json_encode([
            'message' => 'Success',
            'total_budget' => $budget['total_budget'],
            'data' => $allocations
        ]);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Server error: Unable to fetch data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
?>