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

// Get counts for dashboard
$stmt = $db->query("SELECT 
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_dentists,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_dentists
    FROM dentists");
$dentist_counts = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT COUNT(*) FROM patients");
$patient_count = $stmt->fetchColumn();

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row g-4 py-3">
        <!-- Dashboard Cards -->
        <div class="col-md-4">
            <div class="card dashboard-card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Dentists</h5>
                    <p class="card-text display-6"><?php echo $dentist_counts['active_dentists']; ?></p>
                    <a href="manage_dentists.php" class="text-white text-decoration-none">View Details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Approvals</h5>
                    <p class="card-text display-6"><?php echo $dentist_counts['pending_dentists']; ?></p>
                    <a href="approve_dentists.php" class="text-white text-decoration-none">View Details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Patients</h5>
                    <p class="card-text display-6"><?php echo $patient_count; ?></p>
                    <a href="manage_patients.php" class="text-white text-decoration-none">View Details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Appointments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Dentist</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $db->query("SELECT a.*, 
                                                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                                    CONCAT(d.first_name, ' ', d.last_name) as dentist_name
                                                FROM appointments a
                                                JOIN patients p ON a.patient_id = p.id
                                                JOIN dentists d ON a.dentist_id = d.id
                                                WHERE d.status = 'active'
                                                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                                                LIMIT 10");
                                
                                if ($stmt->rowCount() > 0) {
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['dentist_name']) . "</td>";
                                        echo "<td>" . date('M d, Y', strtotime($row['appointment_date'])) . "</td>";
                                        echo "<td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>";
                                        echo "<td>" . formatAppointmentStatus($row['status']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No recent appointments found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
