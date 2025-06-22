<?php
include "database_config.php";

function isValidToken($pdo, $token) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE auth_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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

    try {
        // Get total budget (all time)
        $budgetStmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_budget), 0) as total_budget
            FROM budgets
            WHERE id = ?
        ");
        $budgetStmt->execute([$user['id']]);
        $totalBudget = $budgetStmt->fetch(PDO::FETCH_ASSOC)['total_budget'];

        // Get total expenses (all time)
        $expenseStmt = $pdo->prepare("
            SELECT COALESCE(SUM(expense_amount), 0) as total_expenses
            FROM expenses
            WHERE id = ?
        ");
        $expenseStmt->execute([$user['id']]);
        $totalExpenses = $expenseStmt->fetch(PDO::FETCH_ASSOC)['total_expenses'];

        // Get budget allocations by category (all time)
        $allocationStmt = $pdo->prepare("
            SELECT 
                c.category_name,
                SUM(ba.allocated_amount) as allocated_amount,
                (
                    SELECT COALESCE(SUM(e.expense_amount), 0)
                    FROM expenses e
                    JOIN categories cat ON e.category_id = cat.category_id
                    WHERE cat.category_name = c.category_name
                    AND e.id = ?
                ) as spent_amount
            FROM categories c
            JOIN budget_allocations ba ON c.category_id = ba.category_id
            WHERE c.id = ?
            GROUP BY c.category_name
        ");
        $allocationStmt->execute([$user['id'], $user['id']]);
        $categoryData = $allocationStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total expenses by month
        $monthlyExpensesStmt = $pdo->prepare("
            SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, COALESCE(SUM(expense_amount), 0) as total_expenses
            FROM expenses
            WHERE id = ?
            GROUP BY month
            ORDER BY month
        ");
        $monthlyExpensesStmt->execute([$user['id']]);
        $monthlyExpenses = $monthlyExpensesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get daily expenses for the current month
        $dailyExpensesStmt = $pdo->prepare("
            SELECT DATE_FORMAT(expense_date, '%Y-%m-%d') as day, COALESCE(SUM(expense_amount), 0) as total_expenses
            FROM expenses
            WHERE id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
            GROUP BY day
            ORDER BY day
        ");
        $dailyExpensesStmt->execute([$user['id']]);
        $dailyExpenses = $dailyExpensesStmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'total_budget' => floatval($totalBudget),
            'total_expenses' => floatval($totalExpenses),
            'categories' => $categoryData,
            'monthly_expenses' => $monthlyExpenses,
            'daily_expenses' => $dailyExpenses
        ];

        http_response_code(200);
        echo json_encode($response);

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