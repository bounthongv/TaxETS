<?php
// Login page - NO auth check needed
require_once __DIR__ . "/config.php";

session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}
require_once __DIR__ . "/includes/db.php";

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && $user["active"] == 1 && password_verify($password, $user["password"])) {
        session_start();
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];
        
        $token = bin2hex(random_bytes(32));
        $_SESSION["session_token"] = $token;
        
        if (tableExists($pdo, 'user_sessions')) {
            $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address) VALUES (?, ?, ?)");
            $stmt->execute([$user["id"], $token, $_SERVER["REMOTE_ADDR"] ?? "unknown"]);
        }
        
        require_once __DIR__ . "/includes/user_history.php";
        logUserAction($pdo, $user["id"], $user["name"], "LOGIN", "User logged in", $_SERVER["REMOTE_ADDR"] ?? "");
        
        header("Location: " . BASE_URL . "/index.php");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fas fa-calculator fa-3x text-primary"></i>
            <h3 class="mt-3"><?= APP_NAME ?></h3>
            <p class="text-muted">Tax Expenditure Estimation System</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>
        
        <div class="text-center mt-4 text-muted small">
            Default: admin@example.com / admin123
        </div>
    </div>
</body>
</html>
