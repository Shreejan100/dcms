<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_dentists.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get dentist details
    $stmt = $db->prepare("SELECT d.*, u.username 
                         FROM dentists d 
                         JOIN users u ON d.user_id = u.id 
                         WHERE d.id = ?");
    $stmt->execute([$_GET['id']]);
    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dentist) {
        header("Location: manage_dentists.php");
        exit();
    }

    // Get appointment statistics
    $stmt = $db->prepare("SELECT 
                            COUNT(*) as total_appointments,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments
                         FROM appointments 
                         WHERE dentist_id = ?");
    $stmt->execute([$_GET['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4>View Dentist Details</h4>
        </div>
        <div class="col text-end">
            <?php if ($dentist['status'] === 'pending'): ?>
                <a href="approve_dentists.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Pending Approvals
                </a>
            <?php else: ?>
                <a href="manage_dentists.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            <?php endif; ?>
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
                            <div class="col-md-8"><?php echo htmlspecialchars($dentist['username']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Full Name:</div>
                            <div class="col-md-8">
                                Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Gender:</div>
                            <div class="col-md-8"><?php echo ucfirst($dentist['gender']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Date of Birth:</div>
                            <div class="col-md-8"><?php echo date('F d, Y', strtotime($dentist['dob'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Phone:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($dentist['phone']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Email:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($dentist['email']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Professional Information Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Professional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Specialization:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($dentist['specialization']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Experience:</div>
                            <div class="col-md-8"><?php echo $dentist['work_experience']; ?> years</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Degree:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($dentist['degree']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Consultation Charge:</div>
                            <div class="col-md-8">Rs. <?php echo number_format($dentist['consultation_charge'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Information Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Schedule Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Working Days:</div>
                            <div class="col-md-8">
                                <?php 
                                $days = json_decode($dentist['working_days'], true);
                                echo implode(', ', array_map('ucfirst', $days));
                                ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Working Hours:</div>
                            <div class="col-md-8">
                                <?php 
                                echo date('h:i A', strtotime($dentist['working_hours_start'])) . ' - ' . 
                                     date('h:i A', strtotime($dentist['working_hours_end']));
                                ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Consultation Duration:</div>
                            <div class="col-md-8"><?php echo $dentist['consultation_duration']; ?> minutes</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Break Time:</div>
                            <div class="col-md-8">
                                <?php 
                                echo date('h:i A', strtotime($dentist['break_time_start'])) . ' - ' . 
                                     date('h:i A', strtotime($dentist['break_time_end']));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <?php if ($dentist['status'] === 'active'): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Appointment Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Appointments</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_appointments']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Completed</h6>
                                        <h2 class="mb-0"><?php echo $stats['completed_appointments']; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Pending</h6>
                                        <h2 class="mb-0"><?php echo $stats['pending_appointments']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Cancelled</h6>
                                        <h2 class="mb-0"><?php echo $stats['cancelled_appointments']; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Account Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            This dentist's account is pending approval. Appointment statistics will be available after approval.
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
