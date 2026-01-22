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

$error = '';
$success = '';
$patient = null;

// Get patient data
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT p.*, u.username 
                         FROM patients p 
                         JOIN users u ON p.user_id = u.id 
                         WHERE p.id = ?");
    $stmt->execute([$_GET['id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        header('Location: manage_patients.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $username = sanitizeInput($_POST['username']);
    $new_password = $_POST['new_password'];
    $gender = sanitizeInput($_POST['gender']);
    $dob = $_POST['dob'];
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $medical_history = sanitizeInput($_POST['medical_history']);
    $allergies = sanitizeInput($_POST['allergies']);
    $current_medications = sanitizeInput($_POST['current_medications']);
    
    try {
        $db->beginTransaction();

        // Check if email already exists for other patients
        $stmt = $db->prepare("SELECT id FROM patients WHERE email = ? AND id != ?");
        $stmt->execute([$email, $patient_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered with another patient");
        }

        // Check if username already exists for other users
        $stmt = $db->prepare("SELECT id FROM users 
                            WHERE username = ? AND id != (
                                SELECT user_id FROM patients WHERE id = ?
                            )");
        $stmt->execute([$username, $patient_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already taken by another user");
        }
        
        // Update patient profile
        $stmt = $db->prepare("UPDATE patients SET 
            first_name = ?, last_name = ?, gender = ?, dob = ?, 
            phone = ?, email = ?, address = ?, medical_history = ?, 
            allergies = ?, current_medications = ?
            WHERE id = ?");
        
        $stmt->execute([
            $first_name, $last_name, $gender, $dob, 
            $phone, $email, $address, $medical_history, 
            $allergies, $current_medications,
            $patient_id
        ]);

        // Update username in users table
        $stmt = $db->prepare("UPDATE users u 
                            JOIN patients p ON p.user_id = u.id 
                            SET u.username = ? 
                            WHERE p.id = ?");
        $stmt->execute([$username, $patient_id]);

        // Update password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users u 
                                JOIN patients p ON p.user_id = u.id 
                                SET u.password = ? 
                                WHERE p.id = ?");
            $stmt->execute([$hashed_password, $patient_id]);
        }

        $db->commit();
        header("Location: manage_patients.php?success=updated");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Patient</h5>
            <a href="manage_patients.php" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                
                <!-- Personal Information -->
                <h6 class="mb-3">Personal Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                        <small class="text-muted">This is used for login</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo $patient['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $patient['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $patient['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" value="<?php echo $patient['dob']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>" required>
                    </div>
                </div>

                <!-- Medical Information -->
                <h6 class="mb-3">Medical Information</h6>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Medical History</label>
                        <textarea class="form-control" name="medical_history" rows="3" placeholder="Enter any past medical conditions, surgeries, etc."><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Allergies</label>
                        <textarea class="form-control" name="allergies" rows="2" placeholder="List any allergies"><?php echo htmlspecialchars(isset($patient['allergies']) ? $patient['allergies'] : ''); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Current Medications</label>
                        <textarea class="form-control" name="current_medications" rows="2" placeholder="List current medications"><?php echo htmlspecialchars(isset($patient['current_medications']) ? $patient['current_medications'] : ''); ?></textarea>
                    </div>
                </div>

                <div class="text-end">
                    <a href="manage_patients.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>