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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
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

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM patients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered with another patient");
        }

        // Check if username already exists in users table (for both patients and dentists)
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already taken");
        }

        // Create user account first
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
        $stmt->execute([$username, $hashed_password]);
        $user_id = $db->lastInsertId();
        
        // Create patient profile
        $stmt = $db->prepare("INSERT INTO patients (user_id, first_name, last_name, gender, dob, phone, email,
                            address, medical_history, allergies, current_medications) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id, $first_name, $last_name, $gender, $dob, $phone, $email,
            $address, $medical_history, $allergies, $current_medications
        ]);
        
        $db->commit();
        header("Location: manage_patients.php?success=added");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Add New Patient</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <!-- Personal Information -->
                <h6 class="mb-3">Personal Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                        <small class="text-muted">This will be used for login</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" required>
                    </div>
                </div>

                <!-- Medical Information -->
                <h6 class="mb-3">Medical Information</h6>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Medical History</label>
                        <textarea class="form-control" name="medical_history" rows="3" placeholder="Enter any past medical conditions, surgeries, etc."></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Allergies</label>
                        <textarea class="form-control" name="allergies" rows="2" placeholder="List any allergies"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Current Medications</label>
                        <textarea class="form-control" name="current_medications" rows="2" placeholder="List current medications"></textarea>
                    </div>
                </div>

                <div class="text-end">
                    <a href="manage_patients.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>