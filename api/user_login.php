<?php
// Configuration for database connection
include "database_config.php";

// Function to validate email format
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to generate a random authentication token (JWT can also be used)
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

// Function to handle user login
function loginUser($pdo, $email, $password)
{
    // Check if the email exists
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['status' => 'error', 'message' => 'Invalid email or password'];
    }

    // Verify the password
    if (!password_verify($password, $user['password'])) {
        return ['status' => 'error', 'message' => 'Invalid email or password'];
    }

    // Generate authentication token
    $token = generateToken();

    // Store the token in the database (optional, depending on your token strategy)
    $stmt = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
    $stmt->execute([$token, $user['id']]);

    return ['status' => 'success', 'message' => 'Login successful', 'token' => $token];
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

    // Attempt to log in the user
    $result = loginUser($pdo, $input['email'], $input['password']);

    // Return appropriate response based on login outcome
    if ($result['status'] === 'success') {
        http_response_code(200); // OK
        echo json_encode(['message' => $result['message'], 'token' => $result['token']]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => $result['message']]);
    }
} else {
    // If not a POST request, return a 405 Method Not Allowed error
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
