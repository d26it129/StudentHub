<?php
declare(strict_types=1);

/**
 * Shared response page for simple public forms (contact, feedback, forgot password).
 * Uses the same Bootstrap + css/style.css look as the other StudentHub result pages.
 */
function renderFormPage(
    string $title,
    string $heading,
    string $message,
    string $backHref,
    string $backLabel,
    array $errors = [],
    string $alertClass = 'danger'
): void {
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>StudentHub | ' . $e($title) . '</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
        . '<link rel="stylesheet" href="../css/style.css">'
        . '<script src="../js/script.js" defer></script></head><body>'
        . '<main class="container py-5"><div class="card p-4 p-md-5 mx-auto" style="max-width: 680px;">'
        . '<div class="card-body p-0"><h1 class="h3 fw-bold mb-3">' . $e($heading) . '</h1>'
        . '<p class="text-body-secondary lead mb-4">' . $e($message) . '</p>';

    if ($errors) {
        echo '<div class="alert alert-' . $e($alertClass) . ' mb-4"><ul class="mb-0 ps-3">';
        foreach ($errors as $error) {
            echo '<li>' . $e($error) . '</li>';
        }
        echo '</ul></div>';
    }

    echo '<a class="btn btn-primary" href="' . $e($backHref) . '">&larr; ' . $e($backLabel) . '</a>'
        . '</div></div></main></body></html>';
}

/** Neutralise spreadsheet formula injection before writing a value to CSV. */
function csvSafe(string $value): string
{
    return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
}
