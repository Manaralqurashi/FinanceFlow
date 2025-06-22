<?php
// Configuration for database connection
include "database_config.php";

// Function to validate email format
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to handle user registration
function registerUser($pdo, $email, $password)
{
    // Check if the email is already registered
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        return ['status' => 'error', 'message' => 'Email is already registered'];
    }

    // Hash the password for secure storage
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    if ($stmt->execute([$email, $hashedPassword])) {
        return ['status' => 'success', 'message' => 'User registered successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to register user'];
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input data
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input data
    if (!isset($input['email']) || !validateEmail($input['email'])) {
        http_response_code(400); // Bad request
        echo json_encode(['message' => 'Invalid email']);
        exit();
    }

    if (!isset($input['password']) || strlen($input['password']) < 6) {
        http_response_code(400); // Bad request
        echo json_encode(['message' => 'Password must be at least 6 characters']);
        exit();
    }

    // Register the user and get the result
    $result = registerUser($pdo, $input['email'], $input['password']);

    // Return appropriate response based on registration outcome
    if ($result['status'] === 'success') {
        http_response_code(201); // Created
        echo json_encode(['message' => $result['message']]);
    } else {
        http_response_code(400); // Bad request
        echo json_encode(['message' => $result['message']]);
    }
} else {
    // If not a POST request, return a 405 Method Not Allowed error
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
