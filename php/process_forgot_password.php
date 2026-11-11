<?php
declare(strict_types=1);

require_once __DIR__ . '/form_response.php';

const RESET_BACK = '../pages/forgot_password.html';
const RESET_TOKEN_LIFETIME_SECONDS = 3600;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    renderFormPage('Invalid Request', 'Invalid Request', 'Please submit the form using POST.', RESET_BACK, 'Back');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    renderFormPage('Reset Failed', 'Reset Request Failed', 'Please correct the following errors and try again.', RESET_BACK, 'Back', ['Please enter a valid email address.']);
    exit;
}

require_once __DIR__ . '/db_connect_mysqli.php';
require_once __DIR__ . '/audit_log.php';

$created = $conn->query(
    'CREATE TABLE IF NOT EXISTS password_resets (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX (token_hash),
        CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
if ($created === false) {
    $conn->close();
    http_response_code(500);
    renderFormPage('Server Error', 'Reset Unavailable', 'We could not process your request. Please try again later.', RESET_BACK, 'Back');
    exit;
}

$lookup = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
if ($lookup === false) {
    $conn->close();
    http_response_code(500);
    renderFormPage('Server Error', 'Reset Unavailable', 'We could not process your request. Please try again later.', RESET_BACK, 'Back');
    exit;
}
$lookup->bind_param('s', $email);
$lookup->execute();
$lookup->bind_result($userId);
$found = $lookup->fetch() === true;
$lookup->close();

if ($found) {
    // Only a SHA-256 hash of the token is stored. A mail server would send the raw token
    // inside a reset link; this project has no mail setup, so nothing is displayed or emailed.
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + RESET_TOKEN_LIFETIME_SECONDS);

    $delete = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?');
    if ($delete !== false) {
        $delete->bind_param('i', $userId);
        $delete->execute();
        $delete->close();
    }

    $insert = $conn->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
    if ($insert !== false) {
        $insert->bind_param('iss', $userId, $tokenHash, $expiresAt);
        $insert->execute();
        $insert->close();
        recordAudit($conn, (int) $userId, 'PASSWORD_RESET_REQUEST', 'Password reset requested.');
    }
}

$conn->close();

// The same message is shown whether or not the email exists, so accounts cannot be enumerated.
renderFormPage(
    'Check Your Email',
    'Check Your Email',
    'If that email is registered, a password reset link has been sent. The link stays valid for one hour.',
    '../pages/login.html',
    'Return to Login',
    [],
    'info'
);
