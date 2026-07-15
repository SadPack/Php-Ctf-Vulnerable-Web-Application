<?php
$db = new SQLite3('users.db');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {    $username = $_POST['username'];
    $password = $_POST['password'];

    // VULNERABLE: user input is glued directly into the SQL command
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $db->query($query);
    $user = $result->fetchArray();

    if ($user) {
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Login failed.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
    <h2>Login</h2>
    <form method="POST">
        Username: <input type="text" name="username"><br><br>
        Password: <input type="password" name="password"><br><br>
        <input type="submit" value="Login">
    </form>
    <p><?php echo $message; ?></p>
</body>
</html>
