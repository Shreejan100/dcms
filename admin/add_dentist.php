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
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $gender = sanitizeInput($_POST['gender']);
    $dob = $_POST['dob'];
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $specialization = sanitizeInput($_POST['specialization']);
    $work_experience = (int)$_POST['work_experience'];
    $degree = sanitizeInput($_POST['degree']);
    $consultation_charge = (float)$_POST['consultation_charge'];
    $working_days = isset($_POST['working_days']) ? json_encode($_POST['working_days']) : json_encode([]);
    $working_hours_start = $_POST['working_hours_start'];
    $working_hours_end = $_POST['working_hours_end'];
    $consultation_duration = (int)$_POST['consultation_duration'];
    $break_time_start = $_POST['break_time_start'];
    $break_time_end = $_POST['break_time_end'];
    
    try {
        $db->beginTransaction();

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM dentists WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered with another dentist");
        }
        
        // Check if username already exists in users table (for both patients and dentists)
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already taken");
        }

        // Create user account
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'dentist')");
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([$username, $hashed_password]);
        $user_id = $db->lastInsertId();
        
        // Create dentist profile
        $stmt = $db->prepare("INSERT INTO dentists (user_id, first_name, last_name, gender, dob, phone, email,
                            specialization, work_experience, degree, consultation_charge, working_days, 
                            working_hours_start, working_hours_end, consultation_duration,
                            break_time_start, break_time_end, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute([
            $user_id, $first_name, $last_name, $gender, $dob, $phone, $email,
            $specialization, $work_experience, $degree, $consultation_charge, $working_days,
            $working_hours_start, $working_hours_end, $consultation_duration,
            $break_time_start, $break_time_end
        ]);
        
        $db->commit();
        header("Location: manage_dentists.php?success=added");
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
            <h5 class="card-title mb-0">Add New Dentist</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <!-- Account Information -->
                <h6 class="mb-3">Account Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>

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
                </div>

                <!-- Professional Information -->
                <h6 class="mb-3">Professional Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Specialization</label>
                        <select class="form-select" name="specialization" required>
                            <option value="">Select Specialization</option>
                            <option value="General Dentist">General Dentist</option>
                            <option value="Orthodontist">Orthodontist</option>
                            <option value="Endodontist">Endodontist</option>
                            <option value="Periodontist">Periodontist</option>
                            <option value="Prosthodontist">Prosthodontist</option>
                            <option value="Pediatric Dentist">Pediatric Dentist</option>
                            <option value="Cosmetic Dentist">Cosmetic Dentist</option>
                            <option value="Oral and Maxillofacial Surgeon">Oral and Maxillofacial Surgeon</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Work Experience (years)</label>
                        <input type="number" class="form-control" name="work_experience" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Degree</label>
                        <input type="text" class="form-control" name="degree" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Consultation Charge (Rs.)</label>
                        <input type="number" class="form-control" name="consultation_charge" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Consultation Duration (minutes)</label>
                        <input type="number" class="form-control" name="consultation_duration" min="15" step="15" value="30" required>
                    </div>
                </div>

                <!-- Schedule Information -->
                <h6 class="mb-3">Schedule Information</h6>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Working Days</label>
                        <div class="form-check">
                            <div class="row">
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="monday" checked>
                                    <label class="form-check-label">Monday</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="tuesday" checked>
                                    <label class="form-check-label">Tuesday</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="wednesday" checked>
                                    <label class="form-check-label">Wednesday</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="thursday" checked>
                                    <label class="form-check-label">Thursday</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="friday" checked>
                                    <label class="form-check-label">Friday</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="saturday">
                                    <label class="form-check-label">Saturday</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="checkbox" class="form-check-input" name="working_days[]" value="sunday">
                                    <label class="form-check-label">Sunday</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Working Hours Start</label>
                        <input type="time" class="form-control" name="working_hours_start" value="09:00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Working Hours End</label>
                        <input type="time" class="form-control" name="working_hours_end" value="17:00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Break Time Start</label>
                        <input type="time" class="form-control" name="break_time_start" value="13:00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Break Time End</label>
                        <input type="time" class="form-control" name="break_time_end" value="14:00" required>
                    </div>
                </div>

                <div class="text-end">
                    <a href="manage_dentists.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Dentist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
