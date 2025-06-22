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

// Handle POST request for adding an expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the Authorization token from the headers
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

    if (!isset($input['category_name']) || !isset($input['amount_spent']) || !isset($input['date'])) {
        http_response_code(400);
        echo json_encode(['message' => 'category_name, amount_spent, and date are required']);
        exit();
    }

    $category_name = $input['category_name'];
    $amount_spent = $input['amount_spent'];
    $expense_date = $input['date'];

    // Validate that amount_spent is a positive number
    if (!is_numeric($amount_spent) || $amount_spent <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid amount_spent']);
        exit();
    }

    try {
        // Get the category and active budget for the user
        $stmt = $pdo->prepare("
            SELECT c.category_id, b.budget_id
            FROM categories c
            JOIN budgets b ON b.id = c.id
            WHERE c.id = ? AND c.category_name = ? AND ? BETWEEN b.start_date AND b.end_date AND c.month_year = DATE_FORMAT(?, '%Y-%m-01')
        ");
        $stmt->execute([$user['id'], $category_name, $expense_date, $expense_date]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$category) {
            http_response_code(400);
            echo json_encode(['message' => 'Category not found or no active budget for this month, please first set budget and categories for that month!']);
            exit();
        }

        // Insert the expense with the provided expense_date
        $stmt = $pdo->prepare("
            INSERT INTO expenses (id, category_id, budget_id, expense_amount, expense_date, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user['id'], $category['category_id'], $category['budget_id'], $amount_spent, $expense_date]);

        http_response_code(200);
        echo json_encode(['message' => 'Expense added successfully']);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Server error: Unable to add expense']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
?>