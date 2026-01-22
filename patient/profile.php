<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get patient details
    $stmt = $db->prepare("SELECT p.*, u.username 
                         FROM patients p 
                         JOIN users u ON p.user_id = u.id 
                         WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("Patient profile not found");
    }

    // Get appointment statistics
    $stmt = $db->prepare("SELECT 
                            COUNT(*) as total_appointments,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments
                         FROM appointments 
                         WHERE patient_id = ?");
    $stmt->execute([$patient['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4>My Profile</h4>
        </div>
        <div class="col text-end">
            <a href="edit_profile.php" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit Profile
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="row">
            <!-- Personal Information Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Username:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($patient['username']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Full Name:</div>
                            <div class="col-md-8">
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Gender:</div>
                            <div class="col-md-8"><?php echo ucfirst($patient['gender']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Date of Birth:</div>
                            <div class="col-md-8"><?php echo date('F d, Y', strtotime($patient['dob'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Phone:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($patient['phone']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Email:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($patient['email']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Information Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Medical Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Medical History:</div>
                            <div class="col-md-8"><?php echo nl2br(htmlspecialchars($patient['medical_history'] ?? 'None')); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Allergies:</div>
                            <div class="col-md-8"><?php echo nl2br(htmlspecialchars($patient['allergies'] ?? 'None')); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Current Medications:</div>
                            <div class="col-md-8"><?php echo nl2br(htmlspecialchars($patient['current_medications'] ?? 'None')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Appointment Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6 fw-bold">Total Appointments:</div>
                            <div class="col-md-6"><?php echo $stats['total_appointments']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 fw-bold">Completed:</div>
                            <div class="col-md-6"><?php echo $stats['completed_appointments']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 fw-bold">Pending:</div>
                            <div class="col-md-6"><?php echo $stats['pending_appointments']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 fw-bold">Cancelled:</div>
                            <div class="col-md-6"><?php echo $stats['cancelled_appointments']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
