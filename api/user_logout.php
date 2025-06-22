<?php
// Configuration for database connection
include "database_config.php";

// Function to authenticate the user using the token
function authenticateUser($pdo, $token)
{
    // Check if the provided token exists in the database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE auth_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to handle user logout
function logoutUser($pdo, $userId)
{
    // Set the auth_token to NULL to log the user out
    $stmt = $pdo->prepare("UPDATE users SET auth_token = NULL WHERE id = ?");
    return $stmt->execute([$userId]);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the Authorization token from the headers
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(400); // Bad request
        echo json_encode(['message' => 'Authorization header missing']);
        exit();
    }

    // Extract the token from the Authorization header
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    // Validate the token
    if (!$token) {
        http_response_code(400); // Bad request
        echo json_encode(['message' => 'Invalid token']);
        exit();
    }

    // Authenticate the user
    $user = authenticateUser($pdo, $token);

    if (!$user) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Invalid or expired token']);
        exit();
    }

    // Perform logout
    if (logoutUser($pdo, $user['id'])) {
        http_response_code(200); // OK
        echo json_encode(['message' => 'Logout successful']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to log out, please try again']);
    }
} else {
    // If not a POST request, return a 405 Method Not Allowed error
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
