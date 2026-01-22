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

include '../layouts/header.php';
?>

<div class="container-fluid">
    <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
        <div class="alert alert-success">Patient added successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
        <div class="alert alert-success">Patient updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
        <div class="alert alert-success">Patient deleted successfully.</div>
    <?php endif; ?>

    <!-- Add Patient Button -->
    <div class="row mb-4">
        <div class="col">
            <a href="add_patient.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Patient
            </a>
        </div>
    </div>

    <!-- Patients List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Manage Patients</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Medical History</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query("SELECT * FROM patients ORDER BY first_name, last_name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // Calculate age from date of birth
                            $dob = new DateTime($row['dob']);
                            $today = new DateTime();
                            $age = $dob->diff($today)->y;

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                            echo "<td>" . $age . " years</td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . (empty($row['medical_history']) ? 'None' : '<i class="bi bi-journal-medical text-info"></i> Available') . "</td>";
                            echo "<td>";
                            echo "<a href='view_patient.php?id=" . $row['id'] . "' class='btn btn-sm btn-info text-white me-1'><i class='bi bi-eye'></i> View</a>";
                            echo "<a href='edit_patient.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary me-1'><i class='bi bi-pencil'></i> Edit</a>";
                            echo "<a href='../patient/my_appointments.php?patient_id=" . $row['id'] . "&admin_view=1' class='btn btn-sm btn-secondary me-1'><i class='bi bi-calendar'></i> Appointments</a>";
                            echo "<a href='delete_patient.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this patient?\")'><i class='bi bi-trash'></i> Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>