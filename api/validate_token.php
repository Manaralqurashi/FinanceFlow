<?php
// Configuration for database connection
include "database_config.php";

// Function to check if the token is valid
function isValidToken($pdo, $token)
{
    // Query the database to see if the token exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE auth_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the Authorization token from the headers
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(400); // Bad request
        echo json_encode(['valid' => false, 'message' => 'Authorization header missing']);
        exit();
    }

    // Extract the token
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    // Check if the token is valid
    if (isValidToken($pdo, $token)) {
        http_response_code(200); // OK
        echo json_encode(['valid' => true, 'message' => 'Token is valid']);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired token']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Method Not Allowed']);
}
