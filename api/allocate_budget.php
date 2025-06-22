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

// Handle POST requests for allocating categories
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

    // Read the input data from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['categories'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Categories are required']);
        exit();
    }

    $categories = $input['categories'];

    // Validate categories and percentages
    $total_percentage = 0;
    foreach ($categories as $category) {
        if (
            !isset($category['name']) || !isset($category['percentage']) ||
            !is_numeric($category['percentage']) || $category['percentage'] <= 0
        ) {
            http_response_code(400);
            echo json_encode(['message' => 'Each category must have a valid name and percentage']);
            exit();
        }
        $total_percentage += $category['percentage'];
    }

    if ($total_percentage !== 100) {
        http_response_code(400);
        echo json_encode(['message' => 'Total allocated percentage must be 100']);
        exit();
    }

    try {
        // Retrieve the current active budget for the user
        $stmt = $pdo->prepare("SELECT budget_id, total_budget FROM budgets WHERE id = ? AND NOW() BETWEEN start_date AND end_date");
        $stmt->execute([$user['id']]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            http_response_code(400);
            echo json_encode(['message' => 'No active budget found for the user']);
            exit();
        }

        $budget_id = $budget['budget_id'];
        $total_budget = $budget['total_budget'];

        // Process each category
        foreach ($categories as $category) {
            // Get the category ID from the categories table
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ? AND id = ?");
            $stmt->execute([$category['name'], $user['id']]);
            $category_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category_data) {
                http_response_code(400);
                echo json_encode(['message' => 'Category not found: ' . $category['name']]);
                exit();
            }

            $category_id = $category_data['category_id'];
            $allocated_percentage = $category['percentage'];
            $allocated_amount = ($allocated_percentage / 100) * $total_budget;

            // Check if allocation already exists
            $stmt = $pdo->prepare("SELECT allocation_id FROM budget_allocations WHERE budget_id = ? AND category_id = ?");
            $stmt->execute([$budget_id, $category_id]);
            $allocation = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($allocation) {
                // Update existing allocation
                $stmt = $pdo->prepare("UPDATE budget_allocations SET allocated_percentage = ?, allocated_amount = ? 
                                       WHERE allocation_id = ?");
                $stmt->execute([$allocated_percentage, $allocated_amount, $allocation['allocation_id']]);
            } else {
                // Insert new allocation
                $stmt = $pdo->prepare("INSERT INTO budget_allocations (budget_id, category_id, allocated_percentage, allocated_amount) 
                                       VALUES (?, ?, ?, ?)");
                $stmt->execute([$budget_id, $category_id, $allocated_percentage, $allocated_amount]);
            }
        }

        http_response_code(200);
        echo json_encode(['message' => 'Budget allocations updated successfully']);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Failed to allocate budget']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
