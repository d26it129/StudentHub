<?php
declare(strict_types=1);

require_once __DIR__ . '/form_response.php';

const FEEDBACK_BACK = '../pages/feedback.html';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    renderFormPage('Invalid Request', 'Invalid Request', 'Please submit the feedback form using POST.', FEEDBACK_BACK, 'Back to Feedback');
    exit;
}

$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
$email = trim((string) ($_POST['email'] ?? ''));
$rating = trim((string) ($_POST['rating'] ?? ''));
$feedback = trim(strip_tags((string) ($_POST['feedback'] ?? '')));

$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
} elseif (!preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/", $name) || strlen($name) > 100) {
    $errors[] = 'Name can contain letters, spaces, apostrophes, and hyphens only (max 100 characters).';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    $errors[] = 'Please enter a valid email address.';
}

if (!in_array($rating, ['excellent', 'good', 'average', 'poor'], true)) {
    $errors[] = 'Please select a rating.';
}

if (strlen($feedback) < 10 || strlen($feedback) > 1500) {
    $errors[] = 'Feedback must be between 10 and 1500 characters.';
}

if ($errors) {
    http_response_code(422);
    renderFormPage('Feedback Not Sent', 'Feedback Not Sent', 'Please correct the following errors and try again.', FEEDBACK_BACK, 'Back to Feedback', $errors);
    exit;
}

$storagePath = __DIR__ . '/../data/feedback.json';
$file = fopen($storagePath, 'c+');
if ($file === false || !flock($file, LOCK_EX)) {
    if ($file !== false) {
        fclose($file);
    }
    http_response_code(500);
    renderFormPage('Storage Error', 'Feedback Unavailable', 'Your feedback could not be saved. Please try again later.', FEEDBACK_BACK, 'Back to Feedback');
    exit;
}

$existing = stream_get_contents($file);
$entries = ($existing !== false && trim($existing) !== '') ? json_decode($existing, true) : [];
if (!is_array($entries)) {
    $entries = [];
}

$entries[] = [
    'submitted_at' => date('c'),
    'name' => $name,
    'email' => $email,
    'rating' => $rating,
    'feedback' => $feedback,
];

$encoded = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$saved = $encoded !== false && ftruncate($file, 0) && rewind($file) && fwrite($file, $encoded) !== false;
fflush($file);
flock($file, LOCK_UN);
fclose($file);

if (!$saved) {
    http_response_code(500);
    renderFormPage('Storage Error', 'Feedback Unavailable', 'Your feedback could not be saved. Please try again later.', FEEDBACK_BACK, 'Back to Feedback');
    exit;
}

renderFormPage('Feedback Received', 'Thank You!', 'Your feedback has been recorded and helps us improve StudentHub.', FEEDBACK_BACK, 'Back to Feedback');
