<?php
declare(strict_types=1);

/**
 * Secure "remember me" tokens (selector + hashed validator).
 * The cookie holds "selector:validator"; only a SHA-256 hash of the validator is stored in MySQL,
 * so a leaked database cannot be used to forge a cookie. Tokens rotate on every use.
 */

const REMEMBER_COOKIE = 'remember_me';
const REMEMBER_LIFETIME_SECONDS = 2592000; // 30 days

function rememberCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function ensureRememberTable(mysqli $conn): bool
{
    return $conn->query(
        'CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            selector CHAR(24) NOT NULL,
            validator_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE (selector),
            CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    ) !== false;
}

function issueRememberToken(mysqli $conn, int $userId): void
{
    if (!ensureRememberTable($conn)) {
        return;
    }

    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiresTs = time() + REMEMBER_LIFETIME_SECONDS;
    $expiresAt = date('Y-m-d H:i:s', $expiresTs);

    $statement = $conn->prepare(
        'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    if ($statement === false) {
        return;
    }
    $statement->bind_param('isss', $userId, $selector, $validatorHash, $expiresAt);
    $saved = $statement->execute();
    $statement->close();

    if ($saved) {
        setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, rememberCookieOptions($expiresTs));
    }
}

/** @return array{0: string, 1: string}|null */
function readRememberCookie(): ?array
{
    $raw = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!is_string($raw) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $raw, $m)) {
        return null;
    }
    return [$m[1], $m[2]];
}

/**
 * Called after session_start() when no session exists yet.
 * Restores the session from a valid remember-me cookie. Returns true on success.
 */
function restoreSessionFromRememberCookie(): bool
{
    $cookie = readRememberCookie();
    if ($cookie === null) {
        return false;
    }
    [$selector, $validator] = $cookie;

    // Plain require (not require_once) so the page's own $conn is not affected.
    require __DIR__ . '/db_connect_mysqli.php';
    /** @var mysqli $conn */

    if (!ensureRememberTable($conn)) {
        $conn->close();
        return false;
    }

    $statement = $conn->prepare(
        'SELECT t.id, t.validator_hash, t.expires_at, u.id, u.name, u.email, u.role
         FROM remember_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.selector = ? LIMIT 1'
    );
    if ($statement === false) {
        $conn->close();
        return false;
    }
    $statement->bind_param('s', $selector);
    $statement->execute();
    $statement->bind_result($tokenId, $storedHash, $expiresAt, $userId, $userName, $userEmail, $userRole);
    $found = $statement->fetch() === true;
    $statement->close();

    $valid = $found
        && hash_equals((string) $storedHash, hash('sha256', $validator))
        && strtotime((string) $expiresAt) > time()
        && in_array($userRole, ['admin', 'student'], true);

    if (!$valid) {
        if ($found) {
            // Right selector, wrong/expired validator: revoke it.
            $revoke = $conn->prepare('DELETE FROM remember_tokens WHERE id = ?');
            if ($revoke !== false) {
                $revoke->bind_param('i', $tokenId);
                $revoke->execute();
                $revoke->close();
            }
        }
        setcookie(REMEMBER_COOKIE, '', rememberCookieOptions(time() - 3600));
        $conn->close();
        return false;
    }

    // Rotate: remove the used token and issue a fresh one.
    $remove = $conn->prepare('DELETE FROM remember_tokens WHERE id = ?');
    if ($remove !== false) {
        $remove->bind_param('i', $tokenId);
        $remove->execute();
        $remove->close();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['user_role'] = $userRole;
    $_SESSION['last_activity'] = time();

    issueRememberToken($conn, (int) $userId);
    $conn->close();
    return true;
}

/** Removes the current device's token from MySQL and expires the cookie. */
function clearRememberToken(): void
{
    $cookie = readRememberCookie();

    if ($cookie !== null) {
        require __DIR__ . '/db_connect_mysqli.php';
        /** @var mysqli $conn */
        if (ensureRememberTable($conn)) {
            $statement = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            if ($statement !== false) {
                $statement->bind_param('s', $cookie[0]);
                $statement->execute();
                $statement->close();
            }
        }
        $conn->close();
    }

    setcookie(REMEMBER_COOKIE, '', rememberCookieOptions(time() - 3600));
}
