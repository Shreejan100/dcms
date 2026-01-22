<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dentist', 'admin'])) {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get dentist ID - either from session or from URL parameter for admin
$dentist_id = null;
if ($_SESSION['role'] === 'admin' && isset($_GET['dentist_id'])) {
    $dentist_id = $_GET['dentist_id'];
} else {
    $stmt = $db->prepare("SELECT id FROM dentists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
    $dentist_id = $dentist['id'];
}

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $redirect_url = $_SESSION['role'] === 'admin' ? 
        "appointments.php?admin_view=1&dentist_id=" . $dentist_id : 
        "appointments.php";
    header("Location: " . $redirect_url);
    exit();
}

// Get appointment details and check for existing report
$stmt = $db->prepare("SELECT a.*, 
                      CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                      p.gender, p.dob,
                      tr.id as report_id
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN treatment_reports tr ON a.id = tr.appointment_id
                    WHERE a.id = ? AND a.dentist_id = ?");
$stmt->execute([$appointment_id, $dentist_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    $redirect_url = $_SESSION['role'] === 'admin' ? 
        "appointments.php?admin_view=1&dentist_id=" . $dentist_id : 
        "appointments.php";
    header("Location: " . $redirect_url);
    exit();
}

// If report already exists, redirect to view report
if ($appointment['report_id']) {
    $redirect_url = "view_report.php?id=" . $appointment_id;
    if ($_SESSION['role'] === 'admin') {
        $redirect_url .= "&admin_view=1&dentist_id=" . $dentist_id;
    }
    header("Location: " . $redirect_url);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Double check no report exists before creating
        $stmt = $db->prepare("SELECT id FROM treatment_reports WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        if ($stmt->fetch()) {
            throw new Exception("A treatment report already exists for this appointment.");
        }
        
        // Update appointment status
        $stmt = $db->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$appointment_id]);
        
        // Create treatment report
        $stmt = $db->prepare("INSERT INTO treatment_reports (appointment_id, diagnosis, treatment, prescription) 
                             VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $appointment_id,
            $_POST['diagnosis'],
            $_POST['treatment'],
            $_POST['prescription']
        ]);
        
        $db->commit();
        $redirect_url = "view_report.php?id=" . $appointment_id;
        if ($_SESSION['role'] === 'admin') {
            $redirect_url .= "&admin_view=1&dentist_id=" . $dentist_id;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Treatment Report</h4>
                <a href="<?php echo $_SESSION['role'] === 'admin' ? 
                    'appointments.php?admin_view=1&dentist_id=' . $dentist_id : 
                    'appointments.php'; ?>" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Appointments
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Patient Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">Patient Name:</th>
                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Gender:</th>
                                    <td><?php echo ucfirst(htmlspecialchars($appointment['gender'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td><?php echo date_diff(date_create($appointment['dob']), date_create('today'))->y; ?> years</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">Visit Date:</th>
                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Visit Time:</th>
                                    <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Visit Type:</th>
                                    <td>Consultation</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" id="treatmentForm">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Clinical Notes</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="diagnosis" class="form-label fw-bold">Diagnosis</label>
                            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" 
                                    placeholder="Enter detailed diagnosis..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="treatment" class="form-label fw-bold">Treatment Provided</label>
                            <textarea class="form-control" id="treatment" name="treatment" rows="3" 
                                    placeholder="Enter treatment details..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="prescription" class="form-label fw-bold">Prescription & Instructions</label>
                            <textarea class="form-control" id="prescription" name="prescription" rows="3" 
                                    placeholder="Enter medications and instructions..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Save Treatment Report
                    </button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Treatment History</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $db->prepare("SELECT tr.*, a.appointment_date 
                                        FROM treatment_reports tr
                                        JOIN appointments a ON tr.appointment_id = a.id
                                        WHERE a.patient_id = ?
                                        ORDER BY a.appointment_date DESC");
                    $stmt->execute([$appointment['patient_id']]);
                    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($history)):
                    ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-clipboard-x fs-1"></i>
                            <p class="mt-2">No previous treatment records found.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($history as $record): ?>
                                <div class="timeline-item mb-4">
                                    <div class="timeline-date mb-2">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo date('M d, Y', strtotime($record['appointment_date'])); ?>
                                    </div>
                                    <div class="timeline-content border-start border-primary ps-3">
                                        <h6 class="mb-2 text-primary">Diagnosis</h6>
                                        <p class="mb-2"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                        <h6 class="mb-2 text-primary">Treatment</h6>
                                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($record['treatment']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item {
    position: relative;
}

.timeline-date {
    color: #666;
    font-size: 0.9rem;
}

.timeline-content {
    position: relative;
    margin-left: 1rem;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -0.5rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    background: #fff;
    border: 2px solid #0d6efd;
    border-radius: 50%;
}

.card-header {
    border-bottom: 0;
}

.table-borderless th {
    color: #666;
}
</style>

<?php include '../layouts/footer.php'; ?>
