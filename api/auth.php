<?php
require_once 'config.php';
require_once 'mailer.php';

$conn = getDBConnection();
ensureAuthTables($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$input = getInput();

switch ($action) {
    case 'register':
        if ($method !== 'POST') sendError('Method not allowed', 405);
        handleRegister($conn, $input);
        break;
    case 'login':
        if ($method !== 'POST') sendError('Method not allowed', 405);
        handleLogin($conn, $input);
        break;
    case 'me':
        if ($method !== 'GET') sendError('Method not allowed', 405);
        $user = requireAuthUser($conn);
        sendResponse(['user' => publicUser($user)]);
        break;
    case 'logout':
        if ($method !== 'POST') sendError('Method not allowed', 405);
        handleLogout($conn);
        break;
    case 'forgot':
        if ($method !== 'POST') sendError('Method not allowed', 405);
        handleForgot($conn, $input);
        break;
    case 'verify_otp':
        if ($method !== 'POST') sendError('Method not allowed', 405);
        handleVerifyOtp($conn, $input);
        break;
    case 'reset':
        if ($method !== 'POST') sendError('Method not allowed', 405);
        handleReset($conn, $input);
        break;
    case 'inbox':
        if ($method !== 'GET') sendError('Method not allowed', 405);
        handleInbox($conn);
        break;
    default:
        sendError('Unknown auth action', 400);
}

function ensureAuthTables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        auth_token VARCHAR(64) DEFAULT NULL,
        token_expires DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS password_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(191) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        is_used TINYINT(1) NOT NULL DEFAULT 0,
        reset_token VARCHAR(64) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_otp_email (email)
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS mail_outbox (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(191) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $check = $conn->query("SELECT id FROM users WHERE email = 'admin@symboltable.local' LIMIT 1");
    if ($check && $check->num_rows === 0) {
        $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES ('System Admin', 'admin@symboltable.local', ?)");
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
    }

    // Ensure reset_token column exists on older installs
    $col = $conn->query("SHOW COLUMNS FROM password_otps LIKE 'reset_token'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE password_otps ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL");
    }
}

function publicUser($user) {
    return [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'created_at' => $user['created_at']
    ];
}

function getBearerToken() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        return $_SERVER['HTTP_X_AUTH_TOKEN'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'authorization') {
                $header = $v;
                break;
            }
            if (strtolower($k) === 'x-auth-token') {
                return $v;
            }
        }
    }
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return isset($_GET['token']) ? $_GET['token'] : null;
}

function requireAuthUser($conn) {
    $token = getBearerToken();
    if (!$token) sendError('Unauthorized', 401);
    $stmt = $conn->prepare("SELECT * FROM users WHERE auth_token = ? AND token_expires > NOW() LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) sendError('Session expired. Please login again.', 401);
    return $user;
}

function handleRegister($conn, $input) {
    $name = trim($input['full_name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    $confirm = $input['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') sendError('All fields are required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('Invalid email address');
    if (strlen($password) < 6) sendError('Password must be at least 6 characters');
    if ($password !== $confirm) sendError('Passwords do not match');

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) sendError('Email already registered');
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, auth_token, token_expires) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
    $stmt->bind_param('ssss', $name, $email, $hash, $token);
    if (!$stmt->execute()) sendError('Registration failed', 500);
    $id = $stmt->insert_id;
    $stmt->close();

    sendResponse([
        'message' => 'Account created successfully',
        'token' => $token,
        'user' => ['id' => $id, 'full_name' => $name, 'email' => $email]
    ], 201);
}

function handleLogin($conn, $input) {
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    if ($email === '' || $password === '') sendError('Email and password are required');

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendError('Invalid email or password', 401);
    }

    $token = bin2hex(random_bytes(32));
    $stmt = $conn->prepare("UPDATE users SET auth_token = ?, token_expires = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = ?");
    $stmt->bind_param('si', $token, $user['id']);
    $stmt->execute();
    $stmt->close();

    sendResponse([
        'message' => 'Login successful',
        'token' => $token,
        'user' => publicUser($user)
    ]);
}

function handleLogout($conn) {
    $token = getBearerToken();
    if ($token) {
        $stmt = $conn->prepare("UPDATE users SET auth_token = NULL, token_expires = NULL WHERE auth_token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    }
    sendResponse(['message' => 'Logged out']);
}

function handleForgot($conn, $input) {
    $email = strtolower(trim($input['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('Valid email is required');

    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Always respond generically, but only send OTP if user exists
    if ($user) {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        $conn->query("UPDATE password_otps SET is_used = 1 WHERE email = '" . $conn->real_escape_string($email) . "' AND is_used = 0");

        $stmt = $conn->prepare("INSERT INTO password_otps (email, otp_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $stmt->bind_param('ss', $email, $otpHash);
        $stmt->execute();
        $stmt->close();

        $html = buildOtpEmail($user['full_name'], $otp);
        $mailResult = sendAppEmail($email, 'Your Symbol Table MS OTP', $html);

        sendResponse([
            'message' => 'OTP has been sent to your email address.',
            'email' => $email,
            'mail_stored' => $mailResult['stored'],
            'hint' => 'If your inbox is empty (local XAMPP), open OTP Inbox in this screen.'
        ]);
    }

    sendResponse([
        'message' => 'If this email is registered, an OTP has been sent.',
        'email' => $email
    ]);
}

function handleVerifyOtp($conn, $input) {
    $email = strtolower(trim($input['email'] ?? ''));
    $otp = trim($input['otp'] ?? '');
    if ($email === '' || $otp === '') sendError('Email and OTP are required');

    $stmt = $conn->prepare("SELECT * FROM password_otps WHERE email = ? AND is_used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($otp, $row['otp_hash'])) {
        sendError('Invalid or expired OTP');
    }

    $resetToken = bin2hex(random_bytes(24));
    $stmt = $conn->prepare("UPDATE password_otps SET reset_token = ? WHERE id = ?");
    $stmt->bind_param('si', $resetToken, $row['id']);
    $stmt->execute();
    $stmt->close();

    sendResponse([
        'message' => 'OTP verified. You can set a new password.',
        'reset_token' => $resetToken,
        'email' => $email
    ]);
}

function handleReset($conn, $input) {
    $email = strtolower(trim($input['email'] ?? ''));
    $resetToken = trim($input['reset_token'] ?? '');
    $password = $input['password'] ?? '';
    $confirm = $input['confirm_password'] ?? '';

    if ($email === '' || $resetToken === '' || $password === '') sendError('Missing required fields');
    if (strlen($password) < 6) sendError('Password must be at least 6 characters');
    if ($password !== $confirm) sendError('Passwords do not match');

    $stmt = $conn->prepare("SELECT * FROM password_otps WHERE email = ? AND reset_token = ? AND is_used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $email, $resetToken);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) sendError('Reset session invalid or expired. Request a new OTP.');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, auth_token = NULL, token_expires = NULL WHERE email = ?");
    $stmt->bind_param('ss', $hash, $email);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE password_otps SET is_used = 1, reset_token = NULL WHERE id = ?");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $stmt->close();

    sendResponse(['message' => 'Password updated successfully. Please login.']);
}

function handleInbox($conn) {
    $email = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : '';
    if ($email === '') sendError('Email is required');

    $stmt = $conn->prepare("SELECT id, recipient, subject, body, created_at FROM mail_outbox WHERE recipient = ? ORDER BY id DESC LIMIT 10");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        // Extract OTP for local XAMPP inbox helper
        $otp = null;
        if (preg_match('/OTP_CODE:(\d{6})/', $r['body'], $m)) $otp = $m[1];
        elseif (preg_match('/letter-spacing:8px[^>]*>\s*(\d{6})\s*</', $r['body'], $m)) $otp = $m[1];
        $rows[] = [
            'id' => (int)$r['id'],
            'subject' => $r['subject'],
            'created_at' => $r['created_at'],
            'otp' => $otp,
            'preview' => strip_tags($r['body'])
        ];
    }
    $stmt->close();
    sendResponse(['emails' => $rows]);
}
?>
