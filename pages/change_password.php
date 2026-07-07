<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$user_id = $_SESSION["user_id"] ?? null;
$user_name = $_SESSION["user_name"] ?? "User";

if (!$user_id) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $msg_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirmation do not match.";
        $msg_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters.";
        $msg_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user["password"])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$new_hash, $user_id]);
                $message = "Password changed successfully.";
                $msg_type = "success";
            } else {
                $message = "Current password is incorrect.";
                $msg_type = "danger";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold"><i class="fas fa-key me-2 text-warning"></i> Change Password</h2>
        <p class="text-muted small mb-0">Update your login password for user <strong><?= htmlspecialchars($user_name) ?></strong>.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">

                <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required
                               placeholder="Enter your current password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">New Password</label>
                        <input type="password" name="new_password" class="form-control" required
                               placeholder="At least 6 characters" minlength="6">
                        <div class="form-text text-muted small">Minimum 6 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required
                               placeholder="Re-enter new password">
                    </div>

                    <button type="submit" class="btn btn-warning fw-bold w-100 shadow-sm">
                        <i class="fas fa-save me-2"></i> Change Password
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
