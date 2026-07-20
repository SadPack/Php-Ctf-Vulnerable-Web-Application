# CTF Walkthrough: Vulnerable PHP Mini App

## Overview
This challenge is a, intentionally vulnerable PHP web application built to demonstrate four real-world web security vulnerabilities. The goal is to chain a login bypass with a stored XSS exploit to reach a final flag page, while two additional standalone vulnerabilities as command injection and broken access control are also present for you to find and exploit independently.

**Tech stack:** Kali Linux, PHP, SQLite3, PHP's built-in server.

### Vulnerabilities included

| # | Vulnerability | Location |
|---|---|---|
| 1 | SQL Injection (Authentication Bypass) | `login.php` |
| 2 | Stored Cross-Site Scripting (XSS) | `dashboard.php` |
| 3 | OS Command Injection | `dashboard.php` (ping tool) |
| 4 | Broken Access Control | `admin.php` |

## Setup

1. Ensure PHP and the SQLite3 extension are installed.
2. From the `public/` directory, start the built-in server:
```bash
   php -S localhost:8000
```
3. Navigate to `http://localhost:8000/login.php` in a browser.

## Vulnerability 1: SQL Injection - Authentication Bypass

### Description
The login form builds its SQL query by user input into the query string, rather than using a parameterized query. This allows an attacker to alter the logical structure of the query itself.

### Vulnerable code
```php
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
```
### Steps to exploit
1. Go to `login.php`.
2. In the **Username** field, enter:
3. Leave the **Password** field blank.
4. Click **Login**.

### Why it works
The single quote closes the intended string early. The `--` sequence is SQL's comment syntax, causing the database to ignore everything after it including the password check. The resulting query effectively becomes:
```sql
SELECT * FROM users WHERE username = 'admin' --' AND password = ''
```
Since a user named `admin` exists, the login succeeds with no valid password ever supplied.

### Evidence
(screenshots/login-page-normal.png)
sqli-payload.png
sqli-success.png

### Remediation
Use parameterized queries (prepared statements) so user input is always treated as data, never as part of the SQL command structure.

## Vulnerability 2: Stored Cross-Site Scripting (XSS)

### Description
The dashboard's comment feature saves user input safely (via a prepared statement), but displays it back to all visitors without any output encoding. This allows an attacker to store malicious JavaScript that executes in the browser of anyone who later views the page.

### Vulnerable code
```php
<p><?php echo $row['comment']; ?></p>
```
### Steps to exploit
1. Log in (via the SQLi bypass above, or valid credentials).
2. In the comment box, enter:
```html
   <script>alert('XSS')</script>
```
3. Click **Post Comment**.
4. Observe the alert box fires immediately and will continue to fire for every future visitor to the dashboard, since the payload is now permanently stored in the database.

### Why it works
User input is inserted into the page's HTML with no escaping. The browser cannot distinguish between "text the user wrote" and "code the developer intended to run" it just executes whatever is inside `<script>` tags.

### Evidence
- `xss-payload-entered.png`
- `xss-alert-fired.png`

### Remediation
Encode all user-supplied output before rendering it in HTML, e.g. using `htmlspecialchars()` in PHP. Note that sanitizing *input* on the way into the database is not sufficient the fix belongs at the point of *output*.

## Vulnerability 3: OS Command Injection

### Description
The dashboard includes a "Check Website Status" tool that pings a user supplied hostname using PHP's `shell_exec()`. The hostname is entered directly into the shell command with no filtering, allowing an attacker to append arbitrary additional commands.

### Vulnerable code
```php
$ping_result = shell_exec("ping -c 1 " . $host);
```

### Steps to exploit
1. On the dashboard, locate the **Check Website Status** form.
2. Enter a normal hostname first to confirm expected behavior:
3. Then enter a payload that appends a second command:
4. Click **Ping**.

### Why it works
The semicolon (`;`) is a shell command separator. The underlying operating system executes the ping command as intended, then executes `whoami` immediately afterward, returning the identity of the user running the web server process.

### Evidence
- `ping-normal.png`
- `cmdi-payload.png`
- `cmdi-success.png`

### Remediation
Never pass user input directly into shell execution functions. If shelling out is unavoidable, use an allowlist of permitted characters/hosts, or use a language-native library (e.g. a DNS/ping library) instead of the system shell entirely.

## Vulnerability 4: Broken Access Control

### Description
`admin.php` decides whether to grant administrator access based solely on a `role` value read from the URL query string data that is entirely controlled by the client instead of verifying an authenticated, server-side session.

### Vulnerable code
```php
$role = $_GET['role'] ?? 'guest';
if ($role === 'admin') {
    $access = true;
}
```

### Steps to exploit
1. Without logging in, navigate to:
Note the "Access denied" response.
2. Now navigate to:
3. Observe that access is granted and sensitive data is disclosed, without any authentication having taken place.

### Why it works
The application trusts a value the visitor can freely set in the URL to make a security critical decision. There is no verification against a real session, login state, or server-side user record.

### Evidence
- `admin-denied.png`
- `admin-bypass.png`

### Remediation
Access control decisions must always be enforced server-side, based on an authenticated session (e.g. a session variable set only after verified login) never based on client supplied parameters like URL query strings or hidden form fields.

---

## Flag

After chaining the SQL injection login bypass with the dashboard access, navigating to `flag.php` reveals:

This flag is intentionally awarded for successfully completing the login bypass and reaching the dashboard representing the combined authentication and injection weaknesses in the application.

### Evidence
- `flag-page.png`

## Summary

| Vulnerability | Root Cause | Fix |
|---|---|---|
| SQL Injection | Unparameterized query | Use prepared statements |
| Stored XSS | Unescaped output | Encode output with `htmlspecialchars()` |
| Command Injection | Unsanitized input to `shell_exec()` | Avoid shell execution with user input; use allowlists |
| Broken Access Control | Trusting client-supplied data for auth decisions | Enforce access checks server-side via authenticated sessions |

This demonstrates that vulnerabilities can exist at different stages of the request/response lifecycle — input validation, output encoding, system-level execution, and access control — and that a single application can suffer from multiple distinct classes of weakness simultaneously.
