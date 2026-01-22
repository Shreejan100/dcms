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

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    header("Location: manage_patients.php");
    exit();
}

// Get patient details
$stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as patient_name FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: manage_patients.php");
    exit();
}

// Get all appointments for this patient
$query = "SELECT a.*, 
          CONCAT(d.first_name, ' ', d.last_name) as dentist_name,
          DATE_FORMAT(a.appointment_date, '%Y-%m-%d') as formatted_date,
          TIME_FORMAT(a.appointment_time, '%h:%i %p') as formatted_time
          FROM appointments a
          JOIN dentists d ON a.dentist_id = d.id
          WHERE a.patient_id = ?
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $db->prepare($query);
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Appointments for <?php echo htmlspecialchars($patient['patient_name']); ?></h4>
                <a href="manage_patients.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Patients
                </a>
            </div>
        </div>
    </div>

    <!-- Appointments List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Appointment History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Dentist</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No appointments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($appointment['formatted_date'])); ?></td>
                                    <td><?php echo $appointment['formatted_time']; ?></td>
                                    <td>
                                        <a href="manage_dentists.php?id=<?php echo $appointment['dentist_id']; ?>">
                                            <?php echo htmlspecialchars($appointment['dentist_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($appointment['status']) {
                                            case 'pending':
                                                $status_class = 'warning';
                                                break;
                                            case 'confirmed':
                                                $status_class = 'primary';
                                                break;
                                            case 'completed':
                                                $status_class = 'success';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'danger';
                                                break;
                                            case 'missed':
                                                $status_class = 'secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($appointment['status'] === 'completed'): ?>
                                            <a href="../dentist/view_report.php?id=<?php echo $appointment['id']; ?>&admin_view=1&dentist_id=<?php echo $appointment['dentist_id']; ?>" 
                                               class="btn btn-sm btn-secondary">
                                                <i class="bi bi-file-text"></i> View Report
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="../dentist/appointments.php?admin_view=1&dentist_id=<?php echo $appointment['dentist_id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="bi bi-calendar"></i> View in Schedule
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
