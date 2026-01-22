<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$dentist = null;

// Get dentist data
if (!isset($_GET['id'])) {
    header('Location: manage_dentists.php?error=no_id');
    exit();
}

$dentist_id = (int)$_GET['id'];
if ($dentist_id <= 0) {
    header('Location: manage_dentists.php?error=invalid_id');
    exit();
}

// Get dentist data
$stmt = $db->prepare("SELECT d.*, u.username 
                     FROM dentists d 
                     JOIN users u ON d.user_id = u.id 
                     WHERE d.id = ?");
$stmt->execute([$dentist_id]);
$dentist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dentist) {
    header('Location: manage_dentists.php?error=not_found');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dentist_id = (int)$_POST['dentist_id'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $new_password = $_POST['new_password'];
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

        // Check if email exists for other dentists
        $stmt = $db->prepare("SELECT id FROM dentists WHERE email = ? AND id != ?");
        $stmt->execute([$email, $dentist_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered with another dentist");
        }

        // Update dentist profile
        $stmt = $db->prepare("UPDATE dentists SET 
            first_name = ?, last_name = ?, gender = ?, dob = ?, 
            phone = ?, email = ?, specialization = ?, work_experience = ?, 
            degree = ?, consultation_charge = ?, working_days = ?,
            working_hours_start = ?, working_hours_end = ?, 
            consultation_duration = ?, break_time_start = ?, 
            break_time_end = ?
            WHERE id = ?");
        
        $stmt->execute([
            $first_name, $last_name, $gender, $dob, 
            $phone, $email, $specialization, $work_experience, 
            $degree, $consultation_charge, $working_days,
            $working_hours_start, $working_hours_end, 
            $consultation_duration, $break_time_start, 
            $break_time_end, $dentist_id
        ]);

        // Update password if provided
        if (!empty($new_password)) {
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt->execute([$hashed_password, $dentist['user_id']]);
        }

        $db->commit();
        $success = "Dentist updated successfully!";
        
        // Refresh dentist data
        $stmt = $db->prepare("SELECT d.*, u.username 
                         FROM dentists d 
                         JOIN users u ON d.user_id = u.id 
                         WHERE d.id = ?");
        $stmt->execute([$dentist_id]);
        $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Edit Dentist</h5>
                        <a href="manage_dentists.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="dentist_id" value="<?php echo $dentist['id']; ?>">
                        
                        <!-- Account Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Account Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo $dentist['username']; ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password (leave blank to keep current)</label>
                                        <input type="password" class="form-control" name="new_password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Personal Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo $dentist['first_name']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo $dentist['last_name']; ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender" required>
                                            <option value="male" <?php echo $dentist['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $dentist['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo $dentist['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="dob" 
                                               value="<?php echo $dentist['dob']; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo $dentist['phone']; ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo $dentist['email']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Professional Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Specialization</label>
                                        <select class="form-select" name="specialization" required>
                                            <option value="">Select Specialization</option>
                                            <option value="General Dentist" <?php echo ($dentist['specialization'] == 'General Dentist') ? 'selected' : ''; ?>>General Dentist</option>
                                            <option value="Orthodontist" <?php echo ($dentist['specialization'] == 'Orthodontist') ? 'selected' : ''; ?>>Orthodontist</option>
                                            <option value="Endodontist" <?php echo ($dentist['specialization'] == 'Endodontist') ? 'selected' : ''; ?>>Endodontist</option>
                                            <option value="Periodontist" <?php echo ($dentist['specialization'] == 'Periodontist') ? 'selected' : ''; ?>>Periodontist</option>
                                            <option value="Prosthodontist" <?php echo ($dentist['specialization'] == 'Prosthodontist') ? 'selected' : ''; ?>>Prosthodontist</option>
                                            <option value="Pediatric Dentist" <?php echo ($dentist['specialization'] == 'Pediatric Dentist') ? 'selected' : ''; ?>>Pediatric Dentist</option>
                                            <option value="Cosmetic Dentist" <?php echo ($dentist['specialization'] == 'Cosmetic Dentist') ? 'selected' : ''; ?>>Cosmetic Dentist</option>
                                            <option value="Oral and Maxillofacial Surgeon" <?php echo ($dentist['specialization'] == 'Oral and Maxillofacial Surgeon') ? 'selected' : ''; ?>>Oral and Maxillofacial Surgeon</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Work Experience (years)</label>
                                        <input type="number" class="form-control" name="work_experience" 
                                               value="<?php echo $dentist['work_experience']; ?>" min="0" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Degree</label>
                                        <input type="text" class="form-control" name="degree" 
                                               value="<?php echo $dentist['degree']; ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Consultation Charge (Rs.)</label>
                                        <input type="number" class="form-control" name="consultation_charge" 
                                               value="<?php echo $dentist['consultation_charge']; ?>" min="0" step="0.01" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Consultation Duration (minutes)</label>
                                        <input type="number" class="form-control" name="consultation_duration" 
                                               value="<?php echo $dentist['consultation_duration']; ?>" min="15" step="15" required>
                                        <small class="text-muted">Duration of each consultation (in 15-minute intervals)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Schedule Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Working Days</label>
                                        <div class="form-check">
                                            <div class="row">
                                                <?php
                                                $working_days = json_decode($dentist['working_days'], true);
                                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                                foreach ($days as $day) {
                                                    echo '<div class="col-md-2">
                                                        <input type="checkbox" class="form-check-input" name="working_days[]" 
                                                            value="'.$day.'" '.
                                                            (in_array($day, $working_days) ? 'checked' : '').'>
                                                        <label class="form-check-label">'.ucfirst($day).'</label>
                                                    </div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Working Hours Start</label>
                                        <input type="time" class="form-control" name="working_hours_start" 
                                               value="<?php echo $dentist['working_hours_start']; ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Working Hours End</label>
                                        <input type="time" class="form-control" name="working_hours_end" 
                                               value="<?php echo $dentist['working_hours_end']; ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Break Time Start</label>
                                        <input type="time" class="form-control" name="break_time_start" 
                                               value="<?php echo $dentist['break_time_start']; ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Break Time End</label>
                                        <input type="time" class="form-control" name="break_time_end" 
                                               value="<?php echo $dentist['break_time_end']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <a href="manage_dentists.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Dentist</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
