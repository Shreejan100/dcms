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

$patient = null;

// Get patient data
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        header('Location: admin/manage_patients.php');
        exit();
    }

    // Calculate age
    $dob = new DateTime($patient['dob']);
    $today = new DateTime();
    $age = $dob->diff($today)->y;
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Patient Details</h5>
            <div>
                <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-primary btn-sm me-2">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="manage_patients.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Personal Information -->
            <h6 class="mb-3">Personal Information</h6>
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Name</th>
                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?php echo ucfirst(htmlspecialchars($patient['gender'])); ?></td>
                        </tr>
                        <tr>
                            <th>Age</th>
                            <td><?php echo $age; ?> years</td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?php echo date('F j, Y', strtotime($patient['dob'])); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Phone</th>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo htmlspecialchars($patient['address']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Medical Information -->
            <h6 class="mb-3">Medical Information</h6>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Medical History</h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($patient['medical_history'] ?: 'No medical history recorded.')); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Allergies</h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(isset($patient['allergies']) ? $patient['allergies'] : 'No allergies recorded.')); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Current Medications</h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(isset($patient['current_medications']) ? $patient['current_medications'] : 'No current medications recorded.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments History -->
            <h6 class="mb-3 mt-4">Appointment History</h6>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Dentist</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->prepare("
                            SELECT a.*, 
                                   CONCAT(d.first_name, ' ', d.last_name) as dentist_name
                            FROM appointments a
                            LEFT JOIN dentists d ON a.dentist_id = d.id
                            WHERE a.patient_id = ?
                            ORDER BY a.appointment_date DESC, a.appointment_time DESC
                        ");
                        $stmt->execute([$patient['id']]);
                        
                        if ($stmt->rowCount() > 0) {
                            while ($appointment = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . date('F j, Y', strtotime($appointment['appointment_date'])) . "</td>";
                                echo "<td>" . date('h:i A', strtotime($appointment['appointment_time'])) . "</td>";
                                echo "<td>" . htmlspecialchars($appointment['dentist_name']) . "</td>";
                                echo "<td><span class='badge bg-" . 
                                    ($appointment['status'] === 'completed' ? 'success' : 
                                    ($appointment['status'] === 'cancelled' ? 'danger' : 
                                    ($appointment['status'] === 'confirmed' ? 'primary' : 'warning'))) . 
                                    "'>" . ucfirst($appointment['status']) . "</span></td>";
                                echo "<td>" . htmlspecialchars($appointment['notes'] ?: '-') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No appointment history found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>