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

// Get appointment and report details
$stmt = $db->prepare("SELECT a.*, 
                      CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                      p.gender, p.dob, p.phone, p.email,
                      tr.diagnosis, tr.treatment, tr.prescription,
                      tr.created_at as report_date
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN treatment_reports tr ON a.id = tr.appointment_id
                    WHERE a.id = ? AND a.dentist_id = ?");
$stmt->execute([$appointment_id, $dentist_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    $redirect_url = $_SESSION['role'] === 'admin' ? 
        "appointments.php?admin_view=1&dentist_id=" . $dentist_id : 
        "appointments.php";
    header("Location: " . $redirect_url);
    exit();
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h4>View Treatment Report</h4>
                <a href="<?php echo $_SESSION['role'] === 'admin' ? 
                    "appointments.php?admin_view=1&dentist_id=" . $dentist_id : 
                    "appointments.php"; ?>" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Appointments
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Patient Information -->
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
                                    <td><?php echo htmlspecialchars($report['patient_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Gender:</th>
                                    <td><?php echo ucfirst(htmlspecialchars($report['gender'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td><?php echo date_diff(date_create($report['dob']), date_create('today'))->y; ?> years</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">Visit Date:</th>
                                    <td><?php echo date('M d, Y', strtotime($report['appointment_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Visit Time:</th>
                                    <td><?php echo date('h:i A', strtotime($report['appointment_time'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Report Date:</th>
                                    <td><?php echo date('M d, Y h:i A', strtotime($report['report_date'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Treatment Report -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Clinical Notes</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="fw-bold">Diagnosis</h6>
                        <p class="border-bottom pb-3"><?php echo nl2br(htmlspecialchars($report['diagnosis'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold">Treatment Provided</h6>
                        <p class="border-bottom pb-3"><?php echo nl2br(htmlspecialchars($report['treatment'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold">Prescription & Instructions</h6>
                        <p><?php echo nl2br(htmlspecialchars($report['prescription'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($report['phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($report['email']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-header {
    border-bottom: 0;
}

.table-borderless th {
    color: #666;
}
</style>

<?php include '../layouts/footer.php'; ?>
