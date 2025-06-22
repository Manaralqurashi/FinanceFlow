<?php
// Configuration for database connection
include "database_config.php";

// Function to validate the user's authToken
function isValidToken($pdo, $token)
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE auth_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle POST request to retrieve total budget, categories, and allocations for a specific date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'message' => 'Authorization header missing']);
        exit();
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    $user = isValidToken($pdo, $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired token']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $date = isset($input['date']) ? $input['date'] : date('Y-m-d'); // Default to current date if not provided
    $month_year = date('Y-m-01', strtotime($date)); // Set day to '1'

    try {
        // Fetch total budget for the specified month and year
        $stmt = $pdo->prepare("SELECT total_budget FROM budgets WHERE id = ? AND DATE_FORMAT(start_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')");
        $stmt->execute([$user['id'], $month_year]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            http_response_code(404);
            echo json_encode(['valid' => false, 'message' => 'No budget found for the specified date']);
            exit();
        }

        // Fetch categories and allocations for the specified month and year
        $stmt = $pdo->prepare("
            SELECT c.category_name, ba.allocated_percentage, ba.allocated_amount
            FROM categories c
            JOIN budget_allocations ba ON c.category_id = ba.category_id
            WHERE c.id = ? AND c.month_year = ?
        ");
        $stmt->execute([$user['id'], $month_year]);
        $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = [];
        foreach ($allocations as $allocation) {
            $categories[$allocation['category_name']] = [
                'allocated_percentage' => $allocation['allocated_percentage'],
                'allocated_value' => $allocation['allocated_amount']
            ];
        }

        // Prepare response in required format
        $response = [
            'date' => date('m-Y', strtotime($date)),
            'total_budget' => (float) $budget['total_budget'],
            'categories' => $categories
        ];

        http_response_code(200);
        echo json_encode($response);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['valid' => false, 'message' => 'Server error: Unable to retrieve data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Method Not Allowed']);
}
?>