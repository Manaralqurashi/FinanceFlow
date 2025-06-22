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

// Handle POST request for generating monthly report
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

    try {
        // Get monthly budgets and expenses for the entire year
        $stmt = $pdo->prepare("
            WITH RECURSIVE months AS (
                SELECT 1 as month_num
                UNION ALL
                SELECT month_num + 1
                FROM months
                WHERE month_num < 12
            )
            SELECT 
                LPAD(m.month_num, 2, '0') as month,
                COALESCE(b.total_budget, 0) as total_budget,
                COALESCE(e.total_expenses, 0) as total_expenses
            FROM months m
            LEFT JOIN (
                SELECT 
                    MONTH(start_date) as budget_month,
                    SUM(total_budget) as total_budget
                FROM budgets
                WHERE id = ? AND YEAR(start_date) = ?
                GROUP BY MONTH(start_date)
            ) b ON m.month_num = b.budget_month
            LEFT JOIN (
                SELECT 
                    MONTH(expense_date) as expense_month,
                    SUM(expense_amount) as total_expenses
                FROM expenses
                WHERE id = ? AND YEAR(expense_date) = ?
                GROUP BY MONTH(expense_date)
            ) e ON m.month_num = e.expense_month
            ORDER BY m.month_num
        ");
        
        $stmt->execute([$user['id'], $year, $user['id'], $year]);
        $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize array for all months
        $allMonths = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthKey = str_pad($i, 2, '0', STR_PAD_LEFT);
            $allMonths[$monthKey] = [
                'month' => date('F', mktime(0, 0, 0, $i, 1)),
                'total_budget' => 0,
                'total_expenses' => 0
            ];
        }

        // Fill in the actual data
        foreach ($monthlyData as $data) {
            $monthKey = $data['month'];
            $allMonths[$monthKey]['total_budget'] = round($data['total_budget']);
            $allMonths[$monthKey]['total_expenses'] = round($data['total_expenses']);
        }

        // Send the response
        http_response_code(200);
        echo json_encode([
            'year' => $year,
            'monthly_data' => array_values($allMonths)
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