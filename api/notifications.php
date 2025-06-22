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

// Handle POST request for generating notifications based on spending
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
        // Get active budget for the user
        $stmt = $pdo->prepare("
            SELECT budget_id, total_budget 
            FROM budgets 
            WHERE id = ? AND NOW() BETWEEN start_date AND end_date
        ");
        $stmt->execute([$user['id']]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            http_response_code(400);
            echo json_encode(['message' => 'No active budget found for the user']);
            exit();
        }

        // Get the allocation and expenses for each category, filtering expenses by the current month
        $stmt = $pdo->prepare("
            SELECT 
                c.category_id,
                c.category_name, 
                ba.allocated_percentage, 
                ba.allocated_amount,
                IFNULL(SUM(e.expense_amount), 0) AS total_spent
            FROM categories c
            JOIN budget_allocations ba ON c.category_id = ba.category_id
            LEFT JOIN expenses e ON c.category_id = e.category_id 
                AND e.budget_id = ba.budget_id 
                AND MONTH(e.expense_date) = MONTH(NOW()) 
                AND YEAR(e.expense_date) = YEAR(NOW())
            WHERE c.id = ? AND ba.budget_id = ?
            GROUP BY c.category_id, c.category_name, ba.allocated_percentage, ba.allocated_amount
            ORDER BY c.category_name
        ");
        $stmt->execute([$user['id'], $budget['budget_id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Notification thresholds
        $threshold_approaching = 80; // Approaching limit at 80%
        $threshold_exceeding = 100;  // Exceeding limit

        // Prepare the response data for notifications
        $notifications = [];
        foreach ($categories as $category) {
            $allocated_amount = $category['allocated_amount'];
            $spent_percentage = ($category['total_spent'] / $allocated_amount) * 100;

            // Check if the user is exceeding, approaching, or on track with their budget
            if ($spent_percentage >= $threshold_exceeding) {
                $notifications[] = [
                    'category_name' => $category['category_name'],
                    'status' => 'exceeded',
                    'message' => 'You have exceeded your budget for ' . $category['category_name'],
                    'spent_percentage' => round($spent_percentage)
                ];
            } elseif ($spent_percentage >= $threshold_approaching) {
                $notifications[] = [
                    'category_name' => $category['category_name'],
                    'status' => 'approaching',
                    'message' => 'You are nearing the budget limit for ' . $category['category_name'],
                    'spent_percentage' => round($spent_percentage)
                ];
            } elseif ($spent_percentage < $threshold_approaching) {
                $notifications[] = [
                    'category_name' => $category['category_name'],
                    'status' => 'on_track',
                    'message' => 'Great job! You are on track with your budget for ' . $category['category_name'],
                    'spent_percentage' => round($spent_percentage)
                ];
            }
        }

        // Send the response
        http_response_code(200);
        echo json_encode([
            'total_budget' => $budget['total_budget'],
            'notifications' => $notifications
        ]);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Server error: Unable to retrieve data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
?>