<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['patient', 'admin'])) {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get patient ID - either from session or URL parameter for admin
$patient_id = null;
if ($_SESSION['role'] === 'admin' && isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    // Verify patient exists
    $stmt = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as patient_name FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header("Location: ../admin/manage_patients.php");
        exit();
    }
} else {
    // For patient role, get their own ID
    $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    $patient_id = $patient['id'];
}

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    try {
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' 
                             WHERE id = ? AND patient_id = ? 
                             AND status IN ('pending', 'confirmed')");
        $stmt->execute([$_POST['appointment_id'], $patient_id]);
        $success = "Appointment cancelled successfully.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Filter parameters
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';
$doctor = $_GET['doctor'] ?? '';

// Build query
$query = "SELECT a.*, 
          CONCAT(d.first_name, ' ', d.last_name) as dentist_name,
          d.specialization,
          tr.diagnosis, tr.treatment, tr.prescription
          FROM appointments a
          JOIN dentists d ON a.dentist_id = d.id
          LEFT JOIN treatment_reports tr ON a.id = tr.appointment_id
          WHERE a.patient_id = ?";
$params = [$patient_id];

if ($status) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

if ($doctor) {
    $query .= " AND (LOWER(d.first_name) LIKE ? OR LOWER(d.last_name) LIKE ?)";
    $search_term = '%' . strtolower($doctor) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($date) {
    $query .= " AND DATE(a.appointment_date) = ?";
    $params[] = $date;
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="d-flex justify-content-between align-items-center">
                    <h4>Appointments for <?php echo htmlspecialchars($patient['patient_name']); ?></h4>
                    <div>
                        <a href="book_appointment.php?patient_id=<?php echo $patient_id; ?>&admin_view=1" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> Book New Appointment
                        </a>
                        <a href="../admin/manage_patients.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Patients
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center">
                    <h4>My Appointments</h4>
                    <a href="book_appointment.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Book New Appointment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="missed" <?php echo $status === 'missed' ? 'selected' : ''; ?>>Missed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="doctor" class="form-label">Doctor Name</label>
                    <input type="text" class="form-control" id="doctor" name="doctor" value="<?php echo htmlspecialchars($doctor); ?>" placeholder="Search by doctor name">
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Specific Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <?php if ($status || $date || $doctor): ?>
                            <a href="<?php echo $_SESSION['role'] === 'admin' ? '?patient_id=' . $patient_id . '&admin_view=1' : '?'; ?>" class="btn btn-secondary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($appointments)): ?>
                <p class="text-center text-muted">No appointments found.</p>
            <?php else: ?>
                <div class="accordion" id="appointmentsAccordion">
                    <?php foreach ($appointments as $index => $appointment): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" 
                                        type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo $index; ?>">
                                    <div class="row w-100">
                                        <div class="col-md-3">
                                            <strong><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></strong>
                                            <small class="d-block text-muted">
                                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            Dr. <?php echo htmlspecialchars($appointment['dentist_name']); ?>
                                            <small class="d-block text-muted">
                                                <?php echo htmlspecialchars($appointment['specialization']); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo formatAppointmentStatus($appointment['status']); ?>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" 
                                 class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                 data-bs-parent="#appointmentsAccordion">
                                <div class="accordion-body">
                                    <?php if ($appointment['status'] === 'completed' && $appointment['diagnosis']): ?>
                                        <div class="mb-3">
                                            <h6>Treatment Report</h6>
                                            <p><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($appointment['diagnosis'])); ?></p>
                                            <p><strong>Treatment:</strong> <?php echo nl2br(htmlspecialchars($appointment['treatment'])); ?></p>
                                            <?php if ($appointment['prescription']): ?>
                                                <p><strong>Prescription:</strong> <?php echo nl2br(htmlspecialchars($appointment['prescription'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                        <form method="POST" class="mt-3" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
