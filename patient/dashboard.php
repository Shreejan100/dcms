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

// Get patient details
$stmt = $db->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

// Get upcoming appointments
$stmt = $db->prepare("SELECT a.*, 
                      CONCAT(d.first_name, ' ', d.last_name) as dentist_name,
                      d.specialization
                    FROM appointments a
                    JOIN dentists d ON a.dentist_id = d.id
                    WHERE a.patient_id = ? 
                    AND a.appointment_date >= CURDATE()
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC
                    LIMIT 5");
$stmt->execute([$patient['id']]);
$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    try {
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' 
                             WHERE id = ? AND patient_id = ? 
                             AND status IN ('pending', 'confirmed')");
        $stmt->execute([$_POST['appointment_id'], $patient['id']]);
        $success = "Appointment cancelled successfully.";
        
        // Refresh the upcoming appointments list
        $stmt = $db->prepare("SELECT a.*, 
                           CONCAT(d.first_name, ' ', d.last_name) as dentist_name,
                           d.specialization
                         FROM appointments a
                         JOIN dentists d ON a.dentist_id = d.id
                         WHERE a.patient_id = ? 
                         AND a.appointment_date >= CURDATE()
                         ORDER BY a.appointment_date ASC, a.appointment_time ASC
                         LIMIT 5");
        $stmt->execute([$patient['id']]);
        $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get past appointments
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'");
$stmt->execute([$patient['id']]);
$completed_appointments = $stmt->fetchColumn();

include '../layouts/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col">
            <h4>Welcome, <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>!</h4>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Appointments</h5>
                    <p class="card-text display-6"><?php echo count($upcoming_appointments); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Completed Treatments</h5>
                    <p class="card-text display-6"><?php echo $completed_appointments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <a href="book_appointment.php" class="btn btn-primary mb-2 w-100">
                        <i class="bi bi-calendar-plus"></i> Book Appointment
                    </a>
                    <a href="my_appointments.php" class="btn btn-secondary w-100">
                        <i class="bi bi-calendar-check"></i> View Appointments
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_appointments)): ?>
                        <p class="text-muted">No upcoming appointments.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Dentist</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['dentist_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                            <td><?php echo formatAppointmentStatus($appointment['status']); ?></td>
                                            <td>
                                                <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
