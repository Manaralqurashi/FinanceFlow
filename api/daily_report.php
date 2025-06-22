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

// Handle POST request for generating daily report
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
    $year = isset($input['year']) ? (int) $input['year'] : date('Y');
    $month = isset($input['month']) ? (int) $input['month'] : date('n');

    try {
        // Get the number of days in the specified month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Debug: Let's log the values we're using
        error_log("User ID: " . $user['id'] . ", Year: " . $year . ", Month: " . $month);

        $stmt = $pdo->prepare("
            select 
                day(expense_date) as day,
                sum(expense_amount) as total_expenses
            from expenses
            where month(expense_date) = ?
            and year(expense_date) = ?
            and id = ?
            group by day
            order by day
        ");
        
        $stmt->execute([
            $month,
            $year,
            $user['id']
        ]);

        $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Let's log the query results
        error_log("Query results: " . print_r($dailyData, true));

        // Initialize array for all days
        $allDays = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayKey = str_pad($i, 2, '0', STR_PAD_LEFT);
            $allDays[$dayKey] = [
                'day' => $i,
                'total_expenses' => 0
            ];
        }

        // Fill in the actual data
        foreach ($dailyData as $data) {
            $dayKey = $data['day'];
            $allDays[$dayKey]['total_expenses'] = round($data['total_expenses'], 2);
        }

        // Send the response
        http_response_code(200);
        echo json_encode([
            'year' => $year,
            'month' => date('F', mktime(0, 0, 0, $month, 1)),
            'daily_data' => array_values($allDays)
        ]);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Server error: Unable to retrieve data' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
?>
