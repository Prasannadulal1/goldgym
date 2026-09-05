<?php
/**
 * Run ONCE from the command line:
 *   php create_admin.php myusername my@email.com "a strong password"
 * Delete this file from the server afterward.
 */
require_once __DIR__ . '/includes/db.php';

if (php_sapi_name() !== 'cli') {
    die("Run from the command line: php create_admin.php <username> <email> <password>\n");
}
[$script, $username, $email, $password] = $argv + [null, null, null, null];
if (!$username || !$email || !$password) {
    die("Usage: php create_admin.php <username> <email> <password>\n");
}
if (strlen($password) < 8) {
    die("Password must be at least 8 characters.\n");
}
$hash = password_hash($password, PASSWORD_DEFAULT);
db()->prepare('INSERT INTO admins (username, email, password_hash) VALUES (:u, :e, :p)')
    ->execute(['u' => $username, 'e' => $email, 'p' => $hash]);
echo "Admin account created for '{$username}'.\n";
