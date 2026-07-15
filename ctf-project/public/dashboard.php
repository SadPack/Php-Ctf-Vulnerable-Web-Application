<?php
$db = new SQLite3('users.db');

$ping_result = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['host'])) {
    $host = $_POST['host'];

    // VULNERABLE: user input is glued directly into a shell command
    $ping_result = shell_exec("ping -c 1 " . $host);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = $_POST['comment'];

    // VULNERABLE: comment is inserted using a safe prepared statement (good!)
    // but displayed later WITHOUT sanitization (bad — this is the actual bug)
    $stmt = $db->prepare("INSERT INTO comments (comment) VALUES (:comment)");
    $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
    $stmt->execute();
}

$comments = $db->query("SELECT * FROM comments ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
    <h2>Welcome to your Dashboard</h2>

    <h3>Check Website Status</h3>
    <form method="POST">
        Host: <input type="text" name="host" placeholder="e.g. google.com">
        <input type="submit" value="Ping">
    </form>
    <pre><?php echo htmlspecialchars($ping_result); ?></pre>

    <form method="POST">
        Leave a comment:<br>
        <textarea name="comment" rows="3" cols="40"></textarea><br>
        <input type="submit" value="Post Comment">
    </form>

    <h3>Comments</h3>
    <?php while ($row = $comments->fetchArray()): ?>
        <!-- VULNERABLE: comment is printed directly into HTML, no escaping -->
        <p><?php echo $row['comment']; ?></p>
    <?php endwhile; ?>

    <p><a href="flag.php">Continue</a></p>
</body>
</html>
