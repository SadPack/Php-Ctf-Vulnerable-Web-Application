<?php
// VULNERABLE: access control decision is based on a value the USER controls
// (the URL/GET parameter), instead of checking an actual authenticated session
$role = $_GET['role'] ?? 'guest';

if ($role === 'admin') {
    $access = true;
} else {
    $access = false;
}
?>
<!DOCTYPE html>
<html>
<head><title>Admin Panel</title></head>
<body>
    <h2>Admin Panel</h2>
    <?php if ($access): ?>
        <p>Access granted. Welcome, administrator.</p>
        <p>Secret admin data: <strong>All user passwords are stored in plaintext in users.db</strong></p>
    <?php else: ?>
        <p>Access denied. Admins only.</p>
    <?php endif; ?>
</body>
</html>
