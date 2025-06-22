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

// Handle POST request for retrieving categories allocated by user for a specific month and year
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
    $date = isset($input['date']) ? $input['date'] : date('Y-m-d'); // Default to current date if not provided
    $month_year = substr($date, 0, 7); // Extract 'YYYY-MM' from 'YYYY-MM-DD'

    try {
        // Fetch categories allocated for the specified month and year for the specific user
        $stmt = $pdo->prepare("
            SELECT c.category_id, c.category_name, ba.allocated_amount 
            FROM categories c
            JOIN budget_allocations ba ON c.category_id = ba.category_id 
            WHERE c.user_id = ? AND ba.month_year = ?
        ");
        $stmt->execute([$user['id'], $date]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $formattedCategories = [];
        foreach ($categories as $category) {
            $formattedCategories[] = [
                'category_id' => $category['category_id'],
                'category_name' => $category['category_name'],
                'allocated_amount' => round($category['allocated_amount'])
            ];
        }

        // Send the response
        http_response_code(200);
        echo json_encode($formattedCategories);

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