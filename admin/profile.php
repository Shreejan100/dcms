<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get admin username
$stmt = $db->prepare("SELECT username FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle password change
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All fields are required");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match");
        }

        if (strlen($new_password) < 6) {
            throw new Exception("Password must be at least 6 characters long");
        }

        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);

        $success = "Password updated successfully";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4>Admin Profile</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Username:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($admin['username']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Role:</div>
                        <div class="col-md-8">Administrator</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 6 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
