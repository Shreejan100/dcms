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
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php 
            if ($_GET['success'] === 'added') {
                echo "Dentist added successfully.";
            } else if ($_GET['success'] === 'updated') {
                echo "Dentist updated successfully.";
            } else if ($_GET['success'] === 'deleted') {
                echo "Dentist deleted successfully.";
            } else {
                echo htmlspecialchars($_GET['success']);
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Add Dentist Button -->
    <div class="row mb-4">
        <div class="col">
            <a href="add_dentist.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Dentist
            </a>
        </div>
    </div>

    <!-- Dentists List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Manage Dentists</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Degree</th>
                            <th>Consultation Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query("SELECT d.*, u.username 
                                          FROM dentists d 
                                          JOIN users u ON d.user_id = u.id 
                                          WHERE d.status = 'active'
                                          ORDER BY d.first_name, d.last_name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['work_experience']) . " years</td>";
                            echo "<td>" . htmlspecialchars($row['degree']) . "</td>";
                            echo "<td>Rs. " . number_format($row['consultation_charge'], 0) . "</td>";
                            echo "<td>";
                            echo "<a href='view_dentist.php?id=" . $row['id'] . "' class='btn btn-sm btn-info text-white me-1'><i class='bi bi-eye'></i> View</a>";
                            echo "<a href='edit_dentist.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary me-1'><i class='bi bi-pencil'></i> Edit</a>";
                            echo "<a href='../dentist/appointments.php?admin_view=1&dentist_id=" . $row['id'] . "' class='btn btn-sm btn-success me-1'><i class='bi bi-calendar-check'></i> Appointments</a>";
                            echo "<a href='delete_dentist.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this dentist?\")'><i class='bi bi-trash'></i> Delete</a>";
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
