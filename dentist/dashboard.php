<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dentist') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get dentist details
$stmt = $db->prepare("SELECT * FROM dentists WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dentist = $stmt->fetch(PDO::FETCH_ASSOC);

// Get pending appointments count
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE dentist_id = ? AND status = 'pending'");
$stmt->execute([$dentist['id']]);
$pending_count = $stmt->fetchColumn();

// Get completed appointments count
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE dentist_id = ? AND status = 'completed'");
$stmt->execute([$dentist['id']]);
$completed_count = $stmt->fetchColumn();

// Get today's appointment count
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE dentist_id = ? AND DATE(appointment_date) = CURDATE()");
$stmt->execute([$dentist['id']]);
$today_count = $stmt->fetchColumn();

include '../layouts/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col">
            <h4>Welcome, Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>!</h4>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Today's Appointments</h5>
                    <p class="card-text display-6"><?php echo $today_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Appointments</h5>
                    <p class="card-text display-6"><?php echo $pending_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Completed Treatments</h5>
                    <p class="card-text display-6"><?php echo $completed_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <a href="appointments.php" class="btn btn-primary mb-2 w-100">
                        <i class="bi bi-calendar"></i> View All Appointments
                    </a>
                    <a href="edit_profile.php" class="btn btn-secondary w-100">
                        <i class="bi bi-person"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
