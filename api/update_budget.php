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

// Handle POST request to update total budget and category allocations for a specific month and year
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
    $total_budget = $input['total_budget'];
    $categories = $input['categories'];
    $date = $input['date'];

    $month_year = date('Y-m-01', strtotime("01-$date")); // Set day to '1'

    try {
        $pdo->beginTransaction();

        // Check if a budget already exists for the given month and year
        $stmt = $pdo->prepare("SELECT budget_id FROM budgets WHERE id = ? AND DATE_FORMAT(start_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')");
        $stmt->execute([$user['id'], $month_year]);
        $existingBudget = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingBudget) {
            // Update the existing budget's total budget value
            $budget_id = $existingBudget['budget_id'];
            $stmt = $pdo->prepare("UPDATE budgets SET total_budget = ?, created_at = NOW() WHERE budget_id = ?");
            $stmt->execute([$total_budget, $budget_id]);
        } else {
            // Insert new budget if it doesn't exist for the specified month
            $stmt = $pdo->prepare("INSERT INTO budgets (id, total_budget, start_date, end_date, created_at) VALUES (?, ?, ?, LAST_DAY(?), NOW())");
            $stmt->execute([$user['id'], $total_budget, $month_year, $month_year]);
            $budget_id = $pdo->lastInsertId();
        }

        // Update or insert categories and allocations
        foreach ($categories as $category_name => $details) {
            // Check if the category already exists
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE id = ? AND category_name = ? AND month_year = ?");
            $stmt->execute([$user['id'], $category_name, $month_year]);
            $existingCategory = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingCategory) {
                // Insert a new category if it doesn't exist
                $stmt = $pdo->prepare("INSERT INTO categories (id, category_name, month_year, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user['id'], $category_name, $month_year]);
                $category_id = $pdo->lastInsertId();
            } else {
                $category_id = $existingCategory['category_id'];
            }

            // Check if budget allocation exists for the category in the specified month
            $stmt = $pdo->prepare("SELECT allocation_id FROM budget_allocations WHERE budget_id = ? AND category_id = ?");
            $stmt->execute([$budget_id, $category_id]);
            $existingAllocation = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingAllocation) {
                // Update existing allocation if it exists
                $stmt = $pdo->prepare("UPDATE budget_allocations SET allocated_percentage = ?, allocated_amount = ?, created_at = NOW() WHERE allocation_id = ?");
                $stmt->execute([$details['allocated_percentage'], $details['allocated_value'], $existingAllocation['allocation_id']]);
            } else {
                // Insert new allocation if it doesn't exist
                $stmt = $pdo->prepare("INSERT INTO budget_allocations (budget_id, category_id, allocated_percentage, allocated_amount, month_year, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$budget_id, $category_id, $details['allocated_percentage'], $details['allocated_value'], $month_year]);
            }
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode(['valid' => true, 'message' => 'Budget and allocations updated successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['valid' => false, 'message' => 'Failed to update budget or allocations', 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Method Not Allowed']);
}
?>