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

// Handle POST requests for retrieving the user's budget data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the Authorization token from the headers
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'message' => 'Authorization header missing']);
        exit();
    }

    // Extract the token
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    // Check if the token is valid
    $user = isValidToken($pdo, $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired token']);
        exit();
    }

    // Get the date from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $date = $input['date'];
    $month_year = date('Y-m-01', strtotime("01-$date")); // Convert to YYYY-MM-01 format

    try {
        // Get budget data for the specific month
        $stmt = $pdo->prepare("
            SELECT b.budget_id, b.total_budget, 
                   c.category_name, c.category_id, ba.allocation_id as allocation_id, ba.allocated_percentage, ba.allocated_amount
            FROM budgets b
            LEFT JOIN budget_allocations ba ON b.budget_id = ba.budget_id
            LEFT JOIN categories c ON ba.category_id = c.category_id
            WHERE b.id = ? AND DATE_FORMAT(b.start_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
        ");
        $stmt->execute([$user['id'], $month_year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results && !empty($results[0]['budget_id'])) {
            $budgetData = [
                'total_budget' => $results[0]['total_budget'],
                'categories' => []
            ];

            foreach ($results as $row) {
                if ($row['category_name']) {
                    $budgetData['categories'][$row['category_name']] = [
                        'allocated_percentage' => floatval($row['allocated_percentage']),
                        'allocated_value' => floatval($row['allocated_amount']),
                        'category_id' => $row['category_id'],
                        'allocation_id' => $row['allocation_id']
                    ];
                }
            }

            http_response_code(200);
            echo json_encode(['valid' => true, 'data' => $budgetData]);
        } else {
            http_response_code(404);
            echo json_encode(['valid' => false, 'message' => 'No budget data found for the selected date']);
        }
    } catch (PDOException $e) {
        error_log('PDO Exception: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['valid' => false, 'message' => 'Failed to retrieve budget data' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Method Not Allowed']);
}
?>