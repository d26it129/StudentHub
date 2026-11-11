<?php
declare(strict_types=1);

require_once __DIR__ . '/form_response.php';

const CONTACT_BACK = '../pages/contact.html';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    renderFormPage('Invalid Request', 'Invalid Request', 'Please submit the contact form using POST.', CONTACT_BACK, 'Back to Contact');
    exit;
}

$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim(strip_tags((string) ($_POST['subject'] ?? '')));
$message = trim(strip_tags((string) ($_POST['message'] ?? '')));

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

if (strlen($subject) < 3 || strlen($subject) > 120) {
    $errors[] = 'Subject must be between 3 and 120 characters.';
}

if (strlen($message) < 10 || strlen($message) > 1000) {
    $errors[] = 'Message must be between 10 and 1000 characters.';
}

if ($errors) {
    http_response_code(422);
    renderFormPage('Message Not Sent', 'Message Not Sent', 'Please correct the following errors and try again.', CONTACT_BACK, 'Back to Contact', $errors);
    exit;
}

$storagePath = __DIR__ . '/../data/contact_messages.csv';
$file = fopen($storagePath, 'c+');
if ($file === false || !flock($file, LOCK_EX)) {
    if ($file !== false) {
        fclose($file);
    }
    http_response_code(500);
    renderFormPage('Storage Error', 'Message Unavailable', 'Your message could not be saved. Please try again later.', CONTACT_BACK, 'Back to Contact');
    exit;
}

if (filesize($storagePath) === 0) {
    fputcsv($file, ['Received At', 'Name', 'Email', 'Subject', 'Message']);
}

fseek($file, 0, SEEK_END);
$saved = fputcsv($file, [
    date('Y-m-d H:i:s'),
    csvSafe($name),
    csvSafe($email),
    csvSafe($subject),
    csvSafe($message),
]);
fflush($file);
flock($file, LOCK_UN);
fclose($file);

if ($saved === false) {
    http_response_code(500);
    renderFormPage('Storage Error', 'Message Unavailable', 'Your message could not be saved. Please try again later.', CONTACT_BACK, 'Back to Contact');
    exit;
}

renderFormPage('Message Sent', 'Message Sent', 'Thank you for contacting StudentHub. We will reply within two working days.', CONTACT_BACK, 'Back to Contact');
