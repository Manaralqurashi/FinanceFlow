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

// Handle POST request for fetching category expenses as a percentage of the allocated budget
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
            GROUP BY c.category_id
        ");
        $stmt->execute([$user['id'], $budget['budget_id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare the response data
        $response = [];
        foreach ($categories as $category) {
            $allocated_amount = $category['allocated_amount'];
            $spent_percentage = ($category['total_spent'] / $allocated_amount) * 100;
            $remaining_percentage = 100 - $spent_percentage;

            // Round percentages to whole numbers
            $spent_percentage = round($spent_percentage);
            $remaining_percentage = round($remaining_percentage);

            // Handle the case where expenses exceed the allocated budget
            if ($spent_percentage > 100) {
                $extra_percentage = $spent_percentage - 100; // Calculate how much extra was spent
                $spent_percentage = 100;
                $response[] = [
                    'category_name' => $category['category_name'],
                    'spent_percentage' => 100,
                    'extra_spent_percentage' => round($extra_percentage),
                    'remaining_percentage' => 0
                ];
            } else {
                // Normal case when within or equal to the budget
                $response[] = [
                    'category_name' => $category['category_name'],
                    'spent_percentage' => $spent_percentage,
                    'extra_spent_percentage' => 0, // No extra spent
                    'remaining_percentage' => $remaining_percentage
                ];
            }
        }

        // Send the response
        http_response_code(200);
        echo json_encode([
            'total_budget' => $budget['total_budget'],
            'categories' => $response
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