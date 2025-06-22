<?php
// Database configuration
include "database_config.php";

// Function to validate the user's authToken
function isValidToken($pdo, $token)
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE auth_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle POST request for retrieving expenses by day and category for a month
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Authorization header missing']);
        exit();
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    $user = isValidToken($pdo, $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid or expired token']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $month = isset($input['month']) ? (int) $input['month'] : date('n');
    $year = isset($input['year']) ? (int) $input['year'] : date('Y');
    $month_year = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT); // Format as 'YYYY-MM'

    try {
        // Fetch expenses by day and category for the specified month and year
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(e.expense_date, '%d') AS day,
                c.category_name AS category,
                e.expense_amount AS amount
            FROM expenses e
            JOIN categories c ON e.category_id = c.category_id
            WHERE e.id = ?
            AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
            ORDER BY e.expense_date
        ");
        $stmt->execute([$user['id'], $month_year]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $formattedExpenses = [];
        foreach ($expenses as $expense) {
            $formattedExpenses[] = [
                'day' => $expense['day'],
                'category' => $expense['category'],
                'amount' => $expense['amount']
            ];
        }

        // Prepare the final response structure
        $response = [
            'date' => $month_year,
            'data' => $formattedExpenses
        ];

        // Send the response
        http_response_code(200);
        echo json_encode($response);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage()); // Detailed error logging
        http_response_code(500);
        echo json_encode(['message' => 'Server error: Unable to retrieve data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
?>